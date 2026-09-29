<?php

namespace App\Console\Commands;

use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\Sls;
use App\Models\Usaha;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMojokerto extends Command
{
    protected $signature = 'sipanda:import-mojokerto
                            {--kegiatan=MJK-USAHA-2026 : Kode kegiatan tujuan (dibuat otomatis kalau belum ada)}
                            {--nama= : Nama kegiatan kalau harus dibuat baru (default: Data Lokasi Usaha Kota Mojokerto)}';

    protected $description = 'Impor master SLS dan titik lokasi usaha Kota Mojokerto dari CSV ke PostgreSQL';

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
                'jenis'         => 'lainnya',
                'tahun'         => 2026,
                'status'        => 'aktif',
            ]
        );

        $this->info("Kegiatan tujuan: [{$kegiatan->kode_kegiatan}] {$kegiatan->nama_kegiatan}");

        // ---------------------------------------------------------------
        // 1) Master SLS (kode + nama saja, polygon belum ada -> area NULL)
        //    updateOrCreate tidak menyentuh kolom "area", jadi kalau polygon
        //    sudah pernah diimpor, polygon-nya tetap aman.
        // ---------------------------------------------------------------
        $this->info('1/3 Mengimpor master SLS...');
        $jumlahSls = 0;

        DB::transaction(function () use ($fileSls, &$jumlahSls) {
            foreach ($this->bacaCsv($fileSls) as $r) {
                Sls::updateOrCreate(
                    ['kode_sls' => $r['kode_sls']],
                    [
                        // 3576010 -> prov 35, kab 76, kec 010 ; 3576010001 -> desa 001
                        'kode_provinsi'  => '35',
                        'kode_kabupaten' => '76',
                        'kode_kecamatan' => substr($r['kode_kecamatan'], 4, 3),
                        'kode_desa'      => substr($r['kode_kelurahan'], 7, 3),
                        'nama_sls'       => $r['nama_sls'],
                        'kabupaten_kota' => 'KOTA MOJOKERTO',
                        'kecamatan'      => $r['nama_kecamatan'],
                        'desa_kelurahan' => $r['nama_kelurahan'],
                    ]
                );
                $jumlahSls++;
            }
        });

        // ---------------------------------------------------------------
        // 2) Titik usaha. ATURAN: 1 baris = 1 titik = 1 bangunan (+ 1 usaha).
        //    Tidak digabung per (SLS + no_bang), karena di data sumber
        //    nomor itu bukan penanda bangunan yang konsisten.
        // ---------------------------------------------------------------
        $this->info('2/3 Mengimpor titik usaha...');

        $slsMap = Sls::pluck('id', 'kode_sls')->all();
        $namaTidakValid = ['', '0', '-', '--'];
        $stat = ['baru' => 0, 'diperbarui' => 0, 'usaha' => 0, 'nama_tidak_valid' => 0, 'dilewati' => 0, 'tanpa_sls' => 0];

        DB::transaction(function () use ($fileTitik, $kegiatan, $slsMap, $namaTidakValid, &$stat) {
            foreach ($this->bacaCsv($fileTitik) as $r) {
                $lat = (float) $r['latitude'];
                $lng = (float) $r['longitude'];

                if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
                    $stat['dilewati']++;
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
                    ['id_assignment' => 'MJK-' . $r['id_sumber']],
                    [
                        'kegiatan_id'    => $kegiatan->id,
                        'sls_id'         => $slsId,
                        'kode_sls_asal'  => $slsId ? null : $r['kode_sls'],
                        'nomor_bangunan' => $r['no_bang'],
                        // Data sumber tidak menyebut jenis bangunan -> jangan mengarang.
                        'flag_btt'       => false,
                        'flag_bku'       => false,
                        'flag_repair'    => false,
                        'jumlah_kk'      => 0,
                        'jumlah_usaha'   => 1,
                        'latitude'       => $lat,
                        'longitude'      => $lng,
                        'status'         => 'terdata',
                    ]
                );

                $bangunan->wasRecentlyCreated ? $stat['baru']++ : $stat['diperbarui']++;

                if ($namaValid) {
                    Usaha::updateOrCreate(
                        ['bangunan_id' => $bangunan->id, 'nama_usaha' => $nama],
                        []
                    );
                    $stat['usaha']++;
                } else {
                    $stat['nama_tidak_valid']++;
                }
            }
        });

        // ---------------------------------------------------------------
        // 3) Isi kolom geometry sekaligus dalam 1 query (jauh lebih cepat
        //    daripada per baris), lalu cek titik terhadap polygon SLS
        //    (baru berpengaruh kalau polygon SLS-nya sudah ada).
        // ---------------------------------------------------------------
        $this->info('3/3 Mengisi kolom geometry & memeriksa batas SLS...');

        $geometri = DB::affectingStatement(
            "UPDATE bangunan
             SET lokasi = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
             WHERE kegiatan_id = ? AND id_assignment LIKE 'MJK-%'",
            [$kegiatan->id]
        );

        $diperiksa = Bangunan::hitungUlangDiLuarSls();

        $this->newLine();
        $this->table(['Keterangan', 'Jumlah'], [
            ['SLS (master wilayah) diproses', $jumlahSls],
            ['Titik/bangunan baru', $stat['baru']],
            ['Titik/bangunan diperbarui (impor ulang)', $stat['diperbarui']],
            ['Data usaha (dengan nama)', $stat['usaha']],
            ['Titik tanpa nama usaha valid (kosong/0/-)', $stat['nama_tidak_valid']],
            ['Titik dilewati (koordinat tidak valid)', $stat['dilewati']],
            ['Titik yang kode SLS-nya tidak ada', $stat['tanpa_sls']],
            ['Geometry titik terisi', $geometri],
            ['Titik yang sudah bisa dicek ke polygon SLS', $diperiksa],
        ]);

        if ($diperiksa === 0) {
            $this->warn('Polygon SLS belum ada, jadi pemeriksaan "di luar batas SLS" belum berjalan.');
            $this->warn('Setelah batas SLS Kota Mojokerto diimpor lewat halaman Import, pemeriksaan otomatis dijalankan.');
        }

        return self::SUCCESS;
    }

    /**
     * Baca CSV baris demi baris, hasilnya array asosiatif (header => nilai).
     * Parameter escape ditulis eksplisit supaya tidak muncul peringatan
     * deprecated di PHP 8.4.
     */
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
