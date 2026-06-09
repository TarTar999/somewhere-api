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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->enum('zone_type', ['circle', 'polygon'])->default('circle');

            // Circle parameters
            $table->decimal('center_lat', 10, 8)->nullable();
            $table->decimal('center_lng', 11, 8)->nullable();
            $table->unsignedInteger('radius_meters')->nullable();

            // Polygon parameters
            $table->json('polygon_coordinates')->nullable();

            // Styling
            $table->string('fill_color', 7)->default('#3B82F6');
            $table->decimal('fill_opacity', 3, 2)->default(0.3);
            $table->string('stroke_color', 7)->default('#2563EB');
            $table->unsignedTinyInteger('stroke_width')->default(2);

            // Status and metadata
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index('company_id');
            $table->index('status');
            $table->index('parent_zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
