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
            font-size: 9px;
            color: #242935;
            line-height: 1.3;
            background: #fff;
        }

        /* Header */
        .header {
            background: #fff;
            padding: 10px 20px;
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

        /* Document title bar */
        .document-title-bar {
            background: linear-gradient(90deg, #1a1a2e 0%, #0f172a 100%);
            padding: 10px 20px;
            text-align: center;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #4ade80;
            letter-spacing: 2px;
        }
        .document-number-line {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Main content */
        .container {
            padding: 15px 20px;
        }

        /* Document info bar */
        .doc-info-bar {
            width: 100%;
            margin-bottom: 12px;
            background: #f8fafc;
            border-radius: 4px;
            overflow: hidden;
        }
        .doc-info-bar table {
            width: 100%;
        }
        .doc-info-bar td {
            padding: 6px 8px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .doc-info-bar td:last-child {
            border-right: none;
        }
        .doc-info-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .doc-info-value {
            font-size: 9px;
            font-weight: bold;
            color: #1e293b;
        }
        .doc-info-value.success {
            color: #16a34a;
        }

        /* Address box */
        .address-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #4ade80;
            border-radius: 6px;
            padding: 8px 15px;
            text-align: center;
            margin-bottom: 12px;
        }
        .sw-address {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
        .address-location {
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
        }
        .certified-check {
            display: inline-block;
            margin-left: 8px;
            width: 18px;
            height: 18px;
            background: #22c55e;
            border-radius: 50%;
            text-align: center;
            vertical-align: middle;
            position: relative;
        }
        .certified-check::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 3px;
            width: 5px;
            height: 9px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Two columns layout */
        .two-col-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .two-col-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .two-col-table td:first-child {
            padding-right: 6px;
        }
        .two-col-table td:last-child {
            padding-left: 6px;
        }

        /* Section box */
        .section-box {
            background: #f8fafc;
            border-radius: 4px;
            padding: 8px 10px;
            height: 100%;
        }
        .section-title {
            font-size: 8px;
            font-weight: bold;
            color: #1a1a2e;
            padding-bottom: 4px;
            margin-bottom: 6px;
            border-bottom: 2px solid #4ade80;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .info-label {
            font-size: 8px;
            color: #64748b;
        }
        .info-value {
            font-size: 9px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Declaration box */
        .declaration-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        .declaration-title {
            font-size: 10px;
            font-weight: bold;
            color: #1a1a2e;
            text-transform: uppercase;
            margin-bottom: 8px;
            text-align: center;
        }
        .declaration-text {
            font-size: 9px;
            color: #1e293b;
            text-align: justify;
            line-height: 1.6;
        }
        .declaration-text u {
            font-weight: bold;
        }

        /* Technical info */
        .tech-info {
            background: #f8fafc;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .tech-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .tech-label {
            display: table-cell;
            width: 35%;
            font-size: 8px;
            color: #64748b;
        }
        .tech-value {
            display: table-cell;
            width: 65%;
            font-size: 8px;
            font-weight: 600;
            color: #1e293b;
        }
        .tech-value-code {
            font-family: 'Courier New', monospace;
            font-size: 7px;
            background: #e2e8f0;
            padding: 1px 4px;
            border-radius: 2px;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }
        .sig-image {
            width: 80px;
            height: 30px;
            object-fit: contain;
            display: block;
            margin: -40px 0 -20px -40px;
        }
        .sig-line {
            border-top: 1px solid #1a1a2e;
            margin: 0 auto 4px;
            width: 120px;
        }
        .sig-spacer {
            height: 15px;
        }
        .sig-label {
            font-size: 7px;
            color: #64748b;
        }
        .sig-name {
            font-size: 8px;
            font-weight: bold;
            color: #1a1a2e;
            margin-top: 2px;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 8px 20px;
            border-top: 1px solid #e2e8f0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-table {
            width: 100%;
        }
        .footer-left {
            width: 70%;
            vertical-align: middle;
        }
        .footer-right {
            width: 30%;
            text-align: right;
            vertical-align: middle;
        }
        .footer-company {
            font-size: 8px;
            font-weight: bold;
            color: #1a1a2e;
        }
        .footer-details {
            font-size: 6px;
            color: #64748b;
            margin-top: 2px;
        }
        .footer-hash {
            font-size: 5px;
            font-family: 'Courier New', monospace;
            color: #94a3b8;
            margin-top: 2px;
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
        <div class="document-title">ATTESTATION DE RESIDENCE</div>
        <div class="document-number-line">{{ $proof->document_number }}</div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Document Info Bar -->
        <div class="doc-info-bar">
            <table>
                <tr>
                    <td>
                        <div class="doc-info-label">Emission</div>
                        <div class="doc-info-value">{{ $dates['issued'] }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Validite</div>
                        <div class="doc-info-value">{{ $dates['expires'] }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Verification</div>
                        <div class="doc-info-value">{{ $proof->verification_code }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Statut</div>
                        <div class="doc-info-value success">{{ $proof->isActive() ? 'VALIDE' : 'EXPIRE' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Address Box -->
        <div class="address-box">
            <span class="sw-address">{{ $address->sw_address }}</span>
            <span class="certified-check"></span>
            <div class="address-location">
                {{ $address->quarter ?? '' }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}
                @if($address->street && $address->street->commune_name)
                    - {{ $address->street->commune_name }}
                @endif
                , Cameroun
            </div>
        </div>

        <!-- Two Columns: Beneficiaire & Details -->
        <table class="two-col-table">
            <tr>
                <td>
                    <div class="section-box">
                        <div class="section-title">Titulaire</div>
                        <div class="info-row">
                            <span class="info-label">Nom complet: </span>
                            <span class="info-value">{{ $user->full_name }}</span>
                        </div>
                        @if($user->phone)
                        <div class="info-row">
                            <span class="info-label">Telephone: </span>
                            <span class="info-value">{{ $user->phone }}</span>
                        </div>
                        @endif
                        @if($user->nui_number)
                        <div class="info-row">
                            <span class="info-label">NUI: </span>
                            <span class="info-value">{{ $user->nui_number }}</span>
                        </div>
                        @elseif($user->cni_number)
                        <div class="info-row">
                            <span class="info-label">CNI: </span>
                            <span class="info-value">{{ $user->cni_number }}</span>
                        </div>
                        @endif
                        @if($user->birth_date)
                        <div class="info-row">
                            <span class="info-label">Ne(e) le: </span>
                            <span class="info-value">{{ $user->birth_date->format('d/m/Y') }}</span>
                        </div>
                        @endif
                        @if($user->birth_place)
                        <div class="info-row">
                            <span class="info-label">A: </span>
                            <span class="info-value">{{ $user->birth_place }}</span>
                        </div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="section-box">
                        <div class="section-title">Details du domicile</div>
                        @if($address->lieu_dit)
                        <div class="info-row">
                            <span class="info-label">Lieu-dit: </span>
                            <span class="info-value">{{ $address->lieu_dit }}</span>
                        </div>
                        @endif
                        @if($address->house_type)
                        <div class="info-row">
                            <span class="info-label">Type: </span>
                            <span class="info-value">{{ ucfirst($address->house_type) }}</span>
                        </div>
                        @endif
                        @if($address->home_status)
                        <div class="info-row">
                            <span class="info-label">Statut: </span>
                            <span class="info-value">{{ ucfirst($address->home_status) }}</span>
                        </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">Coordonnees: </span>
                            <span class="info-value">{{ number_format($address->latitude, 5) }}, {{ number_format($address->longitude, 5) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fiabilite: </span>
                            <span class="info-value">{{ $score }}/100</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Declaration -->
        <div class="declaration-box">
            <div class="declaration-title">Declaration sur l'honneur</div>
            <div class="declaration-text">
                Je soussigne(e), <u>{{ $user->full_name }}</u>
                @if($user->nui_number)
                    , NUI <u>{{ $user->nui_number }}</u>
                @elseif($user->cni_number)
                    , CNI <u>{{ $user->cni_number }}</u>
                @endif
                , declare sur l'honneur resider
                @if($address->home_status)
                    en qualite de <u>{{ ucfirst($address->home_status) }}</u>
                @endif
                a l'adresse <u>{{ $address->sw_address }}</u>, {{ $address->quarter ?? '' }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}@if($address->street && $address->street->commune_name), {{ $address->street->commune_name }}@endif, Cameroun.
                <br><br>
                Cette declaration est faite pour servir et valoir ce que de droit. Je certifie que les informations fournies sont exactes et conformes a la realite.
            </div>
        </div>

        <!-- Technical Info -->
        <div class="tech-info">
            <div class="section-title" style="margin-bottom: 6px;">Informations techniques</div>
            <div class="tech-row">
                <span class="tech-label">Document N°</span>
                <span class="tech-value"><span class="tech-value-code">{{ $proof->document_number }}</span></span>
            </div>
            <div class="tech-row">
                <span class="tech-label">Code verification</span>
                <span class="tech-value"><span class="tech-value-code">{{ $proof->verification_code }}</span></span>
            </div>
            <div class="tech-row">
                <span class="tech-label">Empreinte SHA-256</span>
                <span class="tech-value"><span class="tech-value-code" style="font-size: 6px;">{{ substr($documentHash, 0, 32) }}...</span></span>
            </div>
        </div>

        <!-- Signatures -->
        <table class="signatures-table">
            <tr>
                <td>
                    @if($userSignature)
                        <img src="{{ $userSignature }}" alt="Signature" class="sig-image">
                    @else
                        <div class="sig-spacer"></div>
                    @endif
                    <div class="sig-line"></div>
                    <div class="sig-label">Signature du declarant</div>
                    <div class="sig-name">{{ $user->full_name }}</div>
                </td>
                <td>
                    <div class="sig-spacer"></div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Fait a Douala, le {{ $dates['today'] }}</div>
                    <div class="sig-name">Pour {{ $company['brand'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    <div class="footer-company">{{ $company['name'] }} - {{ $company['brand'] }}</div>
                    <div class="footer-details">{{ $company['address'] }} | RCCM: {{ $company['rccm'] }} | NIU: {{ $company['niu'] }}</div>
                    <div class="footer-hash">Hash: {{ substr($documentHash, 0, 40) }}...</div>
                </td>
                <td class="footer-right">
                    <div class="verification-url">
                        Verifiez: {{ config('app.url') }}/verify/{{ $proof->verification_code }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
