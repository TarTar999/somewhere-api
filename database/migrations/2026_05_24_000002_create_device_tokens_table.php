<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Token FCM ou APNs
            $table->text('token');

            // Type de plateforme
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');

            // Informations sur l'appareil
            $table->string('device_id')->nullable(); // ID unique de l'appareil
            $table->string('device_name')->nullable(); // "iPhone 14 Pro", "Samsung Galaxy S23"
            $table->string('device_model')->nullable(); // Modèle technique
            $table->string('os_version')->nullable(); // "iOS 17.0", "Android 14"
            $table->string('app_version')->nullable(); // Version de l'app

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Index
            $table->index(['user_id', 'is_active']);
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
