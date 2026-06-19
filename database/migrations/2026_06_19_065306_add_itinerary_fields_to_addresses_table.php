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
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('itinerary_intersection_id')
                ->nullable()
                ->after('itinerary_distance')
                ->constrained('intersections')
                ->nullOnDelete();
            $table->string('itinerary_intersection_name')->nullable()->after('itinerary_intersection_id');
            $table->json('itinerary_transport_modes')->nullable()->after('itinerary_intersection_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['itinerary_intersection_id']);
            $table->dropColumn([
                'itinerary_intersection_id',
                'itinerary_intersection_name',
                'itinerary_transport_modes',
            ]);
        });
    }
};
