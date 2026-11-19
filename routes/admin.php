<?php

use App\Http\Controllers\Admin\PenugasanController;
use App\Http\Controllers\Admin\ProgresMitraController;
use App\Http\Controllers\Admin\UserApprovalController;
use Illuminate\Support\Facades\Route;

/*
| Route khusus admin. 
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserApprovalController::class, 'index'])->name('users.index');
    Route::post('/users', [UserApprovalController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
    Route::patch('/users/{user}/deactivate', [UserApprovalController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{user}/role', [UserApprovalController::class, 'updateRole'])->name('users.role');

    // Penugasan mitra (viewer) ke SLS
    Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan.index');
    Route::get('/users/{user}/penugasan', [PenugasanController::class, 'show'])->name('users.penugasan');
    Route::get('/users/{user}/penugasan/tambah', [PenugasanController::class, 'create'])->name('users.penugasan.tambah');
    Route::post('/users/{user}/penugasan', [PenugasanController::class, 'store'])->name('users.penugasan.store');
    Route::delete('/users/{user}/penugasan/{sls}', [PenugasanController::class, 'hapusSatu'])->whereNumber('sls')->name('users.penugasan.hapus');

    // Progres kerja mitra berdasarkan penugasan
    Route::get('/progres', [ProgresMitraController::class, 'index'])->name('progres.index');
    Route::get('/users/{user}/progres', [ProgresMitraController::class, 'show'])->name('progres.show');
});
