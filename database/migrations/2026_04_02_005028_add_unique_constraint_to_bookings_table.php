<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This adds a unique partial index to prevent duplicate active bookings
     * (pending_payment or paid) for the same athlete and workout.
     *
     * Note: SQLite supports partial indexes since version 3.8.0 (2013).
     * MySQL 8.0+ supports functional/filtered indexes.
     * PostgreSQL supports partial indexes natively.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite supports partial indexes
            Schema::getConnection()->statement(
                "CREATE UNIQUE INDEX bookings_athlete_workout_active_unique
                 ON bookings(athlete_id, workout_id)
                 WHERE status IN ('pending_payment', 'paid')"
            );
        } elseif ($driver === 'mysql') {
            // MySQL 8.0+ approach - use a generated column
            Schema::table('bookings', function (Blueprint $table) {
                // Add a computed column that's NULL for inactive bookings
                $table->string('active_booking_key')->nullable()->storedAs(
                    "CASE WHEN status IN ('pending_payment', 'paid') THEN CONCAT(athlete_id, '-', workout_id) ELSE NULL END"
                );
                $table->unique('active_booking_key', 'bookings_athlete_workout_active_unique');
            });
        } elseif ($driver === 'pgsql') {
            // PostgreSQL supports partial indexes natively
            Schema::getConnection()->statement(
                "CREATE UNIQUE INDEX bookings_athlete_workout_active_unique
                 ON bookings(athlete_id, workout_id)
                 WHERE status IN ('pending_payment', 'paid')"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            Schema::getConnection()->statement(
                'DROP INDEX IF EXISTS bookings_athlete_workout_active_unique'
            );
        } elseif ($driver === 'mysql') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropUnique('bookings_athlete_workout_active_unique');
                $table->dropColumn('active_booking_key');
            });
        }
    }
};
