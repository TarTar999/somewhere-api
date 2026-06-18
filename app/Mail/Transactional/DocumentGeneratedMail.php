<?php

namespace App\Mail\Transactional;

use App\Models\ProofOfLocation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentGeneratedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ProofOfLocation $document,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre document est prêt - ' . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transactional.document-generated',
            with: [
                'userName' => $this->user->name,
                'documentType' => $this->document->document_type_label,
                'documentNumber' => $this->document->document_number,
                'expiresAt' => $this->document->expires_at->format('d/m/Y'),
                'downloadUrl' => route('api.v1.proof-of-location.download', $this->document->id),
                'verificationCode' => $this->document->verification_code,
            ],
        );
    }
}
