<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat batch import mana yang menulis polygon SLS / titik bangunan,
 * supaya satu import bisa dibatalkan (dihapus) tanpa menyentuh data lain.
 * Import lama (sebelum migrasi ini) tidak terlacak: hanya catatannya yang bisa dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sls', 'bangunan'] as $tabel) {
            if (! Schema::hasColumn($tabel, 'import_batch_id')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->foreignId('import_batch_id')->nullable()
                        ->constrained('import_batches')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sls', 'bangunan'] as $tabel) {
            if (Schema::hasColumn($tabel, 'import_batch_id')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('import_batch_id');
                });
            }
        }
    }
};
