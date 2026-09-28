<?php

use App\Http\Controllers\Admin\UserApprovalController;
use Illuminate\Support\Facades\Route;

/*
| Route khusus admin. Dimuat dari routes/web.php lewat:
|     require __DIR__.'/admin.php';
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserApprovalController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
    Route::patch('/users/{user}/deactivate', [UserApprovalController::class, 'deactivate'])->name('users.deactivate');
});
