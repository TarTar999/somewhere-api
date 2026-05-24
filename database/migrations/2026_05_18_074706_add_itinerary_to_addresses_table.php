<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Itinéraire personnalisé: série de points GPS pour tracer un chemin
            // depuis l'adresse jusqu'à une rue identifiée
            // Format: [{"lat": 4.0511, "lng": 9.7679, "order": 1}, ...]
            $table->json('itinerary')->nullable()->after('longitude');

            // Rue de destination de l'itinéraire (référence optionnelle)
            $table->foreignId('itinerary_street_id')->nullable()->after('itinerary')
                ->constrained('streets')->nullOnDelete();

            // Description/notes sur l'itinéraire (ex: "Prendre à gauche après le manguier")
            $table->text('itinerary_description')->nullable()->after('itinerary_street_id');

            // Distance estimée de l'itinéraire en mètres
            $table->integer('itinerary_distance')->nullable()->after('itinerary_description');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['itinerary_street_id']);
            $table->dropColumn([
                'itinerary',
                'itinerary_street_id',
                'itinerary_description',
                'itinerary_distance',
            ]);
        });
    }
};
