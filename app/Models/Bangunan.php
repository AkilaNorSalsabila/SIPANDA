<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Bangunan extends Model
{
    use HasFactory;

    protected $table = 'bangunan';

    protected $fillable = [
        'kegiatan_id',
        'sls_id',
        'kode_sls_asal',
        'di_luar_sls',
        'id_assignment',
        'nomor_bangunan',
        'nama_bangunan',
        'kode_bang_value',
        'flag_btt',
        'flag_bku',
        'flag_repair',
        'jumlah_kk',
        'jumlah_usaha',
        'lokasi',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'flag_btt'    => 'boolean',
            'flag_bku'    => 'boolean',
            'flag_repair' => 'boolean',
            'di_luar_sls' => 'boolean',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function sls(): BelongsTo
    {
        return $this->belongsTo(Sls::class);
    }

    public function rumahTangga(): HasMany
    {
        return $this->hasMany(RumahTangga::class);
    }

    public function usaha(): HasMany
    {
        return $this->hasMany(Usaha::class);
    }

    public function scopeTanpaSls(Builder $query): Builder
    {
        return $query->whereNull('sls_id');
    }

    public function scopeDiLuarSls(Builder $query): Builder
    {
        return $query->whereNotNull('sls_id')->where('di_luar_sls', true);
    }

    public function kelengkapanRumahTangga(): string
    {
        return $this->rumahTangga()->count() . ' / ' . $this->jumlah_kk;
    }

    public function kelengkapanUsaha(): string
    {
        return $this->usaha()->count() . ' / ' . $this->jumlah_usaha;
    }

    public function setKoordinat(float $lat, float $lng): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->save();

        DB::table('bangunan')
            ->where('id', $this->id)
            ->update([
                'lokasi' => DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
            ]);
    }

    public function isInsideSls(): ?bool
    {
        if (! $this->sls_id) {
            return null;
        }

        $row = DB::table('bangunan as b')
            ->join('sls as s', 's.id', '=', 'b.sls_id')
            ->select(DB::raw('ST_Contains(s.area, b.lokasi) as inside'))
            ->where('b.id', $this->id)
            ->first();

        return (bool) ($row->inside ?? false);
    }

    /**
     * Jarak (dalam meter) dari titik GPS bangunan ke batas polygon SLS
     * yang tercatat. Dibungkus ::geography supaya hasilnya dalam meter
     * (bukan derajat koordinat). 0 kalau titiknya memang di dalam polygon.
     * Null kalau bangunan ini tidak punya sls_id sama sekali.
     */
    public function jarakKeSlsMeter(): ?float
    {
        if (! $this->sls_id) {
            return null;
        }

        $row = DB::table('bangunan as b')
            ->join('sls as s', 's.id', '=', 'b.sls_id')
            ->select(DB::raw('ST_Distance(s.area::geography, b.lokasi::geography) as jarak'))
            ->where('b.id', $this->id)
            ->first();

        return $row ? round((float) $row->jarak, 1) : null;
    }

    /**
     * Cari SLS mana yang SEBENARNYA memuat koordinat titik ini secara
     * geometris — dipakai untuk kasus "posisi di luar batas SLS", supaya
     * bisa dibandingkan: SLS yang TERCATAT (dari data kuesioner/import)
     * vs SLS yang RIIL memuat lokasi GPS-nya (kalau ada & beda).
     */
    public function slsAktual(): ?Sls
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        $row = DB::table('sls')
            ->select('id')
            ->whereRaw(
                'ST_Contains(area, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
                [$this->longitude, $this->latitude]
            )
            ->first();

        return $row ? Sls::find($row->id) : null;
    }
}
