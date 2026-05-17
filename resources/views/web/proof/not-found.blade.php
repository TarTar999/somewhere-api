@extends('web.layouts.app')

@section('title', 'Document non trouvé')

@section('content')
<div class="max-w-md mx-auto text-center">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Document non trouvé</h2>
        <p class="text-gray-600 mb-6">
            Le proof of location que vous recherchez n'existe pas ou a été révoqué.
        </p>
        <p class="text-sm text-gray-500">
            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le support.
        </p>
    </div>
</div>
@endsection
