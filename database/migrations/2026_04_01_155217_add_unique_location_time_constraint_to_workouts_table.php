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
        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL supports partial indexes
            DB::statement('
                CREATE UNIQUE INDEX workouts_location_time_active_unique
                ON workouts (starts_at, lat, lng)
                WHERE status IN (\'draft\', \'published\')
            ');
        } elseif ($driver === 'mysql') {
            // MySQL does not support partial indexes, so we need a workaround
            // Add a computed column that is 'active' for draft/published, NULL for others
            // This way, both draft and published map to the same constraint value
            DB::statement('
                ALTER TABLE workouts ADD COLUMN status_for_unique_check
                VARCHAR(20) AS (CASE WHEN status IN (\'draft\', \'published\') THEN \'active\' ELSE NULL END) STORED
            ');
            Schema::table('workouts', function (Blueprint $table) {
                $table->unique(['starts_at', 'lat', 'lng', 'status_for_unique_check'], 'workouts_location_time_unique');
            });
        } else {
            // SQLite: similar approach but using a virtual column
            // Use 'active' constant for both draft and published to enforce uniqueness
            DB::statement('
                ALTER TABLE workouts ADD COLUMN status_for_unique_check
                TEXT GENERATED ALWAYS AS (CASE WHEN status IN (\'draft\', \'published\') THEN \'active\' ELSE NULL END) VIRTUAL
            ');
            Schema::table('workouts', function (Blueprint $table) {
                $table->unique(['starts_at', 'lat', 'lng', 'status_for_unique_check'], 'workouts_location_time_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS workouts_location_time_active_unique');
        } elseif ($driver === 'mysql') {
            // Check if unique index exists before dropping
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'workouts'
                AND index_name = 'workouts_location_time_unique'
            ");

            if ($indexExists[0]->count > 0) {
                Schema::table('workouts', function (Blueprint $table) {
                    $table->dropUnique('workouts_location_time_unique');
                });
            }

            // Check if status_for_unique_check column exists before dropping
            $columnExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'workouts'
                AND column_name = 'status_for_unique_check'
            ");

            if ($columnExists[0]->count > 0) {
                DB::statement('ALTER TABLE workouts DROP COLUMN status_for_unique_check');
            }
        } else {
            // SQLite: check for index existence before dropping
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM sqlite_master
                WHERE type = 'index'
                AND name = 'workouts_location_time_unique'
            ");

            if ($indexExists[0]->count > 0) {
                Schema::table('workouts', function (Blueprint $table) {
                    $table->dropUnique('workouts_location_time_unique');
                });
            }

            // SQLite: check for column existence before dropping
            // Use pragma_table_xinfo to detect generated columns (VIRTUAL/STORED)
            // pragma_table_info does NOT include generated columns
            $columnExists = DB::select("
                SELECT COUNT(*) as count
                FROM pragma_table_xinfo('workouts')
                WHERE name = 'status_for_unique_check'
            ");

            if ($columnExists[0]->count > 0) {
                DB::statement('ALTER TABLE workouts DROP COLUMN status_for_unique_check');
            }
        }
    }
};
