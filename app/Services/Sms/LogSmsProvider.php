<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): void
    {
        Log::channel('single')->info('[SMS → Log]', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
