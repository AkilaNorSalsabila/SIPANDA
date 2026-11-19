<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelurahan extends Model
{
    protected $table = 'kelurahan';

    protected $fillable = [
        'kecamatan_id',
        'id_kelurahan',
        'nama_kelurahan',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function sls(): HasMany
    {
        return $this->hasMany(Sls::class);
    }

    /** Kode lengkap 10 digit, mis. 3576010001. */
    public function getKodeLengkapAttribute(): string
    {
        return $this->kecamatan->kode_lengkap . $this->id_kelurahan;
    }
}
