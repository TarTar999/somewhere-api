<?php

namespace App\Services;

use App\Models\Address;
use App\Models\ProofOfLocation;
use App\Models\WebAccessToken;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate a QR code from raw data string
     */
    public function generate(string $data, int $size = 300): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'imageBase64' => true,
            'quietzoneSize' => 2,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    public function generateForAddress(Address $address, int $size = 300): string
    {
        $data = json_encode([
            'type' => 'somewhere_address',
            'swAddress' => $address->sw_address,
            'version' => '1.0',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'imageBase64' => true,
            'quietzoneSize' => 2,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    public function generateSvgForAddress(Address $address, int $size = 300): string
    {
        $data = json_encode([
            'type' => 'somewhere_address',
            'swAddress' => $address->sw_address,
            'version' => '1.0',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    public function parseQrData(string $qrData): ?array
    {
        // Try to decode as JSON first
        $decoded = json_decode($qrData, true);

        if ($decoded && isset($decoded['swAddress'])) {
            return [
                'sw_address' => $decoded['swAddress'],
                'type' => $decoded['type'] ?? 'unknown',
                'version' => $decoded['version'] ?? '1.0',
            ];
        }

        // If not JSON, assume it's a plain SW address
        if (str_starts_with($qrData, 'SW-')) {
            return [
                'sw_address' => $qrData,
                'type' => 'plain',
                'version' => '1.0',
            ];
        }

        return null;
    }

    public function generateSvgForProofOfLocation(ProofOfLocation $proof, int $size = 300): string
    {
        $data = json_encode([
            'type' => 'somewhere_proof_of_location',
            'documentNumber' => $proof->document_number,
            'token' => $proof->qr_code_token,
            'url' => $proof->getWebUrl(),
            'validUntil' => $proof->expires_at->toIso8601String(),
            'version' => '1.0',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    public function generateSvgForWebAccess(WebAccessToken $token, int $size = 300): string
    {
        $baseUrl = config('app.url');
        $webUrl = "{$baseUrl}/web/access/{$token->token}";

        $data = json_encode([
            'type' => 'somewhere_web_access',
            'accessType' => $token->type,
            'url' => $webUrl,
            'expiresAt' => $token->expires_at->toIso8601String(),
            'version' => '1.0',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }

    public function generatePngForWebAccess(WebAccessToken $token, int $size = 300): string
    {
        $baseUrl = config('app.url');
        $webUrl = "{$baseUrl}/web/access/{$token->token}";

        $data = json_encode([
            'type' => 'somewhere_web_access',
            'accessType' => $token->type,
            'url' => $webUrl,
            'expiresAt' => $token->expires_at->toIso8601String(),
            'version' => '1.0',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => max(1, (int) ($size / 50)),
            'imageBase64' => true,
            'quietzoneSize' => 2,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($data);
    }
}
