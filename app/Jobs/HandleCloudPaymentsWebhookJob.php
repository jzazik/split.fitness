<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleCloudPaymentsWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $type,
        public array $payload,
        public string $rawBody,
    ) {}

    public function handle(): void
    {
        match ($this->type) {
            'pay' => $this->handlePay(),
            'fail' => $this->handleFail(),
            default => Log::warning('CloudPayments webhook: unknown type', ['type' => $this->type]),
        };
    }

    private function handlePay(): void
    {
        $invoiceId = $this->payload['InvoiceId'] ?? null;
        $transactionId = $this->payload['TransactionId'] ?? null;

        if (! $invoiceId) {
            Log::error('CloudPayments pay webhook: missing InvoiceId', ['payload' => $this->payload]);

            return;
        }

        DB::transaction(function () use ($invoiceId, $transactionId) {
            $payment = Payment::where('booking_id', $invoiceId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::error('CloudPayments pay webhook: payment not found', [
                    'invoice_id' => $invoiceId,
                ]);

                return;
            }

            if ($payment->status === 'succeeded') {
                Log::warning('CloudPayments pay webhook: idempotency skip', [
                    'payment_id' => $payment->id,
                    'booking_id' => $invoiceId,
                ]);

                return;
            }

            $payment->update([
                'status' => 'succeeded',
                'external_payment_id' => $transactionId,
                'paid_at' => now(),
                'raw_webhook_json' => $this->payload,
            ]);

            Booking::where('id', $invoiceId)->update([
                'status' => 'paid',
                'payment_status' => 'paid',
            ]);

            Log::info('CloudPayments payment succeeded', [
                'payment_id' => $payment->id,
                'booking_id' => $invoiceId,
                'transaction_id' => $transactionId,
                'amount' => $payment->amount,
            ]);
        });
    }

    private function handleFail(): void
    {
        $invoiceId = $this->payload['InvoiceId'] ?? null;
        $transactionId = $this->payload['TransactionId'] ?? null;
        $reason = $this->payload['Reason'] ?? null;

        if (! $invoiceId) {
            Log::error('CloudPayments fail webhook: missing InvoiceId', ['payload' => $this->payload]);

            return;
        }

        $payment = Payment::where('booking_id', $invoiceId)->first();

        if (! $payment) {
            Log::error('CloudPayments fail webhook: payment not found', [
                'invoice_id' => $invoiceId,
            ]);

            return;
        }

        if (in_array($payment->status, ['succeeded', 'refunded'])) {
            return;
        }

        $payment->update([
            'status' => 'failed',
            'external_payment_id' => $transactionId,
            'raw_webhook_json' => $this->payload,
        ]);

        Booking::where('id', $invoiceId)->update([
            'payment_status' => 'failed',
        ]);

        Log::info('CloudPayments payment failed', [
            'payment_id' => $payment->id,
            'booking_id' => $invoiceId,
            'reason' => $reason,
        ]);
    }
}
