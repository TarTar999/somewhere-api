<?php

use App\Http\Controllers\Auth\AuthMethodController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PinCodeController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\User\CollectionController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\DeliveryController;
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

// Auth method check (public, rate limited)
Route::post('/auth/check-methods', [AuthMethodController::class, 'checkAuthMethods'])
    ->middleware('throttle:10,1')
    ->name('auth.check-methods');

// Custom login route (overrides Fortify's default to support PIN code)
Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('login.store');

// PIN code management (authenticated)
Route::middleware(['auth'])->prefix('auth')->name('auth.')->group(function () {
    Route::post('/pin-code', [PinCodeController::class, 'store'])->name('pin-code.store');
    Route::put('/pin-code', [PinCodeController::class, 'update'])->name('pin-code.update');
    Route::post('/pin-code/skip', [PinCodeController::class, 'skip'])->name('pin-code.skip');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Collections
    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::get('/create', [CollectionController::class, 'create'])->name('create');
        Route::post('/', [CollectionController::class, 'store'])->name('store');
        Route::get('/{collection}', [CollectionController::class, 'show'])->name('show');
        Route::get('/{collection}/edit', [CollectionController::class, 'edit'])->name('edit');
        Route::put('/{collection}', [CollectionController::class, 'update'])->name('update');
        Route::delete('/{collection}', [CollectionController::class, 'destroy'])->name('destroy');
        Route::post('/{collection}/share', [CollectionController::class, 'share'])->name('share');
    });

    // Deliveries
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('/{delivery}', [DeliveryController::class, 'show'])->name('show');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/company.php';
