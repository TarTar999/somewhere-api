<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EneoController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin panel routes - require authentication and admin privileges
|
*/

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users management
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    // Addresses management
    Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('addresses/{address}', [AddressController::class, 'show'])->name('addresses.show');
    Route::post('addresses/{address}/verify', [AddressController::class, 'verify'])->name('addresses.verify');
    Route::post('addresses/{address}/reject', [AddressController::class, 'reject'])->name('addresses.reject');
    Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

    // Collections management
    Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

    // Notifications management (engagement messages)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('notifications/send', [NotificationController::class, 'send'])->name('notifications.send');
    Route::get('notifications/stats', [NotificationController::class, 'stats'])->name('notifications.stats');

    // ENEO management (restricted to specific user in controller)
    Route::get('eneo', [EneoController::class, 'index'])->name('eneo.index');
    Route::put('eneo/config', [EneoController::class, 'updateConfig'])->name('eneo.config');
    Route::post('eneo/sync', [EneoController::class, 'sync'])->name('eneo.sync');
    Route::delete('eneo/past', [EneoController::class, 'deletePast'])->name('eneo.delete-past');
});
