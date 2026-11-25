<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status perbaikan SLS.
 *
 * Status "perlu perbaikan" utamanya dihitung OTOMATIS dari data
 * (ada titik di luar SLS, atau SLS belum punya polygon). Dua kolom ini
 * untuk tambahan MANUAL dari admin: tandai SLS perlu perbaikan walau
 * tidak terdeteksi otomatis, dan tulis solusi/catatan perbaikannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->boolean('perlu_perbaikan')->default(false);
            $table->text('solusi_perbaikan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->dropColumn(['perlu_perbaikan', 'solusi_perbaikan']);
        });
    }
};
