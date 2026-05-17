<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Domicile - Somewhere</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 10px;
        }
        .document-number {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }
        .qr-container {
            position: absolute;
            top: 40px;
            right: 40px;
            width: 100px;
            text-align: center;
        }
        .qr-code {
            width: 100px;
            height: 100px;
        }
        .qr-label {
            font-size: 8px;
            color: #6b7280;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 8px 0;
            font-weight: bold;
            color: #4b5563;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #1f2937;
        }
        .declaration {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .declaration-text {
            font-size: 11px;
            line-height: 1.8;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 60px auto 10px;
        }
        .signature-label {
            font-size: 10px;
            color: #6b7280;
        }
        .footer {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .sw-address {
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        {!! $qr_code !!}
        <div class="qr-label">Scanner pour vérifier</div>
    </div>

    <div class="header">
        <div class="logo">SOMEWHERE</div>
        <div class="document-title">ATTESTATION DE DOMICILE</div>
        <div class="document-number">N° {{ $document_number }}</div>
    </div>

    <div class="section">
        <div class="section-title">Informations personnelles</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $user->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $user->email }}</div>
            </div>
            @if($user->phone)
            <div class="info-row">
                <div class="info-label">Téléphone</div>
                <div class="info-value">{{ $user->phone }}</div>
            </div>
            @endif
            @if($user->cni_number)
            <div class="info-row">
                <div class="info-label">N° CNI</div>
                <div class="info-value">{{ $user->cni_number }}</div>
            </div>
            @endif
            @if($user->nui_number)
            <div class="info-row">
                <div class="info-label">N° NUI</div>
                <div class="info-value">{{ $user->nui_number }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Adresse déclarée</div>
        <div style="text-align: center; margin: 15px 0;">
            <span class="sw-address">{{ $address->sw_address }}</span>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom de l'adresse</div>
                <div class="info-value">{{ $address->display_name }}</div>
            </div>
            @if($address->quarter)
            <div class="info-row">
                <div class="info-label">Quartier</div>
                <div class="info-value">{{ $address->quarter }}</div>
            </div>
            @endif
            @if($address->sub_quarter)
            <div class="info-row">
                <div class="info-label">Sous-quartier</div>
                <div class="info-value">{{ $address->sub_quarter }}</div>
            </div>
            @endif
            @if($address->lieu_dit)
            <div class="info-row">
                <div class="info-label">Lieu-dit</div>
                <div class="info-value">{{ $address->lieu_dit }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Type d'habitation</div>
                <div class="info-value">{{ ucfirst($address->house_type ?? 'Non spécifié') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut d'occupation</div>
                <div class="info-value">{{ ucfirst($address->home_status ?? 'Non spécifié') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Coordonnées GPS</div>
                <div class="info-value">{{ number_format($address->latitude, 6) }}, {{ number_format($address->longitude, 6) }}</div>
            </div>
        </div>
    </div>

    <div class="declaration">
        <div class="declaration-text">
            Je soussigné(e), <strong>{{ $user->full_name }}</strong>, certifie sur l'honneur que les informations
            ci-dessus sont exactes et que l'adresse indiquée correspond bien à mon lieu de résidence actuel.
            <br><br>
            Cette attestation est générée par la plateforme Somewhere et peut être vérifiée en scannant
            le code QR présent sur ce document ou en utilisant l'identifiant unique <strong>{{ $address->sw_address }}</strong>.
            <br><br>
            <strong>Statut de vérification:</strong> {{ $address->verification_status === 'approved' ? 'Vérifié' : ($address->verification_status === 'pending' ? 'En attente de vérification' : 'Non vérifié') }}
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Signature du déclarant</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Date: {{ $generated_at->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="footer">
        Document généré le {{ $generated_at->format('d/m/Y à H:i') }} par Somewhere - Plateforme d'adressage intelligent<br>
        Ce document est valable pour une durée de 3 mois à compter de sa date d'émission.<br>
        Pour toute vérification: www.somewhere.app/verify/{{ $address->sw_address }}
    </div>
</body>
</html>
