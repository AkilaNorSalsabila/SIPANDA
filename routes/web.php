<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataLokasiController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PenugasanSayaController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\TaggingController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Middleware\EnsureUserIsAdmin;

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'approved'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Semua pengguna yang sudah login & disetujui (admin + viewer)
|--------------------------------------------------------------------------
| Viewer boleh: Dashboard, Peta (+ tagging di SLS yang ditugaskan), dan Profil sendiri.
*/
Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ================= PENUGASAN SAYA (viewer) =================
    Route::get('/penugasan-saya', [PenugasanSayaController::class, 'index'])->name('penugasan.saya');

    // ================= PETA =================
    Route::get('/peta', [PetaController::class, 'index'])->name('peta.index');
    Route::get('/peta/data/sls', [PetaController::class, 'dataSls'])->name('peta.data.sls');
    Route::get('/peta/data/bangunan', [PetaController::class, 'dataBangunan'])->name('peta.data.bangunan');
    Route::get('/peta/bangunan/{bangunan}', [PetaController::class, 'detailBangunan'])->name('peta.detail.bangunan');
    Route::get('/peta/data/sls-list', [PetaController::class, 'daftarSls'])->name('peta.data.sls.daftar');
    Route::get('/peta/sls/{sls}/statistik', [PetaController::class, 'statistikSls'])->name('peta.statistik.sls');

    // ================= TAGGING (admin + viewer yang ditugaskan ke SLS) =================
    // Hak akses per SLS / per titik dicek di TaggingController & model.
    Route::get('/tagging/penugasan', [TaggingController::class, 'penugasan'])->name('tagging.penugasan');
    Route::get('/tagging/nomor-berikutnya', [TaggingController::class, 'nomorBerikutnya'])->name('tagging.nomor');
    Route::get('/tagging/export', [TaggingController::class, 'export'])->name('tagging.export');
    Route::post('/tagging', [TaggingController::class, 'store'])->name('tagging.store');
    Route::patch('/tagging/{bangunan}', [TaggingController::class, 'update'])->whereNumber('bangunan')->name('tagging.update');
    Route::patch('/tagging/{bangunan}/geser', [TaggingController::class, 'geser'])->whereNumber('bangunan')->name('tagging.geser');
    Route::delete('/tagging/{bangunan}', [TaggingController::class, 'destroy'])->whereNumber('bangunan')->name('tagging.destroy');

    // ================= DATA LOKASI (per SLS) =================
    Route::get('/data-lokasi', [DataLokasiController::class, 'index'])->name('data-lokasi.index');
    Route::get('/data-lokasi/{sls}', [DataLokasiController::class, 'show'])->whereNumber('sls')->name('data-lokasi.show');

    // ================= WILAYAH (data JSON untuk dropdown berantai) =================
    Route::get('/wilayah/kecamatan/{kecamatan}/kelurahan', [KelurahanController::class, 'byKecamatan'])
        ->name('wilayah.kelurahan.by-kecamatan');
});

/*
|--------------------------------------------------------------------------
| Khusus admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved', EnsureUserIsAdmin::class])->group(function () {
    // ================= IMPORT DATA =================
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/batas-sls', [ImportController::class, 'storeBatasSls'])->name('import.batas-sls');
    Route::post('/import/titik-lokasi', [ImportController::class, 'storeTitikLokasi'])->name('import.titik-lokasi');
    Route::post('/import/salin-sls', [ImportController::class, 'salinSls'])->name('import.salin-sls');
    Route::delete('/import/{batch}', [ImportController::class, 'destroy'])->whereNumber('batch')->name('import.destroy');
    Route::delete('/import/sls/{sls}/polygon', [ImportController::class, 'hapusPolygon'])->whereNumber('sls')->name('import.polygon.destroy');

    // ================= VERIFIKASI KOREKSI TITIK (hanya admin) =================
    Route::post('/tagging/{bangunan}/verifikasi', [TaggingController::class, 'verifikasi'])->whereNumber('bangunan')->name('tagging.verifikasi');
    Route::post('/tagging/{bangunan}/tolak', [TaggingController::class, 'tolak'])->whereNumber('bangunan')->name('tagging.tolak');

    // ================= KEGIATAN =================
    Route::resource('kegiatan', KegiatanController::class)->except(['show']);

    // ================= DATA LOKASI: status perbaikan (hanya admin) =================
    Route::patch('/data-lokasi/{sls}', [DataLokasiController::class, 'update'])->whereNumber('sls')->name('data-lokasi.update');

    // ================= RIWAYAT AKTIVITAS (hanya admin) =================
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');

    // ================= KECAMATAN & KELURAHAN =================
    Route::resource('kecamatan', KecamatanController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('kelurahan', KelurahanController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';