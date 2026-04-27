<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Fetch live prices from the API.
     *
     * @return array
     */
    public function getPrices()
    {
        try {
            $response = Http::timeout(5)->get('https://prdprc.saglamoglu.app/api/v1/prices/currentmarketproductprices');

            if ($response->failed()) {
                Log::error('Currency API request failed: ' . $response->status());
                return [];
            }

            $data = $response->json();

            if (!isset($data['data']) || !is_array($data['data'])) {
                Log::error('Currency API returned invalid format.');
                return [];
            }

            $filtered = [];
            $targetIds = [
                1 => 'HAS_ALTIN',
                3 => 'USD_KG',
                9 => 'USD_TL',
                10 => 'EUR_TL'
            ];

            foreach ($data['data'] as $item) {
                if (isset($targetIds[$item['marketProductId']])) {
                    $key = $targetIds[$item['marketProductId']];
                    $filtered[$key] = [
                        'buy' => $item['customerBuysAt'],
                        'sell' => $item['customerSellsAt'],
                    ];
                }
            }

            return $filtered;

        } catch (\Exception $e) {
            Log::error('CurrencyService Error: ' . $e->getMessage());
            return [];
        }
    }
}
