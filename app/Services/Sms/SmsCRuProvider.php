<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsCRuProvider implements SmsProviderInterface
{
    protected string $baseUrl = 'https://smsc.ru/sys/send.php';

    public function send(string $phone, string $message): void
    {
        $login = config('services.smscru.login');
        $password = config('services.smscru.password');
        $sender = config('services.smscru.sender');

        if (empty($login) || empty($password)) {
            throw new RuntimeException('SMSC.RU is not configured: SMSCRU_LOGIN and SMSCRU_PASSWORD are required');
        }

        try {
            $response = Http::get($this->baseUrl, [
                'login' => $login,
                'psw' => $password,
                'phones' => $phone,
                'sender' => $sender,
                'mes' => $message,
            ]);

            if (! $response->ok()) {
                $result = $response->json();
                Log::error('SMSC.RU send error', ['status' => $response->status(), 'response' => $result]);

                throw new RuntimeException('SMS send error: '.($result['status_text'] ?? 'Unknown error'));
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SMS send error', ['error' => $e->getMessage()]);

            throw new RuntimeException('SMS send error: '.$e->getMessage(), 0, $e);
        }
    }
}
