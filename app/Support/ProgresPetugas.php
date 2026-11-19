<?php

namespace App\Support;

use App\Models\Kegiatan;
use App\Models\PenugasanSls;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Progres kerja seorang petugas (viewer) berdasarkan SLS yang ditugaskan kepadanya.
 * Dipakai oleh dashboard petugas. Semua angka dihitung dari tabel bangunan yang sudah ada.
 *
 *   titik_saya             : titik hasil tag oleh petugas ini
 *   koreksi_menunggu       : koreksi petugas ini yang menunggu verifikasi admin
 *   koreksi_terverifikasi  : koreksi petugas ini yang sudah diverifikasi admin
 *   dikembalikan           : koreksi petugas ini yang ditolak admin (perlu diperbaiki lagi)
 *   sisa_perbaikan         : titik berstatus perlu_perbaikan di SLS tsb (siapa pun yang mengerjakan)
 *
 * Status SLS: belum (belum ada pekerjaan) | berjalan (ada pekerjaan, masih ada sisa perbaikan) | selesai.
 */
class ProgresPetugas
{
    public static function untuk(User $user): array
    {
        $uid = $user->id;

        // Satu agregasi bangunan per (kegiatan, SLS), dari sudut pandang petugas ini.
        $agregat = DB::table('bangunan as b')
            ->selectRaw("
                b.kegiatan_id,
                b.sls_id,
                COUNT(*) AS jumlah_titik,
                COUNT(*) FILTER (WHERE b.sumber = 'tagging' AND b.dibuat_oleh = ?) AS titik_saya,
                COUNT(*) FILTER (WHERE b.dikoreksi_oleh = ? AND b.status_perbaikan = 'sudah_diperbaiki') AS koreksi_menunggu,
                COUNT(*) FILTER (WHERE b.dikoreksi_oleh = ? AND b.status_perbaikan = 'terverifikasi') AS koreksi_terverifikasi,
                COUNT(*) FILTER (WHERE b.dikoreksi_oleh = ? AND b.status_perbaikan = 'perlu_perbaikan') AS dikembalikan,
                COUNT(*) FILTER (WHERE b.status_perbaikan = 'perlu_perbaikan') AS sisa_perbaikan,
                COUNT(*) FILTER (WHERE b.status_perbaikan IN ('sudah_diperbaiki', 'terverifikasi')) AS sudah_perbaikan,
                GREATEST(
                    MAX(CASE WHEN b.sumber = 'tagging' AND b.dibuat_oleh = ? THEN COALESCE(b.ditandai_at, b.created_at) END),
                    MAX(CASE WHEN b.dikoreksi_oleh = ? THEN b.dikoreksi_at END)
                ) AS terakhir_aktif
            ", [$uid, $uid, $uid, $uid, $uid, $uid])
            ->groupBy('b.kegiatan_id', 'b.sls_id');

        $rows = PenugasanSls::query()
            ->where('penugasan_sls.user_id', $uid)
            ->join('sls', 'sls.id', '=', 'penugasan_sls.sls_id')
            ->join('kegiatan', 'kegiatan.id', '=', 'penugasan_sls.kegiatan_id')
            ->leftJoinSub($agregat, 'agg', function ($j) {
                $j->on('agg.sls_id', '=', 'penugasan_sls.sls_id')
                  ->on('agg.kegiatan_id', '=', 'penugasan_sls.kegiatan_id');
            })
            ->select(
                'penugasan_sls.kegiatan_id',
                'kegiatan.nama_kegiatan',
                'sls.id as sls_id',
                'sls.id_sls',
                'sls.nama_sls',
                'sls.kecamatan',
                'sls.desa_kelurahan'
            )
            ->selectRaw('(sls.area IS NOT NULL) as punya_polygon')
            ->selectRaw('COALESCE(agg.jumlah_titik, 0) AS jumlah_titik')
            ->selectRaw('COALESCE(agg.titik_saya, 0) AS titik_saya')
            ->selectRaw('COALESCE(agg.koreksi_menunggu, 0) AS koreksi_menunggu')
            ->selectRaw('COALESCE(agg.koreksi_terverifikasi, 0) AS koreksi_terverifikasi')
            ->selectRaw('COALESCE(agg.dikembalikan, 0) AS dikembalikan')
            ->selectRaw('COALESCE(agg.sisa_perbaikan, 0) AS sisa_perbaikan')
            ->selectRaw('COALESCE(agg.sudah_perbaikan, 0) AS sudah_perbaikan')
            ->selectRaw('agg.terakhir_aktif')
            ->orderBy('kegiatan.nama_kegiatan')
            ->orderBy('sls.kecamatan')
            ->orderBy('sls.desa_kelurahan')
            ->orderBy('sls.id_sls')
            ->get();

        $rows->transform(function ($r) {
            $aktif = ((int) $r->titik_saya + (int) $r->koreksi_menunggu + (int) $r->koreksi_terverifikasi + (int) $r->dikembalikan) > 0;
            $perluTotal = (int) $r->sisa_perbaikan + (int) $r->sudah_perbaikan;

            $r->status = ! $aktif ? 'belum' : ((int) $r->sisa_perbaikan === 0 ? 'selesai' : 'berjalan');
            $r->persen_perbaikan = $perluTotal > 0 ? (int) round((int) $r->sudah_perbaikan / $perluTotal * 100) : null;
            $r->terakhir_label = $r->terakhir_aktif
                ? Carbon::parse($r->terakhir_aktif, config('app.timezone'))->timezone('Asia/Jakarta')->format('d/m/Y H:i')
                : null;

            return $r;
        });

        $totalSls   = $rows->count();
        $slsAktif   = $rows->where('status', '!=', 'belum')->count();
        $slsSelesai = $rows->where('status', 'selesai')->count();

        // SLS berikutnya yang layak dilanjutkan: punya polygon, belum selesai; yang sudah berjalan didahulukan.
        $lanjut = $rows->filter(fn ($r) => $r->punya_polygon && $r->status !== 'selesai')
            ->sortBy(fn ($r) => $r->status === 'berjalan' ? 0 : 1)
            ->first();

        // Rekap per kegiatan (untuk kartu progres per kegiatan di dashboard dan halaman Penugasan Saya).
        $infoKegiatan = Kegiatan::whereIn('id', $rows->pluck('kegiatan_id')->unique())->get()->keyBy('id');
        $rekap = $rows->groupBy('kegiatan_id')->map(function ($sls, $kegiatanId) use ($infoKegiatan) {
            $total = $sls->count();
            $aktif = $sls->where('status', '!=', 'belum')->count();

            return (object) [
                'kegiatan_id'  => (int) $kegiatanId,
                'kegiatan'     => $infoKegiatan->get($kegiatanId),
                'sls'          => $sls,
                'total'        => $total,
                'aktif'        => $aktif,
                'belum'        => $sls->where('status', 'belum')->count(),
                'berjalan'     => $sls->where('status', 'berjalan')->count(),
                'selesai'      => $sls->where('status', 'selesai')->count(),
                'persen'       => $total ? (int) round($aktif / $total * 100) : 0,
                'titik_saya'   => (int) $sls->sum('titik_saya'),
                'menunggu'     => (int) $sls->sum('koreksi_menunggu'),
                'sisa'         => (int) $sls->sum('sisa_perbaikan'),
                'jumlah_titik' => (int) $sls->sum('jumlah_titik'),
            ];
        })->sortBy(fn ($k) => $k->kegiatan?->nama_kegiatan)->values();

        // Sapaan menurut jam di WIB.
        $jam = (int) Carbon::now('Asia/Jakarta')->format('G');
        $sapaan = match (true) {
            $jam < 11 => 'Selamat pagi',
            $jam < 15 => 'Selamat siang',
            $jam < 18 => 'Selamat sore',
            default   => 'Selamat malam',
        };

        return [
            'user'          => $user,
            'sapaan'        => $sapaan,
            'grup'          => $rows->groupBy('nama_kegiatan'),
            'rekap'         => $rekap,
            'totalSls'      => $totalSls,
            'slsAktif'      => $slsAktif,
            'slsSelesai'    => $slsSelesai,
            'persenSls'     => $totalSls ? (int) round($slsAktif / $totalSls * 100) : 0,
            'totalTitik'    => (int) $rows->sum('titik_saya'),
            'menunggu'      => (int) $rows->sum('koreksi_menunggu'),
            'terverifikasi' => (int) $rows->sum('koreksi_terverifikasi'),
            'dikembalikan'  => (int) $rows->sum('dikembalikan'),
            'sisaPerbaikan' => (int) $rows->sum('sisa_perbaikan'),
            'hitungStatus'  => [
                'semua'    => $totalSls,
                'belum'    => $rows->where('status', 'belum')->count(),
                'berjalan' => $rows->where('status', 'berjalan')->count(),
                'selesai'  => $slsSelesai,
            ],
            'lanjut'        => $lanjut,
            'akurasiMaks'   => config('tagging.akurasi_maks_m'),
        ];
    }
}
