<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');

            // Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->string('currency', 3)->default('XAF');

            // Status
            $table->enum('status', ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->boolean('initiator_confirmed')->default(false);
            $table->boolean('recipient_confirmed')->default(false);

            // Addresses
            $table->foreignId('pickup_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->foreignId('delivery_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();

            // Sharing
            $table->string('share_token', 64)->unique();

            // Timestamps
            $table->timestamps();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Index
            $table->index('initiator_id');
            $table->index('recipient_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
    }
};
