<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pendukung perbaikan alur "perbaiki di QGIS -> impor ulang".
 *
 *  - bangunan.import_batch_awal_id : batch yang MEMBUAT titik. import_batch_id berpindah ke batch terbaru
 *                                    tiap impor ulang, jadi tidak cukup untuk membatalkan satu batch.
 *  - bangunan.alasan_tolak         : catatan admin saat mengembalikan koreksi petugas.
 *  - sls.area_sebelumnya           : polygon sebelum ditimpa impor terbaru (satu langkah undo).
 *  - sls.batch_sebelumnya_id       : batch pemilik polygon sebelumnya.
 *  - import_batches.data_dilewati  : titik dilewati (bukan error) dipisah dari data_error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->foreignId('import_batch_awal_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->text('alasan_tolak')->nullable();
        });

        Schema::table('sls', function (Blueprint $table) {
            $table->foreignId('batch_sebelumnya_id')->nullable()->constrained('import_batches')->nullOnDelete();
        });
        DB::statement('ALTER TABLE sls ADD COLUMN area_sebelumnya geometry(MULTIPOLYGON, 4326) NULL');

        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedInteger('data_dilewati')->default(0)->after('data_error');
        });

        // Data lama: batch yang tercatat dianggap batch pembuatnya (perilaku lama).
        DB::statement("
            UPDATE bangunan SET import_batch_awal_id = import_batch_id
            WHERE import_batch_id IS NOT NULL AND (sumber IS NULL OR sumber <> 'tagging')
        ");
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('data_dilewati');
        });

        DB::statement('ALTER TABLE sls DROP COLUMN IF EXISTS area_sebelumnya');
        Schema::table('sls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_sebelumnya_id');
        });

        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_batch_awal_id');
            $table->dropColumn('alasan_tolak');
        });
    }
};
