<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenugasanSls extends Model
{
    protected $table = 'penugasan_sls';

    protected $fillable = [
        'user_id',
        'kegiatan_id',
        'sls_id',
        'ditugaskan_oleh',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function sls(): BelongsTo
    {
        return $this->belongsTo(Sls::class);
    }
}
