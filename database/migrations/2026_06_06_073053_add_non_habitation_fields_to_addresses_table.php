<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('resident_name', 255)->nullable()->after('honor_declaration');
            $table->boolean('is_non_habitation')->default(false)->after('resident_name');
        });

        // Modify house_type enum to add 'terrain'
        DB::statement("ALTER TABLE addresses MODIFY COLUMN house_type ENUM('immeuble', 'villa', 'maison', 'studio', 'bureau', 'terrain', 'autre') NOT NULL");

        // Modify home_status enum to add 'non_bati'
        DB::statement("ALTER TABLE addresses MODIFY COLUMN home_status ENUM('locataire', 'residence', 'familiale', 'proprietaire', 'commercial', 'non_bati') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert house_type enum
        DB::statement("ALTER TABLE addresses MODIFY COLUMN house_type ENUM('immeuble', 'villa', 'maison', 'studio', 'bureau', 'autre') NOT NULL");

        // Revert home_status enum
        DB::statement("ALTER TABLE addresses MODIFY COLUMN home_status ENUM('locataire', 'residence', 'familiale', 'proprietaire', 'commercial') NOT NULL");

        // Remove columns
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['resident_name', 'is_non_habitation']);
        });
    }
};
