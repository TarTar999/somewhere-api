<?php

namespace App\Mail\Alert;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspiciousActivityMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $activityDetails,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Alerte Sécurité] Activité suspecte détectée - ' . config('documents.company.brand'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert.suspicious-activity',
            with: [
                'userName' => $this->user->name,
                'activityType' => $this->activityDetails['type'] ?? 'Activité inconnue',
                'description' => $this->activityDetails['description'] ?? '',
                'ipAddress' => $this->activityDetails['ip_address'] ?? 'Inconnue',
                'userAgent' => $this->activityDetails['user_agent'] ?? 'Inconnu',
                'occurredAt' => $this->activityDetails['occurred_at'] ?? now()->format('d/m/Y H:i'),
                'securityUrl' => config('app.url') . '/settings/security',
            ],
        );
    }
}
