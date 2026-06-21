<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }} - {{ $proof->document_number }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #242935;
            line-height: 1.4;
            background: #fff;
        }

        /* Header with white background */
        .header {
            background: #fff;
            padding: 12px 25px;
            border-bottom: 2px solid #e2e8f0;
        }
        .header-table {
            width: 100%;
        }
        .logo {
            height: 50px;
            width: 50px;
            vertical-align: middle;
        }
        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            vertical-align: middle;
            padding-left: 10px;
        }
        /* Document title bar below header */
        .document-title-bar {
            background: linear-gradient(90deg, #1a1a2e 0%, #0f172a 100%);
            padding: 10px 25px;
            text-align: center;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #4ade80;
            letter-spacing: 2px;
        }
        .document-number-line {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }
        .header-qr {
            background: #f8fafc;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .header-qr img {
            width: 50px;
            height: 50px;
        }

        /* Main content */
        .container {
            padding: 15px 25px;
        }

        /* Document info bar */
        .doc-info-bar {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            background: #f8fafc;
            border-radius: 6px;
            overflow: hidden;
        }
        .doc-info-item {
            display: table-cell;
            padding: 8px 10px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .doc-info-item:last-child {
            border-right: none;
        }
        .doc-info-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .doc-info-value {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
        }
        .doc-info-value.success {
            color: #16a34a;
        }

        /* Address box - compact to match doc-info-bar height */
        .address-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #4ade80;
            border-radius: 6px;
            padding: 6px 15px;
            text-align: center;
            margin-bottom: 10px;
            display: table;
            width: 100%;
        }
        .address-content {
            display: table-cell;
            vertical-align: middle;
        }
        .sw-address {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            display: inline;
        }
        .address-location {
            font-size: 10px;
            color: #475569;
            display: inline;
            margin-left: 10px;
        }
        .certified-check {
            display: inline-block;
            margin-left: 8px;
            width: 20px;
            height: 20px;
            background: #22c55e;
            border-radius: 50%;
            text-align: center;
            vertical-align: middle;
            position: relative;
        }
        .certified-check::after {
            content: '';
            position: absolute;
            left: 7px;
            top: 4px;
            width: 5px;
            height: 9px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Two columns */
        .two-columns {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .column:last-child {
            padding-right: 0;
            padding-left: 10px;
        }

        /* Section */
        .section {
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1a1a2e;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 2px solid #4ade80;
        }

        /* Info rows */
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .info-label {
            display: table-cell;
            width: 45%;
            color: #64748b;
            font-size: 9px;
            padding: 2px 0;
        }
        .info-value {
            display: table-cell;
            width: 55%;
            font-weight: 600;
            color: #1e293b;
            font-size: 9px;
            padding: 2px 0;
        }

        /* Map section - larger */
        .map-section {
            margin-bottom: 10px;
        }
        .map-container {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .map-image {
            width: 100%;
            height: 300px;
            display: block;
            object-fit: cover;
        }
        .coordinates-bar {
            background: #1a1a2e;
            color: #fff;
            padding: 5px 10px;
            font-size: 9px;
            font-family: 'Courier New', monospace;
        }

        /* Itinerary */
        .itinerary-box {
            margin-top: 6px;
            padding: 6px 10px;
            background: #f0fdf4;
            border-radius: 5px;
            border-left: 3px solid #4ade80;
        }
        .itinerary-title {
            font-size: 8px;
            font-weight: bold;
            color: #166534;
            margin-bottom: 2px;
        }
        .itinerary-detail {
            font-size: 8px;
            color: #15803d;
        }

        /* Score section - compact */
        .score-section {
            background: #f8fafc;
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 8px;
            display: table;
            width: 100%;
        }
        .score-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }
        .score-right {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
        }
        .score-title {
            font-weight: bold;
            color: #1a1a2e;
            font-size: 8px;
            display: inline;
        }
        .score-value {
            font-size: 11px;
            font-weight: bold;
            color: #4ade80;
            margin-left: 8px;
        }
        .score-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            display: inline-block;
            width: 80px;
            vertical-align: middle;
            margin-left: 8px;
        }
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #4ade80 0%, #22c55e 100%);
            border-radius: 2px;
        }
        .score-details {
            font-size: 7px;
            color: #64748b;
        }
        .score-item {
            display: inline-block;
            margin-right: 6px;
        }
        .score-check {
            color: #4ade80;
        }
        .score-uncheck {
            color: #cbd5e1;
        }

        /* Signature section */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-image {
            width: 80px;
            height: 30px;
            object-fit: contain;
            display: block;
            margin: -40px 0 -20px -40px;
        }
        .signature-spacer {
            height: 10px;
        }
        .signature-line {
            border-top: 1px solid #1a1a2e;
            width: 130px;
            margin: 0 auto 5px;
        }
        .signature-label {
            font-size: 8px;
            color: #64748b;
        }
        .signature-name {
            font-size: 9px;
            font-weight: bold;
            color: #1a1a2e;
            margin-top: 2px;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 10px 25px;
            border-top: 1px solid #e2e8f0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-content {
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            width: 70%;
            vertical-align: middle;
        }
        .footer-right {
            display: table-cell;
            width: 30%;
            text-align: right;
            vertical-align: middle;
        }
        .footer-company {
            font-size: 9px;
            font-weight: bold;
            color: #1a1a2e;
        }
        .footer-details {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }
        .footer-hash {
            font-size: 6px;
            font-family: 'Courier New', monospace;
            color: #94a3b8;
            margin-top: 3px;
            word-break: break-all;
        }
        .verification-url {
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    @if($company['logo'])
                        <img src="{{ $company['logo'] }}" alt="Logo" class="logo">
                    @endif
                    <span class="brand-name">SomeWhere App</span>
                </td>
                <td style="width: 50%; text-align: right;">
                    @if($qrCodeUrl)
                    <div class="header-qr">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code">
                    </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Document Title Bar -->
    <div class="document-title-bar">
        <div class="document-title">PLAN DE LOCALISATION</div>
        <div class="document-number-line">{{ $proof->document_number }}</div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Document Info Bar -->
        <div class="doc-info-bar">
            <div class="doc-info-item">
                <div class="doc-info-label">Emission</div>
                <div class="doc-info-value">{{ $dates['issued'] }}</div>
            </div>
            <div class="doc-info-item">
                <div class="doc-info-label">Validite</div>
                <div class="doc-info-value">{{ $dates['expires'] }}</div>
            </div>
            <div class="doc-info-item">
                <div class="doc-info-label">Statut</div>
                <div class="doc-info-value success">{{ $proof->isActive() ? 'VALIDE' : 'EXPIRE' }}</div>
            </div>
        </div>

        <!-- Address Box - Compact single line -->
        <div class="address-box">
            <div class="address-content">
                <span class="sw-address">{{ $address->sw_address }}</span>
                <span class="address-location">
                    {{ $address->quarter ?? '' }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}
                    @if($address->street && $address->street->display_name)
                        - {{ $address->street->display_name }}
                    @endif
                </span>
                <span class="certified-check"></span>
            </div>
        </div>

        <!-- Map Section - Positioned Higher -->
        <div class="map-section">
            <div class="section-title">CARTE DE LOCALISATION</div>
            <div class="map-container">
                @if($mapImage)
                    <img src="{{ $mapImage }}" alt="Carte" class="map-image">
                @else
                    <div style="height: 300px; text-align: center; padding-top: 130px; color: #94a3b8;">
                        Carte non disponible
                    </div>
                @endif
                @if($address->latitude && $address->longitude)
                <div class="coordinates-bar">
                    GPS: {{ number_format($address->latitude, 6) }}, {{ number_format($address->longitude, 6) }}
                    @if($itinerary)
                    | Itineraire: {{ $itinerary['distanceFormatted'] ?? 'N/A' }}
                    @endif
                </div>
                @endif
            </div>
            @if($itinerary)
            <div class="itinerary-box">
                <div class="itinerary-title">ITINERAIRE D'ACCES ({{ $itinerary['pointsCount'] }} points)</div>
                <div class="itinerary-detail">
                    @if($itinerary['destinationStreet'])
                    <strong>Depuis:</strong> {{ $itinerary['destinationStreet'] }}
                    @endif
                    @if($itinerary['distance'])
                    | <strong>Distance:</strong> {{ $itinerary['distanceFormatted'] }}
                    @endif
                    @if($itinerary['description'])
                    | {{ $itinerary['description'] }}
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Two Columns: User Info & Location Details -->
        <div class="two-columns">
            <div class="column">
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
                    @if($user->nui_number)
                    <div class="info-row">
                        <span class="info-label">N° NUI</span>
                        <span class="info-value">{{ $user->nui_number }}</span>
                    </div>
                    @elseif($user->cni_number)
                    <div class="info-row">
                        <span class="info-label">N° CNI</span>
                        <span class="info-value">{{ $user->cni_number }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="column">
                <div class="section">
                    <div class="section-title">DETAILS LOCALISATION</div>
                    @if($address->lieu_dit)
                    <div class="info-row">
                        <span class="info-label">Lieu-dit</span>
                        <span class="info-value">{{ $address->lieu_dit }}</span>
                    </div>
                    @endif
                    @if($address->house_type)
                    <div class="info-row">
                        <span class="info-label">Type habitation</span>
                        <span class="info-value">{{ ucfirst($address->house_type) }}</span>
                    </div>
                    @endif
                    @if($address->home_status)
                    <div class="info-row">
                        <span class="info-label">Statut occupation</span>
                        <span class="info-value">{{ ucfirst($address->home_status) }}</span>
                    </div>
                    @endif
                    @if($address->verification_status === 'approved')
                    <div class="info-row">
                        <span class="info-label">Verification</span>
                        <span class="info-value">Verifiee</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reliability Score - Compact single line -->
        <div class="score-section">
            <div class="score-left">
                <span class="score-title">INDICE DE FIABILITE</span>
                <span class="score-value">{{ $score }}/100</span>
                <span class="score-bar">
                    <span class="score-fill" style="width: {{ $score }}%;"></span>
                </span>
            </div>
            <div class="score-right">
                <div class="score-details">
                    <span class="score-item"><span class="{{ $user->phone ? 'score-check' : 'score-uncheck' }}">{{ $user->phone ? '✓' : '○' }}</span> Tel</span>
                    <span class="score-item"><span class="{{ ($address->latitude && $address->longitude) ? 'score-check' : 'score-uncheck' }}">{{ ($address->latitude && $address->longitude) ? '✓' : '○' }}</span> GPS</span>
                    <span class="score-item"><span class="{{ $address->verification_status === 'approved' ? 'score-check' : 'score-uncheck' }}">{{ $address->verification_status === 'approved' ? '✓' : '○' }}</span> Verif</span>
                    <span class="score-item"><span class="{{ ($user->nui_number || $user->cni_number) ? 'score-check' : 'score-uncheck' }}">{{ ($user->nui_number || $user->cni_number) ? '✓' : '○' }}</span> ID</span>
                    <span class="score-item"><span class="{{ $userSignature ? 'score-check' : 'score-uncheck' }}">{{ $userSignature ? '✓' : '○' }}</span> Sign</span>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                @if($userSignature)
                    <img src="{{ $userSignature }}" alt="Signature" class="signature-image">
                @else
                    <div class="signature-spacer"></div>
                @endif
                <div class="signature-line"></div>
                <div class="signature-label">Signature du beneficiaire</div>
                <div class="signature-name">{{ $user->full_name }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-spacer"></div>
                <div class="signature-line"></div>
                <div class="signature-label">Pour {{ $company['brand'] }}</div>
                <div class="signature-name">Service de certification</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-company">{{ $company['name'] }} - {{ $company['brand'] }}</div>
                <div class="footer-details">
                    {{ $company['address'] }} | RCCM: {{ $company['rccm'] }} | NIU: {{ $company['niu'] }}
                </div>
                <div class="footer-hash">Hash: {{ substr($documentHash, 0, 40) }}...</div>
            </div>
            <div class="footer-right">
                <div class="verification-url">
                    Verifiez: {{ config('app.url') }}/verify/{{ $proof->verification_code }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
