<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master kecamatan. Kode mengikuti pola BPS yang sama dengan id_sls:
 *   id_sls (14 digit) = prov(2) + kab(2) + kec(3) + desa(3) + sls(4)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id();
            $table->string('id_provinsi', 2);
            $table->string('id_kabupaten', 2);
            $table->string('id_kecamatan', 3);
            $table->string('nama_kecamatan');
            $table->string('kabupaten_kota')->default('KOTA MOJOKERTO');
            $table->timestamps();

            $table->unique(['id_provinsi', 'id_kabupaten', 'id_kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecamatan');
    }
};
