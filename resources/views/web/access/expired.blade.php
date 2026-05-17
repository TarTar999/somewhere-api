@extends('web.layouts.app')

@section('title', 'Accès expiré')

@section('content')
<div class="max-w-md mx-auto text-center">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-yellow-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Accès expiré</h2>
        <p class="text-gray-600 mb-6">
            Ce lien d'accès a expiré. Veuillez générer un nouveau QR code depuis l'application mobile.
        </p>
        <p class="text-sm text-gray-500">
            Les liens d'accès sont valides pendant une durée limitée pour votre sécurité.
        </p>
    </div>
</div>
@endsection
