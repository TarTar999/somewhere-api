<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table for temporary web access tokens (QR code scanning)
        Schema::create('web_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('token')->unique();
            $table->enum('type', ['proof_of_location', 'invoice', 'kyc_status', 'dashboard'])->default('dashboard');
            $table->foreignId('resource_id')->nullable(); // ID of the related resource

            // Security
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage')->default(1); // -1 for unlimited
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(['token']);
            $table->index(['user_id', 'type']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_access_tokens');
    }
};
