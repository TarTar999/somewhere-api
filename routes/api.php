<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Auth\AccountController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\CollectionAddressController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\DeliveryRequestController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DomiciliationController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProofOfLocationController;
use App\Http\Controllers\Api\V1\ProofOfResidenceController;
use App\Http\Controllers\Api\V1\StreetController;
use App\Http\Controllers\Api\V1\TrackController;
use App\Http\Controllers\Api\V1\WebAccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    // Traditional email/password login
    Route::post('login', [AuthController::class, 'login']);

    // OTP-based phone login
    Route::post('login/send-otp', [AuthController::class, 'sendLoginOtp']);
    Route::post('login/otp', [AuthController::class, 'loginWithOtp']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('send-otp', [OtpController::class, 'send']);
    Route::post('verify-otp', [OtpController::class, 'verify']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('reset-password', [PasswordController::class, 'reset']);

    // Forgot password routes (SMS OTP based)
    Route::post('forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
    Route::post('forgot-password/send-link', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('forgot-password/reset', [ForgotPasswordController::class, 'resetPassword']);
});

// Proof of residence download (can be accessed with token in URL)
Route::get('proof-of-residence/download', [ProofOfResidenceController::class, 'download'])
    ->name('api.proof-of-residence.download');

// Public delivery request route (accessible without auth)
Route::get('delivery-requests/token/{token}', [DeliveryRequestController::class, 'showByToken']);

// Public track route (accessible via share token)
Route::get('tracks/shared/{token}', [TrackController::class, 'showByToken']);

// Public proof of location verification (via QR code)
Route::get('proof-of-location/verify/{token}', [ProofOfLocationController::class, 'showByQrToken'])
    ->name('api.proof.verify');

// Public document verification (via verification code)
Route::get('documents/verify/{code}', [DocumentController::class, 'verify'])
    ->name('api.documents.verify');

// Document prices (public)
Route::get('documents/prices', [DocumentController::class, 'prices']);

// Web access token validation (public)
Route::get('web-access/validate/{token}', [WebAccessController::class, 'validateToken']);

// Fapshi webhook (public, no auth)
Route::post('webhooks/fapshi', [PaymentController::class, 'handleWebhook'])
    ->name('webhooks.fapshi');

// Streets (public - used for address creation)
Route::prefix('streets')->group(function () {
    Route::post('verify', [StreetController::class, 'verify']);
    Route::get('/', [StreetController::class, 'index']);
    Route::get('osm/{osmId}', [StreetController::class, 'showByOsmId']);
    Route::get('code/{code}', [StreetController::class, 'showByCode']);
    Route::post('{streetId}/calculate-address', [StreetController::class, 'calculateAddress']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('users/{user}', [ProfileController::class, 'update']);
        Route::delete('users/{user}', [ProfileController::class, 'destroy']);
        Route::post('change-password', [PasswordController::class, 'change']);
        Route::post('users/{user}/collections', [CollectionController::class, 'storeForUser']);
    });

    // Account management
    Route::prefix('account')->group(function () {
        Route::post('request-deletion', [AccountController::class, 'requestDeletion']);
        Route::post('cancel-deletion', [AccountController::class, 'cancelDeletion']);
        Route::post('delete-immediately', [AccountController::class, 'deleteImmediately']);
        Route::get('deletion-status', [AccountController::class, 'getDeletionStatus']);
        Route::get('export-data', [AccountController::class, 'exportData']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('config', [PaymentController::class, 'getConfig']);
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('{id}', [PaymentController::class, 'getStatus']);
        // New document payment endpoints
        Route::post('document', [PaymentController::class, 'initiateDocumentPayment']);
        Route::post('document/direct', [PaymentController::class, 'initiateDirectPayment']);
        // Legacy endpoints (deprecated, use document endpoints instead)
        Route::post('proof-of-location', [PaymentController::class, 'initiateProofOfLocationPayment']);
        Route::post('proof-of-location/direct', [PaymentController::class, 'initiateDirectPayment']);
    });

    // Documents (unified endpoint for all document types)
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::get('address/{addressId}', [DocumentController::class, 'byAddress']);
        Route::get('{type}/{id}/download', [DocumentController::class, 'download'])
            ->name('api.documents.download')
            ->where('type', 'location_plan|proof_of_residence|invoice|receipt');
    });

    // KYC
    Route::prefix('kyc')->group(function () {
        Route::get('status', [KycController::class, 'getStatus']);
        Route::post('upload/cni-front', [KycController::class, 'uploadCniFront']);
        Route::post('upload/cni-back', [KycController::class, 'uploadCniBack']);
        Route::post('upload/selfie', [KycController::class, 'uploadSelfie']);
        Route::post('upload/video', [KycController::class, 'uploadVideo']);
        Route::post('submit', [KycController::class, 'submit']);
    });

    // Proof of Location
    Route::prefix('proof-of-location')->group(function () {
        Route::get('/', [ProofOfLocationController::class, 'index']);
        Route::get('active', [ProofOfLocationController::class, 'getActive']);
        Route::get('{id}', [ProofOfLocationController::class, 'show']);
        Route::get('{id}/download', [ProofOfLocationController::class, 'download'])
            ->name('api.proof-of-location.download');
        Route::get('{id}/qr-code', [ProofOfLocationController::class, 'generateWebAccessQr']);
    });

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('{id}', [InvoiceController::class, 'show']);
        Route::get('{id}/download', [InvoiceController::class, 'download'])
            ->name('api.invoices.download');
        Route::get('{id}/qr-code', [InvoiceController::class, 'generateWebAccessQr']);
    });

    // Web Access QR codes
    Route::prefix('web-access')->group(function () {
        Route::post('dashboard-qr', [WebAccessController::class, 'generateDashboardQr']);
        Route::post('kyc-qr', [WebAccessController::class, 'generateKycStatusQr']);
    });

    // Addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('search', [AddressController::class, 'search']);
        Route::get('nearby', [AddressController::class, 'nearby']);
        Route::post('scan', [AddressController::class, 'scan']);
        Route::get('sw/{swAddress}', [AddressController::class, 'showBySwAddress'])
            ->where('swAddress', '.*');
        Route::get('{address}', [AddressController::class, 'show']);
        Route::put('{address}', [AddressController::class, 'update']);
        Route::delete('{address}', [AddressController::class, 'destroy']);
        Route::post('{address}/share', [AddressController::class, 'share']);
        Route::get('{address}/qr-code', [AddressController::class, 'qrCode']);
    });

    // Collections
    Route::prefix('collections')->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::post('/', [CollectionController::class, 'store']);
        Route::get('shared', [CollectionController::class, 'getShared']);
        Route::get('{collection}', [CollectionController::class, 'show']);
        Route::put('{collection}', [CollectionController::class, 'update']);
        Route::delete('{collection}', [CollectionController::class, 'destroy']);
        Route::post('{collection}/share', [CollectionController::class, 'share']);
        Route::post('{collection}/addresses', [CollectionAddressController::class, 'add']);
        Route::delete('{collection}/addresses/{address}', [CollectionAddressController::class, 'remove']);
    });

    // Proof of Residence
    Route::get('proof-of-residence', [ProofOfResidenceController::class, 'generate']);

    // Delivery Requests
    Route::prefix('delivery-requests')->group(function () {
        Route::get('/', [DeliveryRequestController::class, 'index']);
        Route::post('/', [DeliveryRequestController::class, 'store']);
        Route::get('{id}', [DeliveryRequestController::class, 'show']);
        Route::put('{id}/accept', [DeliveryRequestController::class, 'accept']);
        Route::put('{id}/status', [DeliveryRequestController::class, 'updateStatus']);
        Route::put('{id}/confirm', [DeliveryRequestController::class, 'confirm']);
        Route::delete('{id}', [DeliveryRequestController::class, 'destroy']);
    });

    // Tracks (Pistes)
    Route::prefix('tracks')->group(function () {
        Route::get('/', [TrackController::class, 'index']);
        Route::post('/', [TrackController::class, 'store']);
        Route::get('{track}', [TrackController::class, 'show']);
        Route::put('{track}', [TrackController::class, 'update']);
        Route::delete('{track}', [TrackController::class, 'destroy']);
        Route::post('{track}/share', [TrackController::class, 'share']);
        Route::post('{track}/unshare', [TrackController::class, 'unshare']);
        Route::post('{track}/regenerate-token', [TrackController::class, 'regenerateToken']);
    });

    // Domiciliations
    Route::prefix('domiciliations')->group(function () {
        Route::get('/', [DomiciliationController::class, 'index']);
        Route::post('/', [DomiciliationController::class, 'store']);
        Route::get('{domiciliation}', [DomiciliationController::class, 'show']);
        Route::put('{domiciliation}', [DomiciliationController::class, 'update']);
        Route::delete('{domiciliation}', [DomiciliationController::class, 'destroy']);
        Route::post('invite', [DomiciliationController::class, 'generateInvitation']);
        Route::post('accept', [DomiciliationController::class, 'acceptInvitation']);
        Route::get('address/{addressId}/residents', [DomiciliationController::class, 'getResidents']);
        Route::delete('address/{addressId}/residents/{domiciliationId}', [DomiciliationController::class, 'removeResident']);
    });
});
