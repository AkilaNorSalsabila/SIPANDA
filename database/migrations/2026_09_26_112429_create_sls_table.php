<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sls', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sls', 20)->unique();
            $table->string('nama_sls');
            $table->string('kabupaten_kota');
            $table->string('kecamatan');
            $table->string('desa_kelurahan');
            $table->timestamps();

            $table->index(['kabupaten_kota', 'kecamatan', 'desa_kelurahan']);
        });

        // Kolom geometry ditambah lewat SQL mentah, bukan method Blueprint,
        // supaya tidak bergantung pada dukungan spatial-type di versi Laravel.
        DB::statement('ALTER TABLE sls ADD COLUMN area geometry(POLYGON, 4326) NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('sls');
    }
};