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
        Schema::create('outage_programmes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique(); // ID from ENEO API
            $table->string('region', 100);
            $table->string('ville', 100);
            $table->text('lib_traveaux'); // Description des travaux
            $table->text('zone'); // Zone brute
            $table->text('quartier'); // Quartier brut
            $table->date('prog_date'); // Date de la coupure
            $table->time('prog_heure_debut'); // Heure de début
            $table->time('prog_heure_fin'); // Heure de fin
            $table->unsignedInteger('duree_minutes'); // Durée en minutes
            $table->string('statut', 50)->default('prevu'); // prevu, en_cours, termine
            $table->json('zones_array'); // Zones parsées en array
            $table->string('travaux_normalises')->nullable(); // Travaux normalisés
            $table->string('category', 50)->nullable(); // installation, nettoyage, etc.
            $table->string('priority', 20)->default('moyenne'); // basse, moyenne, haute
            $table->json('metadata')->nullable(); // Métadonnées additionnelles
            $table->timestamp('fetched_at'); // Quand on a récupéré cette donnée
            $table->timestamps();

            // Index pour recherche rapide
            $table->index('prog_date');
            $table->index('region');
            $table->index('ville');
            $table->index(['prog_date', 'ville']);
            $table->index(['prog_date', 'statut']);
        });

        // Table pour indexer les zones normalisées (pour matching rapide)
        Schema::create('outage_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outage_programme_id')->constrained()->onDelete('cascade');
            $table->string('zone_name', 150); // Nom de la zone original
            $table->string('zone_normalized', 150); // Nom normalisé pour matching
            $table->timestamps();

            $table->index('zone_normalized');
            $table->index(['outage_programme_id', 'zone_normalized']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outage_zones');
        Schema::dropIfExists('outage_programmes');
    }
};
