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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('cloudpayments');
            $table->string('external_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->enum('status', ['created', 'pending', 'succeeded', 'failed', 'refunded'])->default('created');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_request_json')->nullable();
            $table->json('raw_response_json')->nullable();
            $table->json('raw_webhook_json')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('external_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
