<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\ImportBatch;
use App\Models\Kegiatan;
use App\Models\Sls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ImportController extends Controller
{
    /**
     * Halaman utama Import: form upload + riwayat import.
     */
    public function index(): View
    {
        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();

        $riwayat = ImportBatch::with(['kegiatan', 'uploader'])
            ->latest()
            ->paginate(10);

        return view('import.index', compact('kegiatanList', 'riwayat'));
    }

    /**
     * Import batas_sls.geojson -> tabel sls (data dasar wilayah, dipakai
     * bersama oleh semua kegiatan)
     */
    public function storeBatasSls(Request $request): RedirectResponse
    {
        $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'file'        => ['required', 'file', 'max:20480'],
        ]);

        $this->pastikanFileGeoJson($request);

        $batch = ImportBatch::create([
            'kegiatan_id' => $request->kegiatan_id,
            'uploaded_by' => Auth::id(),
            'jenis_data'  => 'batas_sls',
            'nama_file'   => $request->file('file')->getClientOriginalName(),
            'status'      => 'processing',
        ]);

        $features = $this->bacaGeoJsonFeatures($request->file('file'));

        $valid = 0;
        $errors = [];

        foreach ($features as $i => $feature) {
            $baris = $i + 1;
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;
            $kodeSls = $props['idsls'] ?? null;

            if (! $kodeSls) {
                $errors[] = ['baris' => $baris, 'pesan' => 'Kolom "idsls" kosong, baris dilewati.'];
                continue;
            }

            if (! $geometry) {
                $errors[] = ['baris' => $baris, 'pesan' => "SLS {$kodeSls}: geometry kosong, baris dilewati."];
                continue;
            }

            try {
                $sls = Sls::updateOrCreate(
                    ['id_sls' => $kodeSls],
                    [
                        'id_provinsi'    => $props['kdprov'] ?? null,
                        'id_kabupaten'   => $props['kdkab'] ?? null,
                        'id_kecamatan'   => $props['kdkec'] ?? null,
                        'id_kelurahan'   => $props['kddesa'] ?? null,
                        'nama_sls'       => $props['nmsls'] ?? '-',
                        'kabupaten_kota' => $props['nmkab'] ?? '-',
                        'kecamatan'      => $props['nmkec'] ?? '-',
                        'desa_kelurahan' => $props['nmdesa'] ?? '-',
                        'luas'           => $props['luas'] ?? null,
                        'periode'        => $props['periode'] ?? null,
                    ]
                );

                $sls->setAreaFromGeoJson($geometry);

                $valid++;
            } catch (\Throwable $e) {
                $errors[] = ['baris' => $baris, 'pesan' => "SLS {$kodeSls}: " . $e->getMessage()];
            }
        }

        $this->tutupBatch($batch, count($features), $valid, $errors);

        return redirect()->route('import.index')->with(
            'status',
            "Import batas SLS selesai: {$valid} berhasil, " . count($errors) . ' bermasalah.'
        );
    }

    /**
     * Import titik_lokasi.geojson -> tabel bangunan.
     * WAJIB dijalankan SETELAH import batas SLS, karena tiap titik
     * dicocokkan ke SLS yang sudah ada di database lewat kode SLS.
     *
     */
    public function storeTitikLokasi(Request $request): RedirectResponse
    {
        $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'file'        => ['required', 'file', 'max:20480'],
        ]);

        $this->pastikanFileGeoJson($request);

        $batch = ImportBatch::create([
            'kegiatan_id' => $request->kegiatan_id,
            'uploaded_by' => Auth::id(),
            'jenis_data'  => 'titik_lokasi',
            'nama_file'   => $request->file('file')->getClientOriginalName(),
            'status'      => 'processing',
        ]);

        $features = $this->bacaGeoJsonFeatures($request->file('file'));

        $valid = 0;
        $errors = [];

        foreach ($features as $i => $feature) {
            $baris = $i + 1;
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            $idAssignment = $props['id_assignment'] ?? null;
            $coords = $geometry['coordinates'] ?? null;

            // Fallback berlapis: pakai idsls kalau ada; kalau kosong,
            // coba level_5_full_code; kalau masih kosong, coba idsubsls.
            // Kalau ketiganya kosong, $kodeSls tetap null (bangunan tetap
            // disimpan, cuma tanpa SLS).
            $kodeSls = $props['idsls']
                ?? $props['level_5_full_code']
                ?? $props['idsubsls']
                ?? null;

            if (! $idAssignment) {
                $errors[] = ['baris' => $baris, 'pesan' => 'Kolom "id_assignment" kosong, baris dilewati.'];
                continue;
            }

            if (! $coords || count($coords) < 2) {
                $errors[] = ['baris' => $baris, 'pesan' => "{$idAssignment}: koordinat kosong/tidak lengkap."];
                continue;
            }

            try {
                // Kasus 1: cari SLS berdasarkan kode (hasil fallback di atas).
                // Kalau tidak ketemu, sls_id dikosongkan dan kode aslinya
                // (apa pun isinya, termasuk null) tetap disimpan sebagai arsip.
                $sls = $kodeSls ? Sls::where('id_sls', $kodeSls)->first() : null;

                $bangunan = Bangunan::updateOrCreate(
                    ['id_assignment' => $idAssignment],
                    [
                        'kegiatan_id'     => $request->kegiatan_id,
                        'sls_id'          => $sls?->id,
                        'id_sls_asal'     => $kodeSls,
                        'nomor_bangunan'  => $props['no_bang'] ?? null,
                        'kode_bang_value' => $props['kode_bang_value'] ?? null,
                        'flag_btt'        => ($props['btt'] ?? '0') == '1',
                        'flag_bku'        => ($props['bku'] ?? '0') == '1',
                        'flag_repair'     => ($props['flag_repair'] ?? '0') == '1',
                        'jumlah_kk'       => (int) ($props['kk'] ?? 0),
                        'jumlah_usaha'    => (int) ($props['usaha'] ?? 0),
                        'status'          => 'terdata',
                    ]
                );

                // simpan koordinat (kolom geometry + lat/long biasa sekaligus)
                $bangunan->setKoordinat((float) $coords[1], (float) $coords[0]);

                if ($sls) {
                    // Kasus 2: SLS ketemu, cek apakah titiknya beneran di
                    // dalam polygon SLS itu atau meleset ke luar (untuk QC).
                    $diLuar = ! $bangunan->isInsideSls();
                    $bangunan->update(['di_luar_sls' => $diLuar]);

                    if ($diLuar) {
                        $errors[] = [
                            'baris' => $baris,
                            'pesan' => "{$idAssignment}: koordinat berada DI LUAR batas SLS {$kodeSls} (data tetap disimpan, tandai untuk QC).",
                        ];
                    }
                } elseif ($kodeSls) {
                    // Kasus 3: ada kode SLS (dari salah satu field fallback),
                    // tapi belum ada di tabel sls -> kemungkinan besar batas
                    // SLS wilayah ini belum diimport, bukan datanya yang cacat.
                    $errors[] = [
                        'baris' => $baris,
                        'pesan' => "{$idAssignment}: kode SLS '{$kodeSls}' tidak ditemukan di database (data tetap disimpan tanpa SLS).",
                    ];
                } else {
                    // Kasus 4: idsls, level_5_full_code, dan idsubsls
                    // ketiganya kosong di sumber data. Data tetap disimpan,
                    // tapi ditandai jelas supaya tim tahu perlu cek ulang.
                    $errors[] = [
                        'baris' => $baris,
                        'pesan' => "{$idAssignment}: tidak ada kode SLS pada data (idsls/level_5_full_code/idsubsls kosong semua), data disimpan tanpa SLS.",
                    ];
                }

                $valid++;
            } catch (\Throwable $e) {
                $errors[] = ['baris' => $baris, 'pesan' => ($idAssignment ?? '-') . ': ' . $e->getMessage()];
            }
        }

        $this->tutupBatch($batch, count($features), $valid, $errors);

        return redirect()->route('import.index')->with(
            'status',
            "Import titik lokasi selesai: {$valid} berhasil, " . count($errors) . ' catatan (lihat riwayat).'
        );
    }

    /**
     * Validasi ringan: pastikan file yang diupload memang JSON/GeoJSON valid..
     */
    private function pastikanFileGeoJson(Request $request): void
    {
        $ext = strtolower($request->file('file')->getClientOriginalExtension());

        if (! in_array($ext, ['json', 'geojson'])) {
            abort(422, 'File harus berformat .json atau .geojson');
        }
    }

    private function bacaGeoJsonFeatures($file): array
    {
        $content = json_decode(file_get_contents($file->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(422, 'File GeoJSON tidak valid: ' . json_last_error_msg());
        }

        return $content['features'] ?? [];
    }

    private function tutupBatch(ImportBatch $batch, int $total, int $valid, array $errors): void
    {
        $batch->update([
            'total_data' => $total,
            'data_valid' => $valid,
            'data_error' => count($errors),
            'status'     => 'success',
            'error_log'  => $errors,
        ]);
    }
}
