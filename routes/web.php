<?php

use App\Http\Controllers\BatchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Batch & Import
    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', [BatchController::class, 'index'])->name('index');
        Route::get('/import', [BatchController::class, 'create'])->name('create');
        Route::post('/preview', [BatchController::class, 'preview'])->name('preview');
        Route::post('/import', [BatchController::class, 'import'])->name('store');
        Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
    });

    // Master Peserta & Detail
    Route::prefix('peserta')->name('peserta.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PesertaController::class, 'index'])->name('index');
        Route::get('/{peserta}', [\App\Http\Controllers\PesertaController::class, 'show'])->name('show');
    });
});

require __DIR__.'/settings.php';
