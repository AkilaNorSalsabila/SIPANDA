<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur petugas lapangan:
 *  - tabel penugasan: admin menugaskan SLS ke petugas per kegiatan & tanggal
 *  - kolom jejak tagging di bangunan (siapa, kapan, akurasi GPS, sumber)
 *  - indeks spasial GiST supaya cek "di dalam SLS" dan query peta tetap cepat
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sls_id')->constrained('sls')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kegiatan_id', 'user_id', 'sls_id', 'tanggal'], 'penugasan_unik');
            $table->index(['user_id', 'tanggal']);
        });

        Schema::table('bangunan', function (Blueprint $table) {
            $table->foreignId('ditandai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditandai_at')->nullable();
            $table->decimal('akurasi_gps', 8, 2)->nullable();
            $table->string('sumber_tag', 10)->nullable(); // gps | peta
            $table->text('catatan')->nullable();

            $table->index(['ditandai_oleh', 'ditandai_at']);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS sls_area_gist ON sls USING GIST (area)');
        DB::statement('CREATE INDEX IF NOT EXISTS bangunan_lokasi_gist ON bangunan USING GIST (lokasi)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS sls_area_gist');
        DB::statement('DROP INDEX IF EXISTS bangunan_lokasi_gist');

        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropIndex(['ditandai_oleh', 'ditandai_at']);
            $table->dropConstrainedForeignId('ditandai_oleh');
            $table->dropColumn(['ditandai_at', 'akurasi_gps', 'sumber_tag', 'catatan']);
        });

        Schema::dropIfExists('penugasan');
    }
};
