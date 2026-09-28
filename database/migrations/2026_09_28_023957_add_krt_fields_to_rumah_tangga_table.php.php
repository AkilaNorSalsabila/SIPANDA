<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan data KELUARGA (KK) dan RUMAH TANGGA (KRT) sesuai Blok V.A:
 *  - kolom (2)  No. Urut Keluarga          -> nomor_urut_keluarga
 *  - kolom (3)  Nama Kepala Keluarga       -> nama_kepala_keluarga (sudah ada)
 *  - kolom (8)  No. Urut Rumah Tangga      -> nomor_urut_rumah_tangga
 *  - kolom (9)  Identifikasi KK/KRT        -> identifikasi_kk_krt
 *  - kolom (10) Nama Kepala Rumah Tangga   -> nama_kepala_rumah_tangga
 *
 * Satu baris tabel ini = satu keluarga. Beberapa keluarga yang punya
 * nomor_urut_rumah_tangga sama berarti satu rumah tangga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rumah_tangga', function (Blueprint $table) {
            if (! Schema::hasColumn('rumah_tangga', 'nomor_urut_keluarga')) {
                $table->unsignedInteger('nomor_urut_keluarga')->nullable();
            }
            if (! Schema::hasColumn('rumah_tangga', 'nomor_urut_rumah_tangga')) {
                $table->unsignedInteger('nomor_urut_rumah_tangga')->nullable();
            }
            if (! Schema::hasColumn('rumah_tangga', 'identifikasi_kk_krt')) {
                $table->unsignedTinyInteger('identifikasi_kk_krt')->nullable();
            }
            if (! Schema::hasColumn('rumah_tangga', 'nama_kepala_rumah_tangga')) {
                $table->string('nama_kepala_rumah_tangga')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rumah_tangga', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_urut_keluarga',
                'nomor_urut_rumah_tangga',
                'identifikasi_kk_krt',
                'nama_kepala_rumah_tangga',
            ]);
        });
    }
};
