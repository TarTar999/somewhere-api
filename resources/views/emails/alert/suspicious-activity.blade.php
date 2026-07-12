@extends('emails.layouts.base')

@section('title', 'Alerte de sécurité')

@section('content')
    <div class="info-box error" style="margin-top: 0;">
        <strong>Alerte de sécurité</strong><br>
        Une activité suspecte a été détectée sur votre compte.
    </div>

    <h1>Activité suspecte détectée</h1>

    <p>Bonjour {{ $userName }},</p>

    <p>
        Nous avons détecté une activité inhabituelle sur votre compte. Pour votre sécurité,
        nous vous recommandons de vérifier cette activité et de prendre les mesures nécessaires.
    </p>

    <div class="card">
        <p class="card-title">Détails de l'activité</p>
        <table class="data-table">
            <tr>
                <td class="text-muted">Type d'activité</td>
                <td><strong>{{ $activityType }}</strong></td>
            </tr>
            @if($description)
            <tr>
                <td class="text-muted">Description</td>
                <td>{{ $description }}</td>
            </tr>
            @endif
            <tr>
                <td class="text-muted">Adresse IP</td>
                <td><code>{{ $ipAddress }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Appareil</td>
                <td class="text-small">{{ Str::limit($userAgent, 50) }}</td>
            </tr>
            <tr>
                <td class="text-muted">Date et heure</td>
                <td>{{ $occurredAt }}</td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $securityUrl }}" class="btn">
            Vérifier mon compte
        </a>
    </div>

    <div class="info-box warning">
        <strong>Actions recommandées :</strong><br>
        <ul style="margin: 8px 0; padding-left: 20px;">
            <li>Changez votre mot de passe si vous ne reconnaissez pas cette activité</li>
            <li>Vérifiez les appareils connectés à votre compte</li>
            <li>Activez l'authentification à deux facteurs si ce n'est pas déjà fait</li>
        </ul>
    </div>

    <p>
        <strong>Ce n'était pas vous ?</strong> Contactez immédiatement notre support pour sécuriser votre compte.
    </p>

    <hr class="divider">

    <p class="text-muted text-small">
        Cet email a été envoyé automatiquement par notre système de sécurité.
        Vous ne pouvez pas vous désabonner de ces alertes de sécurité.
    </p>
@endsection

@section('footer')
    <p>
        Besoin d'aide urgente ? Contactez-nous immédiatement à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
