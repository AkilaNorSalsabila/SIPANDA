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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImportController extends Controller
{
    /** Jumlah baris catatan yang disimpan di import_batches.error_log (sisanya hanya dihitung). */
    private const MAKS_LOG_IMPORT = 200;

    /** Berapa contoh kode yang ditampilkan pada catatan "ada di aplikasi tapi tidak ada di file". */
    private const CONTOH_HILANG = 20;

    /** Penghapusan "titik yang tidak ada di file" butuh persetujuan tambahan kalau lebih dari sekian bagian titik import hilang. */
    private const AMBANG_HAPUS_MASSAL = 0.30;

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
        // SLS: polygon yang saat ini dimiliki batch. Titik: titik yang DIBUAT batch itu (bukan yang hanya diperbarui).
        $ids = $riwayat->pluck('id');
        $jejakSls = Sls::whereIn('import_batch_id', $ids)
            ->selectRaw('import_batch_id, count(*) as n')->groupBy('import_batch_id')
            ->pluck('n', 'import_batch_id')->all();
        $jejakTitik = Bangunan::whereIn('import_batch_awal_id', $ids)
            ->selectRaw('import_batch_awal_id, count(*) as n')->groupBy('import_batch_awal_id')
            ->pluck('n', 'import_batch_awal_id')->all();
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
     * Hapus satu import (membatalkan efek batch itu).
     * - Batas SLS: polygon yang dimiliki batch ini DIKEMBALIKAN ke versi sebelumnya (kalau ada, mis. sebelum
     *   impor ulang dari QGIS), selain itu dikosongkan. Baris SLS TIDAK dihapus (penugasan & titik bergantung padanya).
     * - Titik lokasi: hanya titik yang DIBUAT batch ini yang dihapus (beserta usaha & KK-nya). Titik yang
     *   sebelumnya sudah ada dan hanya diperbarui batch ini tidak dihapus, begitu juga titik hasil koreksi / tagging.
     * Import lama yang belum terlacak hanya dihapus catatannya.
     */
    public function destroy(ImportBatch $batch): RedirectResponse
    {
        $label = $batch->nama_file;
        $jenis = $batch->jenis_data === 'batas_sls' ? 'batas SLS' : 'titik lokasi';

        $ringkas = DB::transaction(function () use ($batch) {
            if ($batch->jenis_data === 'batas_sls') {
                $ids = Sls::where('import_batch_id', $batch->id)->pluck('id');
                $dipulihkan = 0;
                $dikosongkan = 0;

                if ($ids->isNotEmpty()) {
                    // Polygon versi sebelumnya (sebelum batch ini menimpa) dipulihkan.
                    $dipulihkan = DB::affectingStatement('
                        UPDATE sls
                        SET area = area_sebelumnya, import_batch_id = batch_sebelumnya_id,
                            area_sebelumnya = NULL, batch_sebelumnya_id = NULL
                        WHERE import_batch_id = ? AND area_sebelumnya IS NOT NULL
                    ', [$batch->id]);

                    // Sisanya (polygon pertama kali dibuat batch ini) dikosongkan.
                    $dikosongkan = DB::affectingStatement('
                        UPDATE sls
                        SET area = NULL, luas = NULL, periode = NULL, import_batch_id = NULL
                        WHERE import_batch_id = ?
                    ', [$batch->id]);

                    // Posisi titik dinilai ulang terhadap polygon yang berlaku sekarang.
                    DB::table('bangunan')->whereIn('sls_id', $ids)->update(['di_luar_sls' => null]);
                    Bangunan::hitungUlangDiLuarSls((int) $batch->kegiatan_id);
                    Bangunan::sinkronStatusPerbaikanSemua((int) $batch->kegiatan_id);
                }

                // Polygon "versi sebelumnya" milik batch yang dihapus tidak punya arti lagi.
                DB::table('sls')->where('batch_sebelumnya_id', $batch->id)
                    ->update(['area_sebelumnya' => null, 'batch_sebelumnya_id' => null]);

                return "{$dipulihkan} polygon SLS dikembalikan ke versi sebelumnya, {$dikosongkan} polygon dikosongkan";
            }

            // Titik yang sudah dikoreksi (sudah diperbaiki / terverifikasi) dipertahankan.
            $dibuat = Bangunan::where('import_batch_awal_id', $batch->id);
            $n = (clone $dibuat)->tidakTerkoreksi()->count();
            (clone $dibuat)->tidakTerkoreksi()->delete(); // usaha & rumah tangga ikut terhapus (FK cascade)

            $dipertahankan = Bangunan::where('import_batch_awal_id', $batch->id)->count();

            // Titik lama yang hanya diperbarui batch ini: penanda batch dikembalikan ke batch pembuatnya.
            $hanyaDiperbarui = Bangunan::where('import_batch_id', $batch->id)
                ->where(fn ($w) => $w->whereNull('import_batch_awal_id')->orWhere('import_batch_awal_id', '!=', $batch->id))
                ->update(['import_batch_id' => DB::raw('import_batch_awal_id')]);

            $teks = "{$n} titik yang dibuat import ini dihapus";
            if ($dipertahankan) {
                $teks .= ", {$dipertahankan} titik hasil koreksi dipertahankan";
            }
            if ($hanyaDiperbarui) {
                $teks .= "; {$hanyaDiperbarui} titik yang sudah ada dan hanya diperbarui import ini tidak dihapus (isinya tidak dikembalikan)";
            }

            return $teks;
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
                'area_sebelumnya' => null, 'batch_sebelumnya_id' => null,
            ]);
            // Tanpa polygon, posisi titik tidak bisa diperiksa lagi.
            DB::table('bangunan')->where('sls_id', $sls->id)->update(['di_luar_sls' => null]);
            Bangunan::sinkronStatusPerbaikanSemua((int) $sls->kegiatan_id);
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
     * memakai batas SLS yang sama. SLS yang sudah ada di kegiatan tujuan tidak ditimpa, kecuali
     * polygon yang masih kosong (atau semua polygon kalau admin memilih "timpa polygon").
     * Titik yang sudah ada di kegiatan tujuan disambungkan ke SLS-nya dan diperiksa ulang.
     */
    public function salinSls(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dari_kegiatan_id'   => ['required', 'exists:kegiatan,id'],
            'ke_kegiatan_id'     => ['required', 'exists:kegiatan,id', 'different:dari_kegiatan_id'],
            'timpa_polygon'      => ['nullable', 'boolean'],
        ], [
            'ke_kegiatan_id.different' => 'Kegiatan asal dan tujuan tidak boleh sama.',
        ]);

        $asal   = Kegiatan::findOrFail($data['dari_kegiatan_id']);
        $tujuan = Kegiatan::findOrFail($data['ke_kegiatan_id']);
        $timpa  = $request->boolean('timpa_polygon');

        [$hasil, $disambung, $diperiksa] = DB::transaction(function () use ($asal, $tujuan, $timpa) {
            $hasil = Sls::salinDariKegiatan($asal->id, $tujuan->id, $timpa);
            $disambung = Bangunan::sambungkanTanpaSls($tujuan->id);
            $diperiksa = Bangunan::hitungUlangDiLuarSls($tujuan->id);
            Bangunan::sinkronStatusPerbaikanSemua($tujuan->id);

            return [$hasil, $disambung, $diperiksa];
        });

        Riwayat::catat(
            modul: 'import',
            aksi: 'import',
            keterangan: "Menyalin {$hasil['disalin']} SLS baru dan memperbarui {$hasil['diperbarui']} polygon dari kegiatan {$asal->nama_kegiatan} ke kegiatan {$tujuan->nama_kegiatan}",
            objekId: $tujuan->id,
            objekLabel: $tujuan->nama_kegiatan,
            baru: [
                'dari' => $asal->nama_kegiatan, 'ke' => $tujuan->nama_kegiatan,
                'jumlah_sls' => $hasil['disalin'], 'polygon_diperbarui' => $hasil['diperbarui'],
                'titik_disambung' => $disambung, 'timpa_polygon' => $timpa,
            ],
        );

        if ($hasil['disalin'] + $hasil['diperbarui'] === 0) {
            $pesan = "Tidak ada yang berubah: kegiatan asal kosong, atau semua SLS-nya sudah ada di {$tujuan->nama_kegiatan}"
                . ($timpa ? ' dengan polygon yang sama.' : ' (centang "timpa polygon" untuk mengganti polygon yang sudah ada).');
        } else {
            $pesan = "{$hasil['disalin']} SLS disalin dan {$hasil['diperbarui']} polygon diperbarui dari {$asal->nama_kegiatan} ke {$tujuan->nama_kegiatan}.";
            $pesan .= " {$disambung} titik tanpa SLS tersambung, {$diperiksa} titik diperiksa ulang terhadap batas SLS.";
        }

        return redirect()->route('import.index')->with('status', $pesan);
    }

    /**
     * Import batas_sls.geojson -> tabel sls, DI DALAM kegiatan yang dipilih.
     * Tiap kegiatan punya snapshot SLS-nya sendiri, jadi mengimpor batas baru
     * untuk satu kegiatan tidak mengubah batas kegiatan lain.
     * Impor ulang memperbarui SLS yang sama (baris & penugasan tetap), menyimpan polygon lama sebagai
     * versi sebelumnya, lalu menyambungkan titik tanpa SLS dan memeriksa ulang posisi semua titik.
     */
    public function storeBatasSls(Request $request): RedirectResponse
    {
        $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'file'        => ['required', 'file', 'max:20480'],
        ]);

        $this->longgarkanBatasProses();
        $this->pastikanFileGeoJson($request);

        // File dibaca & divalidasi SEBELUM batch dibuat, supaya file rusak tidak meninggalkan batch "processing".
        $features = $this->bacaGeoJsonFeatures($request->file('file'));
        $kegiatanId = (int) $request->kegiatan_id;

        $batch = ImportBatch::create([
            'kegiatan_id' => $kegiatanId,
            'uploaded_by' => Auth::id(),
            'jenis_data'  => 'batas_sls',
            'nama_file'   => $request->file('file')->getClientOriginalName(),
            'status'      => 'processing',
        ]);

        $valid = 0;
        $polygonTetap = 0;   // polygon di file sama dengan yang tersimpan (tidak ditimpa)
        $catatan = [];
        $kodeDiFile = [];

        try {
            foreach ($features as $i => $feature) {
                $baris = $i + 1;
                $props = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                $geometry = $feature['geometry'] ?? null;
                $kodeSls = $this->bersihkanKode($props['idsls'] ?? null);

                if (! $kodeSls) {
                    $catatan[] = $this->galat($baris, 'Kolom "idsls" kosong, baris dilewati.');
                    continue;
                }

                $kodeDiFile[$kodeSls] = true;

                if (! is_array($geometry)) {
                    $catatan[] = $this->galat($baris, "SLS {$kodeSls}: geometry kosong, baris dilewati.");
                    continue;
                }

                if (! in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)) {
                    $catatan[] = $this->galat($baris, "SLS {$kodeSls}: geometry bertipe " . ($geometry['type'] ?? '-') . ', harus Polygon / MultiPolygon.');
                    continue;
                }

                try {
                    // Satu transaksi per SLS: kalau polygon gagal disimpan, atribut SLS-nya ikut dibatalkan.
                    DB::transaction(function () use ($kegiatanId, $kodeSls, $props, $geometry, $batch, &$polygonTetap) {
                        $sls = Sls::updateOrCreate(
                            ['kegiatan_id' => $kegiatanId, 'id_sls' => $kodeSls],
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

                        // Polygon yang sama persis dengan yang tersimpan tidak disentuh: versi "sebelumnya" (undo)
                        // dan pemilik batch-nya tetap, jadi impor ulang file yang sama tidak menghapus versi aslinya.
                        if ($sls->polygonSama($geometry)) {
                            $polygonTetap++;
                        } else {
                            $sls->simpanPolygonSebelumnya();      // versi lama disimpan sebelum ditimpa (untuk undo)
                            $sls->setAreaFromGeoJson($geometry);
                            Sls::whereKey($sls->id)->update(['import_batch_id' => $batch->id]);
                        }

                        // Kecamatan & kelurahan otomatis masuk ke master wilayah.
                        $sls->sinkronWilayah();
                    });

                    $valid++;
                } catch (\Throwable $e) {
                    $catatan[] = $this->galat($baris, "SLS {$kodeSls}: " . $e->getMessage());
                }
            }

            // Titik yang diimpor sebelum SLS-nya ada kini disambungkan; semua titik diperiksa posisinya;
            // lalu penanda "perlu perbaikan" disamakan (titik yang sudah dikoreksi tidak disentuh,
            // kecuali kini ternyata di luar batas).
            $disambung = Bangunan::sambungkanTanpaSls($kegiatanId);
            $diperiksa = Bangunan::hitungUlangDiLuarSls($kegiatanId);
            Bangunan::sinkronStatusPerbaikanSemua($kegiatanId);

            // SLS yang ada di aplikasi tetapi tidak ada di file: hanya dilaporkan (SLS punya penugasan & titik).
            $hilang = Sls::where('kegiatan_id', $kegiatanId)->orderBy('id_sls')->pluck('id_sls')
                ->reject(fn ($kode) => isset($kodeDiFile[$kode]))->values();
            if ($hilang->isNotEmpty() && $valid > 0) {
                $catatan[] = $this->info(
                    '-',
                    "{$hilang->count()} SLS ada di aplikasi tetapi tidak ada di file ini (polygon lamanya tetap dipakai). Contoh: "
                    . $hilang->take(self::CONTOH_HILANG)->implode(', ') . ($hilang->count() > self::CONTOH_HILANG ? ', …' : '')
                );
            }
        } catch (\Throwable $e) {
            $this->tutupBatch($batch, count($features), $valid, 0, array_merge($catatan, [$this->galat('-', 'Import berhenti: ' . $e->getMessage())]), gagal: true);

            return redirect()->route('import.index')->with('error', 'Import batas SLS berhenti karena kesalahan: ' . $e->getMessage());
        }

        $this->tutupBatch($batch, count($features), $valid, 0, $catatan);
        $this->catatRiwayatImport($request, $batch, 'batas_sls', count($features), $valid, $this->hitungGalat($catatan));

        $pesan = "Import batas SLS selesai: {$valid} berhasil, " . $this->hitungGalat($catatan) . " bermasalah. "
            . "{$disambung} titik tanpa SLS tersambung, {$diperiksa} titik diperiksa ulang terhadap batas SLS."
            . ($polygonTetap ? " {$polygonTetap} polygon tidak berubah dari versi tersimpan (tidak ditimpa)." : '')
            . ($hilang->isNotEmpty() ? " {$hilang->count()} SLS di aplikasi tidak ada di file (lihat detail riwayat)." : '');

        return $valid === 0
            ? redirect()->route('import.index')->with('error', 'Tidak ada SLS yang berhasil diimpor. Pastikan file yang diunggah adalah batas SLS (kolom idsls + geometry Polygon). ' . $pesan)
            : redirect()->route('import.index')->with('status', $pesan);
    }

    /**
     * Import titik_lokasi.geojson -> tabel bangunan.
     * WAJIB dijalankan SETELAH batas SLS kegiatan ini ada (import batas SLS atau
     * salin dari kegiatan lain), karena tiap titik dicocokkan ke SLS milik kegiatan
     * yang sama lewat kode SLS.
     *
     * Impor ulang setelah perbaikan di QGIS, aturan untuk titik yang SUDAH dikoreksi di aplikasi
     * (sudah diperbaiki / terverifikasi / diedit) dan titik hasil tagging:
     *   - posisi di file BERBEDA dari posisi acuan (posisi awal dari file sebelumnya) -> QGIS memperbaikinya:
     *     file menang, koreksi di aplikasi dibersihkan, titik dinilai ulang;
     *   - posisi di file sama -> tidak ada perbaikan baru dari QGIS: koreksi di aplikasi dipertahankan (dilewati);
     *   - "timpa_koreksi" dicentang -> file selalu menang;
     *   - "hapus_tidak_ada" dicentang -> titik hasil import yang tidak ada di file dihapus (yang sudah dikoreksi dipertahankan).
     */
    public function storeTitikLokasi(Request $request): RedirectResponse
    {
        $request->validate([
            'kegiatan_id'     => ['required', 'exists:kegiatan,id'],
            'file'            => ['required', 'file', 'max:20480'],
            'timpa_koreksi'   => ['nullable', 'boolean'],
            'hapus_tidak_ada' => ['nullable', 'boolean'],
            'hapus_massal_ok' => ['nullable', 'boolean'],
        ]);

        $this->longgarkanBatasProses();
        $this->pastikanFileGeoJson($request);

        $features = $this->bacaGeoJsonFeatures($request->file('file'));
        $kegiatanId = (int) $request->kegiatan_id;
        $timpaKoreksi = $request->boolean('timpa_koreksi');
        $hapusTidakAda = $request->boolean('hapus_tidak_ada');
        $hapusMassalOk = $request->boolean('hapus_massal_ok');

        // Titik dicocokkan ke SLS lewat kode SLS kegiatan ini: tanpa SLS, semua titik masuk "tanpa SLS".
        if (! Sls::where('kegiatan_id', $kegiatanId)->exists()) {
            throw ValidationException::withMessages([
                'file' => 'Kegiatan ini belum punya batas SLS. Impor batas SLS (atau salin dari kegiatan lain) lebih dulu, baru impor titik lokasi.',
            ]);
        }

        $batch = ImportBatch::create([
            'kegiatan_id' => $kegiatanId,
            'uploaded_by' => Auth::id(),
            'jenis_data'  => 'titik_lokasi',
            'nama_file'   => $request->file('file')->getClientOriginalName(),
            'status'      => 'processing',
        ]);

        $valid = 0;
        $dilewati = 0;
        $ditimpa = 0;          // ditimpa karena admin mencentang "timpa juga"
        $diperbaruiDariFile = 0; // ditimpa otomatis karena posisi di file berubah (perbaikan QGIS)
        $dilewatiAtributBeda = 0; // dilewati, tetapi atribut di file berbeda dari aplikasi (tidak diterapkan)
        $catatan = [];
        $usahaDiganti = [];    // id titik yang daftar usahanya sudah diganti pada proses import ini
        $idDiFile = [];

        // Satu query untuk semua titik kegiatan ini (menggantikan satu query per baris).
        $ada = Bangunan::where('kegiatan_id', $kegiatanId)
            ->get(['id', 'id_assignment', 'sumber', 'status_perbaikan', 'diedit_manual_at',
                   'latitude', 'longitude', 'latitude_asal', 'longitude_asal',
                   'sls_id', 'id_sls_asal', 'nomor_bangunan', 'jumlah_kk', 'jumlah_usaha',
                   'flag_btt', 'flag_bku', 'flag_repair'])
            ->keyBy('id_assignment');

        // Cache SLS kegiatan ini: kode -> id.
        $slsMap = Sls::where('kegiatan_id', $kegiatanId)->pluck('id', 'id_sls');

        try {
            foreach ($features as $i => $feature) {
                $baris = $i + 1;
                $props = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                $geometry = is_array($feature['geometry'] ?? null) ? $feature['geometry'] : null;

                $idAssignment = $this->bersihkanKode($props['id_assignment'] ?? null);

                // Fallback berlapis: idsls; kalau kosong (null ATAU string kosong), level_5_full_code;
                // lalu idsubsls (16 digit dipotong ke 14 digit SLS). Kalau semuanya kosong, $kodeSls null
                // (titik tetap disimpan, tanpa SLS).
                $kodeSls = $this->kodeSlsTitik($props);

                if (! $idAssignment) {
                    $catatan[] = $this->galat($baris, 'Kolom "id_assignment" kosong, baris dilewati.');
                    continue;
                }

                // Dicatat sebelum koordinat diperiksa: baris dengan koordinat rusak tetap "ada di file",
                // jadi tidak dianggap hilang (dan tidak ikut terhapus oleh opsi "hapus yang tidak ada di file").
                $idDiFile[$idAssignment] = true;

                $koordinat = $this->bacaKoordinatTitik($geometry);
                if (is_string($koordinat)) {
                    $catatan[] = $this->galat($baris, "{$idAssignment}: {$koordinat}");
                    continue;
                }
                [$lat, $lng] = $koordinat;

                try {
                    $lama = $ada->get($idAssignment);

                    // File tanpa kolom kode SLS untuk titik yang sudah ada: SLS lama dipertahankan
                    // (bukan dilepas dari SLS-nya).
                    if ($kodeSls === null && $lama && $lama->sls_id) {
                        $kodeSls = $lama->id_sls_asal;
                        $sls = (int) $lama->sls_id;
                    } else {
                        $sls = $kodeSls ? ($slsMap[$kodeSls] ?? null) : null;
                    }

                    // Titik yang sudah dikoreksi / hasil tagging dilindungi, kecuali posisi di file
                    // berubah (perbaikan QGIS) atau admin memilih "timpa juga".
                    $dilindungi = $lama && ($lama->sudahDikoreksi() || $lama->sumber === 'tagging');
                    $timpaOtomatis = false;

                    if ($dilindungi && ! $timpaKoreksi) {
                        if ($lama->posisiFileBerubah($lat, $lng)) {
                            $timpaOtomatis = true;
                        } else {
                            $dilewati++;
                            $beda = $this->atributBerbeda($props, $lama, $kodeSls);
                            if ($beda) {
                                $dilewatiAtributBeda++;
                            }
                            $catatan[] = $this->info($baris, "{$idAssignment}: dilewati karena sudah dikoreksi di aplikasi dan posisinya di file tidak berubah (hasil koreksi dipertahankan)."
                                . ($beda ? ' PERHATIAN: atribut di file berbeda dari aplikasi dan TIDAK diterapkan (' . implode(', ', $beda)
                                    . '). Kalau perubahan itu memang dari QGIS, impor ulang dengan "timpa juga titik yang sudah dikoreksi".' : ''));
                            continue;
                        }
                    }

                    DB::transaction(function () use (
                        $kegiatanId, $idAssignment, $kodeSls, $sls, $props, $lat, $lng, $batch,
                        $dilindungi, &$usahaDiganti, &$catatan, $baris, $lama
                    ) {
                        // Kunci pencarian (kegiatan_id + id_assignment): id_assignment yang sama di kegiatan
                        // lain adalah titik yang berbeda, jadi titik kegiatan lain tidak ikut tertimpa.
                        // Atribut hanya diambil dari kolom yang ADA di file: titik yang sudah ada tidak
                        // dikosongkan/di-nol-kan oleh file yang kolomnya lebih sedikit.
                        $bangunan = Bangunan::updateOrCreate(
                            ['kegiatan_id' => $kegiatanId, 'id_assignment' => $idAssignment],
                            $this->atributDariFile($props, $kodeSls, $sls, $lama === null, $batch->id)
                        );

                        if ($bangunan->wasRecentlyCreated) {
                            $bangunan->import_batch_awal_id = $batch->id;   // pembuat titik (untuk membatalkan batch)
                        }

                        if ($dilindungi) {
                            // File menjadi data awal lagi, jejak koreksi dibersihkan.
                            $bangunan->forceFill([
                                'status_perbaikan' => Bangunan::STATUS_NORMAL,
                                'dikoreksi_oleh' => null, 'dikoreksi_at' => null,
                                'diverifikasi_oleh' => null, 'diverifikasi_at' => null,
                                'diedit_manual_at' => null,
                                'latitude_asal' => null, 'longitude_asal' => null,
                                'alasan_tolak' => null,
                            ]);
                        }

                        // simpan koordinat (kolom geometry + lat/long biasa sekaligus; ikut menyimpan atribut di atas)
                        $bangunan->setKoordinat($lat, $lng);

                        // Nama usaha (kalau kolom nama_usaha ada di file) -> tabel usaha. Nama kosong/0/-/-- dilewati.
                        // Usaha lama titik ini DIGANTI (sekali per titik per proses import), bukan ditambah, supaya nama
                        // yang sudah diperbaiki di file tidak menumpuk dengan nama lamanya. Kalau beberapa baris file
                        // memakai id_assignment yang sama, nama-namanya tetap terkumpul. Titik yang sudah dikoreksi di
                        // aplikasi sudah dilewati di atas (kecuali file menang), jadi isian petugas tidak ikut terhapus.
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
                            // SLS ketemu, cek apakah titiknya beneran di dalam polygon SLS itu atau meleset ke luar
                            // (untuk QC). isInsideSls(): true = di dalam, false = di luar, null = belum bisa dicek
                            // (SLS belum punya polygon). null TIDAK boleh dianggap "di luar".
                            $inside = $bangunan->isInsideSls();
                            $diLuar = $inside === null ? null : ! $inside;
                            $bangunan->update(['di_luar_sls' => $diLuar]);

                            if ($diLuar === true) {
                                $catatan[] = $this->galat($baris, "{$idAssignment}: koordinat berada DI LUAR batas SLS {$kodeSls} (data tetap disimpan, tandai untuk QC).");
                            }
                        } elseif ($kodeSls) {
                            // Kode SLS ada tapi belum ada di SLS kegiatan ini -> kemungkinan besar batas SLS kegiatan
                            // ini belum diimport/disalin. Titik akan tersambung otomatis saat batas SLS diimpor.
                            $catatan[] = $this->galat($baris, "{$idAssignment}: kode SLS '{$kodeSls}' tidak ditemukan di SLS kegiatan ini (data tetap disimpan tanpa SLS dan tersambung otomatis saat batas SLS-nya diimpor).");
                        } else {
                            // idsls, level_5_full_code, dan idsubsls kosong semua. Data tetap disimpan, tapi ditandai.
                            $catatan[] = $this->galat($baris, "{$idAssignment}: tidak ada kode SLS pada data (idsls/level_5_full_code/idsubsls kosong semua), data disimpan tanpa SLS.");
                        }
                    });

                    if ($dilindungi) {
                        // Jejak: kondisi koreksi sebelum ditimpa dicatat per titik supaya bisa dipulihkan manual.
                        Riwayat::catat(
                            modul: 'import',
                            aksi: 'ubah',
                            keterangan: "Koreksi titik {$idAssignment} ditimpa file {$batch->nama_file} "
                                . ($timpaOtomatis ? '(posisi di file berubah: perbaikan QGIS)' : '(opsi "timpa juga titik yang sudah dikoreksi")'),
                            objekId: (int) $lama->id,
                            objekLabel: $idAssignment,
                            lama: [
                                'latitude' => $lama->latitude, 'longitude' => $lama->longitude,
                                'lat_asal' => $lama->latitude_asal, 'lon_asal' => $lama->longitude_asal,
                                'status_perbaikan' => $lama->status_perbaikan,
                                'no_bang' => $lama->nomor_bangunan, 'kk' => $lama->jumlah_kk, 'usaha' => $lama->jumlah_usaha,
                            ],
                            baru: [
                                'latitude' => $lat, 'longitude' => $lng,
                                'lat_asal' => null, 'lon_asal' => null,
                                'status_perbaikan' => Bangunan::STATUS_NORMAL,
                                'no_bang' => $props['no_bang'] ?? $lama->nomor_bangunan,
                                'kk' => $props['kk'] ?? $lama->jumlah_kk,
                                'usaha' => $props['usaha'] ?? $lama->jumlah_usaha,
                            ],
                        );

                        if ($timpaOtomatis) {
                            $diperbaruiDariFile++;
                            $catatan[] = $this->info($baris, "{$idAssignment}: posisi di file berubah dari posisi acuan, jadi hasil perbaikan QGIS menggantikan koreksi di aplikasi.");
                        } else {
                            $ditimpa++;
                        }
                    }

                    $valid++;
                } catch (\Throwable $e) {
                    $catatan[] = $this->galat($baris, $idAssignment . ': ' . $e->getMessage());
                }
            }

            // Titik hasil import yang ada di aplikasi tetapi tidak ada di file ini.
            $hilang = $ada->filter(fn ($b) => $b->sumber !== 'tagging' && ! isset($idDiFile[$b->id_assignment]));
            $dihapus = 0;
            $hilangDipertahankan = 0;

            $hapusDiblok = false;
            if ($hilang->isNotEmpty() && ($valid + $dilewati) > 0) {
                // Pengaman: file sebagian wilayah tidak boleh menghapus sebagian besar titik tanpa persetujuan eksplisit.
                $totalImport = $ada->filter(fn ($b) => $b->sumber !== 'tagging')->count();
                $persenHilang = $totalImport > 0 ? $hilang->count() / $totalImport : 0;

                if ($hapusTidakAda && $persenHilang > self::AMBANG_HAPUS_MASSAL && ! $hapusMassalOk) {
                    $hapusDiblok = true;
                    $catatan[] = $this->galat('-', sprintf(
                        'Penghapusan DIBATALKAN: %d dari %d titik import (%.0f%%) tidak ada di file ini, melebihi batas %d%%. '
                        . 'Kemungkinan file hanya memuat sebagian wilayah. Kalau file memang lengkap, ulangi dengan mencentang "izinkan penghapusan massal".',
                        $hilang->count(), $totalImport, $persenHilang * 100, self::AMBANG_HAPUS_MASSAL * 100
                    ));
                }

                if ($hapusTidakAda && ! $hapusDiblok) {
                    foreach ($hilang->pluck('id')->chunk(1000) as $potong) {
                        $dihapus += Bangunan::whereIn('id', $potong)->tidakTerkoreksi()->delete();
                    }
                    $hilangDipertahankan = $hilang->count() - $dihapus;
                    $catatan[] = $this->info('-', "{$dihapus} titik yang tidak ada di file dihapus"
                        . ($hilangDipertahankan ? ", {$hilangDipertahankan} titik dipertahankan karena sudah dikoreksi" : '') . '.');
                } elseif (! $hapusTidakAda) {
                    $catatan[] = $this->info(
                        '-',
                        "{$hilang->count()} titik ada di aplikasi tetapi tidak ada di file ini (tidak dihapus; centang \"hapus titik yang tidak ada di file\" bila ingin disamakan). Contoh id_assignment: "
                        . $hilang->take(self::CONTOH_HILANG)->pluck('id_assignment')->implode(', ')
                        . ($hilang->count() > self::CONTOH_HILANG ? ', …' : '')
                    );
                }
            }

            // Penanda perbaikan disamakan dengan kondisi terbaru (di luar SLS / flag_repair).
            Bangunan::sinkronStatusPerbaikanSemua($kegiatanId);
        } catch (\Throwable $e) {
            $this->tutupBatch($batch, count($features), $valid, $dilewati, array_merge($catatan, [$this->galat('-', 'Import berhenti: ' . $e->getMessage())]), gagal: true);

            return redirect()->route('import.index')->with('error', 'Import titik lokasi berhenti karena kesalahan: ' . $e->getMessage());
        }

        $this->tutupBatch($batch, count($features), $valid, $dilewati, $catatan);
        $this->catatRiwayatImport($request, $batch, 'titik_lokasi', count($features), $valid, $this->hitungGalat($catatan), [
            'dilewati' => $dilewati, 'ditimpa' => $ditimpa, 'diperbarui_dari_file' => $diperbaruiDariFile,
            'tidak_ada_di_file' => $hilang->count(), 'dihapus_tidak_ada' => $dihapus,
            'dilewati_atribut_beda' => $dilewatiAtributBeda, 'hapus_diblok' => $hapusDiblok,
        ]);

        $ket = $dilewati ? " {$dilewati} titik dilewati karena sudah dikoreksi dan posisinya di file tidak berubah." : '';
        $ket .= $diperbaruiDariFile ? " {$diperbaruiDariFile} titik diperbarui dari file karena posisinya berubah (perbaikan QGIS)." : '';
        $ket .= $dilewatiAtributBeda ? " {$dilewatiAtributBeda} dari titik yang dilewati punya atribut di file yang berbeda dari aplikasi (tidak diterapkan, lihat detail riwayat)." : '';
        $ket .= $ditimpa ? " {$ditimpa} titik hasil koreksi ditimpa." : '';
        $ket .= $hilang->isNotEmpty()
            ? ($hapusDiblok
                ? " Penghapusan titik yang tidak ada di file DIBATALKAN (terlalu banyak titik hilang; lihat detail riwayat)."
                : ($hapusTidakAda ? " {$dihapus} titik yang tidak ada di file dihapus." : " {$hilang->count()} titik di aplikasi tidak ada di file (lihat detail riwayat)."))
            : '';

        $pesan = "Import titik lokasi selesai: {$valid} berhasil, " . $this->hitungGalat($catatan) . ' catatan (lihat riwayat).' . $ket;

        return $valid === 0 && $dilewati === 0
            ? redirect()->route('import.index')->with('error', 'Tidak ada titik yang berhasil diimpor. Pastikan file yang diunggah adalah titik lokasi (kolom id_assignment + geometry Point). ' . $pesan)
            : redirect()->route('import.index')->with('status', $pesan);
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** Import file besar (ribuan titik) butuh lebih lama dan lebih banyak memori daripada default PHP. */
    private function longgarkanBatasProses(): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
    }

    /**
     * Validasi ringan: pastikan file yang diupload memang JSON/GeoJSON.
     * Kesalahan dikembalikan sebagai pesan validasi di halaman Import (bukan halaman error 422).
     */
    private function pastikanFileGeoJson(Request $request): void
    {
        $ext = strtolower($request->file('file')->getClientOriginalExtension());

        if (! in_array($ext, ['json', 'geojson'], true)) {
            throw ValidationException::withMessages(['file' => 'File harus berformat .json atau .geojson']);
        }
    }

    /**
     * Baca GeoJSON, pastikan valid, bersistem koordinat WGS84 (EPSG:4326 / CRS84), dan memuat feature.
     * Dipanggil SEBELUM ImportBatch dibuat.
     */
    private function bacaGeoJsonFeatures($file): array
    {
        $content = json_decode(file_get_contents($file->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($content)) {
            throw ValidationException::withMessages([
                'file' => 'File GeoJSON tidak valid' . (json_last_error() !== JSON_ERROR_NONE ? ': ' . json_last_error_msg() : '.'),
            ]);
        }

        // Aplikasi menyimpan koordinat apa adanya sebagai EPSG:4326. File dengan CRS lain (mis. UTM) akan
        // menghasilkan posisi salah, jadi ditolak. Ekspor ulang dari QGIS dengan CRS EPSG:4326.
        $crs = $content['crs']['properties']['name'] ?? null;
        if (is_string($crs) && ! preg_match('/CRS84|EPSG:{1,2}4326/i', $crs)) {
            throw ValidationException::withMessages([
                'file' => "Sistem koordinat file ({$crs}) bukan WGS84 / EPSG:4326. Ekspor ulang dari QGIS dengan CRS EPSG:4326.",
            ]);
        }

        // Satu Feature tunggal diperlakukan sebagai koleksi berisi satu feature.
        if (($content['type'] ?? null) === 'Feature') {
            $content = ['features' => [$content]];
        }

        $features = $content['features'] ?? [];

        if (! is_array($features) || count($features) === 0) {
            throw ValidationException::withMessages(['file' => 'File tidak memuat satu pun feature. Pastikan file adalah GeoJSON FeatureCollection.']);
        }

        return $features;
    }

    /** Kode sebagai string rapi; angka JSON diubah ke teks, spasi dibuang, kosong menjadi null. */
    private function bersihkanKode(mixed $nilai): ?string
    {
        if (! is_scalar($nilai) || is_bool($nilai)) {
            return null;
        }

        // Kolom ID yang tersimpan sebagai angka desimal di QGIS terbaca float; (string) float menghasilkan
        // notasi ilmiah (mis. 3.5760110010001E+15) yang merusak kode, jadi bilangan bulat dicetak utuh.
        if (is_float($nilai)) {
            $nilai = (is_finite($nilai) && floor($nilai) === $nilai && abs($nilai) < 1e18)
                ? number_format($nilai, 0, '', '')
                : rtrim(rtrim(sprintf('%.15F', $nilai), '0'), '.');
        }

        $teks = trim((string) $nilai);

        return $teks === '' ? null : $teks;
    }

    /**
     * Atribut titik yang diisi dari satu baris file. Titik BARU memakai nilai bawaan untuk kolom yang tidak ada;
     * titik yang SUDAH ada hanya diubah pada kolom yang memang ada di file.
     */
    private function atributDariFile(array $props, ?string $kodeSls, ?int $slsId, bool $baru, int $batchId): array
    {
        $atribut = [
            'sls_id'          => $slsId,
            'id_sls_asal'     => $kodeSls,
            'status'          => 'terdata',
            'import_batch_id' => $batchId,
        ];

        // Kode SLS tidak ketemu -> posisi tidak bisa dicek. Kosongkan nilai lama supaya "di luar SLS"
        // yang basi tidak menahan status perlu_perbaikan.
        if (! $slsId) {
            $atribut['di_luar_sls'] = null;
        }

        $ambil = function (string $kolomFile, string $kolomDb, callable $olah, mixed $bawaan) use (&$atribut, $props, $baru) {
            if (array_key_exists($kolomFile, $props)) {
                $atribut[$kolomDb] = $olah($props[$kolomFile]);
            } elseif ($baru) {
                $atribut[$kolomDb] = $bawaan;
            }
        };

        $ambil('no_bang', 'nomor_bangunan', fn ($v) => $v, null);
        $ambil('kode_bang_value', 'kode_bang_value', fn ($v) => $v, null);
        $ambil('btt', 'flag_btt', fn ($v) => ($v ?? '0') == '1', false);
        $ambil('bku', 'flag_bku', fn ($v) => ($v ?? '0') == '1', false);
        $ambil('flag_repair', 'flag_repair', fn ($v) => ($v ?? '0') == '1', false);
        $ambil('kk', 'jumlah_kk', fn ($v) => (int) $v, 0);

        // Kalau file memuat nama_usaha tapi tanpa kolom "usaha", satu titik = satu usaha.
        if (array_key_exists('usaha', $props)) {
            $atribut['jumlah_usaha'] = (int) $props['usaha'];
        } elseif (array_key_exists('nama_usaha', $props)) {
            $atribut['jumlah_usaha'] = 1;
        } elseif ($baru) {
            $atribut['jumlah_usaha'] = 0;
        }

        return $atribut;
    }

    /** Nama kolom file yang nilainya berbeda dari titik di aplikasi (dipakai untuk memperingatkan titik yang dilewati). */
    private function atributBerbeda(array $props, Bangunan $lama, ?string $kodeSls): array
    {
        $beda = [];

        if (array_key_exists('no_bang', $props) && (string) $props['no_bang'] !== (string) $lama->nomor_bangunan) {
            $beda[] = 'no_bang';
        }
        if (array_key_exists('kk', $props) && (int) $props['kk'] !== (int) $lama->jumlah_kk) {
            $beda[] = 'kk';
        }
        if (array_key_exists('usaha', $props) && (int) $props['usaha'] !== (int) $lama->jumlah_usaha) {
            $beda[] = 'usaha';
        }
        if (array_key_exists('btt', $props) && (($props['btt'] ?? '0') == '1') !== (bool) $lama->flag_btt) {
            $beda[] = 'btt';
        }
        if (array_key_exists('bku', $props) && (($props['bku'] ?? '0') == '1') !== (bool) $lama->flag_bku) {
            $beda[] = 'bku';
        }
        if ($kodeSls !== null && $kodeSls !== (string) $lama->id_sls_asal) {
            $beda[] = 'idsls';
        }

        return $beda;
    }

    /** idsls -> level_5_full_code -> idsubsls (16 digit dipotong jadi 14 digit kode SLS). */
    private function kodeSlsTitik(array $props): ?string
    {
        foreach (['idsls', 'level_5_full_code', 'idsubsls'] as $kolom) {
            $kode = $this->bersihkanKode($props[$kolom] ?? null);

            if ($kode === null) {
                continue;
            }

            if ($kolom === 'idsubsls' && preg_match('/^\d{16}$/', $kode)) {
                $kode = substr($kode, 0, 14);
            }

            return $kode;
        }

        return null;
    }

    /**
     * Koordinat dari geometry titik. Mengembalikan [lat, lng], atau string pesan kalau tidak valid.
     *
     * @return array{0: float, 1: float}|string
     */
    private function bacaKoordinatTitik(?array $geometry): array|string
    {
        if (! $geometry) {
            return 'geometry kosong.';
        }

        if (($geometry['type'] ?? null) !== 'Point') {
            return 'geometry bertipe ' . ($geometry['type'] ?? '-') . ', harus Point.';
        }

        $c = $geometry['coordinates'] ?? null;

        if (! is_array($c) || count($c) < 2 || ! is_numeric($c[0]) || ! is_numeric($c[1])) {
            return 'koordinat kosong/tidak lengkap.';
        }

        $lng = (float) $c[0];
        $lat = (float) $c[1];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return "koordinat di luar rentang ({$lat}, {$lng}); pastikan urutan x=bujur, y=lintang dan CRS EPSG:4326.";
        }

        if ($lat == 0.0 && $lng == 0.0) {
            return 'koordinat 0,0 (kemungkinan data kosong).';
        }

        return [$lat, $lng];
    }

    private function galat(int|string $baris, string $pesan): array
    {
        return ['baris' => $baris, 'pesan' => $pesan, 'tingkat' => 'galat'];
    }

    /** Catatan informasi (bukan error): tidak dihitung di kolom "bermasalah". */
    private function info(int|string $baris, string $pesan): array
    {
        return ['baris' => $baris, 'pesan' => $pesan, 'tingkat' => 'info'];
    }

    private function hitungGalat(array $catatan): int
    {
        return count(array_filter($catatan, fn ($c) => ($c['tingkat'] ?? 'galat') !== 'info'));
    }

    /** RIWAYAT: satu baris ringkasan per proses import (detail baris bermasalah ada di tabel import_batches). */
    private function catatRiwayatImport(Request $request, ImportBatch $batch, string $jenis, int $total, int $valid, int $jumlahCatatan, array $tambahan = []): void
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
            ] + $tambahan,
        );
    }

    /**
     * Tutup batch. Status "failed" kalau tidak ada satu pun baris yang berhasil / dilewati (mis. salah jenis
     * file) atau proses berhenti karena kesalahan. Catatan galat disimpan lebih dulu daripada catatan info.
     */
    private function tutupBatch(ImportBatch $batch, int $total, int $valid, int $dilewati, array $catatan, bool $gagal = false): void
    {
        $jumlahGalat = $this->hitungGalat($catatan);

        // galat dulu, info sesudahnya (sort PHP 8 bersifat stabil)
        usort($catatan, fn ($a, $b) => (($a['tingkat'] ?? 'galat') === 'info') <=> (($b['tingkat'] ?? 'galat') === 'info'));

        $logTersimpan = $catatan;
        if (count($catatan) > self::MAKS_LOG_IMPORT) {
            $logTersimpan = array_slice($catatan, 0, self::MAKS_LOG_IMPORT);
            $logTersimpan[] = $this->info('-', 'Dan ' . (count($catatan) - self::MAKS_LOG_IMPORT) . ' catatan lainnya tidak ditampilkan '
                . '(hanya ' . self::MAKS_LOG_IMPORT . ' catatan pertama yang disimpan).');
        }

        $batch->update([
            'total_data'    => $total,
            'data_valid'    => $valid,
            'data_error'    => $jumlahGalat,   // jumlah asli, bukan jumlah yang disimpan
            'data_dilewati' => $dilewati,
            'status'        => ($gagal || ($total > 0 && ($valid + $dilewati) === 0)) ? 'failed' : 'success',
            'error_log'     => $logTersimpan,
        ]);
    }
}
