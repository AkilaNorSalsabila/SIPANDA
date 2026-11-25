<?php

namespace App\Console\Commands;

use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\Sls;
use App\Models\Usaha;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor awal data Kota Mojokerto dari CSV (database/data/mojokerto) ke skema SIPANDA saat ini:
 *  - SLS dibuat DI DALAM kegiatan tujuan (SLS adalah snapshot per kegiatan), tanpa polygon;
 *    polygon diimpor kemudian lewat halaman Import > Batas SLS.
 *  - Titik memakai kunci (kegiatan_id, id_assignment = "MJK-{id_sumber}"), jadi command aman dijalankan ulang.
 *  - Titik yang sudah dikoreksi di aplikasi tidak ditimpa.
 */
class ImportMojokerto extends Command
{
    protected $signature = 'sipanda:import-mojokerto
                            {--kegiatan=MJK-USAHA-2026 : Kode kegiatan tujuan (dibuat otomatis kalau belum ada)}
                            {--nama= : Nama kegiatan kalau harus dibuat baru (default: Data Lokasi Usaha Kota Mojokerto)}
                            {--periode=2026 : Periode kegiatan kalau harus dibuat baru}';

    protected $description = 'Impor SLS dan titik lokasi usaha Kota Mojokerto dari CSV ke kegiatan tujuan';

    public function handle(): int
    {
        $dir = database_path('data/mojokerto');
        $fileSls = $dir . '/sls.csv';
        $fileTitik = $dir . '/titik_usaha.csv';

        foreach ([$fileSls, $fileTitik] as $file) {
            if (! is_file($file)) {
                $this->error("File tidak ditemukan: {$file}");
                return self::FAILURE;
            }
        }

        $kegiatan = Kegiatan::firstOrCreate(
            ['kode_kegiatan' => $this->option('kegiatan')],
            [
                'nama_kegiatan' => $this->option('nama') ?: 'Data Lokasi Usaha Kota Mojokerto',
                'periode'       => (string) $this->option('periode'),
            ]
        );

        $this->info("Kegiatan tujuan: [{$kegiatan->kode_kegiatan}] {$kegiatan->nama_kegiatan}");

        // ---------------------------------------------------------------
        $this->info('1/3 Mengimpor SLS...');
        $jumlahSls = 0;

        DB::transaction(function () use ($fileSls, $kegiatan, &$jumlahSls) {
            foreach ($this->bacaCsv($fileSls) as $r) {
                $sls = Sls::updateOrCreate(
                    ['kegiatan_id' => $kegiatan->id, 'id_sls' => $r['kode_sls']],
                    [
                        // 3576010 -> prov 35, kab 76, kec 010 ; 3576010001 -> desa 001
                        'id_provinsi'    => '35',
                        'id_kabupaten'   => '76',
                        'id_kecamatan'   => substr($r['kode_kecamatan'], 4, 3),
                        'id_kelurahan'   => substr($r['kode_kelurahan'], 7, 3),
                        'nama_sls'       => $r['nama_sls'],
                        'kabupaten_kota' => 'KOTA MOJOKERTO',
                        'kecamatan'      => $r['nama_kecamatan'],
                        'desa_kelurahan' => $r['nama_kelurahan'],
                    ]
                );
                $sls->sinkronWilayah();   // masuk ke master kecamatan / kelurahan
                $jumlahSls++;
            }
        });

        // ---------------------------------------------------------------
        $this->info('2/3 Mengimpor titik usaha...');

        $slsMap = Sls::where('kegiatan_id', $kegiatan->id)->pluck('id', 'id_sls')->all();
        $namaTidakValid = ['', '0', '-', '--'];
        $stat = ['baru' => 0, 'diperbarui' => 0, 'usaha' => 0, 'nama_tidak_valid' => 0, 'dilewati' => 0, 'dilindungi' => 0, 'tanpa_sls' => 0];

        $sudahAda = Bangunan::where('kegiatan_id', $kegiatan->id)
            ->where('id_assignment', 'like', 'MJK-%')
            ->get(['id', 'id_assignment', 'status_perbaikan', 'diedit_manual_at'])
            ->keyBy('id_assignment');

        DB::transaction(function () use ($fileTitik, $kegiatan, $slsMap, $namaTidakValid, $sudahAda, &$stat) {
            foreach ($this->bacaCsv($fileTitik) as $r) {
                $lat = (float) $r['latitude'];
                $lng = (float) $r['longitude'];

                if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
                    $stat['dilewati']++;
                    continue;
                }

                $kunci = 'MJK-' . $r['id_sumber'];

                // Titik yang sudah dikoreksi di aplikasi tidak ditimpa.
                if ($sudahAda->get($kunci)?->sudahDikoreksi()) {
                    $stat['dilindungi']++;
                    continue;
                }

                $slsId = $slsMap[$r['kode_sls']] ?? null;
                if (! $slsId) {
                    $stat['tanpa_sls']++;
                }

                $nama = trim($r['nama_usaha']);
                $namaValid = ! in_array(mb_strtolower($nama), $namaTidakValid, true);

                $bangunan = Bangunan::updateOrCreate(
                    // kunci = id baris di database sumber -> aman dijalankan ulang
                    ['kegiatan_id' => $kegiatan->id, 'id_assignment' => $kunci],
                    [
                        'sls_id'         => $slsId,
                        'id_sls_asal'    => $r['kode_sls'],
                        'nomor_bangunan' => $r['no_bang'],
                        // Data sumber tidak menyebut jenis bangunan -> jangan mengarang.
                        'flag_btt'       => false,
                        'flag_bku'       => false,
                        'flag_repair'    => false,
                        'jumlah_kk'      => 0,
                        'jumlah_usaha'   => 1,   // satu titik = satu usaha (sama seperti sebelumnya)
                        'latitude'       => $lat,
                        'longitude'      => $lng,
                        'status'         => 'terdata',
                        ...($slsId ? [] : ['di_luar_sls' => null]),
                    ]
                );

                $bangunan->wasRecentlyCreated ? $stat['baru']++ : $stat['diperbarui']++;

                // Nama usaha diganti (bukan ditambah) supaya nama yang diperbaiki di sumber tidak menumpuk.
                $bangunan->usaha()->delete();
                if ($namaValid) {
                    Usaha::create(['bangunan_id' => $bangunan->id, 'nama_usaha' => $nama]);
                    $stat['usaha']++;
                } else {
                    $stat['nama_tidak_valid']++;
                }
            }
        });

        // ---------------------------------------------------------------
        // 3) Isi kolom geometry sekaligus dalam 1 query (jauh lebih cepat daripada per baris), lalu cek
        //    titik terhadap polygon SLS (baru berpengaruh kalau polygon SLS-nya sudah ada).
        // ---------------------------------------------------------------
        $this->info('3/3 Mengisi kolom geometry & memeriksa batas SLS...');

        $geometri = DB::affectingStatement(
            "UPDATE bangunan
             SET lokasi = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
             WHERE kegiatan_id = ? AND id_assignment LIKE 'MJK-%' AND latitude IS NOT NULL AND longitude IS NOT NULL",
            [$kegiatan->id]
        );

        $diperiksa = Bangunan::hitungUlangDiLuarSls($kegiatan->id);
        Bangunan::sinkronStatusPerbaikanSemua($kegiatan->id);

        $this->newLine();
        $this->table(['Keterangan', 'Jumlah'], [
            ['SLS diproses (kegiatan ini)', $jumlahSls],
            ['Titik/bangunan baru', $stat['baru']],
            ['Titik/bangunan diperbarui (impor ulang)', $stat['diperbarui']],
            ['Titik dilewati: sudah dikoreksi di aplikasi', $stat['dilindungi']],
            ['Data usaha (dengan nama)', $stat['usaha']],
            ['Titik tanpa nama usaha valid (kosong/0/-)', $stat['nama_tidak_valid']],
            ['Titik dilewati (koordinat tidak valid)', $stat['dilewati']],
            ['Titik yang kode SLS-nya tidak ada', $stat['tanpa_sls']],
            ['Geometry titik terisi', $geometri],
            ['Titik yang sudah bisa dicek ke polygon SLS', $diperiksa],
        ]);

        if ($diperiksa === 0) {
            $this->warn('Polygon SLS belum ada, jadi pemeriksaan "di luar batas SLS" belum berjalan.');
            $this->warn('Impor batas SLS Kota Mojokerto lewat halaman Import (pilih kegiatan ini); pemeriksaan otomatis dijalankan setelahnya.');
        }

        return self::SUCCESS;
    }

    /** Baca CSV baris demi baris sebagai array asosiatif (baris dengan jumlah kolom berbeda dilewati). */
    private function bacaCsv(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, ',', '"', '\\');

        while (($baris = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($baris === [null] || count($baris) !== count($header)) {
                continue;
            }

            yield array_combine($header, $baris);
        }

        fclose($handle);
    }
}
