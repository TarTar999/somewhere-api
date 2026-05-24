<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    /**
     * Handle payment callback from Fapshi and redirect to app
     *
     * Fapshi redirects here after payment, then we redirect to the app
     * using the custom URL scheme (somewhereapp://)
     */
    public function handleCallback(Request $request)
    {
        $transactionId = $request->query('transId');
        $status = $request->query('status', 'unknown');
        $addressId = $request->query('addressId');

        // Build the deep link URL
        $scheme = config('app.deep_link_scheme', 'somewhereapp');

        $params = http_build_query([
            'transId' => $transactionId,
            'status' => $status,
            'addressId' => $addressId,
        ]);

        $deepLink = "{$scheme}://payment-callback?{$params}";

        // For development with Expo, use exp:// scheme
        if (config('app.env') === 'local' && config('app.expo_url')) {
            $expoUrl = config('app.expo_url');
            $deepLink = "{$expoUrl}/--/payment-callback?{$params}";
        }

        // Return a simple HTML page that redirects to the app
        return response()->view('payment-callback', [
            'deepLink' => $deepLink,
            'transactionId' => $transactionId,
            'status' => $status,
        ]);
    }

    /**
     * Build the callback URL for Fapshi
     */
    public static function getCallbackUrl(int $addressId): string
    {
        $baseUrl = config('app.share_url', config('app.url'));
        return "{$baseUrl}/payment/callback?addressId={$addressId}";
    }
}
