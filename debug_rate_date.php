<?php
require 'c:/xampp/htdocs/dianora_siparis/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/dianora_siparis/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$siparisTarihi = '2025-12-30';
echo "Order Date: $siparisTarihi\n";

$ayar = DB::table('ayar_gecmisi')
    ->where('tarih', '<=', $siparisTarihi)
    ->orderBy('tarih', 'desc')
    ->first();

if ($ayar) {
    echo "Found Setting Date: " . $ayar->tarih . "\n";
    echo "Rate: " . $ayar->etsy_usa_tax_rate . "\n";
} else {
    echo "No settings found.\n";
}
