<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make email nullable (phone is now the primary identifier)
            $table->string('email')->nullable()->change();

            // Add unique index on phone
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove unique index on phone
            $table->dropUnique(['phone']);

            // Make email required again
            $table->string('email')->nullable(false)->change();
        });
    }
};
