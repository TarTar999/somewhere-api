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
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();

            // Document reference (polymorphic for different document types)
            $table->morphs('documentable');

            // User who downloaded (nullable for anonymous downloads)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Device detection
            $table->string('device_type', 20)->nullable(); // mobile, desktop, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();

            // Geolocation from IP
            $table->json('geo_data')->nullable(); // country, city, etc.

            // Referrer
            $table->string('referrer', 500)->nullable();

            // Download context
            $table->string('download_type', 20)->default('view'); // view, download, print
            $table->boolean('is_watermarked')->default(false);

            $table->timestamps();

            // Indexes for reporting
            $table->index(['documentable_type', 'documentable_id', 'created_at'], 'doc_downloads_doc_idx');
            $table->index(['user_id', 'created_at'], 'doc_downloads_user_idx');
            $table->index('ip_address', 'doc_downloads_ip_idx');
            $table->index('created_at', 'doc_downloads_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
