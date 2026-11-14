<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Redirect unauthenticated root to login, or dashboard
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Transaksi routes
    Route::get('/transaksi', [TransactionController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [TransactionController::class, 'store'])->name('transaksi.store');
    Route::post('/transaksi/{id}/koreksi', [TransactionController::class, 'koreksi'])->name('transaksi.koreksi');
    Route::post('/transaksi/{id}/batal', [TransactionController::class, 'batal'])->name('transaksi.batal');

    // Profil & Akun
    Route::get('/akun', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/akun', [AccountController::class, 'update'])->name('account.update');
});

// Wajib Ganti Kredensial (accessible only when logged in, exempted from password.changed redirect)
Route::middleware('auth')->group(function () {
    Route::get('/akun/ganti-kredensial', [AccountController::class, 'showChangeCredentials'])
        ->name('account.change-credentials');
    Route::post('/akun/ganti-kredensial', [AccountController::class, 'updateCredentials'])
        ->name('account.update-credentials');
});

require __DIR__.'/auth.php';
