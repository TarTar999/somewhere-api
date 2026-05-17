<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
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
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .company-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 11px;
            color: #666;
        }
        .invoice-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .invoice-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .client-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 10px;
        }
        .client-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .client-details {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        th:last-child {
            text-align: right;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        td:last-child {
            text-align: right;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row:last-child {
            border-bottom: none;
        }
        .totals-label {
            display: table-cell;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .grand-total .totals-label {
            font-size: 14px;
            color: #333;
        }
        .grand-total .totals-value {
            font-size: 18px;
            color: #667eea;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }
        .footer {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 5px;
        }
        .payment-info-title {
            font-weight: bold;
            color: #1565c0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-details">
                    {{ $company['address'] }}<br>
                    {{ $company['phone'] }}<br>
                    {{ $company['email'] }}
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">Date: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
                <div style="margin-top: 15px;">
                    @if($invoice->isPaid())
                        <span class="status-badge status-paid">PAYÉE</span>
                    @else
                        <span class="status-badge status-pending">EN ATTENTE</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="client-section">
            <div class="section-title">Facturé à</div>
            <div class="client-name">{{ $user->first_name }} {{ $user->last_name }}</div>
            <div class="client-details">
                {{ $user->email }}<br>
                @if($user->phone){{ $user->phone }}@endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->description }}</td>
                    <td>{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Sous-total</span>
                <span class="totals-value">{{ number_format($invoice->amount, 0, ',', ' ') }} {{ $invoice->currency }}</span>
            </div>
            @if($invoice->tax_amount > 0)
            <div class="totals-row">
                <span class="totals-label">TVA</span>
                <span class="totals-value">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} {{ $invoice->currency }}</span>
            </div>
            @endif
            <div class="grand-total">
                <div class="totals-row">
                    <span class="totals-label">Total</span>
                    <span class="totals-value">{{ number_format($invoice->total_amount, 0, ',', ' ') }} {{ $invoice->currency }}</span>
                </div>
            </div>
        </div>

        @if($invoice->isPaid())
        <div class="payment-info">
            <div class="payment-info-title">Informations de paiement</div>
            <div>
                Payée le {{ $invoice->paid_at->format('d/m/Y à H:i') }}
                @if($payment)
                    via {{ ucfirst(str_replace('_', ' ', $payment->medium ?? 'Mobile Money')) }}
                @endif
            </div>
        </div>
        @endif

        <div class="footer">
            <p>Merci pour votre confiance!</p>
            <p>{{ $company['name'] }} - {{ $company['address'] }}</p>
        </div>
    </div>
</body>
</html>
