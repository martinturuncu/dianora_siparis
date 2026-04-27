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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $prices = \Illuminate\Support\Facades\Cache::remember('currency_prices', 60, function () { 
                return (new \App\Services\CurrencyService())->getPrices();
            });
            $view->with('currencyPrices', $prices);
        });
    }
}
