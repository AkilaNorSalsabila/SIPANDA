<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rumah_tangga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bangunan_id')->constrained('bangunan')->cascadeOnDelete();

            $table->string('nomor_kk', 20)->nullable();
            $table->string('nama_kepala_keluarga')->nullable();
            $table->unsignedSmallInteger('jumlah_anggota')->nullable();
            $table->text('alamat')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rumah_tangga');
    }
};
