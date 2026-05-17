<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plan de Localisation - {{ $proof->document_number }}</title>
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
            border-bottom: 3px solid #4ade80;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 16px;
            color: #4ade80;
            font-weight: bold;
        }
        .header .brand {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .document-info {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            color: #fff;
        }
        .document-info .doc-number {
            font-size: 14px;
            font-weight: bold;
            color: #4ade80;
        }
        .document-info .verification {
            font-size: 12px;
            margin-top: 8px;
            color: #ccc;
        }
        .document-info .verification-code {
            font-family: monospace;
            font-size: 14px;
            color: #fff;
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #4ade80;
            display: inline-block;
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
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4ade80;
        }
        .validity-box.expired {
            background: #ffebee;
            border-left-color: #ef5350;
        }
        .validity-box .status {
            font-size: 14px;
            font-weight: bold;
            color: #2e7d32;
        }
        .validity-box.expired .status {
            color: #c62828;
        }
        .validity-dates {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .address-highlight {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 25px;
            border: 2px dashed #4ade80;
        }
        .sw-address {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a2e;
            font-family: monospace;
        }
        .address-location {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .qr-code img {
            max-width: 150px;
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
        .footer .company-info {
            font-weight: bold;
            color: #666;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(74, 222, 128, 0.08);
            font-weight: bold;
            white-space: nowrap;
        }
        .price-tag {
            background: #4ade80;
            color: #1a1a2e;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="watermark">SOMEWHERE</div>

    <div class="container">
        <div class="header">
            @if($company['logo'])
                <img src="{{ $company['logo'] }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $company['brand'] }}</h1>
            <div class="subtitle">PLAN DE LOCALISATION</div>
            <div class="brand">{{ $company['name'] }}</div>
        </div>

        <div class="document-info">
            <div class="doc-number">Document N° {{ $proof->document_number }}</div>
            <div class="verification">
                Code de verification:
                <div class="verification-code">{{ $proof->verification_code }}</div>
            </div>
        </div>

        <div class="validity-box {{ $proof->isExpired() ? 'expired' : '' }}">
            <div class="status">
                @if($proof->isActive())
                    ✓ DOCUMENT VALIDE
                @else
                    ✗ DOCUMENT EXPIRE
                @endif
            </div>
            <div class="validity-dates">
                Emis le {{ $proof->issued_at->format('d/m/Y') }} - Valide jusqu'au {{ $proof->expires_at->format('d/m/Y') }}
            </div>
        </div>

        <div class="address-highlight">
            <div class="sw-address">{{ $address->sw_address }}</div>
            <div class="address-location">
                {{ $address->quarter }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}
                @if($address->street && $address->street->display_name)
                    <br>{{ $address->street->display_name }}
                @endif
            </div>
            <div class="price-tag">{{ number_format($proof->price, 0, ',', ' ') }} XAF</div>
        </div>

        <div class="section">
            <div class="section-title">BENEFICIAIRE</div>
            <div class="info-row">
                <span class="info-label">Nom complet</span>
                <span class="info-value">{{ $user->full_name }}</span>
            </div>
            @if($user->phone)
            <div class="info-row">
                <span class="info-label">Telephone</span>
                <span class="info-value">{{ $user->phone }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">LOCALISATION</div>
            <div class="info-row">
                <span class="info-label">Adresse SomeWhere</span>
                <span class="info-value">{{ $address->sw_address }}</span>
            </div>
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
                <span class="info-label">Coordonnees GPS</span>
                <span class="info-value">{{ number_format($address->latitude, 6) }}, {{ number_format($address->longitude, 6) }}</span>
            </div>
            @if($address->street)
            <div class="info-row">
                <span class="info-label">Rue</span>
                <span class="info-value">{{ $address->street->display_name }} (Code: {{ $address->street->code }})</span>
            </div>
            @endif
        </div>

        <div class="qr-section">
            <div class="qr-code">
                <img src="{{ $qrCodeUrl }}" alt="QR Code de verification">
            </div>
            <div class="qr-caption">
                Scannez ce QR code pour verifier l'authenticite de ce document<br>
                ou visitez {{ config('app.url') }}/verify/{{ $proof->verification_code }}
            </div>
        </div>

        <div class="footer">
            <p class="company-info">{{ $company['name'] }} - {{ $company['brand'] }}</p>
            <p>{{ $company['address'] }} | {{ $company['phone'] }} | {{ $company['email'] }}</p>
            @if($company['rccm'])
            <p>RCCM: {{ $company['rccm'] }} | NIU: {{ $company['niu'] }}</p>
            @endif
            <p style="margin-top: 5px;">Document genere le {{ now()->format('d/m/Y a H:i') }}</p>
        </div>
    </div>
</body>
</html>
