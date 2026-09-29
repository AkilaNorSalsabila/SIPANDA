<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KegiatanController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'approved'])->name('dashboard');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ================= IMPORT DATA =================
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/batas-sls', [ImportController::class, 'storeBatasSls'])->name('import.batas-sls');
    Route::post('/import/titik-lokasi', [ImportController::class, 'storeTitikLokasi'])->name('import.titik-lokasi');

    // ================= PETA =================
    Route::get('/peta', [PetaController::class, 'index'])->name('peta.index');
    Route::get('/peta/data/sls', [PetaController::class, 'dataSls'])->name('peta.data.sls');
    Route::get('/peta/data/bangunan', [PetaController::class, 'dataBangunan'])->name('peta.data.bangunan');
    Route::get('/peta/bangunan/{bangunan}', [PetaController::class, 'detailBangunan'])->name('peta.detail.bangunan');
    Route::get('/peta/data/sls-list', [PetaController::class, 'daftarSls'])->name('peta.data.sls.daftar');
    Route::get('/peta/sls/{sls}/statistik', [PetaController::class, 'statistikSls'])->name('peta.statistik.sls');

    Route::resource('kegiatan', KegiatanController::class);
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
