<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Http;

$response = Http::get('https://prdprc.saglamoglu.app/api/v1/prices/currentmarketproductprices');
$data = $response->json()['data'];

echo "Searching for price around 142,000...\n\n";

foreach ($data as $item) {
    if ($item['customerBuysAt'] > 140000 && $item['customerBuysAt'] < 145000) {
        echo "FOUND MATCH!\n";
        echo "ID: " . $item['id'] . "\n";
        echo "MarketProductID: " . $item['marketProductId'] . "\n";
        echo "Buy: " . $item['customerBuysAt'] . "\n";
        echo "Sell: " . $item['customerSellsAt'] . "\n";
        echo "------------------\n";
    }
}

echo "\nChecking ID 3 explicitly:\n";
foreach ($data as $item) {
    if ($item['marketProductId'] == 3) {
        print_r($item);
    }
}
