<?php

namespace App\Providers;

use App\Currency;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Currency::class, function ($app) {
            return new Currency(env('OPEN_EXCHANGE_API_KEY'));
        });
    }
}
