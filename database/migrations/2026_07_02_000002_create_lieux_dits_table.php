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
        Schema::create('lieux_dits', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_normalized', 100)->index(); // Pour recherche insensible aux accents
            $table->string('city', 100)->nullable(); // Ville (Douala, Yaoundé, etc.)
            $table->string('region', 100)->nullable(); // Région
            $table->boolean('is_verified')->default(false); // Vérifié par admin
            $table->boolean('is_system')->default(false); // Ajouté par le système (seed)
            $table->unsignedInteger('usage_count')->default(0); // Nombre d'utilisations
            $table->timestamps();

            // Index composite pour recherche rapide
            $table->index(['name_normalized', 'city']);
            $table->unique(['name_normalized', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lieux_dits');
    }
};
