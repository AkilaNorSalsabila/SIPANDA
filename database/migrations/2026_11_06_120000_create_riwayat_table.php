<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat aktivitas (audit log).
 *
 * Satu baris = satu kejadian penting: siapa, kapan, melakukan apa, pada data
 * apa, dan (untuk perubahan) nilai lama -> nilai baru. Tabel ini hanya
 * ditambah (append-only): tidak ada fitur ubah/hapus di aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat', function (Blueprint $table) {
            $table->id();

            // Pelaku. Kalau akunnya dihapus, user_id jadi NULL tapi nama_user tetap
            // tersimpan sebagai salinan, jadi jejaknya tidak hilang.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_user')->nullable();

            $table->string('modul', 30);               // sls, kegiatan, kecamatan, kelurahan, akun, import, auth
            $table->string('aksi', 30);                // tambah, ubah, hapus, import, setujui, tolak, ...

            // Data yang terkena (id + label teks). Label disalin supaya tetap
            // terbaca walau datanya sudah dihapus.
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->string('objek_label')->nullable();

            $table->text('keterangan');                // kalimat yang mudah dibaca manusia
            $table->json('data_lama')->nullable();     // nilai sebelum diubah
            $table->json('data_baru')->nullable();     // nilai sesudah diubah
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['modul', 'objek_id']);
            $table->index('aksi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat');
    }
};
