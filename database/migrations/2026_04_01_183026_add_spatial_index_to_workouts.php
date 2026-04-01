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
        Schema::table('workouts', function (Blueprint $table) {
            // Composite index for bbox queries (lat/lng range filtering)
            $table->index(['lat', 'lng'], 'workouts_lat_lng_idx');

            // Composite index for map queries combining status, date, and location
            $table->index(['status', 'starts_at', 'lat', 'lng'], 'workouts_map_query_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropIndex('workouts_lat_lng_idx');
            $table->dropIndex('workouts_map_query_idx');
        });
    }
};
