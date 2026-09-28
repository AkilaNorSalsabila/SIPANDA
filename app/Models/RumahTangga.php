<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RumahTangga extends Model
{
    use HasFactory;

    protected $table = 'rumah_tangga';

    protected $fillable = [
        'bangunan_id',
        'nomor_kk',
        'nama_kepala_keluarga',
        'jumlah_anggota',
        'alamat',
        // Blok V.A: pemisahan Keluarga (KK) dan Rumah Tangga (KRT)
        'nomor_urut_keluarga',
        'nomor_urut_rumah_tangga',
        'identifikasi_kk_krt',
        'nama_kepala_rumah_tangga',
    ];

    public function bangunan(): BelongsTo
    {
        return $this->belongsTo(Bangunan::class);
    }

    /**
     * Tampilkan nomor KK yang disamarkan
     * Dipakai di blade: {{ $rumahTangga->nomorKkTersamar() }}
     */
    public function nomorKkTersamar(): ?string
    {
        if (! $this->nomor_kk) {
            return null;
        }

        $nomor = $this->nomor_kk;
        $panjang = strlen($nomor);

        if ($panjang <= 6) {
            return str_repeat('*', $panjang);
        }

        return substr($nomor, 0, 4) . str_repeat('*', $panjang - 6) . substr($nomor, -2);
    }
}
