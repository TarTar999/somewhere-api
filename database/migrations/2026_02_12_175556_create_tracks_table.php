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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('structure'); // Array of {lat, lon} points
            $table->string('color')->default('#3B82F6'); // Default blue color
            $table->boolean('is_public')->default(false);
            $table->string('share_token')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('share_token');
        });

        // Table pivot pour le partage de pistes avec d'autres utilisateurs
        Schema::create('track_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamps();

            $table->unique(['track_id', 'shared_with_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_shares');
        Schema::dropIfExists('tracks');
    }
};
