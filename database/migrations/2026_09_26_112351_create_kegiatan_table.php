<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kegiatan', 30)->unique();     // mis: SE2026, SUSENAS-MAR26
            $table->string('nama_kegiatan');                   // mis: Sensus Ekonomi 2026
            $table->enum('jenis', ['se', 'susenas', 'sakernas', 'sensus', 'podes', 'lainnya'])
                  ->default('lainnya');
            $table->year('tahun');
            $table->string('periode', 50)->nullable();         // mis: Maret 2026
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
