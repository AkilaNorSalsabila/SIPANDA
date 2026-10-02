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

    public function daftarSls(Request $request): JsonResponse
    {
        $request->validate(['kegiatan_id' => ['required', 'exists:kegiatan,id']]);

        $kegiatanId = $request->kegiatan_id;

        $list = Sls::query()
            ->select('sls.id', 'sls.id_sls', 'sls.nama_sls', 'sls.desa_kelurahan', 'sls.kecamatan')
            ->selectRaw('(sls.area IS NOT NULL) as punya_polygon')
            ->withCount(['bangunan as jumlah_bangunan' => fn ($q) => $q->where('kegiatan_id', $kegiatanId)])
            ->whereHas('bangunan', fn ($q) => $q->where('kegiatan_id', $kegiatanId))
            ->orderBy('sls.id_sls')
            ->get();

        return response()->json($list);
    }

    /**
     * Polygon SLS. Hanya SLS yang sudah punya polygon (area tidak NULL).
     */
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
                        'id'             => $sls->id,
                        'id_sls'         => $sls->id_sls,
                        'nama_sls'       => $sls->nama_sls,
                        'kabupaten_kota' => $sls->kabupaten_kota,
                        'kecamatan'      => $sls->kecamatan,
                        'desa_kelurahan' => $sls->desa_kelurahan,
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
                    'sls_id'         => $b->sls_id,
                    'nomor_bangunan' => $b->nomor_bangunan,
                    'jumlah_kk'      => $b->jumlah_kk,
                    'jumlah_usaha'   => $b->jumlah_usaha,
                    'status_kondisi' => $this->statusKondisi($b),
                ],
            ];
        });

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    public function statistikSls(Request $request, Sls $sls): JsonResponse
    {
        $request->validate(['kegiatan_id' => ['nullable', 'exists:kegiatan,id']]);

        $query = $sls->bangunan()->with(['rumahTangga', 'usaha']);

        if ($request->filled('kegiatan_id')) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        $bangunan = $query->get()->sortBy(fn ($b) => (int) $b->nomor_bangunan)->values();

        $total = $bangunan->count();

        $daftar = $bangunan->map(function ($b) {
            $rt = $b->rumahTangga;

            return [
                'id'             => $b->id,
                'nomor_bangunan' => $b->nomor_bangunan ?? '-',
                // No. Urut Rumah Tangga (Blok V.A kolom 8)
                'nomor_urut_rt'  => $rt->pluck('nomor_urut_rumah_tangga')->filter()->unique()->join(', ') ?: '-',
                'nama_usaha'     => $b->usaha->pluck('nama_usaha')->filter()->join(', ') ?: '-',
                // Nama Kepala Keluarga / KK (Blok V.A kolom 3)
                'nama_keluarga'  => $rt->pluck('nama_kepala_keluarga')->filter()->join(', ') ?: '-',
                // Nama Kepala Rumah Tangga / KRT (Blok V.A kolom 10)
                'nama_rumah_tangga' => $rt->pluck('nama_kepala_rumah_tangga')->filter()->unique()->join(', ') ?: '-',
            ];
        });

        return response()->json([
            'sls' => [
                'id'             => $sls->id,
                'id_sls'         => $sls->id_sls,
                'nama_sls'       => $sls->nama_sls,
                'kabupaten_kota' => $sls->kabupaten_kota,
                'kecamatan'      => $sls->kecamatan,
                'desa_kelurahan' => $sls->desa_kelurahan,
            ],
            'statistik' => [
                'total_bangunan' => $total,
                'jumlah_usaha'   => (int) $bangunan->sum('jumlah_usaha'),
                'keluarga_kk'    => (int) $bangunan->sum('jumlah_kk'),
            ],
            'daftar' => $daftar,
        ]);
    }

    public function detailBangunan(Bangunan $bangunan): JsonResponse
    {
        $bangunan->load(['sls', 'kegiatan', 'rumahTangga', 'usaha']);

        $statusKondisi = $this->statusKondisi($bangunan);

        $slsAktual = null;
        $jarakMeter = null;

        if ($statusKondisi === 'di_luar_sls') {
            $aktual = $bangunan->slsAktual();
            $slsAktual = $aktual ? [
                'id_sls' => $aktual->id_sls,
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

            'sls_tercatat' => $bangunan->sls ? [
                'id_sls'         => $bangunan->sls->id_sls,
                'nama_sls'       => $bangunan->sls->nama_sls,
                'kecamatan'      => $bangunan->sls->kecamatan,
                'desa_kelurahan' => $bangunan->sls->desa_kelurahan,
            ] : ($bangunan->id_sls_asal ? [
                'id_sls'   => $bangunan->id_sls_asal,
                'nama_sls' => null,
            ] : null),

            'sls_aktual'  => $slsAktual,
            'jarak_meter' => $jarakMeter,

            // 5 standar informasi 
            'rumah_tangga' => $bangunan->rumahTangga->map(fn ($rt) => [
                'id'                     => $rt->id,
                'nomor_kk'               => $rt->nomorKkTersamar(),
                'nomor_urut_rumah_tangga'=> $rt->nomor_urut_rumah_tangga,
                'nama_kepala_keluarga'   => $rt->nama_kepala_keluarga,
                'nama_kepala_rumah_tangga' => $rt->nama_kepala_rumah_tangga,
                'jumlah_anggota'         => $rt->jumlah_anggota,
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

        if ($b->di_luar_sls === null) {
            return 'belum_dicek';
        }

        return $b->di_luar_sls ? 'di_luar_sls' : 'normal';
    }
}
