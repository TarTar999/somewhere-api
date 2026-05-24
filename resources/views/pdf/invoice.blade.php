<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
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
            height: 45px;
            width: 45px;
            vertical-align: middle;
        }
        .brand-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            vertical-align: middle;
            padding-left: 8px;
        }
        .header-qr {
            background: #f8fafc;
            padding: 3px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .header-qr img {
            width: 45px;
            height: 45px;
        }

        /* Document title bar */
        .document-title-bar {
            background: linear-gradient(90deg, #1a1a2e 0%, #0f172a 100%);
            padding: 8px 20px;
            text-align: center;
            margin-bottom: 25px;
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
            padding: 20px 20px 12px 20px;
        }

        /* Document info bar */
        .doc-info-bar {
            width: 100%;
            margin-bottom: 10px;
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
        .doc-info-value.warning {
            color: #d97706;
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
        }
        .section-title {
            font-size: 8px;
            font-weight: bold;
            color: #1a1a2e;
            padding-bottom: 4px;
            margin-bottom: 5px;
            border-bottom: 2px solid #4ade80;
            text-transform: uppercase;
        }
        .section-name {
            font-size: 10px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        .section-detail {
            font-size: 8px;
            color: #475569;
            margin-bottom: 2px;
        }

        /* Invoice table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .invoice-table th {
            background: linear-gradient(90deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 6px 10px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
        }
        .invoice-table th:last-child {
            text-align: right;
        }
        .invoice-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 9px;
        }
        .invoice-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .item-description {
            font-weight: 600;
        }
        .item-details {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .totals-table td:first-child {
            width: 60%;
        }
        .totals-table td:last-child {
            width: 40%;
        }
        .totals-box {
            background: #f8fafc;
            border-radius: 4px;
            overflow: hidden;
        }
        .totals-box table {
            width: 100%;
        }
        .totals-box td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
        }
        .totals-box tr:last-child td {
            border-bottom: none;
        }
        .totals-box .label {
            color: #64748b;
        }
        .totals-box .value {
            text-align: right;
            font-weight: 600;
            color: #1e293b;
        }
        .grand-total td {
            background: linear-gradient(90deg, #1a1a2e 0%, #16213e 100%);
            padding: 8px 10px;
        }
        .grand-total .label {
            color: #94a3b8;
            font-size: 10px;
        }
        .grand-total .value {
            color: #4ade80;
            font-size: 13px;
            font-weight: bold;
        }

        /* Payment/Notes box */
        .info-box {
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .info-box.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #4ade80;
        }
        .info-box.warning {
            background: #fefce8;
            border: 1px solid #fde047;
        }
        .info-box-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .info-box.success .info-box-title {
            color: #166534;
        }
        .info-box.warning .info-box-title {
            color: #854d0e;
        }
        .info-box-content {
            font-size: 8px;
        }
        .info-box.success .info-box-content {
            color: #15803d;
        }
        .info-box.warning .info-box-content {
            color: #713f12;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
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
            height: 20px;
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
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }
        .footer-thanks {
            font-size: 9px;
            color: #4ade80;
            font-weight: bold;
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
        <div class="document-title">FACTURE</div>
        <div class="document-number-line">{{ $invoice->invoice_number }}</div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Document Info Bar -->
        <div class="doc-info-bar">
            <table>
                <tr>
                    <td>
                        <div class="doc-info-label">Date emission</div>
                        <div class="doc-info-value">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Echeance</div>
                        <div class="doc-info-value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'Immediat' }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Reference</div>
                        <div class="doc-info-value">{{ $invoice->invoice_number }}</div>
                    </td>
                    <td>
                        <div class="doc-info-label">Statut</div>
                        <div class="doc-info-value {{ $invoice->isPaid() ? 'success' : 'warning' }}">
                            {{ $invoice->isPaid() ? 'PAYEE' : 'EN ATTENTE' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Two Columns: Emetteur & Client -->
        <table class="two-col-table">
            <tr>
                <td>
                    <div class="section-box">
                        <div class="section-title">Emetteur</div>
                        <div class="section-name">{{ $company['name'] }}</div>
                        <div class="section-detail">{{ $company['address'] }}</div>
                        @if($company['phone'])<div class="section-detail">Tel: {{ $company['phone'] }}</div>@endif
                        @if($company['rccm'])<div class="section-detail">RCCM: {{ $company['rccm'] }}</div>@endif
                        @if($company['niu'])<div class="section-detail">NIU: {{ $company['niu'] }}</div>@endif
                    </div>
                </td>
                <td>
                    <div class="section-box">
                        <div class="section-title">Facture a</div>
                        <div class="section-name">{{ $user->full_name }}</div>
                        @if($user->email)<div class="section-detail">{{ $user->email }}</div>@endif
                        @if($user->phone)<div class="section-detail">Tel: {{ $user->phone }}</div>@endif
                        @if($payment && $payment->proofOfLocation && $payment->proofOfLocation->address)
                        <div class="section-detail">Adresse: {{ $payment->proofOfLocation->address->sw_address }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Invoice Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Description</th>
                    <th style="width: 30%;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="item-description">{{ $invoice->description }}</div>
                        @if($payment && $payment->proofOfLocation)
                        <div class="item-details">
                            Document: {{ $payment->proofOfLocation->document_number }}
                            | Type: {{ $payment->proofOfLocation->document_type === 'proof_of_residence' ? 'Attestation de residence' : 'Plan de localisation' }}
                        </div>
                        @endif
                    </td>
                    <td>{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td></td>
                <td>
                    <div class="totals-box">
                        <table>
                            <tr>
                                <td class="label">Sous-total HT</td>
                                <td class="value">{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                            </tr>
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <td class="label">TVA ({{ $invoice->tax_rate ?? 19.25 }}%)</td>
                                <td class="value">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                            </tr>
                            @endif
                            <tr class="grand-total">
                                <td class="label">TOTAL TTC</td>
                                <td class="value">{{ number_format($invoice->total_amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Payment Info or Notes -->
        @if($invoice->isPaid())
        <div class="info-box success">
            <div class="info-box-title">Informations de paiement</div>
            <div class="info-box-content">
                Date: {{ $invoice->paid_at->format('d/m/Y a H:i') }}
                @if($payment)
                | Moyen: {{ ucfirst(str_replace('_', ' ', $payment->medium ?? 'Mobile Money')) }}
                @if($payment->external_id) | Ref: {{ $payment->external_id }}@endif
                @endif
            </div>
        </div>
        @else
        <div class="info-box warning">
            <div class="info-box-title">Modalites de paiement</div>
            <div class="info-box-content">
                Paiement par Mobile Money (MTN, Orange). Reference: <strong>{{ $invoice->invoice_number }}</strong> | Contact: {{ $company['email'] }} | {{ $company['phone'] }}
            </div>
        </div>
        @endif

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
                    <div class="sig-label">Le client</div>
                    <div class="sig-name">{{ $user->full_name }}</div>
                </td>
                <td>
                    <div class="sig-spacer"></div>
                    <div class="sig-line"></div>
                    <div class="sig-label">Pour {{ $company['brand'] }}</div>
                    <div class="sig-name">Service comptable</div>
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
                </td>
                <td class="footer-right">
                    <div class="footer-thanks">Merci!</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
