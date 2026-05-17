<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('language', 10)->default('fr');
            $table->enum('unit', ['metric', 'imperial'])->default('metric');
            $table->enum('notifications', ['enabled', 'disabled'])->default('enabled');
            $table->enum('map_type', ['ApplePlan', 'GoogleMap'])->default('GoogleMap');

            $table->string('proof_of_residence')->nullable();
            $table->timestamp('proof_of_residence_date')->nullable();

            $table->boolean('google_search')->default(true);
            $table->boolean('is_city_mapper')->default(false);
            $table->boolean('dark_mode')->default(false);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
