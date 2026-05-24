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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type de notification
            $table->string('type'); // document_expiring, document_expired, kyc_status, engagement, system
            $table->string('category')->default('general'); // document, kyc, engagement, system

            // Contenu
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Données additionnelles (document_id, etc.)

            // Référence optionnelle
            $table->string('reference_type')->nullable(); // App\Models\ProofOfLocation, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // Statut
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable(); // Pour push notifications
            $table->boolean('is_push_sent')->default(false);

            // Priorité et action
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('action_url')->nullable(); // Deep link ou URL d'action
            $table->string('action_type')->nullable(); // navigate, open_document, etc.

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'category']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
