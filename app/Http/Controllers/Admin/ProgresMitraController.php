<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Progres kerja mitra (akun viewer) berdasarkan SLS yang ditugaskan.
 *
 * Semua angka dihitung dari data yang sudah ada (tabel penugasan_sls + bangunan),
 * tanpa tabel baru:
 *
 *   - SLS ditugaskan : baris penugasan_sls mitra pada kegiatan terpilih
 *   - SLS aktif      : SLS ditugaskan yang sudah ada titik hasil tag atau koreksi mitra tsb
 *   - Titik di-tag   : bangunan.sumber = 'tagging' dan dibuat_oleh = mitra
 *   - Koreksi        : bangunan.dikoreksi_oleh = mitra, dipecah menurut status_perbaikan
 *                      (sudah_diperbaiki = menunggu verifikasi, terverifikasi)
 *   - Sisa perbaikan : titik berstatus perlu_perbaikan di SLS yang ditugaskan (siapa pun yang mengerjakan)
 *   - Terakhir aktif : waktu tag / koreksi terbaru oleh mitra tsb
 */
class ProgresMitraController extends Controller
{
    /** Agregat per (mitra, SLS) yang dipakai ulang oleh halaman ringkasan dan halaman detail. */
    private const AGREGAT = [
        'COUNT(b.id) AS total_titik',
        "COUNT(b.id) FILTER (WHERE b.sumber = 'tagging' AND b.dibuat_oleh = ps.user_id) AS titik_tag",
        "COUNT(b.id) FILTER (WHERE b.dikoreksi_oleh = ps.user_id AND b.status_perbaikan = 'sudah_diperbaiki') AS koreksi_menunggu",
        "COUNT(b.id) FILTER (WHERE b.dikoreksi_oleh = ps.user_id AND b.status_perbaikan = 'terverifikasi') AS koreksi_terverifikasi",
        "COUNT(b.id) FILTER (WHERE b.status_perbaikan = 'perlu_perbaikan') AS sisa_perbaikan",
        "COUNT(b.id) FILTER (WHERE b.status_perbaikan IN ('sudah_diperbaiki','terverifikasi')) AS sudah_perbaikan",
        "GREATEST(
            MAX(CASE WHEN b.sumber = 'tagging' AND b.dibuat_oleh = ps.user_id THEN COALESCE(b.ditandai_at, b.created_at) END),
            MAX(CASE WHEN b.dikoreksi_oleh = ps.user_id THEN b.dikoreksi_at END)
        ) AS terakhir_aktif",
    ];

    /** 1. Ringkasan progres semua mitra pada satu kegiatan. */
    public function index(Request $request)
    {
        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();
        $kegiatan = $kegiatanList->firstWhere('id', (int) $request->query('kegiatan')) ?? $kegiatanList->first();
        $search = trim((string) $request->query('q'));

        $mitra = collect();
        if ($kegiatan) {
            // Satu baris per (mitra, SLS), lalu dijumlahkan per mitra di PHP supaya SQL tetap sederhana.
            $perSls = DB::table('penugasan_sls as ps')
                ->join('users as u', 'u.id', '=', 'ps.user_id')
                ->leftJoin('bangunan as b', function ($j) {
                    $j->on('b.sls_id', '=', 'ps.sls_id')->on('b.kegiatan_id', '=', 'ps.kegiatan_id');
                })
                ->where('ps.kegiatan_id', $kegiatan->id)
                ->where('u.role', 'viewer')
                ->where('u.status', 'approved')
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->whereRaw('LOWER(u.name) like ?', ['%' . mb_strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(u.username) like ?', ['%' . mb_strtolower($search) . '%'])))
                ->groupBy('ps.user_id', 'ps.sls_id', 'u.name', 'u.username')
                ->selectRaw('ps.user_id, ps.sls_id, u.name, u.username, ' . implode(', ', self::AGREGAT))
                ->get()
                ->groupBy('user_id');

            // Mitra yang belum punya penugasan tetap ditampilkan (baris kosong), supaya admin tahu.
            $semuaMitra = User::query()
                ->where('role', 'viewer')->where('status', 'approved')
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->whereRaw('LOWER(name) like ?', ['%' . mb_strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(username) like ?', ['%' . mb_strtolower($search) . '%'])))
                ->orderBy('name')
                ->get(['id', 'name', 'username']);

            $mitra = $semuaMitra->map(function ($m) use ($perSls) {
                return $this->ringkasMitra($m, $perSls->get($m->id, collect()));
            });
        }

        $total = [
            'mitra'      => $mitra->count(),
            'belum'      => $mitra->where('status', 'belum')->count(),
            'berjalan'   => $mitra->where('status', 'berjalan')->count(),
            'selesai'    => $mitra->where('status', 'selesai')->count(),
            'titik_tag'  => (int) $mitra->sum('titik_tag'),
            'menunggu'   => (int) $mitra->sum('koreksi_menunggu'),
        ];

        return view('admin.progres.index', compact('mitra', 'kegiatanList', 'kegiatan', 'search', 'total'));
    }

    /** 2. Rincian progres satu mitra, per SLS yang ditugaskan. */
    public function show(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 422, 'Admin tidak punya penugasan, jadi tidak ada progres untuk ditampilkan.');

        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();
        $kegiatan = $kegiatanList->firstWhere('id', (int) $request->query('kegiatan')) ?? $kegiatanList->first();
        $q = mb_strtolower(trim((string) $request->query('q')));

        $ringkas = null;
        $daftar = null;

        if ($kegiatan) {
            $dasar = DB::table('penugasan_sls as ps')
                ->join('sls', 'sls.id', '=', 'ps.sls_id')
                ->leftJoin('bangunan as b', function ($j) {
                    $j->on('b.sls_id', '=', 'ps.sls_id')->on('b.kegiatan_id', '=', 'ps.kegiatan_id');
                })
                ->where('ps.user_id', $user->id)
                ->where('ps.kegiatan_id', $kegiatan->id)
                ->groupBy('ps.sls_id', 'sls.id_sls', 'sls.nama_sls', 'sls.kecamatan', 'sls.desa_kelurahan');

            // Ringkasan seluruh SLS mitra (tanpa pencarian & halaman) untuk kartu di atas tabel.
            $semua = (clone $dasar)
                ->selectRaw('ps.user_id, ps.sls_id, ' . implode(', ', self::AGREGAT))
                ->groupBy('ps.user_id')
                ->get();
            $ringkas = $this->ringkasMitra($user, $semua);

            $daftar = $dasar
                ->when($q !== '', fn ($w) => $w->whereRaw(
                    "LOWER(sls.id_sls || ' ' || sls.nama_sls || ' ' || sls.desa_kelurahan || ' ' || sls.kecamatan) like ?",
                    ["%{$q}%"]
                ))
                ->selectRaw('ps.user_id, ps.sls_id, sls.id_sls, sls.nama_sls, sls.kecamatan, sls.desa_kelurahan, ' . implode(', ', self::AGREGAT))
                ->groupBy('ps.user_id')
                ->orderBy('sls.kecamatan')->orderBy('sls.desa_kelurahan')->orderBy('sls.id_sls')
                ->paginate(20)
                ->withQueryString();

            $daftar->getCollection()->transform(function ($r) {
                $r->status = $this->statusSls($r);
                $r->terakhir_label = $this->labelWaktu($r->terakhir_aktif);
                return $r;
            });
        }

        return view('admin.progres.show', compact('user', 'kegiatanList', 'kegiatan', 'ringkas', 'daftar', 'q'));
    }

    /** Jumlahkan baris (mitra, SLS) menjadi satu ringkasan mitra. */
    private function ringkasMitra($mitra, $barisSls): object
    {
        $slsDitugaskan = $barisSls->count();
        $slsAktif = $barisSls->filter(fn ($r) => ((int) $r->titik_tag + (int) $r->koreksi_menunggu + (int) $r->koreksi_terverifikasi) > 0)->count();
        $sisa = (int) $barisSls->sum('sisa_perbaikan');
        $sudah = (int) $barisSls->sum('sudah_perbaikan');
        $perluTotal = $sisa + $sudah;

        $terakhir = $barisSls->pluck('terakhir_aktif')->filter()->max();

        $status = 'belum';
        if ($slsAktif > 0) {
            $status = ($slsAktif === $slsDitugaskan && $sisa === 0) ? 'selesai' : 'berjalan';
        }

        return (object) [
            'id'                    => $mitra->id,
            'name'                  => $mitra->name,
            'username'              => $mitra->username,
            'sls_ditugaskan'        => $slsDitugaskan,
            'sls_aktif'             => $slsAktif,
            'persen_sls'            => $slsDitugaskan ? (int) round($slsAktif / $slsDitugaskan * 100) : 0,
            'titik_tag'             => (int) $barisSls->sum('titik_tag'),
            'koreksi_menunggu'      => (int) $barisSls->sum('koreksi_menunggu'),
            'koreksi_terverifikasi' => (int) $barisSls->sum('koreksi_terverifikasi'),
            'sisa_perbaikan'        => $sisa,
            'persen_perbaikan'      => $perluTotal ? (int) round($sudah / $perluTotal * 100) : null,
            'terakhir_label'        => $this->labelWaktu($terakhir),
            'status'                => $slsDitugaskan === 0 ? 'tanpa_tugas' : $status,
        ];
    }

    /** Status satu SLS: belum mulai / berjalan / selesai. */
    private function statusSls($r): string
    {
        $aktif = ((int) $r->titik_tag + (int) $r->koreksi_menunggu + (int) $r->koreksi_terverifikasi) > 0;
        if (! $aktif) {
            return 'belum';
        }
        return (int) $r->sisa_perbaikan === 0 ? 'selesai' : 'berjalan';
    }

    /** Waktu di database disimpan dalam zona waktu aplikasi (UTC); tampilkan dalam WIB. */
    private function labelWaktu($nilai): ?string
    {
        return $nilai
            ? Carbon::parse($nilai, config('app.timezone'))->timezone('Asia/Jakarta')->format('d/m/Y H:i')
            : null;
    }
}
