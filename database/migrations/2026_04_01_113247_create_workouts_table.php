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
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->string('location_name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);

            $table->dateTime('starts_at');
            $table->integer('duration_minutes');

            $table->decimal('total_price', 10, 2);
            $table->decimal('slot_price', 10, 2);
            $table->unsignedInteger('slots_total');
            $table->unsignedInteger('slots_booked')->default(0);

            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');

            $table->dateTime('published_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('coach_id');
            $table->index('sport_id');
            $table->index('city_id');
            $table->index('status');
            $table->index('starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
