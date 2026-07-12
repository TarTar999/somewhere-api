<?php

namespace App\Mail\Transactional;

use App\Models\Address;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AddressRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Address $address,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre adresse n\'a pas été validée - ' . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transactional.address-rejected',
            with: [
                'userName' => $this->user->name,
                'swAddress' => $this->address->sw_address,
                'displayName' => $this->address->display_name,
                'reason' => $this->reason,
            ],
        );
    }
}
