<?php

namespace App\Mail\Transactional;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $paymentDetails,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de paiement - ' . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transactional.payment-confirmation',
            with: [
                'userName' => $this->user->name,
                'amount' => $this->paymentDetails['amount'] ?? 0,
                'currency' => $this->paymentDetails['currency'] ?? 'XAF',
                'transactionId' => $this->paymentDetails['transaction_id'] ?? '',
                'documentType' => $this->paymentDetails['document_type'] ?? 'Document',
                'paidAt' => $this->paymentDetails['paid_at'] ?? now()->format('d/m/Y H:i'),
            ],
        );
    }
}
