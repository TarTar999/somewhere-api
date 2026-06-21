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
use App\Http\Controllers\Api\V1\CompanyAddressController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CompanyDocumentController;
use App\Http\Controllers\Api\V1\CompanyLabelController;
use App\Http\Controllers\Api\V1\CompanyMemberController;
use App\Http\Controllers\Api\V1\CompanySubscriptionController;
use App\Http\Controllers\Api\V1\CompanyZoneController;
use App\Http\Controllers\Api\V1\DeliveryRequestController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DomiciliationController;
use App\Http\Controllers\Api\V1\IntersectionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\NotificationController;
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
    // Check available auth methods for a phone number
    Route::post('check-methods', [\App\Http\Controllers\Auth\AuthMethodController::class, 'checkAuthMethods'])
        ->middleware('throttle:10,1');

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

// PDF Preview routes (local development only)
if (app()->environment('local')) {
    Route::prefix('test/pdf')->group(function () {
        Route::get('location-plan/{proofId}', function ($proofId) {
            $proof = \App\Models\ProofOfLocation::with(['user', 'address.street', 'address.itineraryStreet', 'payment'])->findOrFail($proofId);
            $pdfService = app(\App\Services\PdfService::class);
            return $pdfService->generateLocationPlanPdf($proof);
        });
        Route::get('proof-of-residence/{proofId}', function ($proofId) {
            $proof = \App\Models\ProofOfLocation::with(['user', 'address.street', 'address.itineraryStreet', 'payment'])->findOrFail($proofId);
            $pdfService = app(\App\Services\PdfService::class);
            return $pdfService->generateProofOfResidencePdf($proof);
        });
        Route::get('invoice/{invoiceId}', function ($invoiceId) {
            $invoice = \App\Models\Invoice::with(['user', 'payment.address', 'payment.proofOfLocation'])->findOrFail($invoiceId);
            $pdfService = app(\App\Services\PdfService::class);
            return $pdfService->generateInvoicePdf($invoice);
        });
    });

    // Debug signature route (from address)
    Route::get('test/signature/{addressId}', function ($addressId) {
        $address = \App\Models\Address::findOrFail($addressId);
        $pdfService = app(\App\Services\PdfService::class);
        $signatureDataUrl = $pdfService->getSignatureDataUrl($address->signature);

        return response()->json([
            'address_id' => $addressId,
            'has_signature' => !empty($address->signature),
            'signature_length' => $address->signature ? strlen($address->signature) : 0,
            'signature_preview' => $address->signature ? substr($address->signature, 0, 100) . '...' : null,
            'starts_with_M' => $address->signature ? str_starts_with(trim($address->signature), 'M') : false,
            'contains_L' => $address->signature ? str_contains($address->signature, 'L') : false,
            'converted_data_url' => $signatureDataUrl ? substr($signatureDataUrl, 0, 100) . '...' : null,
            'full_signature' => $address->signature,
        ]);
    });

    // Preview signature as SVG (from address)
    Route::get('test/signature-preview/{addressId}', function ($addressId) {
        $address = \App\Models\Address::findOrFail($addressId);
        $pdfService = app(\App\Services\PdfService::class);
        $signatureDataUrl = $pdfService->getSignatureDataUrl($address->signature);

        if (!$signatureDataUrl) {
            return 'No signature for address ' . $addressId;
        }

        return '<html><body style="padding: 50px; background: #f0f0f0;">
            <h3>Address Signature Preview (ID: ' . $addressId . ')</h3>
            <div style="background: white; padding: 20px; border: 1px solid #ccc; display: inline-block;">
                <img src="' . $signatureDataUrl . '" style="max-width: 300px; max-height: 150px;">
            </div>
            <pre style="margin-top: 20px; background: #eee; padding: 10px; overflow: auto; max-width: 800px;">' . htmlspecialchars(substr($address->signature ?? '', 0, 500)) . '...</pre>
        </body></html>';
    });
}

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

// Document download with token (public - no auth required)
Route::get('documents/download/{token}', [DocumentController::class, 'downloadWithToken'])
    ->name('api.documents.download-token');

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

// Map data (public for heatmap visualization)
Route::prefix('map')->group(function () {
    Route::get('heatmap', [\App\Http\Controllers\Api\V1\MapController::class, 'heatmap']);
    Route::get('clusters', [\App\Http\Controllers\Api\V1\MapController::class, 'clusters']);
    Route::get('addresses-in-bounds', [\App\Http\Controllers\Api\V1\MapController::class, 'addressesInBounds']);
    Route::get('search', [\App\Http\Controllers\Api\V1\MapController::class, 'search']);
    Route::get('zones/{zone}/stats', [\App\Http\Controllers\Api\V1\MapController::class, 'zoneStats']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('validate', [AuthController::class, 'validate']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::get('avatars', [ProfileController::class, 'getAvatarConfig']);
        Route::put('users/{user}', [ProfileController::class, 'update']);
        Route::delete('users/{user}', [ProfileController::class, 'destroy']);
        Route::post('change-password', [PasswordController::class, 'change']);
        Route::post('users/{user}/collections', [CollectionController::class, 'storeForUser']);

        // Signature management
        Route::get('signature', [ProfileController::class, 'getSignature']);
        Route::post('signature', [ProfileController::class, 'updateSignature']);
        Route::delete('signature', [ProfileController::class, 'deleteSignature']);

        // Auth status (PIN, password)
        Route::get('auth-status', [ProfileController::class, 'getAuthStatus']);

        // PIN code management
        Route::post('pin-code', [\App\Http\Controllers\Api\V1\Auth\PinCodeController::class, 'store']);
        Route::put('pin-code', [\App\Http\Controllers\Api\V1\Auth\PinCodeController::class, 'update']);
        Route::delete('pin-code', [\App\Http\Controllers\Api\V1\Auth\PinCodeController::class, 'destroy']);
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
        Route::post('{type}/{id}/download-token', [DocumentController::class, 'generateDownloadToken'])
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
        // Itinerary (custom path) management
        Route::put('{address}/itinerary', [AddressController::class, 'updateItinerary']);
        Route::delete('{address}/itinerary', [AddressController::class, 'deleteItinerary']);
    });

    // Intersections (carrefours)
    Route::prefix('intersections')->group(function () {
        Route::get('nearby', [IntersectionController::class, 'nearby']);
        Route::post('/', [IntersectionController::class, 'store']);
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
        Route::get('{collection}/shared-with', [CollectionController::class, 'getSharedWith']);
        Route::delete('{collection}/share/{user}', [CollectionController::class, 'unshare']);
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

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('summary', [NotificationController::class, 'summary']);
        Route::get('recent', [NotificationController::class, 'recent']);
        Route::post('mark-read', [NotificationController::class, 'markMultipleAsRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('bulk', [NotificationController::class, 'destroyMultiple']);
        Route::get('{notification}', [NotificationController::class, 'show']);
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
    });

    // Device Tokens (Push Notifications)
    Route::prefix('device-tokens')->group(function () {
        Route::get('/', [DeviceTokenController::class, 'index']);
        Route::post('register', [DeviceTokenController::class, 'register']);
        Route::post('unregister', [DeviceTokenController::class, 'unregister']);
        Route::delete('all', [DeviceTokenController::class, 'destroyAll']);
        Route::delete('{id}', [DeviceTokenController::class, 'destroy']);
    });

    // Companies
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('plans', [CompanyController::class, 'plans']);
        Route::get('current', [CompanyController::class, 'current']);
        Route::post('switch/{company}', [CompanyController::class, 'switchCompany']);
        Route::get('{company}', [CompanyController::class, 'show']);
        Route::put('{company}', [CompanyController::class, 'update']);

        // Members
        Route::get('members', [CompanyMemberController::class, 'index']);
        Route::get('members/search', [CompanyMemberController::class, 'search']);
        Route::post('members/invite', [CompanyMemberController::class, 'invite']);
        Route::put('members/{member}/role', [CompanyMemberController::class, 'updateRole']);
        Route::delete('members/{member}', [CompanyMemberController::class, 'remove']);

        // Company Addresses
        Route::get('addresses', [CompanyAddressController::class, 'index']);
        Route::get('addresses/search', [CompanyAddressController::class, 'search']);
        Route::get('addresses/{address}', [CompanyAddressController::class, 'show']);

        // Company Documents
        Route::get('documents', [CompanyDocumentController::class, 'index']);
        Route::post('documents', [CompanyDocumentController::class, 'create']);
        Route::get('documents/usage', [CompanyDocumentController::class, 'usage']);

        // Subscription
        Route::get('subscription', [CompanySubscriptionController::class, 'show']);
        Route::post('subscription/subscribe', [CompanySubscriptionController::class, 'subscribe']);
        Route::post('subscription/renew', [CompanySubscriptionController::class, 'renew']);
        Route::post('subscription/cancel', [CompanySubscriptionController::class, 'cancel']);
        Route::post('subscription/change-plan', [CompanySubscriptionController::class, 'changePlan']);
        Route::get('subscription/payments', [CompanySubscriptionController::class, 'payments']);

        // Labels
        Route::prefix('{company}/labels')->group(function () {
            Route::get('/', [CompanyLabelController::class, 'index']);
            Route::post('/', [CompanyLabelController::class, 'store']);
            Route::get('icons', [CompanyLabelController::class, 'icons']);
            Route::post('bulk', [CompanyLabelController::class, 'bulkCreate']);
            Route::delete('bulk', [CompanyLabelController::class, 'bulkDelete']);
            Route::get('{label}', [CompanyLabelController::class, 'show']);
            Route::put('{label}', [CompanyLabelController::class, 'update']);
            Route::delete('{label}', [CompanyLabelController::class, 'destroy']);
        });

        // Zones
        Route::prefix('{company}/zones')->group(function () {
            Route::get('/', [CompanyZoneController::class, 'index']);
            Route::post('/', [CompanyZoneController::class, 'store']);
            Route::get('hierarchy', [CompanyZoneController::class, 'hierarchy']);
            Route::get('contains', [CompanyZoneController::class, 'containsPoint']);
            Route::get('in-bounds', [CompanyZoneController::class, 'inBounds']);
            Route::get('export/geojson', [CompanyZoneController::class, 'exportGeoJson']);
            Route::get('{zone}', [CompanyZoneController::class, 'show']);
            Route::put('{zone}', [CompanyZoneController::class, 'update']);
            Route::delete('{zone}', [CompanyZoneController::class, 'destroy']);
            Route::get('{zone}/children', [CompanyZoneController::class, 'children']);
            Route::get('{zone}/statistics', [CompanyZoneController::class, 'statistics']);
            Route::post('{zone}/duplicate', [CompanyZoneController::class, 'duplicate']);
        });
    });

    // Accept company invitation (outside company middleware)
    Route::post('companies/invitation/{token}/accept', [CompanyMemberController::class, 'acceptInvitation']);
});
