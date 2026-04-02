<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class CloudPaymentsService implements PaymentServiceInterface
{
    public function createPayment(Booking $booking): Payment
    {
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->athlete_id,
            'provider' => 'cloudpayments',
            'amount' => $booking->total_amount,
            'currency' => 'RUB',
            'status' => 'created',
        ]);

        Log::info('Payment created for CloudPayments widget', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'amount' => $payment->amount,
        ]);

        return $payment;
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        $secret = config('services.cloudpayments.api_secret');

        if (empty($secret)) {
            Log::critical('CloudPayments API secret not configured');

            return false;
        }

        $expected = base64_encode(
            hash_hmac('sha256', $body, $secret, true)
        );

        return hash_equals($expected, $signature);
    }

    public function getWidgetData(Booking $booking, Payment $payment): array
    {
        return [
            'public_id' => config('services.cloudpayments.public_id'),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'invoice_id' => (string) $booking->id,
            'description' => $this->buildDescription($booking),
        ];
    }

    private function buildDescription(Booking $booking): string
    {
        $booking->loadMissing('workout.sport');
        $workout = $booking->workout;

        $parts = array_filter([
            $workout->sport?->name,
            $workout->location_name,
        ]);

        return 'Тренировка: ' . implode(' — ', $parts);
    }
}
