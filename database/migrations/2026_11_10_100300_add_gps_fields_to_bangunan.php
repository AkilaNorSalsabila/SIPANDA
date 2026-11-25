<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak pengambilan titik: akurasi GPS (meter), metode (gps | peta) dan waktu.
 * Memakai pengecekan hasColumn karena sebagian kolom sudah dibuat migrasi sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            if (! Schema::hasColumn('bangunan', 'akurasi_gps')) {
                $table->decimal('akurasi_gps', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('bangunan', 'sumber_tag')) {
                $table->string('sumber_tag', 10)->nullable();   // gps | peta
            }
            if (! Schema::hasColumn('bangunan', 'ditandai_at')) {
                $table->timestamp('ditandai_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja kosong: kolom bisa berasal dari migrasi lain.
    }
};
