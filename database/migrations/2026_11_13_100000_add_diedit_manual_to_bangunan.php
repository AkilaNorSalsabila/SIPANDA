<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            // Terisi saat admin mengedit titik hasil import lewat peta.
            // Import ulang melewati titik ini supaya hasil koreksi tidak tertimpa.
            $table->timestamp('diedit_manual_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropColumn('diedit_manual_at');
        });
    }
};
