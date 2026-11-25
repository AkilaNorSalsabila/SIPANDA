<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hanya ada dua peran: admin dan viewer.
 * Akun lama dengan peran lain (mis. "petugas", "operator") atau kosong dijadikan viewer
 * supaya mendapat menu Tagging dan bisa diberi penugasan SLS oleh admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($q) {
                $q->whereNull('role')->orWhereNotIn('role', ['admin', 'viewer']);
            })
            ->update(['role' => 'viewer']);
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan: peran lama tidak disimpan.
    }
};
