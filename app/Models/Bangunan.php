<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Bangunan extends Model
{
    use HasFactory;

    protected $table = 'bangunan';

    protected $fillable = [
        'kegiatan_id',
        'sls_id',
        'id_sls_asal',
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
            // NULL tetap NULL (= belum bisa dicek), bukan dianggap false.
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

    /** Kode SLS di data tidak ketemu di tabel sls. */
    public function scopeTanpaSls(Builder $query): Builder
    {
        return $query->whereNull('sls_id');
    }

    /** SLS ketemu, tapi koordinat titik di luar polygon SLS itu. */
    public function scopeDiLuarSls(Builder $query): Builder
    {
        return $query->whereNotNull('sls_id')->where('di_luar_sls', true);
    }

    /** SLS ketemu, tapi SLS itu belum punya polygon, jadi belum bisa dicek. */
    public function scopeBelumDicek(Builder $query): Builder
    {
        return $query->whereNotNull('sls_id')->whereNull('di_luar_sls');
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

    /**
     * true  = titik di dalam polygon SLS-nya
     * false = titik di luar polygon SLS-nya
     * null  = tidak bisa dicek (tidak ada sls_id, ATAU SLS-nya belum punya polygon)
     */
    public function isInsideSls(): ?bool
    {
        if (! $this->sls_id) {
            return null;
        }

        $row = DB::table('bangunan as b')
            ->join('sls as s', 's.id', '=', 'b.sls_id')
            ->select(DB::raw('(s.area IS NOT NULL AND b.lokasi IS NOT NULL) as bisa_dicek'))
            ->addSelect(DB::raw('ST_Contains(s.area, b.lokasi) as inside'))
            ->where('b.id', $this->id)
            ->first();

        if (! $row || ! $row->bisa_dicek) {
            return null;
        }

        return (bool) $row->inside;
    }

    /**
     * Hitung ulang di_luar_sls untuk SEMUA titik yang SLS-nya sudah punya
     * polygon, dalam satu query. Dipanggil otomatis setelah import batas SLS,
     * supaya titik yang diimpor lebih dulu langsung ikut terperiksa.
     * Mengembalikan jumlah titik yang diperiksa.
     */
    public static function hitungUlangDiLuarSls(): int
    {
        return DB::affectingStatement('
            UPDATE bangunan b
            SET di_luar_sls = NOT ST_Contains(s.area, b.lokasi)
            FROM sls s
            WHERE b.sls_id = s.id
              AND s.area IS NOT NULL
              AND b.lokasi IS NOT NULL
        ');
    }

    /**
     * Jarak (meter) dari titik ke batas polygon SLS yang tercatat.
     * ::geography membuat hasilnya dalam meter, bukan derajat.
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

        if (! $row || $row->jarak === null) {
            return null;
        }

        return round((float) $row->jarak, 1);
    }

    /** SLS mana yang secara geometris memuat koordinat titik ini. */
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
