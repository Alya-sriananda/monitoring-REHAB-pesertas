<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\KomunikasiController;
use App\Http\Controllers\PaymentMonitoringController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\RehabCasePdfController;
use App\Http\Controllers\RehabRegistrationController;
use App\Http\Controllers\TemplateSettingsController;
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

    // Komunikasi
    Route::post('/komunikasi/preview', [KomunikasiController::class, 'generatePreview'])->name('komunikasi.preview');
    Route::post('/rehab-cases/{rehab_case}/komunikasi', [KomunikasiController::class, 'store'])->name('rehab-cases.komunikasi.store');

    // PDF
    Route::get('/rehab-cases/{rehab_case}/pdf', RehabCasePdfController::class)->name('rehab-cases.pdf');
    // Settings Templates
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/templates', [TemplateSettingsController::class, 'index'])->name('templates.index');
        Route::post('/templates/pesan', [TemplateSettingsController::class, 'storePesan'])->name('templates.pesan.store');
        Route::put('/templates/pesan/{templatePesan}', [TemplateSettingsController::class, 'updatePesan'])->name('templates.pesan.update');
        Route::delete('/templates/pesan/{templatePesan}', [TemplateSettingsController::class, 'destroyPesan'])->name('templates.pesan.destroy');
    });
});

require __DIR__.'/settings.php';
