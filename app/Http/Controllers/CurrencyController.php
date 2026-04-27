<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        // We fetch fresh prices for this page, or we could rely on the view composer's cache if we wanted.
        // But to ensure recalculations are based on the latest data available to the service, we call it here.
        // Note: The service itself doesn't cache internally, the AppServiceProvider does.
        // So this will do a fresh request unless we wrap it in Cache here too.
        // For 'Live' page, maybe we want it fresh?
        // Let's use the same cache key 'currency_prices' to be consistent if it was just fetched.
        // Or actually, let's just reuse the logic or call getPrices directly.
        // Since user wants "Live", let's not over-cache here, but the ServiceProvider already caches for 1 min.
        // Calling getPrices() again will trigger a new request if we don't use Cache facade here.
        // However, standard practice is to rely on the service.
        // To avoid double request if the layout is already doing it, we could just let the layout handle it
        // BUT the controller needs to pass data to ITS view content potentially.
        // Let's just fetch it.

        $prices = \Illuminate\Support\Facades\Cache::remember('currency_prices', 60, function () { 
            return $this->currencyService->getPrices();
        });

        return view('doviz.index', compact('prices'));
    }
}
