<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'payment_id',
        'invoice_number',
        'file_path',
        'description',
        'amount',
        'currency',
        'tax_amount',
        'total_amount',
        'invoice_date',
        'due_date',
        'paid_at',
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->access_token)) {
                $invoice->access_token = Str::random(64);
            }
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            if (empty($invoice->invoice_date)) {
                $invoice->invoice_date = now();
            }
            if (empty($invoice->total_amount)) {
                $invoice->total_amount = $invoice->amount + ($invoice->tax_amount ?? 0);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function markAsPaid(): void
    {
        $this->update(['paid_at' => now()]);
    }

    public function getWebUrl(): string
    {
        return route('web.invoice.show', ['token' => $this->access_token]);
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('SW-INV-%s%s-%04d', $year, $month, $count);
    }

    public static function createFromPayment(Payment $payment, string $description): self
    {
        return self::create([
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'description' => $description,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'tax_amount' => 0, // No tax for now
            'total_amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
        ]);
    }
}
