<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // SW Address identifier
            $table->string('sw_address')->unique();
            $table->string('display_name');

            // Geolocation
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();

            // Address details
            $table->enum('house_type', ['immeuble', 'villa', 'maison', 'studio', 'bureau', 'autre'])->nullable();
            $table->enum('home_status', ['locataire', 'residence', 'familiale', 'proprietaire', 'commercial'])->nullable();

            // Location hierarchy
            $table->string('quarter')->nullable();
            $table->string('sub_quarter')->nullable();
            $table->string('lieu_dit')->nullable();
            $table->text('description')->nullable();

            // Official address info
            $table->string('official_address')->nullable();
            $table->string('way_code')->nullable();
            $table->string('way_display_name')->nullable();

            // Verification
            $table->boolean('honor_declaration')->default(false);
            $table->text('signature')->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('video_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for geospatial queries
            $table->index(['latitude', 'longitude']);
            $table->index('sw_address');
            $table->index('user_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
