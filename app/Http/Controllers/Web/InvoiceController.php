<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Show invoice via access token
     */
    public function show(string $token)
    {
        $invoice = Invoice::where('access_token', $token)
            ->with(['user', 'payment'])
            ->first();

        if (!$invoice) {
            return view('web.invoice.not-found');
        }

        return view('web.invoice.show', [
            'invoice' => $invoice,
            'user' => $invoice->user,
            'payment' => $invoice->payment,
        ]);
    }

    /**
     * Download invoice PDF via access token
     */
    public function download(string $token)
    {
        $invoice = Invoice::where('access_token', $token)->first();

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        return $this->invoiceService->download($invoice);
    }
}
