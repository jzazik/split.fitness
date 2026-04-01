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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['workout_id']);
            $table->dropForeign(['athlete_id']);

            // Recreate with restrictOnDelete to prevent data loss
            $table->foreign('workout_id')->references('id')->on('workouts')->restrictOnDelete();
            $table->foreign('athlete_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop restrict foreign keys
            $table->dropForeign(['workout_id']);
            $table->dropForeign(['athlete_id']);

            // Restore original cascade delete behavior
            $table->foreign('workout_id')->references('id')->on('workouts')->cascadeOnDelete();
            $table->foreign('athlete_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
