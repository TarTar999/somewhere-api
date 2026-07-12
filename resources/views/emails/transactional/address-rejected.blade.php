@extends('emails.layouts.base')

@section('title', 'Adresse non validée')

@section('content')
    <h1>Adresse non validée</h1>

    <p>Bonjour {{ $userName }},</p>

    <p>
        Nous sommes désolés de vous informer que votre demande de validation d'adresse n'a pas été acceptée.
    </p>

    <div class="card">
        <p class="card-title">Adresse concernée</p>
        <table class="data-table">
            <tr>
                <td class="text-muted">Adresse SomeWhere</td>
                <td><strong>{{ $swAddress }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Nom</td>
                <td>{{ $displayName }}</td>
            </tr>
        </table>
    </div>

    @if($reason)
        <div class="info-box error">
            <strong>Raison du refus :</strong><br>
            {{ $reason }}
        </div>
    @endif

    <p>
        Vous pouvez corriger les informations et soumettre une nouvelle demande de validation.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ config('app.url') }}" class="btn">
            Modifier mon adresse
        </a>
    </div>

    <hr class="divider">

    <p class="text-muted text-small">
        Si vous pensez qu'il s'agit d'une erreur, n'hésitez pas à contacter notre support.
    </p>
@endsection

@section('footer')
    <p>
        Besoin d'aide ? Contactez-nous à
        <a href="mailto:{{ config('documents.company.support_email') }}">{{ config('documents.company.support_email') }}</a>
    </p>
@endsection
