<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->nullable()->constrained()->onDelete('set null');

            // Fapshi transaction details
            $table->string('transaction_id')->unique();
            $table->string('external_id')->nullable(); // Our internal reference
            $table->enum('type', ['proof_of_location', 'kyc_verification', 'subscription', 'other'])->default('proof_of_location');
            $table->integer('amount'); // Amount in XAF (no decimals)
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'successful', 'failed', 'expired', 'cancelled'])->default('pending');

            // Fapshi response data
            $table->string('payment_link')->nullable();
            $table->string('medium')->nullable(); // mobile_money, orange_money, etc.
            $table->string('phone')->nullable(); // Phone used for payment
            $table->json('fapshi_response')->nullable(); // Full response from Fapshi
            $table->text('failure_reason')->nullable();

            // Webhook tracking
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
