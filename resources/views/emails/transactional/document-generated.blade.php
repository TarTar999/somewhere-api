@extends('emails.layouts.base')

@section('title', 'Votre document est prêt')

@section('content')
    <h1>Votre document est prêt !</h1>

    <p>Bonjour {{ $userName }},</p>

    <p>
        Nous avons le plaisir de vous informer que votre document a été généré avec succès
        et est maintenant disponible au téléchargement.
    </p>

    <div class="card">
        <p class="card-title">Détails du document</p>
        <table class="data-table">
            <tr>
                <td class="text-muted">Type</td>
                <td><strong>{{ $documentType }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Numéro</td>
                <td><code>{{ $documentNumber }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Valide jusqu'au</td>
                <td>{{ $expiresAt }}</td>
            </tr>
            <tr>
                <td class="text-muted">Code de vérification</td>
                <td><code>{{ $verificationCode }}</code></td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $downloadUrl }}" class="btn">
            Télécharger le document
        </a>
    </div>

    <div class="info-box success">
        <strong>Comment vérifier l'authenticité ?</strong><br>
        Utilisez le code de vérification ci-dessus ou scannez le QR code présent sur le document
        pour confirmer son authenticité.
    </div>

    <hr class="divider">

    <p class="text-muted text-small">
        Ce document est valable {{ config('documents.validity_months') }} mois à compter de sa date d'émission.
        Passé ce délai, vous devrez générer un nouveau document.
    </p>
@endsection

@section('footer')
    <p>
        Vous avez des questions ? Contactez notre support à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
