<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            // Siapa yang men-tag titik ini lewat aplikasi (NULL untuk data hasil import).
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();

            // 'import' = dari file GeoJSON/CSV, 'tagging' = ditambahkan lewat peta.
            $table->string('sumber', 20)->default('import');

            $table->index(['sumber', 'dibuat_oleh']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Peran yang dipilih saat mendaftar. Hanya permintaan: peran asli
            // (users.role) baru berubah setelah admin menyetujui.
            $table->string('role_diminta', 20)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_diminta');
        });

        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropIndex(['sumber', 'dibuat_oleh']);
            $table->dropConstrainedForeignId('dibuat_oleh');
            $table->dropColumn('sumber');
        });
    }
};
