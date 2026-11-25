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
        'dibuat_oleh',
        'sumber',
        'akurasi_gps',
        'sumber_tag',
        'ditandai_at',
        'diedit_manual_at',
        'status_perbaikan',
        'dikoreksi_oleh',
        'dikoreksi_at',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'latitude_asal',
        'longitude_asal',
        'import_batch_id',
        'import_batch_awal_id',
        'alasan_tolak',
    ];

    /** Status perbaikan titik (kolom status_perbaikan). */
    public const STATUS_NORMAL = 'normal';
    public const STATUS_PERLU = 'perlu_perbaikan';
    public const STATUS_SUDAH = 'sudah_diperbaiki';
    public const STATUS_TERVERIFIKASI = 'terverifikasi';

    protected function casts(): array
    {
        return [
            'flag_btt'    => 'boolean',
            'flag_bku'    => 'boolean',
            'flag_repair' => 'boolean',
            // NULL tetap NULL (= belum bisa dicek), bukan dianggap false.
            'di_luar_sls' => 'boolean',
            'akurasi_gps' => 'float',
            'ditandai_at' => 'datetime',
            // Terisi kalau admin mengoreksi titik hasil import lewat peta.
            'diedit_manual_at' => 'datetime',
            'dikoreksi_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
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

    /** Akun yang men-tag titik ini (NULL untuk data import). */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Akun yang terakhir mengoreksi titik ini (NULL kalau belum pernah dikoreksi). */
    public function dikoreksiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }

    public function scopeHasilTagging(Builder $query): Builder
    {
        return $query->where('sumber', 'tagging');
    }

    /**
     * Titik yang sudah dikoreksi lewat aplikasi: TIDAK ditimpa import ulang
     * (kecuali admin memilih "timpa juga") dan tidak ikut terhapus saat import dihapus.
     */
    public function scopeTerkoreksi(Builder $query): Builder
    {
        return $query->where(fn ($w) => $w
            ->whereIn('status_perbaikan', [self::STATUS_SUDAH, self::STATUS_TERVERIFIKASI])
            ->orWhereNotNull('diedit_manual_at'));
    }

    public function scopeTidakTerkoreksi(Builder $query): Builder
    {
        return $query->whereNotIn('status_perbaikan', [self::STATUS_SUDAH, self::STATUS_TERVERIFIKASI])
            ->whereNull('diedit_manual_at');
    }

    /**
     * [#10] Hasil kerja petugas di lapangan: titik hasil tagging + titik import yang sudah dikoreksi.
     * Dipakai untuk melindungi kegiatan dari penghapusan tanpa sengaja.
     */
    public function scopeHasilKerja(Builder $query): Builder
    {
        return $query->where(fn ($w) => $w
            ->where('sumber', 'tagging')
            ->orWhere(fn ($x) => $x->terkoreksi()));
    }

    public function sudahDikoreksi(): bool
    {
        return in_array($this->status_perbaikan, [self::STATUS_SUDAH, self::STATUS_TERVERIFIKASI], true)
            || $this->diedit_manual_at !== null;
    }

    /**
     * Siapa boleh mengubah / menggeser titik:
     *  - admin: titik apa pun (hasil tagging maupun import);
     *  - petugas: titik hasil tagging miliknya sendiri, DAN titik hasil import di SLS yang
     *    ditugaskan kepadanya (untuk mengoreksi data awal dari file).
     * Syaratnya selalu: SLS itu masih ditugaskan kepadanya.
     * id_assignment dan kode SLS asal tidak pernah bisa diubah (kunci pencocokan dengan file sumber).
     */
    public function bolehDiubahOleh(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->sls_id) {
            return false;
        }

        // Titik hasil tagging: hanya pembuatnya.
        if ($this->sumber === 'tagging' && (int) $this->dibuat_oleh !== (int) $user->id) {
            return false;
        }

        return $user->bolehTagging((int) $this->kegiatan_id, (int) $this->sls_id);
    }

    /** Hapus hanya untuk titik hasil tagging. Titik import dihapus lewat menu Import Data. */
    public function bolehDihapusOleh(User $user): bool
    {
        return $this->sumber === 'tagging' && $this->bolehDiubahOleh($user);
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

    /** normal | di_luar_sls | tanpa_sls | belum_dicek (dipakai peta untuk warna titik). */
    public function statusKondisi(): string
    {
        if (! $this->sls_id) {
            return 'tanpa_sls';
        }

        if ($this->di_luar_sls === null) {
            return 'belum_dicek';
        }

        return $this->di_luar_sls ? 'di_luar_sls' : 'normal';
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

        // [#3] Koordinat dikirim sebagai parameter (binding), bukan disisipkan ke teks SQL.
        DB::update(
            'UPDATE bangunan SET lokasi = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$lng, $lat, $this->id]
        );
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
            ->addSelect(DB::raw('ST_Covers(s.area, b.lokasi) as inside'))
            ->where('b.id', $this->id)
            ->first();

        if (! $row || ! $row->bisa_dicek) {
            return null;
        }

        return (bool) $row->inside;
    }

    /**
     * Koordinat "acuan dari file": posisi titik menurut file import terakhir yang diterapkan.
     * Titik yang pernah digeser di aplikasi menyimpan posisi file-nya di latitude_asal/longitude_asal;
     * titik yang belum pernah digeser, posisi sekarangnya masih posisi dari file.
     * Dipakai bersama posisi sekarang oleh posisiFileBerubah() untuk menilai apakah file baru (hasil perbaikan QGIS) benar-benar mengubah posisi titik.
     *
     * @return array{0: ?float, 1: ?float} [lat, lng]
     */
    public function koordinatAcuan(): array
    {
        $lat = $this->latitude_asal ?? $this->latitude;
        $lng = $this->longitude_asal ?? $this->longitude;

        return [$lat !== null ? (float) $lat : null, $lng !== null ? (float) $lng : null];
    }

    /**
     * true kalau koordinat dari file berbeda (> ~5 cm) dari posisi acuan file DAN dari posisi titik sekarang.
     * Sama dengan posisi sekarang = file hasil export dari aplikasi yang diimpor kembali tanpa perubahan;
     * sama dengan posisi acuan = file lama yang diimpor lagi. Keduanya bukan perbaikan baru dari QGIS.
     */
    public function posisiFileBerubah(float $lat, float $lng): bool
    {
        $sama = fn (mixed $a, mixed $b, float $x, float $y) => $a !== null && $b !== null
            && abs((float) $a - $x) <= 5e-7 && abs((float) $b - $y) <= 5e-7;

        [$acuanLat, $acuanLng] = $this->koordinatAcuan();

        if ($sama($this->latitude, $this->longitude, $lat, $lng)) {
            return false;
        }

        return ! $sama($acuanLat, $acuanLng, $lat, $lng);
    }

    /**
     * Sambungkan titik yang sls_id-nya kosong ke SLS (kegiatan yang sama) yang kodenya cocok dengan
     * id_sls_asal. Dipanggil setelah batas SLS diimpor / disalin, supaya titik yang diimpor sebelum
     * SLS-nya ada tidak tertinggal tanpa SLS. Mengembalikan jumlah titik yang tersambung.
     */
    public static function sambungkanTanpaSls(?int $kegiatanId = null): int
    {
        return DB::affectingStatement('
            UPDATE bangunan b
            SET sls_id = s.id
            FROM sls s
            WHERE b.sls_id IS NULL
              AND b.id_sls_asal IS NOT NULL
              AND s.kegiatan_id = b.kegiatan_id
              AND s.id_sls = b.id_sls_asal
        ' . ($kegiatanId ? ' AND b.kegiatan_id = ?' : ''), $kegiatanId ? [$kegiatanId] : []);
    }

    /**
     * Hitung ulang di_luar_sls untuk SEMUA titik yang SLS-nya sudah punya
     * polygon, dalam satu query. Dipanggil otomatis setelah import batas SLS,
     * supaya titik yang diimpor lebih dulu langsung ikut terperiksa.
     * Mengembalikan jumlah titik yang diperiksa.
     */
    public static function hitungUlangDiLuarSls(?int $kegiatanId = null): int
    {
        return DB::affectingStatement('
            UPDATE bangunan b
            SET di_luar_sls = NOT ST_Covers(s.area, b.lokasi)
            FROM sls s
            WHERE b.sls_id = s.id
              AND s.area IS NOT NULL
              AND b.lokasi IS NOT NULL
        ' . ($kegiatanId ? ' AND b.kegiatan_id = ?' : ''), $kegiatanId ? [$kegiatanId] : []);
    }

    /**
     * Samakan status_perbaikan titik "normal" / "perlu_perbaikan" dengan kondisi terbarunya:
     * perlu_perbaikan kalau di luar batas SLS atau flag_repair menyala, selain itu normal.
     * Titik yang sudah dikoreksi (sudah_diperbaiki / terverifikasi) hanya dikembalikan ke
     * perlu_perbaikan kalau ternyata kini di luar batas SLS (mis. polygon SLS diimpor ulang).
     * Dipanggil setelah import dan setelah polygon SLS berubah, sehingga penanda perbaikan
     * hilang sendiri saat masalahnya selesai.
     */
    public static function sinkronStatusPerbaikanSemua(?int $kegiatanId = null): int
    {
        $filter = $kegiatanId ? ' AND kegiatan_id = ?' : '';
        $bind = $kegiatanId ? [$kegiatanId] : [];

        $a = DB::affectingStatement(
            "UPDATE bangunan
              SET status_perbaikan = CASE WHEN di_luar_sls IS TRUE OR flag_repair IS TRUE THEN 'perlu_perbaikan' ELSE 'normal' END
              WHERE status_perbaikan IN ('normal', 'perlu_perbaikan')" . $filter,
            $bind
        );

        $b = DB::affectingStatement(
            "UPDATE bangunan
              SET status_perbaikan = 'perlu_perbaikan', diverifikasi_oleh = NULL, diverifikasi_at = NULL
              WHERE status_perbaikan IN ('sudah_diperbaiki', 'terverifikasi') AND di_luar_sls IS TRUE" . $filter,
            $bind
        );

        return $a + $b;
    }

    /** Sama seperti sinkronStatusPerbaikanSemua(), untuk satu titik. */
    public function sinkronStatusPerbaikan(): void
    {
        if (! in_array($this->status_perbaikan, [self::STATUS_NORMAL, self::STATUS_PERLU, null], true)) {
            return;
        }

        $baru = ($this->di_luar_sls === true || $this->flag_repair) ? self::STATUS_PERLU : self::STATUS_NORMAL;

        if ($this->status_perbaikan !== $baru) {
            $this->forceFill(['status_perbaikan' => $baru])->save();
        }
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

    /** SLS (dalam kegiatan yang sama) yang secara geometris memuat koordinat titik ini. */
    public function slsAktual(): ?Sls
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        // Hanya SLS milik kegiatan titik ini: tiap kegiatan punya batas SLS-nya sendiri.
        $row = DB::table('sls')
            ->select('id')
            ->where('kegiatan_id', $this->kegiatan_id)
            ->whereRaw(
                'ST_Covers(area, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
                [$this->longitude, $this->latitude]
            )
            ->first();

        return $row ? Sls::find($row->id) : null;
    }
}
