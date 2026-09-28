<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usaha extends Model
{
    use HasFactory;

    protected $table = 'usaha';

    protected $fillable = [
        'bangunan_id',
        'nama_usaha',
        'jenis_usaha',
        'alamat',
    ];

    public function bangunan(): BelongsTo
    {
        return $this->belongsTo(Bangunan::class);
    }
}
