<?php

use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\ProofController;
use App\Http\Controllers\Web\WebAccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Portal Routes
|--------------------------------------------------------------------------
|
| These routes are for the web portal that allows users to view their
| documents via QR code scanning from the mobile app.
|
*/

// Web access via QR code token (main entry point)
Route::get('/web/access/{token}', [WebAccessController::class, 'access'])
    ->name('web.access');

// Proof of Location routes
Route::prefix('web/proof')->name('web.proof.')->group(function () {
    Route::get('/{token}', [ProofController::class, 'show'])->name('show');
    Route::get('/{token}/verify', [ProofController::class, 'verify'])->name('verify');
    Route::get('/{token}/download', [ProofController::class, 'download'])->name('download');
});

// Invoice routes
Route::prefix('web/invoice')->name('web.invoice.')->group(function () {
    Route::get('/{token}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{token}/download', [InvoiceController::class, 'download'])->name('download');
});
