<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, we need to alter the enum to add new values
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('proof_of_location', 'kyc_verification', 'subscription', 'other', 'location_plan', 'proof_of_residence') DEFAULT 'proof_of_location'");
    }

    public function down(): void
    {
        // Revert to original enum values
        // Note: This will fail if there are records with the new types
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('proof_of_location', 'kyc_verification', 'subscription', 'other') DEFAULT 'proof_of_location'");
    }
};
