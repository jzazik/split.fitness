<?php

namespace App\Providers;

use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\SmsCRuProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProviderInterface::class, function () {
            if ($this->app->environment('production') || config('services.smscru.force')) {
                return new SmsCRuProvider;
            }

            return new LogSmsProvider;
        });
    }
}
