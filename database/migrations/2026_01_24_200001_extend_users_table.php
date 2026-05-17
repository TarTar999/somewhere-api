<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make original name field nullable (we use first_name/last_name now)
            $table->string('name')->nullable()->change();

            // Split name into first_name and last_name
            $table->string('first_name')->after('id')->nullable();
            $table->string('last_name')->after('first_name')->nullable();

            // Additional user fields
            $table->string('phone')->nullable()->after('email');
            $table->enum('sex', ['male', 'female'])->nullable()->after('phone');
            $table->string('nui_number')->nullable()->after('sex');
            $table->string('cni_number')->nullable()->after('nui_number');
            $table->date('cni_expiration_date')->nullable()->after('cni_number');
            $table->string('avatar_path')->nullable()->after('cni_expiration_date');

            // Admin flag
            $table->boolean('is_admin')->default(false)->after('avatar_path');

            // Soft deletes
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'sex',
                'nui_number',
                'cni_number',
                'cni_expiration_date',
                'avatar_path',
                'is_admin',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
