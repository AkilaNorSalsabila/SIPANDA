<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menangani 2 kasus:
     * 1) Kode SLS di titik TIDAK ketemu di tabel sls
     *    -> sls_id dikosongkan (null), kode aslinya disimpan di kode_sls_asal
     * 2) Kode SLS ketemu, tapi koordinat titik jatuh DI LUAR polygon SLS itu
     *    -> sls_id tetap terisi, tapi di_luar_sls = true (untuk fitur QC)
     */
    public function up(): void
    {
        // sls_id awalnya NOT NULL (dari migration create_bangunan_table),
        // sekarang dibolehkan kosong untuk kasus SLS tidak ditemukan.
        DB::statement('ALTER TABLE bangunan ALTER COLUMN sls_id DROP NOT NULL');

        // Ganti aturan hapus: kalau SLS-nya dihapus, bangunan JANGAN ikut
        // terhapus (cascade) — cukup sls_id-nya dikosongkan (set null).
        DB::statement('ALTER TABLE bangunan DROP CONSTRAINT IF EXISTS bangunan_sls_id_foreign');
        DB::statement('ALTER TABLE bangunan ADD CONSTRAINT bangunan_sls_id_foreign
            FOREIGN KEY (sls_id) REFERENCES sls(id) ON DELETE SET NULL');

        Schema::table('bangunan', function (Blueprint $table) {
            $table->string('kode_sls_asal', 20)->nullable()->after('sls_id');
            $table->boolean('di_luar_sls')->default(false)->after('kode_sls_asal');
        });
    }

    public function down(): void
    {
        Schema::table('bangunan', function (Blueprint $table) {
            $table->dropColumn(['kode_sls_asal', 'di_luar_sls']);
        });

        DB::statement('ALTER TABLE bangunan DROP CONSTRAINT IF EXISTS bangunan_sls_id_foreign');
        DB::statement('ALTER TABLE bangunan ADD CONSTRAINT bangunan_sls_id_foreign
            FOREIGN KEY (sls_id) REFERENCES sls(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE bangunan ALTER COLUMN sls_id SET NOT NULL');
    }
};
