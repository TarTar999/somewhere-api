<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recu - {{ $receipt->receipt_number }}</title>
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
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4ade80;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 18px;
            color: #4ade80;
            font-weight: bold;
        }
        .receipt-badge {
            background: #4ade80;
            color: #1a1a2e;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin-top: 10px;
        }
        .receipt-info {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
        }
        .receipt-number {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
        }
        .receipt-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .verification-code {
            font-family: monospace;
            font-size: 12px;
            color: #666;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        .amount-box {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
        }
        .amount-label {
            font-size: 12px;
            color: #ccc;
            margin-bottom: 5px;
        }
        .amount-value {
            font-size: 32px;
            font-weight: bold;
            color: #4ade80;
        }
        .amount-currency {
            font-size: 16px;
            color: #4ade80;
        }
        .payment-status {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .qr-code img {
            max-width: 120px;
            margin-bottom: 10px;
        }
        .qr-caption {
            font-size: 10px;
            color: #666;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .footer .company-info {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .thank-you {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #4ade80;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($company['logo'])
                <img src="{{ $company['logo'] }}" alt="Logo" class="logo">
            @endif
            <h1>{{ $company['brand'] }}</h1>
            <div class="subtitle">RECU DE PAIEMENT</div>
            <div class="receipt-badge">PAYE</div>
        </div>

        <div class="receipt-info">
            <div class="receipt-number">Recu N° {{ $receipt->receipt_number }}</div>
            <div class="receipt-date">Date: {{ $receipt->paid_at->format('d/m/Y a H:i') }}</div>
            <div class="verification-code">Code: {{ $receipt->verification_code }}</div>
        </div>

        <div class="section">
            <div class="section-title">Client</div>
            <div class="info-row">
                <span class="info-label">Nom</span>
                <span class="info-value">{{ $user->full_name }}</span>
            </div>
            @if($user->email)
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            @endif
            @if($user->phone)
            <div class="info-row">
                <span class="info-label">Telephone</span>
                <span class="info-value">{{ $user->phone }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Description</div>
            <div class="info-row">
                <span class="info-label">Service</span>
                <span class="info-value">{{ $receipt->description }}</span>
            </div>
            @if($receipt->transaction_reference)
            <div class="info-row">
                <span class="info-label">Reference</span>
                <span class="info-value">{{ $receipt->transaction_reference }}</span>
            </div>
            @endif
            @if($receipt->payment_method)
            <div class="info-row">
                <span class="info-label">Mode de paiement</span>
                <span class="info-value">{{ $receipt->payment_method }}</span>
            </div>
            @endif
        </div>

        <div class="amount-box">
            <div class="amount-label">MONTANT PAYE</div>
            <div class="amount-value">{{ number_format($receipt->amount, 0, ',', ' ') }}</div>
            <div class="amount-currency">{{ $receipt->currency }}</div>
            <div class="payment-status">PAIEMENT CONFIRME</div>
        </div>

        <div class="thank-you">
            Merci pour votre confiance !
        </div>

        <div class="qr-section">
            <div class="qr-code">
                <img src="{{ $qrCodeUrl }}" alt="QR Code de verification">
            </div>
            <div class="qr-caption">
                Scannez pour verifier l'authenticite de ce recu
            </div>
        </div>

        <div class="footer">
            <p class="company-info">{{ $company['name'] }}</p>
            <p>{{ $company['address'] }}</p>
            <p>{{ $company['phone'] }} | {{ $company['email'] }}</p>
            @if($company['rccm'])
            <p>RCCM: {{ $company['rccm'] }} | NIU: {{ $company['niu'] }}</p>
            @endif
            <p style="margin-top: 10px; font-style: italic;">
                Ce document est un recu electronique genere automatiquement.
            </p>
        </div>
    </div>
</body>
</html>
