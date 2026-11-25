<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SLS menjadi milik satu kegiatan (snapshot per kegiatan).
 *
 * Batas SLS bisa berubah dari satu survei ke survei berikutnya (pemekaran /
 * perubahan administrasi, penyesuaian kerangka sampel). Dengan satu baris
 * `sls` per (kegiatan, id_sls), polygon kegiatan lama tidak ikut berubah saat
 * kegiatan baru memakai batas yang berbeda.
 *
 * Data yang sudah ada: setiap kegiatan yang ada saat ini mendapat salinan
 * SLS (termasuk polygon) yang sekarang tersimpan. Titik (bangunan) dan
 * penugasan diarahkan ke salinan milik kegiatannya sendiri, lalu baris SLS
 * lama (tanpa kegiatan) dihapus.
 */
return new class extends Migration
{
    /** Kolom SLS yang disalin ke tiap kegiatan. */
    private const KOLOM = [
        'id_sls', 'kecamatan_id', 'kelurahan_id',
        'id_provinsi', 'id_kabupaten', 'id_kecamatan', 'id_kelurahan',
        'nama_sls', 'kabupaten_kota', 'kecamatan', 'desa_kelurahan',
        'luas', 'periode', 'area', 'perlu_perbaikan', 'solusi_perbaikan',
    ];

    public function up(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->nullable()->after('id')
                ->constrained('kegiatan')->cascadeOnDelete();
        });

        // id_sls tidak lagi unik global (kode yang sama ada di tiap kegiatan), jadi unique lama
        // harus dilepas SEBELUM SLS disalin; unique baru per kegiatan dipasang setelah salinan selesai.
        DB::statement('ALTER TABLE sls DROP CONSTRAINT IF EXISTS sls_kode_sls_unique');
        DB::statement('ALTER TABLE sls DROP CONSTRAINT IF EXISTS sls_id_sls_unique');
        DB::statement('DROP INDEX IF EXISTS sls_kode_sls_unique');
        DB::statement('DROP INDEX IF EXISTS sls_id_sls_unique');

        DB::transaction(function () {
            $kegiatanIds = DB::table('kegiatan')->orderBy('id')->pluck('id');
            $adaMaster   = DB::table('sls')->whereNull('kegiatan_id')->exists();

            if ($kegiatanIds->isEmpty() || ! $adaMaster) {
                return;
            }

            $kolom   = implode(', ', self::KOLOM);
            $kolomS  = implode(', ', array_map(fn ($k) => "s.$k", self::KOLOM));

            foreach ($kegiatanIds as $kid) {
                // 1. Salin semua SLS master ke kegiatan ini. import_batch_id hanya dipertahankan
                //    kalau batch itu memang milik kegiatan ini.
                DB::insert("
                    INSERT INTO sls ($kolom, kegiatan_id, import_batch_id, created_at, updated_at)
                    SELECT $kolomS, ?, CASE WHEN ib.kegiatan_id = ? THEN s.import_batch_id END, NOW(), NOW()
                    FROM sls s
                    LEFT JOIN import_batches ib ON ib.id = s.import_batch_id
                    WHERE s.kegiatan_id IS NULL
                ", [$kid, $kid]);

                // 2. Arahkan titik & penugasan kegiatan ini ke salinannya.
                foreach (['bangunan', 'penugasan_sls', 'penugasan'] as $tabel) {
                    if (! Schema::hasTable($tabel)) {
                        continue;
                    }

                    DB::update("
                        UPDATE $tabel t
                        SET sls_id = baru.id
                        FROM sls lama
                        JOIN sls baru ON baru.id_sls = lama.id_sls AND baru.kegiatan_id = ?
                        WHERE t.kegiatan_id = ?
                          AND t.sls_id = lama.id
                          AND lama.kegiatan_id IS NULL
                    ", [$kid, $kid]);
                }
            }

            // 3. Pastikan tidak ada yang masih menunjuk ke baris lama sebelum dihapus
            //    (FK cascade akan ikut menghapus data yang masih menunjuk ke sana).
            foreach (['bangunan', 'penugasan_sls', 'penugasan'] as $tabel) {
                if (! Schema::hasTable($tabel)) {
                    continue;
                }

                $sisa = DB::table($tabel)
                    ->whereIn('sls_id', DB::table('sls')->whereNull('kegiatan_id')->select('id'))
                    ->count();

                if ($sisa > 0) {
                    throw new RuntimeException("Migrasi dibatalkan: {$sisa} baris di tabel {$tabel} masih menunjuk ke SLS lama.");
                }
            }

            DB::table('sls')->whereNull('kegiatan_id')->delete();
        });

        Schema::table('sls', function (Blueprint $table) {
            $table->unique(['kegiatan_id', 'id_sls'], 'sls_kegiatan_id_sls_unique');
        });

        if (! DB::table('sls')->whereNull('kegiatan_id')->exists()) {
            DB::statement('ALTER TABLE sls ALTER COLUMN kegiatan_id SET NOT NULL');
        }
    }

    /**
     * Tidak benar-benar bisa dikembalikan: setelah dipisah per kegiatan, id_sls yang sama
     * bisa ada di banyak baris. Yang dikembalikan hanya struktur kolomnya.
     */
    public function down(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->dropUnique('sls_kegiatan_id_sls_unique');
            $table->dropConstrainedForeignId('kegiatan_id');
        });
    }
};
