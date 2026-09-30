<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn(['deskripsi', 'jenis', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable();
            $table->string('jenis')->nullable();
            $table->integer('tahun')->nullable();
        });
    }
};