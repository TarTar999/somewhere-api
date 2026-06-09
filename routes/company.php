<?php

use App\Http\Controllers\Company\AddressController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\InvitationController;
use App\Http\Controllers\Company\LabelController;
use App\Http\Controllers\Company\MemberController;
use App\Http\Controllers\Company\OnboardingController;
use App\Http\Controllers\Company\SelectionController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\SubscriptionController;
use App\Http\Controllers\Company\ZoneController;
use App\Http\Controllers\Public\MapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Map Routes
|--------------------------------------------------------------------------
*/
Route::prefix('map')->name('public.map.')->group(function () {
    Route::get('/', [MapController::class, 'index'])->name('index');
    Route::get('search', [MapController::class, 'search'])->name('search');
});

Route::get('address/{address}', [MapController::class, 'showAddress'])->name('public.address');

/*
|--------------------------------------------------------------------------
| Company Onboarding Routes (Auth required, no company middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('company')->name('company.')->group(function () {
    // Company selection
    Route::get('select', [SelectionController::class, 'index'])->name('select');
    Route::post('select/{company}', [SelectionController::class, 'select'])->name('select.store');

    // Company creation / onboarding
    Route::get('create', [OnboardingController::class, 'create'])->name('create');
    Route::post('create', [OnboardingController::class, 'store'])->name('store');

    // Invitation acceptance
    Route::get('invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('invitation/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept');

    // Status pages
    Route::get('suspended', function () {
        return inertia('company/suspended');
    })->name('suspended');

    Route::get('subscription/expired', function () {
        return inertia('company/subscription-expired');
    })->name('subscription.expired');
});

/*
|--------------------------------------------------------------------------
| Company Dashboard Routes (Auth + Company middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'company'])->prefix('company')->name('company.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Members management
    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::get('members/invite', [MemberController::class, 'create'])->name('members.create');
    Route::post('members/invite', [MemberController::class, 'store'])->name('members.store');
    Route::put('members/{user}/role', [MemberController::class, 'updateRole'])->name('members.update-role');
    Route::delete('members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Addresses
    Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('addresses/{address}', [AddressController::class, 'show'])->name('addresses.show');
    Route::post('addresses/{address}/document', [AddressController::class, 'createDocument'])->name('addresses.create-document');

    // Labels (manager+ only)
    Route::middleware('company:manager')->group(function () {
        Route::get('labels', [LabelController::class, 'index'])->name('labels.index');
        Route::post('labels', [LabelController::class, 'store'])->name('labels.store');
        Route::put('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
        Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
        Route::delete('labels', [LabelController::class, 'bulkDestroy'])->name('labels.bulk-destroy');
    });

    // Zones (manager+ only)
    Route::middleware('company:manager')->group(function () {
        Route::get('zones', [ZoneController::class, 'index'])->name('zones.index');
        Route::get('zones/create', [ZoneController::class, 'create'])->name('zones.create');
        Route::post('zones', [ZoneController::class, 'store'])->name('zones.store');
        Route::get('zones/{zone}', [ZoneController::class, 'show'])->name('zones.show');
        Route::get('zones/{zone}/edit', [ZoneController::class, 'edit'])->name('zones.edit');
        Route::put('zones/{zone}', [ZoneController::class, 'update'])->name('zones.update');
        Route::delete('zones/{zone}', [ZoneController::class, 'destroy'])->name('zones.destroy');
        Route::post('zones/{zone}/duplicate', [ZoneController::class, 'duplicate'])->name('zones.duplicate');
    });

    // Subscription (admin only)
    Route::middleware('company:admin')->group(function () {
        Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::get('subscription/plans', [SubscriptionController::class, 'plans'])->name('subscription.plans');
        Route::post('subscription/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
        Route::post('subscription/renew', [SubscriptionController::class, 'renew'])->name('subscription.renew');
        Route::post('subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('subscription/invoices', [SubscriptionController::class, 'invoices'])->name('subscription.invoices');

        // Company settings
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});
