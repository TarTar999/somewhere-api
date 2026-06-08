<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('company_subscriptions')->onDelete('set null');
            $table->string('transaction_id')->unique();
            $table->string('external_id')->nullable();
            $table->integer('amount');
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'successful', 'failed', 'expired'])->default('pending');
            $table->string('payment_link')->nullable();
            $table->string('medium')->nullable();
            $table->string('phone')->nullable();
            $table->json('fapshi_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payments');
    }
};
