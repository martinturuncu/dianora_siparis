<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Services\CurrencyService;

$service = new CurrencyService();
$prices = $service->getPrices();

if (isset($prices['USD_KG'])) {
    echo "SUCCESS: USD_KG found!\n";
    print_r($prices['USD_KG']);
} else {
    echo "FAILURE: USD_KG NOT in response.\n";
    print_r($prices);
}
