<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * id_assignment unik PER KEGIATAN, bukan unik global.
 *
 * Sebelumnya id_assignment unik di seluruh tabel, sehingga mengimpor titik dengan
 * id_assignment yang sama di kegiatan baru akan memindahkan/menimpa titik kegiatan lama.
 * Sekarang kunci uniknya (kegiatan_id, id_assignment): import ulang di kegiatan yang sama
 * tetap memperbarui, di kegiatan lain membuat titik baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bangunan DROP CONSTRAINT IF EXISTS bangunan_id_assignment_unique');
        DB::statement('DROP INDEX IF EXISTS bangunan_id_assignment_unique');

        Schema::table('bangunan', function (Blueprint $table) {
            $table->unique(['kegiatan_id', 'id_assignment'], 'bangunan_kegiatan_id_assignment_unique');
        });
    }

    /** Hanya aman kalau tidak ada id_assignment yang sama di dua kegiatan. */
    public function down(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropUnique('bangunan_kegiatan_id_assignment_unique');
            $table->unique('id_assignment');
        });
    }
};
