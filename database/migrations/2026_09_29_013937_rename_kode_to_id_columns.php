<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan penamaan kolom kode wilayah supaya konsisten dengan sumber
 * data (id_sumber, id_assignment, dst pakai awalan "id_", bukan "kode_"):
 *   sls.kode_sls        -> sls.id_sls
 *   sls.kode_provinsi    -> sls.id_provinsi
 *   sls.kode_kabupaten   -> sls.id_kabupaten
 *   sls.kode_kecamatan   -> sls.id_kecamatan
 *   sls.kode_desa        -> sls.id_kelurahan
 *   bangunan.kode_sls_asal -> bangunan.id_sls_asal
 *
 * kode_bang_value TIDAK diubah: itu bukan kode wilayah, tapi nilai
 * kategori jenis bangunan (isinya 1/2/3), jadi bukan bagian dari
 * penyeragaman ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->renameColumn('kode_sls', 'id_sls');
            $table->renameColumn('kode_provinsi', 'id_provinsi');
            $table->renameColumn('kode_kabupaten', 'id_kabupaten');
            $table->renameColumn('kode_kecamatan', 'id_kecamatan');
            $table->renameColumn('kode_desa', 'id_kelurahan');
        });

        Schema::table('bangunan', function (Blueprint $table) {
            $table->renameColumn('kode_sls_asal', 'id_sls_asal');
        });
    }

    public function down(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->renameColumn('id_sls', 'kode_sls');
            $table->renameColumn('id_provinsi', 'kode_provinsi');
            $table->renameColumn('id_kabupaten', 'kode_kabupaten');
            $table->renameColumn('id_kecamatan', 'kode_kecamatan');
            $table->renameColumn('id_kelurahan', 'kode_desa');
        });

        Schema::table('bangunan', function (Blueprint $table) {
            $table->renameColumn('id_sls_asal', 'kode_sls_asal');
        });
    }
};
