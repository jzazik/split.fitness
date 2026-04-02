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
        $apikey = config('services.smscru.apikey');
        $sender = config('services.smscru.sender');

        $useApiKey = ! empty($apikey);

        if (! $useApiKey && (empty($login) || empty($password))) {
            throw new RuntimeException('SMSC.RU is not configured: set SMSCRU_APIKEY or both SMSCRU_LOGIN and SMSCRU_PASSWORD');
        }

        $params = [
            'phones' => $phone,
            'mes' => $message,
            'fmt' => 3,
        ];

        if ($useApiKey) {
            $params['login'] = $login ?: '';
            $params['psw'] = $apikey;
        } else {
            $params['login'] = $login;
            $params['psw'] = $password;
        }

        if (! empty($sender)) {
            $params['sender'] = $sender;
        }

        try {
            Log::info('SMSC.RU: sending SMS', [
                'phone' => $phone,
                'auth' => $useApiKey ? 'apikey' : 'login+password',
            ]);

            $response = Http::get($this->baseUrl, $params);
            $result = $response->json();

            Log::info('SMSC.RU: response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (isset($result['error'])) {
                Log::error('SMSC.RU: API error', [
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'phone' => $phone,
                ]);

                throw new RuntimeException('SMS send error: '.$result['error']);
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('SMSC.RU: exception', ['error' => $e->getMessage(), 'phone' => $phone]);

            throw new RuntimeException('SMS send error: '.$e->getMessage(), 0, $e);
        }
    }
}
