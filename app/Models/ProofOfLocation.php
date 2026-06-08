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
        'document_type',
        'document_number',
        'file_path',
        'qr_code_token',
        'verification_code',
        'price',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'download_count',
        'last_downloaded_at',
        'qr_scan_count',
        'last_scanned_at',
        'company_id',
        'is_company_document',
    ];

    // Document types
    public const TYPE_LOCATION_PLAN = 'location_plan';
    public const TYPE_PROOF_OF_RESIDENCE = 'proof_of_residence';

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
            'is_company_document' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($proof) {
            if (empty($proof->qr_code_token)) {
                $proof->qr_code_token = Str::random(64);
            }
            if (empty($proof->verification_code)) {
                $proof->verification_code = self::generateVerificationCode();
            }
            if (empty($proof->document_type)) {
                $proof->document_type = self::TYPE_LOCATION_PLAN;
            }
            if (empty($proof->issued_at)) {
                $proof->issued_at = now();
            }
            if (empty($proof->expires_at)) {
                $months = (int) config('documents.validity_months', 3);
                $proof->expires_at = now()->addMonths($months);
            }
            if (empty($proof->price)) {
                $proof->price = self::getPrice($proof->document_type);
            }
        });
    }

    /**
     * Generate verification code: SW-XXXX-XXXX-XXXX
     */
    public static function generateVerificationCode(): string
    {
        return sprintf(
            'SW-%s-%s-%s',
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4))
        );
    }

    /**
     * Get price for document type
     */
    public static function getPrice(string $documentType): int
    {
        return match ($documentType) {
            self::TYPE_LOCATION_PLAN => (int) config('documents.prices.location_plan', 2000),
            self::TYPE_PROOF_OF_RESIDENCE => (int) config('documents.prices.proof_of_residence', 3000),
            default => 0,
        };
    }

    /**
     * Check if this is a location plan
     */
    public function isLocationPlan(): bool
    {
        return $this->document_type === self::TYPE_LOCATION_PLAN;
    }

    /**
     * Check if this is a proof of residence
     */
    public function isProofOfResidence(): bool
    {
        return $this->document_type === self::TYPE_PROOF_OF_RESIDENCE;
    }

    /**
     * Get document type label
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            self::TYPE_LOCATION_PLAN => 'Plan de Localisation',
            self::TYPE_PROOF_OF_RESIDENCE => 'Attestation de Résidence',
            default => 'Document',
        };
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrl(): string
    {
        return config('app.url') . '/verify/' . $this->verification_code;
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isCompanyDocument(): bool
    {
        return $this->is_company_document && $this->company_id !== null;
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
            'valid_until' => $this->expires_at->toIso8601String(),
        ];
    }

    public static function generateDocumentNumber(User $user, Address $address, ?string $documentType = null): string
    {
        $prefix = match ($documentType) {
            self::TYPE_PROOF_OF_RESIDENCE => 'SW-RES',
            self::TYPE_LOCATION_PLAN => 'SW-LOC',
            default => 'SW-DOC',
        };

        return sprintf(
            '%s-%d-%d-%s',
            $prefix,
            $user->id,
            $address->id,
            strtoupper(substr(md5(now()->timestamp . $user->id), 0, 8))
        );
    }

    /**
     * Scope to filter by document type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope for location plans only
     */
    public function scopeLocationPlans($query)
    {
        return $query->where('document_type', self::TYPE_LOCATION_PLAN);
    }

    /**
     * Scope for proof of residence only
     */
    public function scopeProofsOfResidence($query)
    {
        return $query->where('document_type', self::TYPE_PROOF_OF_RESIDENCE);
    }
}
