<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sls extends Model
{
    use HasFactory;

    protected $table = 'sls';

    protected $fillable = [
        'kode_sls',
        'kode_provinsi',
        'kode_kabupaten',
        'kode_kecamatan',
        'kode_desa',
        'nama_sls',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'luas',
        'periode',
        'area',
    ];

    public function bangunan(): HasMany
    {
        return $this->hasMany(Bangunan::class);
    }

    public function areaAsGeoJson(): ?array
    {
        $row = DB::table('sls')
            ->select(DB::raw('ST_AsGeoJSON(area) as geojson'))
            ->where('id', $this->id)
            ->first();

        return $row?->geojson ? json_decode($row->geojson, true) : null;
    }
    
    public function setAreaFromGeoJson(array $geojson): void
    {
        $json = json_encode($geojson);

        DB::table('sls')
            ->where('id', $this->id)
            ->update([
                'area' => DB::raw("ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON('{$json}'), 4326))"),
            ]);
    }
}
