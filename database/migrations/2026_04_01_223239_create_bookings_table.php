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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedInteger('slots_count')->default(1);
            $table->decimal('slot_price', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->enum('status', [
                'pending_payment',
                'paid',
                'expired',
                'cancelled',
                'refunded'
            ])->default('pending_payment');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            $table->dateTime('booked_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index('workout_id');
            $table->index('athlete_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['athlete_id', 'workout_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
