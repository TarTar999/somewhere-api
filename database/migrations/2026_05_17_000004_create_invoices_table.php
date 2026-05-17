<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');

            // Invoice details
            $table->string('invoice_number')->unique();
            $table->string('file_path')->nullable();
            $table->string('description');
            $table->integer('amount');
            $table->string('currency', 3)->default('XAF');
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Access token for web viewing
            $table->string('access_token')->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['access_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
