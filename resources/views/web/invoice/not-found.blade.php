@extends('web.layouts.app')

@section('title', 'Facture non trouvée')

@section('content')
<div class="max-w-md mx-auto text-center">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Facture non trouvée</h2>
        <p class="text-gray-600">
            La facture que vous recherchez n'existe pas ou n'est plus accessible.
        </p>
    </div>
</div>
@endsection
