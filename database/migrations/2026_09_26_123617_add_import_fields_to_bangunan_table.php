<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            // ID unik dari data mentor (id_assignment), dipakai untuk mencegah
            // data double kalau file yang sama di-import ulang.
            $table->string('id_assignment', 40)->nullable()->unique()->after('id');

            // Kode jenis bangunan dari mentor (kode_bang_value: 1/2/3/dst).
            $table->string('kode_bang_value', 10)->nullable()->after('nama_bangunan');

            // Flag dari data mentor:
            // btt = Bangunan Tempat Tinggal, bku = Bangunan Khusus Usaha
            $table->boolean('flag_btt')->default(true)->after('kode_bang_value');
            $table->boolean('flag_bku')->default(false)->after('flag_btt');
            $table->boolean('flag_repair')->default(false)->after('flag_bku');

            // Ini JUMLAH (bukan nama). Nama sebenarnya (kalau ada) disimpan
            // manual oleh admin di tabel rumah_tangga / usaha secara terpisah.
            $table->unsignedSmallInteger('jumlah_kk')->default(0)->after('flag_repair');
            $table->unsignedSmallInteger('jumlah_usaha')->default(0)->after('jumlah_kk');
        });
    }

    public function down(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropColumn([
                'id_assignment', 'kode_bang_value', 'flag_btt',
                'flag_bku', 'flag_repair', 'jumlah_kk', 'jumlah_usaha',
            ]);
        });
    }
};
