<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\PaymentMonitoringController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\RehabRegistrationController;
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
        Route::get('/', [PesertaController::class, 'index'])->name('index');
        Route::get('/{peserta}', [PesertaController::class, 'show'])->name('show');
        Route::post('/{peserta}/rehab', [RehabRegistrationController::class, 'store'])->name('rehab.store');
    });

    // Payment Monitoring
    Route::post('/rehab-cases/{rehab_case}/payments', [PaymentMonitoringController::class, 'store'])->name('rehab-cases.payments.store');
});

require __DIR__.'/settings.php';
