<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proof of Location - {{ $document_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #666;
        }
        .document-info {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .document-info .doc-number {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            color: #666;
            font-size: 11px;
        }
        .info-value {
            display: table-cell;
            width: 60%;
            font-weight: bold;
        }
        .validity-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .validity-box.expired {
            background: #ffebee;
        }
        .validity-box .status {
            font-size: 14px;
            font-weight: bold;
            color: #2e7d32;
        }
        .validity-box.expired .status {
            color: #c62828;
        }
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .qr-code {
            margin-bottom: 10px;
        }
        .qr-caption {
            font-size: 10px;
            color: #666;
        }
        .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(102, 126, 234, 0.1);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">SOMEWHERE</div>

    <div class="container">
        <div class="header">
            <h1>SOMEWHERE</h1>
            <div class="subtitle">Proof of Location - Attestation de domicile</div>
        </div>

        <div class="document-info">
            <div class="doc-number">Document N° {{ $document_number }}</div>
        </div>

        <div class="validity-box {{ $proof->isExpired() ? 'expired' : '' }}">
            <div class="status">
                @if($proof->isActive())
                    ✓ DOCUMENT VALIDE
                @else
                    ✗ DOCUMENT EXPIRÉ
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">TITULAIRE</div>
            <div class="info-row">
                <span class="info-label">Nom complet</span>
                <span class="info-value">{{ $user->first_name }} {{ $user->last_name }}</span>
            </div>
            @if($user->cni_number)
            <div class="info-row">
                <span class="info-label">N° CNI</span>
                <span class="info-value">{{ $user->cni_number }}</span>
            </div>
            @endif
            @if($user->nui_number)
            <div class="info-row">
                <span class="info-label">N° NUI</span>
                <span class="info-value">{{ $user->nui_number }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">ADRESSE DE RÉSIDENCE</div>
            <div class="info-row">
                <span class="info-label">Adresse Somewhere</span>
                <span class="info-value">{{ $address->sw_address }}</span>
            </div>
            @if($address->display_name)
            <div class="info-row">
                <span class="info-label">Nom d'affichage</span>
                <span class="info-value">{{ $address->display_name }}</span>
            </div>
            @endif
            @if($address->quarter)
            <div class="info-row">
                <span class="info-label">Quartier</span>
                <span class="info-value">{{ $address->quarter }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}</span>
            </div>
            @endif
            @if($address->lieu_dit)
            <div class="info-row">
                <span class="info-label">Lieu-dit</span>
                <span class="info-value">{{ $address->lieu_dit }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Coordonnées GPS</span>
                <span class="info-value">{{ number_format($address->latitude, 6) }}, {{ number_format($address->longitude, 6) }}</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">VALIDITÉ</div>
            <div class="info-row">
                <span class="info-label">Date d'émission</span>
                <span class="info-value">{{ $proof->issued_at->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Valide jusqu'au</span>
                <span class="info-value">{{ $proof->expires_at->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-code">
                {!! $qr_code !!}
            </div>
            <div class="qr-caption">
                Scannez ce QR code pour vérifier l'authenticité de ce document
            </div>
        </div>

        <div class="footer">
            <p>Ce document est généré électroniquement par Somewhere et peut être vérifié en ligne.</p>
            <p>{{ config('app.company_name') }} - {{ config('app.company_address') }}</p>
            <p>Généré le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>
