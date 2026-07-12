@extends('emails.layouts.base')

@section('title', 'Confirmation de paiement')

@section('content')
    <h1>Paiement confirmé</h1>

    <p>Bonjour {{ $userName }},</p>

    <p>
        Nous confirmons la réception de votre paiement. Voici le récapitulatif de votre transaction.
    </p>

    <div class="card">
        <p class="card-title">Détails du paiement</p>
        <table class="data-table">
            <tr>
                <td class="text-muted">Montant</td>
                <td><strong>{{ number_format($amount, 0, ',', ' ') }} {{ $currency }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Transaction</td>
                <td><code>{{ $transactionId }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Document</td>
                <td>{{ $documentType }}</td>
            </tr>
            <tr>
                <td class="text-muted">Date</td>
                <td>{{ $paidAt }}</td>
            </tr>
        </table>
    </div>

    <div class="info-box success">
        <strong>Votre document est en cours de génération</strong><br>
        Vous recevrez un email dès que votre document sera prêt au téléchargement.
    </div>

    <hr class="divider">

    <p class="text-muted text-small">
        Ce reçu fait office de preuve de paiement. Conservez-le pour vos archives.
    </p>
@endsection

@section('footer')
    <p>
        Une question sur votre paiement ? Contactez-nous à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
