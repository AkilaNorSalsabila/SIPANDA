<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master kelurahan/desa. Selalu berada di bawah satu kecamatan.
 * id_kelurahan = 3 digit terakhir kode desa (sama seperti kolom
 * sls.id_kelurahan), jadi hanya unik di dalam satu kecamatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelurahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->string('id_kelurahan', 3);
            $table->string('nama_kelurahan');
            $table->timestamps();

            $table->unique(['kecamatan_id', 'id_kelurahan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelurahan');
    }
};
