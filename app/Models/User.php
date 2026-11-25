<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

   
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',      // admin | viewer (operator tidak dipakai)
        'role_diminta', // peran yang dipilih saat mendaftar (baru berlaku setelah disetujui admin)
        'status',    // pending | approved | rejected | nonaktif
    ];

   
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Peran yang boleh dipilih / diberikan lewat aplikasi. */
    public const ROLE = [
        'viewer' => 'Viewer (mitra)',
        'admin'  => 'Admin',
    ];

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanSls::class);
    }

    public function bangunanTagging(): HasMany
    {
        return $this->hasMany(Bangunan::class, 'dibuat_oleh');
    }

    /**
     * Boleh men-tag titik di SLS ini untuk kegiatan ini?
     * Admin boleh di mana saja; viewer hanya di SLS yang ditugaskan kepadanya.
     */
    public function bolehTagging(int $kegiatanId, int $slsId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->penugasan()
            ->where('kegiatan_id', $kegiatanId)
            ->where('sls_id', $slsId)
            ->exists();
    }

    public function labelRole(): string
    {
        return self::ROLE[$this->role] ?? ucfirst((string) $this->role);
    }
}
