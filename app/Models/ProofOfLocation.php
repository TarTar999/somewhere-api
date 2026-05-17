<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProofOfLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_id',
        'payment_id',
        'document_number',
        'file_path',
        'qr_code_token',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'download_count',
        'last_downloaded_at',
        'qr_scan_count',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'last_scanned_at' => 'datetime',
            'download_count' => 'integer',
            'qr_scan_count' => 'integer',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($proof) {
            if (empty($proof->qr_code_token)) {
                $proof->qr_code_token = Str::random(64);
            }
            if (empty($proof->issued_at)) {
                $proof->issued_at = now();
            }
            if (empty($proof->expires_at)) {
                $months = (int) config('app.proof_of_location_validity_months', 3);
                $proof->expires_at = now()->addMonths($months);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function revoke(string $reason): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    public function recordQrScan(): void
    {
        $this->increment('qr_scan_count');
        $this->update(['last_scanned_at' => now()]);
    }

    public function getWebUrl(): string
    {
        return route('web.proof.show', ['token' => $this->qr_code_token]);
    }

    public function getQrCodeData(): array
    {
        return [
            'url' => $this->getWebUrl(),
            'document_number' => $this->document_number,
            'valid_until' => $this->expires_at->toISOString(),
        ];
    }

    public static function generateDocumentNumber(User $user, Address $address): string
    {
        return sprintf(
            'SW-POL-%d-%d-%s',
            $user->id,
            $address->id,
            strtoupper(substr(md5(now()->timestamp . $user->id), 0, 8))
        );
    }
}
