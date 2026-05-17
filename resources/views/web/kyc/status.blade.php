@extends('web.layouts.app')

@section('title', 'Statut KYC')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="gradient-bg text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Vérification d'identité (KYC)</h2>
                    <p class="text-purple-200">{{ $user->first_name }} {{ $user->last_name }}</p>
                </div>
                <div class="text-right">
                    @if($kyc && $kyc->isApproved())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500 text-white">
                            Vérifié
                        </span>
                    @elseif($kyc && $kyc->status === 'in_review')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500 text-white">
                            En cours de vérification
                        </span>
                    @elseif($kyc && $kyc->isRejected())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-500 text-white">
                            Rejeté
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-500 text-white">
                            En attente
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            @if($kyc)
                <!-- Progress -->
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progression</span>
                        <span class="text-sm font-medium text-gray-700">{{ $kyc->getCompletionPercentage() }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ $kyc->getCompletionPercentage() }}%"></div>
                    </div>
                </div>

                <!-- Documents Status -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Documents</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">CNI (Recto)</span>
                            @if($kyc->cni_front_path)
                                <span class="text-green-600">✓ Téléchargé</span>
                            @else
                                <span class="text-gray-400">Non téléchargé</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">CNI (Verso)</span>
                            @if($kyc->cni_back_path)
                                <span class="text-green-600">✓ Téléchargé</span>
                            @else
                                <span class="text-gray-400">Non téléchargé</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">Selfie</span>
                            @if($kyc->selfie_path)
                                <span class="text-green-600">✓ Téléchargé</span>
                            @else
                                <span class="text-gray-400">Non téléchargé</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">Téléphone vérifié</span>
                            @if($kyc->phone_verified)
                                <span class="text-green-600">✓ Vérifié</span>
                            @else
                                <span class="text-gray-400">Non vérifié</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Rejection Reason -->
                @if($kyc->isRejected() && $kyc->rejection_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h4 class="font-medium text-red-800 mb-2">Raison du rejet</h4>
                        <p class="text-red-700">{{ $kyc->rejection_reason }}</p>
                    </div>
                @endif

                <!-- Dates -->
                @if($kyc->isApproved())
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500 mb-1">Approuvé le</p>
                            <p class="text-gray-800 font-medium">{{ $kyc->approved_at->format('d/m/Y') }}</p>
                        </div>
                        @if($kyc->expires_at)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500 mb-1">Valide jusqu'au</p>
                                <p class="text-gray-800 font-medium">{{ $kyc->expires_at->format('d/m/Y') }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="text-gray-600">Aucune vérification KYC en cours.</p>
                    <p class="text-sm text-gray-500 mt-2">Utilisez l'application mobile pour commencer la vérification.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
