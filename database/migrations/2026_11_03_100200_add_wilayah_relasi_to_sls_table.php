<?php

use App\Models\Sls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghubungkan SLS ke master kecamatan & kelurahan.
 *
 * Kolom teks lama (sls.kecamatan, sls.desa_kelurahan, id_kecamatan,
 * id_kelurahan) SENGAJA tidak dihapus karena halaman Peta masih memakainya.
 *
 * SLS yang sudah ada otomatis dikelompokkan ke master baru lewat
 * Sls::sinkronWilayah(), jadi tidak perlu input ulang manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->foreignId('kecamatan_id')->nullable()->after('id_sls')
                ->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('kelurahan_id')->nullable()->after('kecamatan_id')
                ->constrained('kelurahan')->nullOnDelete();
        });

        Sls::query()->orderBy('id')->each(fn (Sls $sls) => $sls->sinkronWilayah());
    }

    public function down(): void
    {
        Schema::table('sls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelurahan_id');
            $table->dropConstrainedForeignId('kecamatan_id');
        });
    }
};
