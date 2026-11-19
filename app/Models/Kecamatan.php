<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    protected $fillable = [
        'id_provinsi',
        'id_kabupaten',
        'id_kecamatan',
        'nama_kecamatan',
        'kabupaten_kota',
    ];

    public function kelurahan(): HasMany
    {
        return $this->hasMany(Kelurahan::class);
    }

    public function sls(): HasMany
    {
        return $this->hasMany(Sls::class);
    }

    /** Kode lengkap 7 digit, mis. 3576010. */
    public function getKodeLengkapAttribute(): string
    {
        return $this->id_provinsi . $this->id_kabupaten . $this->id_kecamatan;
    }
}
