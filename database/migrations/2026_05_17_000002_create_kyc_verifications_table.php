<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // KYC Status
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'expired'])->default('pending');
            $table->enum('level', ['basic', 'standard', 'premium'])->default('basic');

            // Identity documents
            $table->string('cni_front_path')->nullable();
            $table->string('cni_back_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('video_path')->nullable();

            // Document verification
            $table->boolean('cni_verified')->default(false);
            $table->boolean('selfie_verified')->default(false);
            $table->boolean('address_verified')->default(false);
            $table->boolean('phone_verified')->default(false);

            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
