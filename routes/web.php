<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KegiatanController;
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
| Viewer hanya boleh: Dashboard, Peta, dan Profil sendiri.
*/
Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ================= PETA =================
    Route::get('/peta', [PetaController::class, 'index'])->name('peta.index');
    Route::get('/peta/data/sls', [PetaController::class, 'dataSls'])->name('peta.data.sls');
    Route::get('/peta/data/bangunan', [PetaController::class, 'dataBangunan'])->name('peta.data.bangunan');
    Route::get('/peta/bangunan/{bangunan}', [PetaController::class, 'detailBangunan'])->name('peta.detail.bangunan');
    Route::get('/peta/data/sls-list', [PetaController::class, 'daftarSls'])->name('peta.data.sls.daftar');
    Route::get('/peta/sls/{sls}/statistik', [PetaController::class, 'statistikSls'])->name('peta.statistik.sls');
});

/*
|--------------------------------------------------------------------------
| Khusus admin
|--------------------------------------------------------------------------
| Import Data dan Kegiatan. Viewer yang membuka URL-nya langsung dapat 403.
*/
Route::middleware(['auth', 'approved', EnsureUserIsAdmin::class])->group(function () {
    // ================= IMPORT DATA =================
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/batas-sls', [ImportController::class, 'storeBatasSls'])->name('import.batas-sls');
    Route::post('/import/titik-lokasi', [ImportController::class, 'storeTitikLokasi'])->name('import.titik-lokasi');

    // ================= KEGIATAN =================
    Route::resource('kegiatan', KegiatanController::class);
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';