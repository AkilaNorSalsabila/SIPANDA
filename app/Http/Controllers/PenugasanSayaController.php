<?php

namespace App\Http\Controllers;

use App\Support\ProgresPetugas;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Halaman "Penugasan Saya" (petugas): rekap SLS yang ditugaskan admin, per kegiatan,
 * dengan filter (kegiatan, status, pencarian) dan halaman berikutnya (pagination).
 * Progres ringkas dan tombol "Lanjutkan tagging" ada di Dashboard.
 */
class PenugasanSayaController extends Controller
{
    private const PER_HALAMAN = 10;

    public function index(Request $request)
    {
        $user = $request->user();

        // Admin tidak punya penugasan; arahkan ke ringkasan penugasan mitra.
        if ($user->isAdmin()) {
            return redirect()->route('admin.penugasan.index');
        }

        $data = ProgresPetugas::untuk($user);
        $rekap = $data['rekap'];

        // ---------- Filter ----------
        // Kegiatan hanya dianggap valid kalau memang ada di penugasan petugas ini.
        $kegiatanDipilih = (int) $request->query('kegiatan');
        if (! $rekap->contains('kegiatan_id', $kegiatanDipilih)) {
            $kegiatanDipilih = null;
        }

        $status = in_array($request->query('status'), ['belum', 'berjalan', 'selesai'], true)
            ? $request->query('status')
            : null;

        $q = mb_strtolower(trim((string) $request->query('q')));

        $baris = $rekap->pluck('sls')->flatten(1);

        $disaring = $baris
            ->when($kegiatanDipilih, fn ($c) => $c->where('kegiatan_id', $kegiatanDipilih))
            ->when($status, fn ($c) => $c->where('status', $status))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(
                mb_strtolower("{$r->id_sls} {$r->nama_sls} {$r->desa_kelurahan} {$r->kecamatan} {$r->nama_kegiatan}"),
                $q
            )))
            ->values();

        // ---------- Pagination ----------
        $halaman = max(1, (int) $request->query('page', 1));
        $daftar = new LengthAwarePaginator(
            $disaring->forPage($halaman, self::PER_HALAMAN)->values(),
            $disaring->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('penugasan-saya.index', $data + [
            'daftar'          => $daftar,
            'kegiatanDipilih' => $kegiatanDipilih,
            'statusDipilih'   => $status,
            'q'               => $q,
            'adaFilter'       => $kegiatanDipilih || $status || $q !== '',
        ]);
    }
}
