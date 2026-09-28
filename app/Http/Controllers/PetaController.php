<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\Sls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PetaController extends Controller
{
    public function index(): View
    {
        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();

        return view('peta.index', compact('kegiatanList'));
    }

    public function dataSls(): JsonResponse
    {
        $slsList = Sls::whereNotNull('area')->get();

        $features = $slsList
            ->map(function (Sls $sls) {
                $geometry = $sls->areaAsGeoJson();

                if (! $geometry) {
                    return null;
                }

                return [
                    'type' => 'Feature',
                    'geometry' => $geometry,
                    'properties' => [
                        'id'              => $sls->id,
                        'kode_sls'        => $sls->kode_sls,
                        'nama_sls'        => $sls->nama_sls,
                        'kabupaten_kota'  => $sls->kabupaten_kota,
                        'kecamatan'       => $sls->kecamatan,
                        'desa_kelurahan'  => $sls->desa_kelurahan,
                        'jumlah_bangunan' => $sls->bangunan()->count(),
                        'jumlah_kk'       => (int) $sls->bangunan()->sum('jumlah_kk'),
                        'jumlah_usaha'    => (int) $sls->bangunan()->sum('jumlah_usaha'),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    public function dataBangunan(Request $request): JsonResponse
    {
        $request->validate(['kegiatan_id' => ['required', 'exists:kegiatan,id']]);

        $bangunanList = Bangunan::where('kegiatan_id', $request->kegiatan_id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $features = $bangunanList->map(function (Bangunan $b) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $b->longitude, (float) $b->latitude],
                ],
                'properties' => [
                    'id'             => $b->id,
                    'nomor_bangunan' => $b->nomor_bangunan,
                    'jumlah_kk'      => $b->jumlah_kk,
                    'jumlah_usaha'   => $b->jumlah_usaha,
                    'status_kondisi' => $this->statusKondisi($b),
                ],
            ];
        });

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    /**
     * Detail lengkap 1 bangunan untuk popup peta, termasuk:
     * - data dasar 
     * - nama KK/usaha manual 
     * - kalau statusnya "di luar SLS": SLS yang TERCATAT vs SLS AKTUAL
     *   (hasil cek geometris) + jarak deviasi dalam meter
     */
    public function detailBangunan(Bangunan $bangunan): JsonResponse
    {
        $bangunan->load(['sls', 'kegiatan', 'rumahTangga', 'usaha']);

        $statusKondisi = $this->statusKondisi($bangunan);

        $slsAktual = null;
        $jarakMeter = null;

        if ($statusKondisi === 'di_luar_sls') {
            $aktual = $bangunan->slsAktual();
            $slsAktual = $aktual ? [
                'kode_sls' => $aktual->kode_sls,
                'nama_sls' => $aktual->nama_sls,
            ] : null;

            $jarakMeter = $bangunan->jarakKeSlsMeter();
        }

        return response()->json([
            'id'              => $bangunan->id,
            'nomor_bangunan'  => $bangunan->nomor_bangunan,
            'kode_bang_value' => $bangunan->kode_bang_value,
            'flag_btt'        => $bangunan->flag_btt,
            'flag_bku'        => $bangunan->flag_bku,
            'jumlah_kk'       => $bangunan->jumlah_kk,
            'jumlah_usaha'    => $bangunan->jumlah_usaha,
            'status_kondisi'  => $statusKondisi,
            'kegiatan'        => $bangunan->kegiatan->nama_kegiatan ?? null,
            'latitude'        => $bangunan->latitude,
            'longitude'       => $bangunan->longitude,

            // SLS yang tercatat di data (hasil import / kuesioner)
            'sls_tercatat' => $bangunan->sls ? [
                'kode_sls'       => $bangunan->sls->kode_sls,
                'nama_sls'       => $bangunan->sls->nama_sls,
                'kecamatan'      => $bangunan->sls->kecamatan,
                'desa_kelurahan' => $bangunan->sls->desa_kelurahan,
            ] : ($bangunan->kode_sls_asal ? [
                'kode_sls' => $bangunan->kode_sls_asal,
                'nama_sls' => null,
            ] : null),

            // SLS yang RIIL memuat koordinat GPS-nya (cuma diisi kalau di_luar_sls)
            'sls_aktual'  => $slsAktual,
            'jarak_meter' => $jarakMeter,

            'rumah_tangga' => $bangunan->rumahTangga->map(fn ($rt) => [
                'id'                   => $rt->id,
                'nomor_kk'             => $rt->nomorKkTersamar(),
                'nama_kepala_keluarga' => $rt->nama_kepala_keluarga,
                'jumlah_anggota'       => $rt->jumlah_anggota,
            ]),
            'usaha' => $bangunan->usaha->map(fn ($u) => [
                'id'          => $u->id,
                'nama_usaha'  => $u->nama_usaha,
                'jenis_usaha' => $u->jenis_usaha,
            ]),
        ]);
    }

    private function statusKondisi(Bangunan $b): string
    {
        if (! $b->sls_id) {
            return 'tanpa_sls';
        }

        if ($b->di_luar_sls) {
            return 'di_luar_sls';
        }

        return 'normal';
    }
}
