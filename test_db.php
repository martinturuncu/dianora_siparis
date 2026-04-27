<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing DB connection...\n";
    $count = \Illuminate\Support\Facades\DB::connection('sqlsrv')->table('Urunler')->count();
    echo "Connection Successful! Total Products: " . $count . "\n";
    
    echo "Fetching first 5 products...\n";
    $products = \Illuminate\Support\Facades\DB::connection('sqlsrv')->table('Urunler')->take(5)->get();
    foreach($products as $p) {
        echo $p->UrunKodu . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
