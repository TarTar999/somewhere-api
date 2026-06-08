<?php

use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Payment callback (Fapshi redirects here, then we redirect to app)
Route::get('/payment/callback', [PaymentCallbackController::class, 'handleCallback'])
    ->name('payment.callback');

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Share routes (public, no auth required)
Route::prefix('share')->name('share.')->group(function () {
    Route::get('address/{id}', [ShareController::class, 'address'])->name('address');
    Route::get('address/sw/{swAddress}', [ShareController::class, 'addressBySw'])
        ->where('swAddress', '.*')
        ->name('address.sw');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/company.php';
