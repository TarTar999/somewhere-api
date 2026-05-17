<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Receipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'payment_id',
        'invoice_id',
        'receipt_number',
        'description',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'verification_code',
        'access_token',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (!$receipt->receipt_number) {
                $receipt->receipt_number = self::generateReceiptNumber();
            }
            if (!$receipt->verification_code) {
                $receipt->verification_code = self::generateVerificationCode();
            }
            if (!$receipt->access_token) {
                $receipt->access_token = Str::random(64);
            }
            if (!$receipt->company_name) {
                $receipt->company_name = config('company.name', 'Ket-Up Sarl');
            }
            if (!$receipt->company_address) {
                $receipt->company_address = config('company.address');
            }
            if (!$receipt->company_phone) {
                $receipt->company_phone = config('company.phone');
            }
            if (!$receipt->company_email) {
                $receipt->company_email = config('company.email');
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Generate receipt number: REC-YYYYMMDD-XXXX
    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return sprintf('REC-%s-%04d', $date, $count);
    }

    // Generate verification code: SW-XXXX-XXXX-XXXX
    public static function generateVerificationCode(): string
    {
        return sprintf(
            'SW-%s-%s-%s',
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4))
        );
    }

    // Get web URL for viewing
    public function getWebUrl(): string
    {
        return config('app.url') . '/documents/receipt/' . $this->access_token;
    }

    // Get verification URL
    public function getVerificationUrl(): string
    {
        return config('app.url') . '/verify/' . $this->verification_code;
    }

    // Format amount with currency
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }
}
