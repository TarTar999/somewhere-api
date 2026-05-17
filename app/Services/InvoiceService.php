<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Create invoice from a successful payment
     */
    public function createFromPayment(Payment $payment): Invoice
    {
        $descriptions = [
            'proof_of_location' => 'Proof of Location - Document officiel',
            'kyc_verification' => 'KYC Verification - Vérification d\'identité',
            'subscription' => 'Abonnement Somewhere',
            'other' => 'Service Somewhere',
        ];

        $description = $descriptions[$payment->type] ?? $descriptions['other'];

        if ($payment->address) {
            $description .= " ({$payment->address->sw_address})";
        }

        $invoice = Invoice::createFromPayment($payment, $description);

        // Generate PDF
        $this->generatePdf($invoice);

        return $invoice->fresh();
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->load(['user', 'payment']);

        $data = [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'payment' => $invoice->payment,
            'company' => [
                'name' => config('app.name', 'Somewhere'),
                'address' => config('app.company_address', 'Douala, Cameroun'),
                'phone' => config('app.company_phone', '+237 600 000 000'),
                'email' => config('app.company_email', 'contact@somewhere.cm'),
            ],
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = "invoice_{$invoice->invoice_number}.pdf";
        $path = "invoices/{$invoice->user_id}/{$filename}";

        Storage::disk('invoices')->put($path, $pdf->output());

        $invoice->update(['file_path' => $path]);

        return $path;
    }

    /**
     * Download invoice PDF
     */
    public function download(Invoice $invoice)
    {
        if (!$invoice->file_path || !Storage::disk('invoices')->exists($invoice->file_path)) {
            $this->generatePdf($invoice);
            $invoice->refresh();
        }

        return Storage::disk('invoices')->download(
            $invoice->file_path,
            "invoice_{$invoice->invoice_number}.pdf"
        );
    }

    /**
     * Get user invoices
     */
    public function getUserInvoices(User $user, int $perPage = 15)
    {
        return $user->invoices()
            ->with('payment')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Format invoice for API response
     */
    public function formatInvoiceForResponse(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoiceNumber' => $invoice->invoice_number,
            'description' => $invoice->description,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'taxAmount' => $invoice->tax_amount,
            'totalAmount' => $invoice->total_amount,
            'invoiceDate' => $invoice->invoice_date->toDateString(),
            'dueDate' => $invoice->due_date?->toDateString(),
            'paidAt' => $invoice->paid_at?->toISOString(),
            'isPaid' => $invoice->isPaid(),
            'webUrl' => $invoice->getWebUrl(),
            'downloadUrl' => route('api.invoices.download', $invoice->id),
            'createdAt' => $invoice->created_at->toISOString(),
        ];
    }
}
