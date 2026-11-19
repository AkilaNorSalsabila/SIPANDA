<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\ImportBatch;
use App\Models\Kegiatan;
use App\Models\Riwayat;
use App\Models\Sls;
use App\Models\Usaha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportController extends Controller
{
    /**
     * Halaman utama Import: form upload + riwayat import.
     */
    public function index(Request $request): View
    {
        $kegiatanList = Kegiatan::orderBy('nama_kegiatan')->get();

        $riwayat = ImportBatch::with(['kegiatan', 'uploader'])
            ->latest()
            ->paginate(10);

        // Berapa data yang masih terlacak ke tiap batch (dipakai pesan konfirmasi hapus).
        $ids = $riwayat->pluck('id');
        $jejakSls = Sls::whereIn('import_batch_id', $ids)
            ->selectRaw('import_batch_id, count(*) as n')->groupBy('import_batch_id')
            ->pluck('n', 'import_batch_id')->all();
        $jejakTitik = $this->titikImportQuery()->whereIn('import_batch_id', $ids)
            ->selectRaw('import_batch_id, count(*) as n')->groupBy('import_batch_id')
            ->pluck('n', 'import_batch_id')->all();
        $jejak = $jejakSls + $jejakTitik;

        // SLS (semua kegiatan) yang saat ini punya polygon: tempat menghapus polygon yang salah impor (per SLS).
        $cariSls = mb_strtolower(trim((string) $request->query('q_sls')));
        $slsBerpolygon = Sls::whereNotNull('area')
            ->select('id', 'kegiatan_id', 'id_sls', 'nama_sls', 'kecamatan', 'desa_kelurahan')
            ->with('kegiatan:id,nama_kegiatan')
            ->when($cariSls !== '', fn ($w) => $w->whereRaw(
                "LOWER(id_sls || ' ' || nama_sls || ' ' || desa_kelurahan || ' ' || kecamatan) like ?",
                ["%{$cariSls}%"]
            ))
            ->orderBy('kegiatan_id')->orderBy('kecamatan')->orderBy('desa_kelurahan')->orderBy('id_sls')
            ->paginate(10, ['*'], 'sls_page')
            ->withQueryString();

        return view('import.index', compact('kegiatanList', 'riwayat', 'jejak', 'slsBerpolygon', 'cariSls'));
    }

    /**
     * Hapus satu import.
     * - Batas SLS: polygon SLS dari batch ini dikosongkan (baris SLS TIDAK dihapus, karena
     *   penugasan dan titik bergantung padanya). SLS kegiatan lain tidak terpengaruh.
     * - Titik lokasi: titik dari batch ini dihapus beserta usaha & KK-nya.
     *   Titik hasil tagging petugas tidak pernah ikut terhapus.
     * Import lama yang belum terlacak hanya dihapus catatannya.
     */
    public function destroy(ImportBatch $batch): RedirectResponse
    {
        $label = $batch->nama_file;
        $jenis = $batch->jenis_data === 'batas_sls' ? 'batas SLS' : 'titik lokasi';

        $ringkas = DB::transaction(function () use ($batch) {
            if ($batch->jenis_data === 'batas_sls') {
                $ids = Sls::where('import_batch_id', $batch->id)->pluck('id');

                if ($ids->isNotEmpty()) {
                    DB::table('sls')->whereIn('id', $ids)->update([
                        'area' => null, 'luas' => null, 'periode' => null, 'import_batch_id' => null,
                    ]);
                    // Tanpa polygon, posisi titik tidak bisa diperiksa lagi.
                    DB::table('bangunan')->whereIn('sls_id', $ids)->update(['di_luar_sls' => null]);
                    Bangunan::sinkronStatusPerbaikanSemua();   // tanpa polygon, penanda "luar batas" ikut lepas
                }

                return $ids->count() . ' polygon SLS dikosongkan';
            }

            // Titik yang sudah dikoreksi (sudah diperbaiki / terverifikasi) dipertahankan.
            $q = $this->titikImportQuery()->where('import_batch_id', $batch->id)->tidakTerkoreksi();
            $n = (clone $q)->count();
            $q->delete(); // usaha & rumah tangga ikut terhapus (FK cascade)

            $dipertahankan = $this->titikImportQuery()->where('import_batch_id', $batch->id)->terkoreksi()->count();

            return $n . ' titik dihapus' . ($dipertahankan ? ", {$dipertahankan} titik hasil koreksi dipertahankan" : '');
        });

        $batch->delete();

        Riwayat::catat(
            modul: 'import',
            aksi: 'hapus',
            keterangan: "Menghapus import {$jenis} dari file {$label}: {$ringkas}",
            objekId: $batch->id,
            objekLabel: $label,
        );

        return back()->with('status', "Import {$label} dihapus ({$ringkas}).");
    }

    /**
     * Hapus polygon SATU SLS (mis. salah impor). Baris SLS, penugasan, dan titiknya tidak dihapus;
     * hanya bentuk wilayahnya yang dikosongkan, sehingga tagging di SLS itu berhenti sampai polygon diimpor lagi.
     */
    public function hapusPolygon(Sls $sls): RedirectResponse
    {
        DB::transaction(function () use ($sls) {
            DB::table('sls')->where('id', $sls->id)->update([
                'area' => null, 'luas' => null, 'periode' => null, 'import_batch_id' => null,
            ]);
            // Tanpa polygon, posisi titik tidak bisa diperiksa lagi.
            DB::table('bangunan')->where('sls_id', $sls->id)->update(['di_luar_sls' => null]);
            Bangunan::sinkronStatusPerbaikanSemua();
        });

        Riwayat::catat(
            modul: 'sls',
            aksi: 'hapus',
            keterangan: "Menghapus polygon SLS {$sls->id_sls} ({$sls->nama_sls})",
            objekId: $sls->id,
            objekLabel: $sls->id_sls,
        );

        return back()->with('status', "Polygon SLS {$sls->id_sls} dihapus.");
    }

    /**
     * Salin SLS (beserta polygon) dari kegiatan lain ke kegiatan ini, untuk kegiatan yang
     * memakai batas SLS yang sama. SLS yang sudah ada di kegiatan tujuan tidak ditimpa.
     */
    public function salinSls(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dari_kegiatan_id'   => ['required', 'exists:kegiatan,id'],
            'ke_kegiatan_id'     => ['required', 'exists:kegiatan,id', 'different:dari_kegiatan_id'],
        ], [
            'ke_kegiatan_id.different' => 'Kegiatan asal dan tujuan tidak boleh sama.',
        ]);

        $asal   = Kegiatan::findOrFail($data['dari_kegiatan_id']);
        $tujuan = Kegiatan::findOrFail($data['ke_kegiatan_id']);

        $jumlah = DB::transaction(fn () => Sls::salinDariKegiatan($asal->id, $tujuan->id));

        Riwayat::catat(
            modul: 'import',
            aksi: 'import',
            keterangan: "Menyalin {$jumlah} SLS dari kegiatan {$asal->nama_kegiatan} ke kegiatan {$tujuan->nama_kegiatan}",
            objekId: $tujuan->id,
            objekLabel: $tujuan->nama_kegiatan,
            baru: ['dari' => $asal->nama_kegiatan, 'ke' => $tujuan->nama_kegiatan, 'jumlah_sls' => $jumlah],
        );

        $pesan = $jumlah > 0
            ? "{$jumlah} SLS disalin dari {$asal->nama_kegiatan} ke {$tujuan->nama_kegiatan}."
            : "Tidak ada SLS baru yang disalin: kegiatan asal kosong atau semua SLS-nya sudah ada di {$tujuan->nama_kegiatan}.";

        return redirect()->route('import.index')->with('status', $pesan);
    }

    /** Titik yang dibuat import (bukan hasil tagging petugas). */
    private function titikImportQuery()
    {
        return Bangunan::query()->where(fn ($w) => $w->whereNull('sumber')->orWhere('sumber', '!=', 'tagging'));
    }

    /**
     * Import batas_sls.geojson -> tabel sls, DI DALAM kegiatan yang dipilih.
     * Tiap kegiatan punya snapshot SLS-nya sendiri, jadi mengimpor batas baru
     * untuk satu kegiatan tidak mengubah batas kegiatan lain.
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
                    ['kegiatan_id' => (int) $request->kegiatan_id, 'id_sls' => $kodeSls],
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
                Sls::whereKey($sls->id)->update(['import_batch_id' => $batch->id]);

                // Kecamatan & kelurahan otomatis masuk ke master wilayah.
                $sls->sinkronWilayah();

                $valid++;
            } catch (\Throwable $e) {
                $errors[] = ['baris' => $baris, 'pesan' => "SLS {$kodeSls}: " . $e->getMessage()];
            }
        }

        // Titik yang sudah ada sebelum polygon ini diimpor langsung diperiksa posisinya,
        // lalu penanda "perlu perbaikan" disamakan (titik yang sudah dikoreksi tidak disentuh).
        $diperiksa = Bangunan::hitungUlangDiLuarSls((int) $request->kegiatan_id);
        Bangunan::sinkronStatusPerbaikanSemua((int) $request->kegiatan_id);

        $this->tutupBatch($batch, count($features), $valid, $errors);
        $this->catatRiwayatImport($request, $batch, 'batas_sls', count($features), $valid, count($errors));

        return redirect()->route('import.index')->with(
            'status',
            "Import batas SLS selesai: {$valid} berhasil, " . count($errors) . " bermasalah. {$diperiksa} titik diperiksa ulang terhadap batas SLS."
        );
    }

    /**
     * Import titik_lokasi.geojson -> tabel bangunan.
     * WAJIB dijalankan SETELAH batas SLS kegiatan ini ada (import batas SLS atau
     * salin dari kegiatan lain), karena tiap titik dicocokkan ke SLS milik kegiatan
     * yang sama lewat kode SLS.
     *
     */
    public function storeTitikLokasi(Request $request): RedirectResponse
    {
        $request->validate([
            'kegiatan_id'   => ['required', 'exists:kegiatan,id'],
            'file'          => ['required', 'file', 'max:20480'],
            'timpa_koreksi' => ['nullable', 'boolean'],
        ]);

        // Default: titik yang sudah dikoreksi di aplikasi DILEWATI. Admin dapat memilih menimpa juga.
        $timpaKoreksi = $request->boolean('timpa_koreksi');
        $dilewati = 0;
        $ditimpa = 0;
        $usahaDiganti = [];   // [#6] id titik yang daftar usahanya sudah diganti pada proses import ini

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
                $sls = $kodeSls
                    ? Sls::where('kegiatan_id', $request->kegiatan_id)->where('id_sls', $kodeSls)->first()
                    : null;

                // Titik yang sudah dikoreksi lewat aplikasi (petugas/admin) tidak ditimpa import ulang,
                // kecuali admin memilih "timpa juga".
                $sudahDikoreksi = Bangunan::where('kegiatan_id', (int) $request->kegiatan_id)
                    ->where('id_assignment', $idAssignment)
                    ->terkoreksi()
                    ->exists();

                if ($sudahDikoreksi && ! $timpaKoreksi) {
                    $dilewati++;
                    $errors[] = ['baris' => $baris, 'pesan' => "{$idAssignment}: dilewati karena sudah dikoreksi di aplikasi (hasil koreksi dipertahankan)."];
                    continue;
                }

                // Kunci pencarian (kegiatan_id + id_assignment): id_assignment yang sama di kegiatan
                // lain adalah titik yang berbeda, jadi titik kegiatan lain tidak ikut tertimpa.
                $bangunan = Bangunan::updateOrCreate(
                    ['kegiatan_id' => (int) $request->kegiatan_id, 'id_assignment' => $idAssignment],
                    [
                        'sls_id'          => $sls?->id,
                        'id_sls_asal'     => $kodeSls,
                        'nomor_bangunan'  => $props['no_bang'] ?? null,
                        'kode_bang_value' => $props['kode_bang_value'] ?? null,
                        'flag_btt'        => ($props['btt'] ?? '0') == '1',
                        'flag_bku'        => ($props['bku'] ?? '0') == '1',
                        'flag_repair'     => ($props['flag_repair'] ?? '0') == '1',
                        'jumlah_kk'       => (int) ($props['kk'] ?? 0),
                        // Kalau file memuat nama_usaha tapi tanpa kolom "usaha", satu titik = satu usaha.
                        'jumlah_usaha'    => (int) ($props['usaha'] ?? (array_key_exists('nama_usaha', $props) ? 1 : 0)),
                        'status'          => 'terdata',
                        // [#5] Kode SLS tidak ketemu -> posisi tidak bisa dicek. Kosongkan nilai lama
                        // supaya "di luar SLS" yang basi tidak menahan status perlu_perbaikan.
                        ...($sls ? [] : ['di_luar_sls' => null]),
                    ]
                );

                // simpan koordinat (kolom geometry + lat/long biasa sekaligus)
                $bangunan->setKoordinat((float) $coords[1], (float) $coords[0]);
                Bangunan::whereKey($bangunan->id)->update(['import_batch_id' => $batch->id]);

                if ($sudahDikoreksi) {
                    // "Timpa juga": file menjadi data awal lagi, jejak koreksi dibersihkan.
                    Bangunan::whereKey($bangunan->id)->update([
                        'status_perbaikan' => Bangunan::STATUS_NORMAL,
                        'dikoreksi_oleh' => null, 'dikoreksi_at' => null,
                        'diverifikasi_oleh' => null, 'diverifikasi_at' => null,
                        'diedit_manual_at' => null,
                        'latitude_asal' => null, 'longitude_asal' => null,
                    ]);
                    $ditimpa++;
                }

                // [#6] Nama usaha (kalau kolom nama_usaha ada di file) -> tabel usaha. Nama kosong/0/-/-- dilewati.
                // Usaha lama titik ini DIGANTI (sekali per titik per proses import), bukan ditambah, supaya nama
                // yang sudah diperbaiki di file tidak menumpuk dengan nama lamanya. Kalau beberapa baris file
                // memakai id_assignment yang sama, nama-namanya tetap terkumpul. Titik yang sudah dikoreksi di
                // aplikasi sudah dilewati di atas (kecuali "timpa juga"), jadi isian petugas tidak ikut terhapus.
                if (array_key_exists('nama_usaha', $props)) {
                    if (! isset($usahaDiganti[$bangunan->id])) {
                        $bangunan->usaha()->delete();
                        $usahaDiganti[$bangunan->id] = true;
                    }

                    $namaUsaha = trim((string) ($props['nama_usaha'] ?? ''));
                    if (! in_array(mb_strtolower($namaUsaha), ['', '0', '-', '--'], true)) {
                        Usaha::firstOrCreate(['bangunan_id' => $bangunan->id, 'nama_usaha' => $namaUsaha]);
                    }
                }

                if ($sls) {
                    // Kasus 2: SLS ketemu, cek apakah titiknya beneran di
                    // dalam polygon SLS itu atau meleset ke luar (untuk QC).
                    // isInsideSls(): true = di dalam, false = di luar, null = belum bisa
                    // dicek (SLS belum punya polygon). null TIDAK boleh dianggap "di luar".
                    $inside = $bangunan->isInsideSls();
                    $diLuar = $inside === null ? null : ! $inside;
                    $bangunan->update(['di_luar_sls' => $diLuar]);

                    if ($diLuar === true) {
                        $errors[] = [
                            'baris' => $baris,
                            'pesan' => "{$idAssignment}: koordinat berada DI LUAR batas SLS {$kodeSls} (data tetap disimpan, tandai untuk QC).",
                        ];
                    }
                } elseif ($kodeSls) {
                    // Kasus 3: ada kode SLS (dari salah satu field fallback),
                    // tapi belum ada di SLS kegiatan ini -> kemungkinan besar batas
                    // SLS kegiatan ini belum diimport/disalin, bukan datanya yang cacat.
                    $errors[] = [
                        'baris' => $baris,
                        'pesan' => "{$idAssignment}: kode SLS '{$kodeSls}' tidak ditemukan di SLS kegiatan ini (data tetap disimpan tanpa SLS). Import batas SLS atau salin SLS dari kegiatan lain dulu.",
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

        // Penanda perbaikan disamakan dengan kondisi terbaru (di luar SLS / flag_repair).
        Bangunan::sinkronStatusPerbaikanSemua((int) $request->kegiatan_id);

        $this->tutupBatch($batch, count($features), $valid, $errors);
        $this->catatRiwayatImport($request, $batch, 'titik_lokasi', count($features), $valid, count($errors));

        $ket = $dilewati ? " {$dilewati} titik dilewati karena sudah dikoreksi." : '';
        $ket .= $ditimpa ? " {$ditimpa} titik hasil koreksi ditimpa." : '';

        return redirect()->route('import.index')->with(
            'status',
            "Import titik lokasi selesai: {$valid} berhasil, " . count($errors) . ' catatan (lihat riwayat).' . $ket
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

    /** RIWAYAT: satu baris ringkasan per proses import (detail baris bermasalah ada di tabel import_batches). */
    private function catatRiwayatImport(Request $request, ImportBatch $batch, string $jenis, int $total, int $valid, int $jumlahCatatan): void
    {
        $namaJenis = $jenis === 'batas_sls' ? 'batas SLS' : 'titik lokasi';
        $kegiatan  = Kegiatan::find($request->kegiatan_id);

        Riwayat::catat(
            modul: 'import',
            aksi: 'import',
            keterangan: "Mengimpor {$namaJenis} dari file {$batch->nama_file}: {$valid} dari {$total} data berhasil, {$jumlahCatatan} catatan",
            objekId: $batch->id,
            objekLabel: $batch->nama_file,
            baru: [
                'jenis_data' => $namaJenis,
                'file'       => $batch->nama_file,
                'kegiatan'   => $kegiatan?->nama_kegiatan,
                'total_data' => $total,
                'data_valid' => $valid,
                'catatan'    => $jumlahCatatan,
            ],
        );
    }

    /** [#16] Jumlah baris catatan yang disimpan di import_batches.error_log (sisanya hanya dihitung). */
    private const MAKS_LOG_IMPORT = 200;

    private function tutupBatch(ImportBatch $batch, int $total, int $valid, array $errors): void
    {
        $jumlahCatatan = count($errors);
        $logTersimpan = $errors;

        if ($jumlahCatatan > self::MAKS_LOG_IMPORT) {
            $logTersimpan = array_slice($errors, 0, self::MAKS_LOG_IMPORT);
            $logTersimpan[] = [
                'baris' => '-',
                'pesan' => 'Dan ' . ($jumlahCatatan - self::MAKS_LOG_IMPORT) . ' catatan lainnya tidak ditampilkan '
                    . '(hanya ' . self::MAKS_LOG_IMPORT . ' catatan pertama yang disimpan).',
            ];
        }

        $batch->update([
            'total_data' => $total,
            'data_valid' => $valid, 
            'data_error' => $jumlahCatatan,   // jumlah asli, bukan jumlah yang disimpan
            'status' => 'success',
            'error_log' => $logTersimpan,
        ]);
    }
}
