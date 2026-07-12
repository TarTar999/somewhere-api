<?php

namespace App\Mail\Alert;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Company $company,
        public int $daysUntilExpiration,
    ) {}

    public function envelope(): Envelope
    {
        $urgency = $this->daysUntilExpiration <= 3 ? '[URGENT] ' : '';

        return new Envelope(
            subject: "{$urgency}Votre abonnement expire dans {$this->daysUntilExpiration} jours - " . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        $subscription = $this->company->activeSubscription;

        return new Content(
            view: 'emails.alert.subscription-expiring',
            with: [
                'userName' => $this->user->name,
                'companyName' => $this->company->name,
                'daysUntilExpiration' => $this->daysUntilExpiration,
                'expiresAt' => $subscription?->ends_at?->format('d/m/Y') ?? 'N/A',
                'planName' => $subscription?->plan_name ?? 'N/A',
                'renewUrl' => config('app.url') . '/company/' . $this->company->id . '/subscription',
                'isUrgent' => $this->daysUntilExpiration <= 3,
            ],
        );
    }
}
