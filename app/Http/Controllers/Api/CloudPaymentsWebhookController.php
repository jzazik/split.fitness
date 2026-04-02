<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleCloudPaymentsWebhookJob;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudPaymentsWebhookController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface $paymentService,
    ) {}

    public function pay(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::critical('CloudPayments webhook: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['code' => 13]);
        }

        HandleCloudPaymentsWebhookJob::dispatch(
            type: 'pay',
            payload: $request->all(),
            rawBody: $request->getContent(),
        );

        return response()->json(['code' => 0]);
    }

    public function fail(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::critical('CloudPayments webhook: invalid signature (fail)', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['code' => 13]);
        }

        HandleCloudPaymentsWebhookJob::dispatch(
            type: 'fail',
            payload: $request->all(),
            rawBody: $request->getContent(),
        );

        return response()->json(['code' => 0]);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('Content-HMAC')
            ?? $request->header('X-Content-HMAC', '');

        return $this->paymentService->verifyWebhookSignature(
            $request->getContent(),
            $signature,
        );
    }
}
