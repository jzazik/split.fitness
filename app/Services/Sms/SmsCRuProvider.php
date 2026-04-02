<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsCRuProvider implements SmsProviderInterface
{
    protected ?string $login;

    protected ?string $password;

    protected ?string $sender;

    protected string $baseUrl = 'https://smsc.ru/sys/send.php';

    public function __construct()
    {
        $this->login = config('services.smscru.login');
        $this->password = config('services.smscru.password');
        $this->sender = config('services.smscru.sender');

        if (empty($this->login) || empty($this->password)) {
            throw new RuntimeException('SMSC.RU is not configured');
        }
    }

    public function send(string $phone, string $message): void
    {
        try {
            $response = Http::get($this->baseUrl, [
                'login' => $this->login,
                'psw' => $this->password,
                'phones' => $phone,
                'sender' => $this->sender,
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
