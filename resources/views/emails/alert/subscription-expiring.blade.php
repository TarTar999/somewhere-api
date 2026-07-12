@extends('emails.layouts.base')

@section('title', 'Votre abonnement expire bientôt')

@section('content')
    @if($isUrgent)
        <div class="info-box error" style="margin-top: 0;">
            <strong>Action urgente requise</strong><br>
            Votre abonnement expire dans {{ $daysUntilExpiration }} jour(s) !
        </div>
    @endif

    <h1>Votre abonnement expire bientôt</h1>

    <p>Bonjour {{ $userName }},</p>

    <p>
        Nous vous informons que l'abonnement de votre entreprise <strong>{{ $companyName }}</strong>
        arrive à expiration dans <strong>{{ $daysUntilExpiration }} jour(s)</strong>.
    </p>

    <div class="card">
        <p class="card-title">Détails de l'abonnement</p>
        <table class="data-table">
            <tr>
                <td class="text-muted">Entreprise</td>
                <td><strong>{{ $companyName }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Plan actuel</td>
                <td>{{ $planName }}</td>
            </tr>
            <tr>
                <td class="text-muted">Date d'expiration</td>
                <td>
                    <span class="badge {{ $isUrgent ? 'badge-error' : 'badge-warning' }}">
                        {{ $expiresAt }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $renewUrl }}" class="btn btn-success">
            Renouveler maintenant
        </a>
    </div>

    <div class="info-box warning">
        <strong>Que se passe-t-il après l'expiration ?</strong><br>
        <ul style="margin: 8px 0; padding-left: 20px;">
            <li>Vos membres ne pourront plus générer de documents</li>
            <li>L'accès aux fonctionnalités entreprise sera suspendu</li>
            <li>Vos données seront conservées pendant 30 jours</li>
        </ul>
    </div>

    <hr class="divider">

    <p class="text-muted text-small">
        Vous recevez cet email car vous êtes administrateur de l'entreprise {{ $companyName }}.
    </p>
@endsection

@section('footer')
    <p>
        Des questions sur votre abonnement ? Contactez-nous à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
