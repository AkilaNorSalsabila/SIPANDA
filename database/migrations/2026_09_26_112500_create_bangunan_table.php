<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bangunan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('sls_id')->constrained('sls')->cascadeOnDelete();

            $table->string('nomor_bangunan', 20)->nullable();
            $table->string('nama_bangunan')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', ['terdata', 'belum_terdata', 'bermasalah'])
                  ->default('belum_terdata');

            $table->timestamps();

            $table->index(['kegiatan_id', 'sls_id']);
        });

        DB::statement('ALTER TABLE bangunan ADD COLUMN lokasi geometry(POINT, 4326) NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('bangunan');
    }
};