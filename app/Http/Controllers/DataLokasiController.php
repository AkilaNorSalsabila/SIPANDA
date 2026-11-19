<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\Kecamatan;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Riwayat;
use App\Models\Sls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Data Lokasi: titik bangunan/usaha dikelompokkan per SLS, lengkap dengan
 * status "perlu perbaikan" dan solusinya.
 */
class DataLokasiController extends Controller
{
    public function index(Request $request): View
    {
        $kegiatanId = $request->filled('kegiatan_id') ? (int) $request->kegiatan_id : null;
        $status = in_array($request->status, ['perlu', 'normal'], true) ? $request->status : null;
        $uid = $this->idPetugas($request);   // null = admin (melihat semua), angka = petugas (hanya SLS penugasannya)

        // Kegiatan dipilih = hanya SLS milik kegiatan itu yang punya titik. Tanpa kegiatan, semua
        // snapshot SLS ditampilkan (tiap baris membawa nama kegiatannya).
        $daftar = $this->batasiSls($this->querySls($kegiatanId), $uid)
            ->when($kegiatanId, fn ($q) => $q->untukKegiatan($kegiatanId)->denganTitik($kegiatanId))
            ->tap(fn ($q) => $this->filterWilayah($q, $request))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($w) => $w
                    ->whereRaw('LOWER(sls.nama_sls) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                    ->orWhere('sls.id_sls', 'like', "%{$q}%"));
            })
            ->when($status, fn ($q) => $q->perluPerbaikan($status === 'perlu', $kegiatanId))
            ->orderBy('sls.id_sls')
            ->paginate(20)
            ->withQueryString();

        // Ringkasan mengikuti filter wilayah & kegiatan (bukan filter status/pencarian).
        $dasar = fn () => $this->batasiSls(Sls::query(), $uid)
            ->when($kegiatanId, fn ($q) => $q->untukKegiatan($kegiatanId)->denganTitik($kegiatanId))
            ->tap(fn ($q) => $this->filterWilayah($q, $request));
        $adaFilterWilayah = $request->filled('kecamatan_id') || $request->filled('kelurahan_id');

        $ringkasan = [
            'sls'        => $dasar()->count(),
            'perbaikan'  => $dasar()->perluPerbaikan(true, $kegiatanId)->count(),
            'titik'      => Bangunan::query()
                ->when($kegiatanId, fn ($q) => $q->where('kegiatan_id', $kegiatanId))
                ->whereHas('sls', fn ($q) => $this->batasiSls($this->filterWilayah($q, $request), $uid))
                ->count(),
            // Titik tanpa SLS tidak punya kecamatan, jadi hanya relevan tanpa filter wilayah.
            // Petugas tidak melihat kartu ini (titik tanpa SLS bukan bagian penugasannya).
            'tanpa_sls'  => ($adaFilterWilayah || $uid !== null) ? null : Bangunan::query()
                ->when($kegiatanId, fn ($q) => $q->where('kegiatan_id', $kegiatanId))
                ->whereNull('sls_id')
                ->count(),
        ];

        return view('data-lokasi.index', [
            'daftar'        => $daftar,
            'ringkasan'     => $ringkasan,
            'kegiatanList'  => $this->daftarKegiatan($uid),
            'kecamatanList' => Kecamatan::orderBy('id_kecamatan')
                ->when($uid !== null, fn ($q) => $q->whereIn('id', $this->batasiSls(Sls::query(), $uid)->select('sls.kecamatan_id')))
                ->get(['id', 'nama_kecamatan']),
            'kelurahanList' => $request->filled('kecamatan_id')
                ? Kelurahan::where('kecamatan_id', $request->kecamatan_id)
                    ->when($uid !== null, fn ($q) => $q->whereIn('id', $this->batasiSls(Sls::query(), $uid)->select('sls.kelurahan_id')))
                    ->orderBy('id_kelurahan')->get(['id', 'nama_kelurahan'])
                : collect(),
        ]);
    }

    public function show(Request $request, int $sls): View
    {
        $kegiatanId = $request->filled('kegiatan_id') ? (int) $request->kegiatan_id : null;

        $uid = $this->idPetugas($request);

        // Petugas hanya boleh membuka SLS yang ditugaskan kepadanya (selain itu: 404).
        $data = $this->batasiSls($this->querySls($kegiatanId), $uid)->findOrFail($sls);

        $titik = Bangunan::query()
            ->select(['id', 'kegiatan_id', 'sls_id', 'id_assignment', 'nomor_bangunan', 'latitude', 'longitude', 'di_luar_sls', 'status', 'status_perbaikan'])
            ->with('kegiatan:id,nama_kegiatan')
            ->where('sls_id', $sls)
            ->when($kegiatanId, fn ($q) => $q->where('kegiatan_id', $kegiatanId))
            ->orderByRaw('CASE WHEN di_luar_sls THEN 0 ELSE 1 END')   // titik di luar SLS tampil dulu (NULL tidak ikut di atas)
            ->orderBy('nomor_bangunan')
            ->paginate(25)
            ->withQueryString();

        // RIWAYAT: perubahan SLS ini (hanya untuk admin)
        $riwayat = $request->user()->isAdmin()
            ? Riwayat::where('modul', 'sls')->where('objek_id', $sls)
                ->latest('created_at')->latest('id')->limit(15)->get()
            : collect();

        return view('data-lokasi.show', [
            'riwayat'      => $riwayat,
            'sls'          => $data,
            'titik'        => $titik,
            'kegiatanList' => $this->daftarKegiatan($uid),
            'kegiatanId'   => $kegiatanId,
        ]);
    }

    /** Admin: tandai manual + tulis solusi perbaikan. */
    public function update(Request $request, int $sls): RedirectResponse
    {
        $data = $request->validate([
            'perlu_perbaikan'  => ['nullable', 'boolean'],
            'solusi_perbaikan' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = Sls::query()
            ->select(['id', 'id_sls', 'nama_sls', 'perlu_perbaikan', 'solusi_perbaikan'])
            ->findOrFail($sls);

        $lama = [
            'perlu_perbaikan'  => (bool) $model->perlu_perbaikan,
            'solusi_perbaikan' => $model->solusi_perbaikan,
        ];
        $baru = [
            'perlu_perbaikan'  => (bool) ($data['perlu_perbaikan'] ?? false),
            'solusi_perbaikan' => filled($data['solusi_perbaikan'] ?? null) ? trim($data['solusi_perbaikan']) : null,
        ];

        $model->update($baru);

        // RIWAYAT: catat hanya kalau ada yang berubah
        $uraian = [];
        if ($lama['perlu_perbaikan'] !== $baru['perlu_perbaikan']) {
            $uraian[] = $baru['perlu_perbaikan'] ? 'menandai perlu perbaikan' : 'menghapus tanda perlu perbaikan';
        }
        if ((string) $lama['solusi_perbaikan'] !== (string) $baru['solusi_perbaikan']) {
            $uraian[] = $baru['solusi_perbaikan'] === null ? 'mengosongkan catatan solusi' : 'memperbarui catatan solusi';
        }
        if ($uraian) {
            Riwayat::catat(
                modul: 'sls',
                aksi: 'ubah',
                keterangan: ucfirst(implode(' dan ', $uraian)) . " pada SLS {$model->nama_sls}",
                objekId: $model->id,
                objekLabel: "{$model->id_sls} - {$model->nama_sls}",
                lama: $lama,
                baru: $baru,
            );
        }

        return back()->with('status', 'Status perbaikan SLS berhasil disimpan.');
    }

    /**
     * SLS + jumlah titik, jumlah titik di luar SLS, dan penanda punya polygon.
     * Kolom polygon (area) sengaja tidak ikut diambil karena besar.
     */
    private function querySls(?int $kegiatanId): Builder
    {
        $filterKegiatan = fn ($q) => $q->when($kegiatanId, fn ($x) => $x->where('kegiatan_id', $kegiatanId));

        return Sls::query()
            ->select([
                'sls.id', 'sls.kegiatan_id', 'sls.id_sls', 'sls.nama_sls', 'sls.kecamatan_id', 'sls.kelurahan_id',
                'sls.perlu_perbaikan', 'sls.solusi_perbaikan',
            ])
            ->selectRaw('(sls.area IS NOT NULL) as punya_polygon')
            ->with(['kecamatanMaster:id,nama_kecamatan', 'kelurahanMaster:id,nama_kelurahan', 'kegiatan:id,nama_kegiatan'])
            ->withCount([
                'bangunan as jumlah_titik'  => $filterKegiatan,
                'bangunan as jumlah_di_luar' => fn ($q) => $filterKegiatan($q)->where('di_luar_sls', true),
            ]);
    }

    /** id petugas (viewer) yang sedang login; null kalau admin, yang boleh melihat semua data. */
    private function idPetugas(Request $request): ?int
    {
        $user = $request->user();

        return $user->isAdmin() ? null : (int) $user->id;
    }

    /**
     * Batasi query SLS ke SLS yang ditugaskan kepada petugas (per kegiatan).
     * Untuk admin ($uid null) query dibiarkan apa adanya.
     */
    private function batasiSls($query, ?int $uid)
    {
        if ($uid === null) {
            return $query;
        }

        return $query->whereExists(fn ($q) => $q->selectRaw('1')
            ->from('penugasan_sls as ps')
            ->whereColumn('ps.sls_id', 'sls.id')
            ->whereColumn('ps.kegiatan_id', 'sls.kegiatan_id')
            ->where('ps.user_id', $uid));
    }

    /** Daftar kegiatan untuk dropdown: admin semua, petugas hanya kegiatan penugasannya. */
    private function daftarKegiatan(?int $uid)
    {
        return Kegiatan::orderBy('nama_kegiatan')
            ->when($uid !== null, fn ($q) => $q->whereIn('id',
                DB::table('penugasan_sls')->where('user_id', $uid)->select('kegiatan_id')))
            ->get(['id', 'nama_kegiatan']);
    }

    private function filterWilayah($query, Request $request)
    {
        return $query
            ->when($request->filled('kecamatan_id'), fn ($q) => $q->where('sls.kecamatan_id', $request->kecamatan_id))
            ->when($request->filled('kelurahan_id'), fn ($q) => $q->where('sls.kelurahan_id', $request->kelurahan_id));
    }
}
