@extends('web.layouts.app')

@section('title', 'Facture ' . $invoice->invoice_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="gradient-bg text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Facture</h2>
                    <p class="text-purple-200">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="text-right">
                    @if($invoice->isPaid())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500 text-white">
                            Payée
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-500 text-white">
                            En attente
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Client Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Client</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-800 font-medium">{{ $user->first_name }} {{ $user->last_name }}</p>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    @if($user->phone)
                        <p class="text-gray-600">{{ $user->phone }}</p>
                    @endif
                </div>
            </div>

            <!-- Invoice Details -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Détails</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">Description</span>
                        <span class="text-gray-800 font-medium">{{ $invoice->description }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600">Date de facture</span>
                        <span class="text-gray-800">{{ $invoice->invoice_date->format('d/m/Y') }}</span>
                    </div>
                    @if($invoice->paid_at)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-gray-600">Date de paiement</span>
                            <span class="text-gray-800">{{ $invoice->paid_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Amount -->
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-800">Total</span>
                    <span class="text-2xl font-bold text-purple-600">
                        {{ number_format($invoice->total_amount, 0, ',', ' ') }} {{ $invoice->currency }}
                    </span>
                </div>
            </div>

            <!-- Download Button -->
            @if(isset($canDownload) && $canDownload)
                <div class="pt-4">
                    <a href="{{ route('web.invoice.download', $invoice->access_token) }}"
                       class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white gradient-bg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le PDF
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
