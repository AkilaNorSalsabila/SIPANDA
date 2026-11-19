<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Riwayat aktivitas pengguna.
 *
 * Cara pakai di controller (di bawah proses simpan yang sudah berhasil):
 *
 *   Riwayat::catat(
 *       modul: 'kegiatan',
 *       aksi: 'ubah',
 *       keterangan: 'Mengubah kegiatan KD-001',
 *       objekId: $kegiatan->id,
 *       objekLabel: 'KD-001 - Data Lokasi Usaha',
 *       lama: ['nama_kegiatan' => 'A'],
 *       baru: ['nama_kegiatan' => 'B'],
 *   );
 *
 * Mencatat riwayat TIDAK PERNAH boleh membuat aplikasi error: kalau gagal,
 * cukup ditulis ke storage/logs/laravel.log dan proses utama jalan terus.
 */
class Riwayat extends Model
{
    protected $table = 'riwayat';

    /** Tabel ini hanya punya created_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'nama_user',
        'modul',
        'aksi',
        'objek_id',
        'objek_label',
        'keterangan',
        'data_lama',
        'data_baru',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'data_lama'  => 'array',
            'data_baru'  => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Nama modul untuk tampilan & filter. */
    public const MODUL = [
        'sls'       => 'Data Lokasi (SLS)',
        'kegiatan'  => 'Kegiatan',
        'import'    => 'Import Data',
        'kecamatan' => 'Kecamatan',
        'kelurahan' => 'Kelurahan',
        'akun'      => 'Manajemen Akun',
        'penugasan' => 'Penugasan SLS',
        'tagging'   => 'Tagging Titik',
        'auth'      => 'Login & Pendaftaran',
    ];

    /** Nama aksi untuk tampilan & filter. */
    public const AKSI = [
        'tambah'      => 'Tambah',
        'ubah'        => 'Ubah',
        'hapus'       => 'Hapus',
        'import'      => 'Import',
        'setujui'     => 'Setujui',
        'tolak'       => 'Tolak',
        'nonaktifkan' => 'Nonaktifkan',
        'daftar'      => 'Daftar',
        'login'       => 'Login',
        'logout'      => 'Logout',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat satu kejadian. Mengembalikan modelnya, atau null kalau tidak
     * dicatat (tidak ada perubahan nilai / gagal menyimpan).
     *
     * Khusus aksi "ubah": kalau $lama dan $baru diberikan, yang disimpan
     * hanya kolom yang benar-benar berubah. Kalau tidak ada yang berubah,
     * tidak ada yang dicatat.
     */
    public static function catat(
        string $modul,
        string $aksi,
        string $keterangan,
        ?int $objekId = null,
        ?string $objekLabel = null,
        ?array $lama = null,
        ?array $baru = null,
        ?User $pelaku = null,
    ): ?self {
        try {
            if ($aksi === 'ubah' && $lama !== null && $baru !== null) {
                [$lama, $baru] = self::selisih($lama, $baru);

                if ($lama === [] && $baru === []) {
                    return null;
                }
            }

            $pelaku ??= Auth::user();

            return static::create([
                'user_id'     => $pelaku?->id,
                'nama_user'   => $pelaku?->name ?? 'Pengunjung',
                'modul'       => $modul,
                'aksi'        => $aksi,
                'objek_id'    => $objekId,
                'objek_label' => $objekLabel,
                'keterangan'  => $keterangan,
                'data_lama'   => $lama ?: null,
                'data_baru'   => $baru ?: null,
                'ip_address'  => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat riwayat aktivitas', [
                'modul'       => $modul,
                'aksi'        => $aksi,
                'keterangan'  => $keterangan,
                'pesan_error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ambil hanya kolom yang nilainya berbeda. null dan teks kosong dianggap
     * sama, true/false dibandingkan sebagai 1/0.
     *
     * @return array{0: array, 1: array}  [lama, baru]
     */
    public static function selisih(array $lama, array $baru): array
    {
        $normal = fn ($v) => is_bool($v) ? ($v ? '1' : '0') : (string) ($v ?? '');

        $selisihLama = [];
        $selisihBaru = [];

        foreach ($baru as $kolom => $nilaiBaru) {
            $nilaiLama = $lama[$kolom] ?? null;

            if ($normal($nilaiLama) !== $normal($nilaiBaru)) {
                $selisihLama[$kolom] = $nilaiLama;
                $selisihBaru[$kolom] = $nilaiBaru;
            }
        }

        return [$selisihLama, $selisihBaru];
    }
}
