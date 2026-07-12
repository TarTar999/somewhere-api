@extends('emails.layouts.base')

@section('title', 'Mise à jour de votre vérification d\'identité')

@section('content')
    @if($isApproved)
        <h1>Vérification approuvée</h1>

        <p>Bonjour {{ $userName }},</p>

        <p>
            Excellente nouvelle ! Votre vérification d'identité a été <strong>approuvée</strong>.
        </p>

        <div class="info-box success">
            <strong>Compte vérifié</strong><br>
            Vous avez maintenant accès à l'ensemble des fonctionnalités de l'application.
        </div>
    @elseif($isRejected)
        <h1>Vérification refusée</h1>

        <p>Bonjour {{ $userName }},</p>

        <p>
            Nous sommes désolés de vous informer que votre vérification d'identité a été <strong>refusée</strong>.
        </p>

        @if($reason)
            <div class="info-box error">
                <strong>Raison du refus :</strong><br>
                {{ $reason }}
            </div>
        @endif

        <p>
            Vous pouvez soumettre une nouvelle demande de vérification en corrigeant les points mentionnés ci-dessus.
        </p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ config('app.url') }}/settings/kyc" class="btn">
                Soumettre une nouvelle demande
            </a>
        </div>
    @else
        <h1>Mise à jour de votre vérification</h1>

        <p>Bonjour {{ $userName }},</p>

        <p>
            Le statut de votre vérification d'identité est maintenant : <strong>{{ $status }}</strong>.
        </p>

        @if($reason)
            <div class="info-box">
                <strong>Information :</strong><br>
                {{ $reason }}
            </div>
        @endif
    @endif

    <hr class="divider">

    <p class="text-muted text-small">
        La vérification d'identité nous permet de sécuriser votre compte et de garantir l'authenticité de vos documents.
    </p>
@endsection

@section('footer')
    <p>
        Besoin d'aide ? Contactez-nous à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
