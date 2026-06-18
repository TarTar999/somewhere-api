<?php

namespace App\Mail\Alert;

use App\Models\ProofOfLocation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ProofOfLocation $document,
        public int $daysUntilExpiration,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre document expire dans {$this->daysUntilExpiration} jours - " . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert.document-expiring',
            with: [
                'userName' => $this->user->name,
                'documentType' => $this->document->document_type_label,
                'documentNumber' => $this->document->document_number,
                'expiresAt' => $this->document->expires_at->format('d/m/Y'),
                'daysLeft' => $this->daysUntilExpiration,
                'renewUrl' => config('app.url') . '/dashboard',
            ],
        );
    }
}
