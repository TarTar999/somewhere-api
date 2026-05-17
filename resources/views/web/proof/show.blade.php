@extends('web.layouts.app')

@section('title', 'Proof of Location - ' . $proof->document_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="gradient-bg text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Proof of Location</h2>
                    <p class="text-purple-200">Document officiel</p>
                </div>
                <div class="text-right">
                    @if($proof->isActive())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500 text-white">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Valide
                        </span>
                    @elseif($proof->isExpired())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Expiré
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-500 text-white">
                            {{ ucfirst($proof->status) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Document Number -->
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500 mb-1">Numéro de document</p>
                <p class="text-lg font-mono font-bold text-gray-800">{{ $proof->document_number }}</p>
            </div>

            <!-- Holder Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Titulaire</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-800 font-medium">{{ $user->first_name }} {{ $user->last_name }}</p>
                </div>
            </div>

            <!-- Address Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Adresse</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div>
                        <p class="text-sm text-gray-500">Adresse SW</p>
                        <p class="text-gray-800 font-mono">{{ $address->sw_address }}</p>
                    </div>
                    @if($address->display_name)
                        <div>
                            <p class="text-sm text-gray-500">Nom d'affichage</p>
                            <p class="text-gray-800">{{ $address->display_name }}</p>
                        </div>
                    @endif
                    @if($address->quarter)
                        <div>
                            <p class="text-sm text-gray-500">Quartier</p>
                            <p class="text-gray-800">{{ $address->quarter }}{{ $address->sub_quarter ? ', ' . $address->sub_quarter : '' }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Validity Dates -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 mb-1">Date d'émission</p>
                    <p class="text-gray-800 font-medium">{{ $proof->issued_at->format('d/m/Y') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 mb-1">Valide jusqu'au</p>
                    <p class="text-gray-800 font-medium {{ $proof->isExpired() ? 'text-red-600' : '' }}">
                        {{ $proof->expires_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <!-- Download Button -->
            @if(isset($canDownload) && $canDownload && $proof->isActive())
                <div class="pt-4">
                    <a href="{{ route('web.proof.download', $proof->qr_code_token) }}"
                       class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white gradient-bg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le PDF
                    </a>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 border-t">
            <p class="text-xs text-gray-500 text-center">
                Ce document est généré électroniquement par Somewhere et peut être vérifié en scannant le QR code.
            </p>
        </div>
    </div>
</div>
@endsection
