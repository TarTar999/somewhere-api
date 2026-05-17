<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domiciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');

            // Custom name given by user (e.g., "Maison", "Bureau", "Maison 2")
            $table->string('name')->default('Domicile');

            // Role: owner (proprietor), resident (locataire/résident), visitor
            $table->enum('role', ['owner', 'resident', 'visitor'])->default('resident');

            // Status: pending (waiting for scan), approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');

            // Token for QR code domiciliation invitation
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();

            // Primary domiciliation flag
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            // A user can only have one domiciliation per address
            $table->unique(['user_id', 'address_id']);

            // Index for faster lookups
            $table->index(['address_id', 'status']);
            $table->index('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domiciliations');
    }
};
