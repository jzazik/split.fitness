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
        Schema::table('users', function (Blueprint $table) {
            // Drop old name field
            $table->dropColumn('name');

            // Add role and profile fields
            $table->enum('role', ['athlete', 'coach', 'admin'])->after('id');
            $table->string('phone')->nullable()->unique()->after('password');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('first_name')->nullable()->after('phone_verified_at');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('middle_name')->nullable()->after('last_name');
            $table->string('avatar_path')->nullable()->after('middle_name');
            $table->unsignedBigInteger('city_id')->nullable()->after('avatar_path');
            $table->enum('status', ['active', 'blocked'])->default('active')->after('city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn([
                'role',
                'phone',
                'phone_verified_at',
                'first_name',
                'last_name',
                'middle_name',
                'avatar_path',
                'city_id',
                'status',
            ]);

            // Restore old name field
            $table->string('name')->after('id');
        });
    }
};
