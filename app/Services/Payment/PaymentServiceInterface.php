<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\Payment;

interface PaymentServiceInterface
{
    public function createPayment(Booking $booking): Payment;

    public function getWidgetData(Booking $booking, Payment $payment): array;

    public function verifyWebhookSignature(string $body, string $signature): bool;
}
