<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sls extends Model
{
    use HasFactory;

    protected $table = 'sls';

    protected $fillable = [
        'kegiatan_id',
        'id_sls',
        'kecamatan_id',
        'kelurahan_id',
        'id_provinsi',
        'id_kabupaten',
        'id_kecamatan',
        'id_kelurahan',
        'nama_sls',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'luas',
        'periode',
        'area',
        'perlu_perbaikan',
        'solusi_perbaikan',
    ];

    protected function casts(): array
    {
        return [
            'perlu_perbaikan' => 'boolean',
        ];
    }

    /**
     * SLS adalah snapshot per kegiatan: satu baris untuk tiap (kegiatan, id_sls).
     * Batas SLS kegiatan lama tidak berubah ketika kegiatan baru memakai batas lain.
     */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function bangunan(): HasMany
    {
        return $this->hasMany(Bangunan::class);
    }

    /** SLS milik satu kegiatan. */
    public function scopeUntukKegiatan(Builder $query, int $kegiatanId): Builder
    {
        return $query->where('sls.kegiatan_id', $kegiatanId);
    }

    /**
     * Salin SLS (beserta polygonnya) dari satu kegiatan ke kegiatan lain, misalnya saat batas
     * SLS kegiatan baru sama dengan kegiatan sebelumnya. Hanya id_sls yang belum ada di
     * kegiatan tujuan yang disalin; yang sudah ada tidak ditimpa. Tanda "perlu perbaikan"
     * tidak ikut disalin karena itu catatan QC kegiatan asal.
     * Mengembalikan jumlah SLS yang disalin.
     */
    public static function salinDariKegiatan(int $asalId, int $tujuanId): int
    {
        return DB::affectingStatement('
            INSERT INTO sls (
                kegiatan_id, id_sls, kecamatan_id, kelurahan_id,
                id_provinsi, id_kabupaten, id_kecamatan, id_kelurahan,
                nama_sls, kabupaten_kota, kecamatan, desa_kelurahan,
                luas, periode, area, perlu_perbaikan, solusi_perbaikan, created_at, updated_at
            )
            SELECT
                ?, s.id_sls, s.kecamatan_id, s.kelurahan_id,
                s.id_provinsi, s.id_kabupaten, s.id_kecamatan, s.id_kelurahan,
                s.nama_sls, s.kabupaten_kota, s.kecamatan, s.desa_kelurahan,
                s.luas, s.periode, s.area, FALSE, NULL, NOW(), NOW()
            FROM sls s
            WHERE s.kegiatan_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM sls t WHERE t.kegiatan_id = ? AND t.id_sls = s.id_sls
              )
        ', [$tujuanId, $asalId, $tujuanId]);
    }

    /**
     * SLS "perlu perbaikan" kalau salah satu:
     *  - SLS belum punya polygon batas (berapa pun jumlah titiknya),
     *  - ada titik yang jatuh di luar polygon SLS-nya,
     *  - admin menandainya manual (kolom perlu_perbaikan).
     * $kegiatanId membatasi pengecekan titik di luar SLS ke satu kegiatan.
     */
    public function scopePerluPerbaikan(Builder $query, bool $perlu = true, ?int $kegiatanId = null): Builder
    {
        $titik = fn ($b) => $b->when($kegiatanId, fn ($x) => $x->where('kegiatan_id', $kegiatanId));

        $kondisi = fn (Builder $w) => $w
            ->where('sls.perlu_perbaikan', true)
            ->orWhereNull('sls.area')
            ->orWhereHas('bangunan', fn ($b) => $titik($b)->where('di_luar_sls', true));

        return $perlu ? $query->where($kondisi) : $query->whereNot($kondisi);
    }

    /** Hanya SLS yang punya titik (di kegiatan tertentu kalau $kegiatanId diisi). */
    public function scopeDenganTitik(Builder $query, ?int $kegiatanId = null): Builder
    {
        return $query->whereHas('bangunan', fn ($b) => $b->when($kegiatanId, fn ($x) =>$x->where('kegiatan_id', $kegiatanId)));
    }

    /**
     * Alasan status perbaikan untuk SLS ini. Butuh atribut hasil query
     * DataLokasiController: jumlah_titik, jumlah_di_luar, punya_polygon.
     */
    public function alasanPerbaikan(): array
    {
        $alasan = [];

        if (! (bool) ($this->punya_polygon ?? true)) {
            $alasan[] = 'SLS belum punya batas polygon';
        }
        if ((int) ($this->jumlah_di_luar ?? 0) > 0) {
            $alasan[] = $this->jumlah_di_luar . ' titik di luar batas SLS';
        }
        if ($this->perlu_perbaikan) {
            $alasan[] = 'Ditandai perlu perbaikan oleh admin';
        }

        return $alasan;
    }

    public function kecamatanMaster(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function kelurahanMaster(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id');
    }

    /**
     * Pastikan kecamatan & kelurahan SLS ini ada di tabel master, lalu
     * hubungkan lewat kecamatan_id / kelurahan_id.
     *
     * Kode diambil dari id_sls (14 digit: prov 2 + kab 2 + kec 3 + desa 3
     * + sls 4). Kalau id_sls bukan 14 digit, dipakai kolom id_* yang ada.
     * Nama di master tidak ditimpa kalau sudah ada (supaya nama yang
     * dikoreksi admin tidak kembali ke nama di file import).
     */
    public function sinkronWilayah(): void
    {
        $id = (string) $this->id_sls;

        if (preg_match('/^\d{14}$/', $id)) {
            [$prov, $kab, $kec, $desa] = [
                substr($id, 0, 2), substr($id, 2, 2), substr($id, 4, 3), substr($id, 7, 3),
            ];
        } else {
            $prov = str_pad((string) $this->id_provinsi, 2, '0', STR_PAD_LEFT);
            $kab  = str_pad((string) $this->id_kabupaten, 2, '0', STR_PAD_LEFT);
            $kec  = str_pad((string) $this->id_kecamatan, 3, '0', STR_PAD_LEFT);
            $desa = str_pad((string) $this->id_kelurahan, 3, '0', STR_PAD_LEFT);
        }

        if (! $this->id_kecamatan && ! preg_match('/^\d{14}$/', $id)) {
            return; // kode wilayah tidak diketahui, tidak ada yang bisa dikelompokkan
        }

        $kecamatan = Kecamatan::firstOrCreate(
            ['id_provinsi' => $prov, 'id_kabupaten' => $kab, 'id_kecamatan' => $kec],
            [
                'nama_kecamatan' => $this->kecamatan ?: '-',
                'kabupaten_kota' => $this->kabupaten_kota ?: 'KOTA MOJOKERTO',
            ]
        );

        $kelurahan = Kelurahan::firstOrCreate(
            ['kecamatan_id' => $kecamatan->id, 'id_kelurahan' => $desa],
            ['nama_kelurahan' => $this->desa_kelurahan ?: '-']
        );

        if ($this->kecamatan_id !== $kecamatan->id || $this->kelurahan_id !== $kelurahan->id) {
            $this->forceFill([
                'kecamatan_id' => $kecamatan->id,
                'kelurahan_id' => $kelurahan->id,
            ])->saveQuietly();
        }
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
        // [#3] GeoJSON dikirim sebagai parameter (binding), bukan disisipkan ke teks SQL.
        // CAST(? AS text) menghindari error "function is not unique" pada PostGIS 3.
        DB::update(
            'UPDATE sls SET area = ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(CAST(? AS text)), 4326)) WHERE id = ?',
            [json_encode($geojson, JSON_THROW_ON_ERROR), $this->id]
        );
    }
}
