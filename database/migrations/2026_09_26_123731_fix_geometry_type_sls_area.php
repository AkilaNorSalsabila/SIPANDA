<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data mentor (batas_sls.geojson) geometry-nya MultiPolygon, sedangkan
     * kolom "area" yang kita buat sebelumnya bertipe POLYGON biasa. Migration
     * ini mengubah tipenya jadi MULTIPOLYGON supaya bisa menampung keduanya
     * (Polygon tunggal otomatis dianggap valid juga oleh PostGIS).
     */
    public function up(): void
    {
        // USING ST_Multi(...) mengubah data lama (kalau ada) dari Polygon
        // jadi MultiPolygon secara otomatis, jadi aman walau tabel sudah berisi data.
        DB::statement(
            'ALTER TABLE sls ALTER COLUMN area TYPE geometry(MULTIPOLYGON, 4326) USING ST_Multi(area)::geometry(MULTIPOLYGON, 4326)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE sls ALTER COLUMN area TYPE geometry(POLYGON, 4326) USING ST_GeometryN(area, 1)::geometry(POLYGON, 4326)'
        );
    }
};
