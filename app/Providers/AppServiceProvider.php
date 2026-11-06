<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aparte Mollie-client voor de Balances API: die vraagt een organization access
        // token, de gewone MOLLIE_KEY van de laravel-mollie-facade werkt daar niet.
        $this->app->bind('mollie.org_client', fn () => (new \Mollie\Api\MollieApiClient)
            ->setAccessToken((string) config('mollie_fees.org_token')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
