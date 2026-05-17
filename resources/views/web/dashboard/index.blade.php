@extends('web.layouts.app')

@section('title', 'Mon Dashboard - Somewhere')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="gradient-bg text-white p-6">
            <h2 class="text-2xl font-bold">Bienvenue, {{ $user->first_name }}!</h2>
            <p class="text-purple-200">Accès temporaire valide jusqu'à {{ $token->expires_at->format('H:i') }}</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- KYC Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($user->kycVerification && $user->kycVerification->isApproved())
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Statut KYC</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($user->kycVerification)
                            {{ ucfirst($user->kycVerification->status) }}
                        @else
                            Non commencé
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Active Proofs -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Proofs actifs</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->proofOfLocations->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Factures</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $user->invoices->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Proofs -->
    @if($user->proofOfLocations->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Proofs of Location récents</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($user->proofOfLocations as $proof)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800">{{ $proof->document_number }}</p>
                            <p class="text-sm text-gray-500">{{ $proof->address->sw_address ?? 'N/A' }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500">
                                Expire le {{ $proof->expires_at->format('d/m/Y') }}
                            </span>
                            @if($proof->isActive())
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Actif</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Expiré</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Invoices -->
    @if($user->invoices->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Factures récentes</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($user->invoices as $invoice)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800">{{ $invoice->invoice_number }}</p>
                            <p class="text-sm text-gray-500">{{ $invoice->description }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="font-medium text-gray-800">
                                {{ number_format($invoice->total_amount, 0, ',', ' ') }} {{ $invoice->currency }}
                            </span>
                            @if($invoice->isPaid())
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Payée</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">En attente</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
