<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\PenugasanSls;
use App\Models\Riwayat;
use App\Models\Sls;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin menugaskan mitra (akun viewer) ke SLS tertentu per kegiatan.
 * Tiga halaman dengan tugas yang terpisah:
 *  1. index   : ringkasan semua mitra
 *  2. show    : SLS yang SUDAH ditugaskan ke satu mitra (hapus satu per satu)
 *  3. create  : menugaskan SLS BARU ke mitra itu (hanya menambah, tidak pernah mencabut)
 */
class PenugasanController extends Controller
{
    /** 1. Ringkasan penugasan semua mitra pada satu kegiatan. */
    public function index(Request $request)
    {
        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();
        $kegiatan = $kegiatanList->firstWhere('id', (int) $request->query('kegiatan')) ?? $kegiatanList->first();
        $search = trim((string) $request->query('q'));

        $mitra = User::query()
            ->where('role', 'viewer')
            ->where('status', 'approved')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereRaw('LOWER(name) like ?', ['%' . mb_strtolower($search) . '%'])
                ->orWhereRaw('LOWER(username) like ?', ['%' . mb_strtolower($search) . '%'])))
            ->orderBy('name')
            ->get();

        $jumlahSls = $kegiatan
            ? PenugasanSls::where('kegiatan_id', $kegiatan->id)
                ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id')
            : collect();

        $titikTagging = Bangunan::where('sumber', 'tagging')
            ->whereNotNull('dibuat_oleh')
            ->selectRaw('dibuat_oleh, count(*) as total')
            ->groupBy('dibuat_oleh')
            ->pluck('total', 'dibuat_oleh');

        // SLS dihitung per kegiatan: tiap kegiatan punya snapshot SLS-nya sendiri.
        $totalSls = $kegiatan ? Sls::untukKegiatan($kegiatan->id)->count() : 0;
        $slsTanpaPolygon = $kegiatan ? Sls::untukKegiatan($kegiatan->id)->whereNull('area')->count() : 0;
        $slsBelumDitugaskan = $kegiatan
            ? Sls::untukKegiatan($kegiatan->id)->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('penugasan_sls')
                ->whereColumn('penugasan_sls.sls_id', 'sls.id')
                ->where('penugasan_sls.kegiatan_id', $kegiatan->id))->count()
            : 0;

        return view('admin.penugasan.index', compact(
            'mitra', 'kegiatanList', 'kegiatan', 'jumlahSls', 'titikTagging',
            'totalSls', 'slsTanpaPolygon', 'slsBelumDitugaskan', 'search'
        ));
    }

    /** 2. SLS yang sudah ditugaskan ke satu mitra. */
    public function show(Request $request, User $user)
    {
        $this->pastikanMitra($user);

        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();
        $kegiatan = $kegiatanList->firstWhere('id', (int) $request->query('kegiatan')) ?? $kegiatanList->first();
        $q = mb_strtolower(trim((string) $request->query('q')));

        $daftar = null;
        if ($kegiatan) {
            $daftar = PenugasanSls::query()
                ->where('penugasan_sls.user_id', $user->id)
                ->where('penugasan_sls.kegiatan_id', $kegiatan->id)
                ->join('sls', 'sls.id', '=', 'penugasan_sls.sls_id')
                ->select('sls.id as sls_id', 'sls.id_sls', 'sls.nama_sls', 'sls.kecamatan', 'sls.desa_kelurahan')
                ->selectRaw('(sls.area IS NOT NULL) as punya_polygon')
                ->selectRaw('(SELECT COUNT(*) FROM bangunan b WHERE b.sls_id = sls.id AND b.kegiatan_id = penugasan_sls.kegiatan_id) as jumlah_titik')
                ->selectRaw("(SELECT COUNT(*) FROM bangunan b WHERE b.sls_id = sls.id AND b.kegiatan_id = penugasan_sls.kegiatan_id AND b.sumber = 'tagging' AND b.dibuat_oleh = ?) as titik_mitra", [$user->id])
                ->when($q !== '', fn ($w) => $w->whereRaw(
                    "LOWER(sls.id_sls || ' ' || sls.nama_sls || ' ' || sls.desa_kelurahan || ' ' || sls.kecamatan) like ?",
                    ["%{$q}%"]
                ))
                ->orderBy('sls.kecamatan')->orderBy('sls.desa_kelurahan')->orderBy('sls.id_sls')
                ->paginate(20)
                ->withQueryString();
        }

        $ringkasan = PenugasanSls::where('user_id', $user->id)
            ->selectRaw('kegiatan_id, count(*) as total')->groupBy('kegiatan_id')->pluck('total', 'kegiatan_id');

        return view('admin.penugasan.show', compact('user', 'kegiatanList', 'kegiatan', 'daftar', 'ringkasan', 'q'));
    }

    /** 3a. Form menugaskan SLS baru: hanya SLS yang BELUM dipegang mitra ini. */
    public function create(Request $request, User $user)
    {
        $this->pastikanMitra($user);

        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();
        $kegiatan = $kegiatanList->firstWhere('id', (int) $request->query('kegiatan')) ?? $kegiatanList->first();

        $mitraLain = collect();
        $slsGrup = collect();

        if ($kegiatan) {
            $sudah = PenugasanSls::where('user_id', $user->id)->where('kegiatan_id', $kegiatan->id)->pluck('sls_id');

            // SLS yang dipegang mitra lain di kegiatan ini (hanya info, boleh tumpang tindih).
            $mitraLain = PenugasanSls::where('kegiatan_id', $kegiatan->id)
                ->where('user_id', '!=', $user->id)
                ->join('users', 'users.id', '=', 'penugasan_sls.user_id')
                ->get(['penugasan_sls.sls_id', 'users.name'])
                ->groupBy('sls_id')
                ->map(fn ($rows) => $rows->pluck('name')->all());

            // Hanya SLS milik kegiatan ini (batas SLS tiap kegiatan bisa berbeda).
            $slsGrup = Sls::query()
                ->untukKegiatan($kegiatan->id)
                ->whereNotIn('id', $sudah)
                ->select('id', 'id_sls', 'nama_sls', 'kecamatan', 'desa_kelurahan')
                ->selectRaw('(area IS NOT NULL) as punya_polygon')
                ->withCount(['bangunan as jumlah_titik' => fn ($q) => $q->where('kegiatan_id', $kegiatan->id)])
                ->orderBy('kecamatan')->orderBy('desa_kelurahan')->orderBy('id_sls')
                ->get()
                ->groupBy(['kecamatan', 'desa_kelurahan']);
        }

        $ringkasan = PenugasanSls::where('user_id', $user->id)
            ->selectRaw('kegiatan_id, count(*) as total')->groupBy('kegiatan_id')->pluck('total', 'kegiatan_id');

        return view('admin.penugasan.create', compact('user', 'kegiatanList', 'kegiatan', 'slsGrup', 'mitraLain', 'ringkasan'));
    }

    /** 3b. Simpan: hanya MENAMBAH penugasan. Tidak ada yang dicabut dari sini. */
    public function store(Request $request, User $user)
    {
        $this->pastikanMitra($user);

        $data = $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'sls_ids'     => ['required', 'array', 'min:1'],
            'sls_ids.*'   => ['integer', 'exists:sls,id'],
        ], [
            'sls_ids.required' => 'Pilih minimal satu SLS yang akan ditugaskan.',
            'sls_ids.min'      => 'Pilih minimal satu SLS yang akan ditugaskan.',
        ]);

        $kegiatan = Kegiatan::findOrFail($data['kegiatan_id']);

        // Hanya SLS milik kegiatan ini yang boleh ditugaskan; id dari kegiatan lain diabaikan.
        $ids = Sls::untukKegiatan($kegiatan->id)
            ->whereIn('id', collect($data['sls_ids'])->map(fn ($id) => (int) $id)->unique())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return back()->withErrors(['sls_ids' => 'SLS yang dipilih bukan milik kegiatan ini.']);
        }

        $sudah = PenugasanSls::where('user_id', $user->id)->where('kegiatan_id', $kegiatan->id)
            ->whereIn('sls_id', $ids)->pluck('sls_id');
        $baru = $ids->diff($sudah)->values();

        DB::transaction(function () use ($baru, $user, $kegiatan) {
            foreach ($baru as $slsId) {
                PenugasanSls::create([
                    'user_id'         => $user->id,
                    'kegiatan_id'     => $kegiatan->id,
                    'sls_id'          => $slsId,
                    'ditugaskan_oleh' => auth()->id(),
                ]);
            }
        });

        if ($baru->isNotEmpty()) {
            $label = "{$user->name} (@{$user->username})";
            Riwayat::catat(
                modul: 'penugasan',
                aksi: 'tambah',
                keterangan: "Menugaskan {$baru->count()} SLS ke {$label} pada kegiatan {$kegiatan->nama_kegiatan}",
                objekId: $user->id,
                objekLabel: $label,
                baru: ['kegiatan' => $kegiatan->nama_kegiatan, 'ditambah' => $baru->count()],
            );
        }

        return redirect()
            ->route('admin.users.penugasan', [$user, 'kegiatan' => $kegiatan->id])
            ->with('success', "{$baru->count()} SLS ditugaskan ke {$user->name}.");
    }

    /** Cabut penugasan SATU SLS dari mitra. Titik yang sudah di-tag tetap tersimpan. */
    public function hapusSatu(Request $request, User $user, Sls $sls)
    {
        $this->pastikanMitra($user);
        $kegiatan = Kegiatan::findOrFail(
            $request->validate(['kegiatan_id' => ['required', 'exists:kegiatan,id']])['kegiatan_id']
        );

        $n = PenugasanSls::where('user_id', $user->id)
            ->where('kegiatan_id', $kegiatan->id)->where('sls_id', $sls->id)->delete();

        if ($n) {
            $label = "{$user->name} (@{$user->username})";
            Riwayat::catat(
                modul: 'penugasan',
                aksi: 'hapus',
                keterangan: "Mencabut penugasan SLS {$sls->id_sls} dari {$label} pada kegiatan {$kegiatan->nama_kegiatan}",
                objekId: $user->id,
                objekLabel: $label,
            );
        }


        return back()->with('success', "Penugasan SLS {$sls->id_sls} dicabut dari {$user->name}.");
    }

    /** Penugasan hanya untuk viewer; admin boleh tagging di SLS mana pun. */
    private function pastikanMitra(User $user): void
    {
        abort_if($user->isAdmin(), 422, 'Admin tidak perlu penugasan: admin dapat melakukan tagging di SLS mana pun.');
    }
}
