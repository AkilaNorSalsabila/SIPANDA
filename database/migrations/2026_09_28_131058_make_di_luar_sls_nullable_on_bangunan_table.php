<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * di_luar_sls sekarang punya 3 keadaan:
     *   NULL  = belum bisa dicek (SLS-nya belum punya polygon batas)
     *   FALSE = titik berada di dalam polygon SLS
     *   TRUE  = titik berada di luar polygon SLS
     *
     * Sebelumnya kolom ini NOT NULL default FALSE, sehingga titik yang SLS-nya
     * belum punya batas akan salah tampil sebagai "normal / di dalam SLS".
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE bangunan ALTER COLUMN di_luar_sls DROP NOT NULL');
        DB::statement('ALTER TABLE bangunan ALTER COLUMN di_luar_sls DROP DEFAULT');

        // Titik yang statusnya memang belum bisa dipastikan -> NULL
        DB::statement('
            UPDATE bangunan
            SET di_luar_sls = NULL
            WHERE sls_id IS NULL
               OR sls_id IN (SELECT id FROM sls WHERE area IS NULL)
        ');
    }

    public function down(): void
    {
        DB::statement('UPDATE bangunan SET di_luar_sls = FALSE WHERE di_luar_sls IS NULL');
        DB::statement('ALTER TABLE bangunan ALTER COLUMN di_luar_sls SET DEFAULT FALSE');
        DB::statement('ALTER TABLE bangunan ALTER COLUMN di_luar_sls SET NOT NULL');
    }
};
