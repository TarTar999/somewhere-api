@extends('emails.layouts.base')

@section('title', 'Document bientôt expiré')

@section('content')
    <h1>Votre document expire bientôt</h1>

    <p>Bonjour {{ $userName }},</p>

    <div class="info-box warning">
        <strong>Action requise</strong><br>
        Votre document expire dans <strong>{{ $daysLeft }} jour(s)</strong>.
        Pensez à le renouveler pour éviter toute interruption de service.
    </div>

    <div class="card">
        <p class="card-title">Document concerné</p>
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
                <td class="text-muted">Date d'expiration</td>
                <td>
                    <span class="badge badge-warning">{{ $expiresAt }}</span>
                </td>
            </tr>
        </table>
    </div>

    <p>
        Un document expiré n'est plus valide et ne peut plus être utilisé comme preuve officielle.
        Nous vous recommandons de générer un nouveau document dès maintenant.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $renewUrl }}" class="btn">
            Renouveler mon document
        </a>
    </div>

    <hr class="divider">

    <p class="text-muted text-small">
        Cet email a été envoyé automatiquement car votre document arrive à expiration.
        Si vous avez déjà renouvelé ce document, vous pouvez ignorer cet email.
    </p>
@endsection

@section('footer')
    <p>
        Besoin d'aide ? Notre équipe est disponible à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
