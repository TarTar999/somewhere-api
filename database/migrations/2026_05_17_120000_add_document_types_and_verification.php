<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add document_type to proof_of_locations
        Schema::table('proof_of_locations', function (Blueprint $table) {
            $table->enum('document_type', ['location_plan', 'proof_of_residence'])
                ->default('location_plan')
                ->after('payment_id');
            $table->string('verification_code', 32)->unique()->nullable()->after('qr_code_token');
            $table->integer('price')->default(0)->after('verification_code');
        });

        // Add verification_code and company info to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('verification_code', 32)->unique()->nullable()->after('access_token');
            $table->string('company_name')->default('Ket-Up Sarl')->after('verification_code');
            $table->string('company_address')->nullable()->after('company_name');
            $table->string('company_phone')->nullable()->after('company_address');
            $table->string('company_email')->nullable()->after('company_phone');
            $table->string('company_rccm')->nullable()->after('company_email');
            $table->string('company_niu')->nullable()->after('company_rccm');
            $table->enum('invoice_type', ['invoice', 'receipt'])->default('invoice')->after('invoice_number');
        });

        // Create receipts table (linked to payments)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');

            // Receipt details
            $table->string('receipt_number')->unique();
            $table->string('description');
            $table->integer('amount');
            $table->string('currency', 3)->default('XAF');
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();

            // Company info
            $table->string('company_name')->default('Ket-Up Sarl');
            $table->string('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();

            // Verification
            $table->string('verification_code', 32)->unique();
            $table->string('access_token')->unique();

            // Dates
            $table->timestamp('paid_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index(['verification_code']);
            $table->index(['access_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'company_name',
                'company_address',
                'company_phone',
                'company_email',
                'company_rccm',
                'company_niu',
                'invoice_type',
            ]);
        });

        Schema::table('proof_of_locations', function (Blueprint $table) {
            $table->dropColumn(['document_type', 'verification_code', 'price']);
        });
    }
};
