<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();

            $table->enum('type', ['system', 'custom', 'delivery'])->default('custom');

            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
