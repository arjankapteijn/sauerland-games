<?php

namespace App\Providers;

use App\Services\Signal\SignalGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SignalGateway::class, fn () => new SignalGateway(
            apiUrl: (string) config('services.signal.api_url'),
            botNumber: (string) config('services.signal.bot_number'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
