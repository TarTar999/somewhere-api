<?php

namespace App\Mail\Transactional;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KycStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $status,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = match ($this->status) {
            'approved' => 'approuvée',
            'rejected' => 'refusée',
            'pending' => 'en attente',
            default => $this->status,
        };

        return new Envelope(
            subject: "Vérification d'identité {$statusLabel} - " . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transactional.kyc-status',
            with: [
                'userName' => $this->user->name,
                'status' => $this->status,
                'reason' => $this->reason,
                'isApproved' => $this->status === 'approved',
                'isRejected' => $this->status === 'rejected',
            ],
        );
    }
}
