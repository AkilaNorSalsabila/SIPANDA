<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan mitra (akun viewer) ke SLS.
 *
 * Satu baris = "akun X boleh melakukan tagging di SLS Y untuk kegiatan Z".
 * Admin mengatur ini dari Manajemen Akun; di akun viewer, SLS yang ditugaskan
 * langsung muncul di halaman Peta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_sls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('sls_id')->constrained('sls')->cascadeOnDelete();
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'kegiatan_id', 'sls_id']);
            $table->index(['kegiatan_id', 'sls_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_sls');
    }
};
