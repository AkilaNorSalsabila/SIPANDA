<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status perbaikan per TITIK.
 *
 *   normal            : tidak ada masalah
 *   perlu_perbaikan   : titik di luar batas SLS, atau ditandai flag_repair (diisi otomatis)
 *   sudah_diperbaiki  : sudah dikoreksi petugas lewat peta, menunggu verifikasi admin
 *   terverifikasi     : koreksi sudah diperiksa admin (atau dikoreksi langsung oleh admin)
 *
 * Titik yang sudah dikoreksi tidak ditimpa import ulang. Koordinat awal dari file
 * disimpan di latitude_asal / longitude_asal supaya bisa dibandingkan atau dikembalikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->string('status_perbaikan', 20)->default('normal');

            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikoreksi_at')->nullable();

            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();

            // Koordinat awal dari file import (terisi saat titik import pertama kali digeser).
            $table->decimal('latitude_asal', 10, 7)->nullable();
            $table->decimal('longitude_asal', 10, 7)->nullable();

            $table->index(['kegiatan_id', 'status_perbaikan']);
        });

        // Titik yang sebelumnya sudah diedit manual oleh admin dianggap sudah diperbaiki & terverifikasi.
        DB::statement("
            UPDATE bangunan
            SET status_perbaikan = 'terverifikasi',
                dikoreksi_at = diedit_manual_at,
                diverifikasi_at = diedit_manual_at
            WHERE diedit_manual_at IS NOT NULL
        ");

        // Sisanya: perlu_perbaikan kalau di luar batas SLS atau ditandai flag_repair.
        DB::statement("
            UPDATE bangunan
            SET status_perbaikan = 'perlu_perbaikan'
            WHERE status_perbaikan = 'normal'
              AND (di_luar_sls IS TRUE OR flag_repair IS TRUE)
        ");
    }

    public function down(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropIndex(['kegiatan_id', 'status_perbaikan']);
            $table->dropConstrainedForeignId('dikoreksi_oleh');
            $table->dropConstrainedForeignId('diverifikasi_oleh');
            $table->dropColumn([
                'status_perbaikan', 'dikoreksi_at', 'diverifikasi_at', 'latitude_asal', 'longitude_asal',
            ]);
        });
    }
};
