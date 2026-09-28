<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            // Kode wilayah lengkap dari BPS (kdprov, kdkab, kdkec, kddesa)
            $table->string('kode_provinsi', 5)->nullable()->after('kode_sls');
            $table->string('kode_kabupaten', 5)->nullable()->after('kode_provinsi');
            $table->string('kode_kecamatan', 5)->nullable()->after('kode_kabupaten');
            $table->string('kode_desa', 5)->nullable()->after('kode_kecamatan');

            $table->decimal('luas', 12, 8)->nullable()->after('desa_kelurahan');
            $table->string('periode', 20)->nullable()->after('luas');
        });
    }

    public function down(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->dropColumn([
                'kode_provinsi', 'kode_kabupaten', 'kode_kecamatan',
                'kode_desa', 'luas', 'periode',
            ]);
        });
    }
};
