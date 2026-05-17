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
        Schema::create('streets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('osm_id')->unique();
            $table->string('osm_type')->default('way');
            $table->string('display_name');
            $table->string('code')->unique()->nullable();
            $table->string('commune_name')->nullable();
            $table->unsignedTinyInteger('commune_number')->default(1);
            $table->json('structure')->nullable();
            $table->json('bounding_box')->nullable();
            $table->decimal('start_lat', 10, 8)->nullable();
            $table->decimal('start_lon', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['commune_name', 'commune_number']);
        });

        // Add street_id to addresses table
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('street_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('street_number')->nullable()->after('street_id');
            $table->decimal('distance_on_street', 10, 2)->nullable()->after('street_number');
            $table->enum('street_side', ['left', 'right'])->nullable()->after('distance_on_street');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['street_id']);
            $table->dropColumn(['street_id', 'street_number', 'distance_on_street', 'street_side']);
        });

        Schema::dropIfExists('streets');
    }
};
