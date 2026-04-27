<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orderId = '10642';

echo "Checking SiparisID: $orderId\n";

$urunler = \Illuminate\Support\Facades\DB::connection('sqlsrv')->table('SiparisUrunleri')
    ->where('SiparisID', $orderId)
    ->get();

echo "Found " . $urunler->count() . " products in SiparisUrunleri table.\n";

foreach ($urunler as $u) {
    echo "\n\n==================================================\n";
    echo "Product SKU (StokKodu): [" . $u->StokKodu . "]\n";
    
    $stok = $u->StokKodu;
    
    $matches = \Illuminate\Support\Facades\DB::connection('sqlsrv')->select("
        SELECT UrunKodu, Id FROM Urunler 
        WHERE UrunKodu = ? 
        OR UrunKodu = ? 
        OR ? = UrunKodu + '-yeni'
    ", [$stok, $stok . '-yeni', $stok]);
        
    echo "Found " . count($matches) . " MATCHES in Urunler table for SKU [" . $stok . "]:\n";
    foreach($matches as $m) {
        echo "   -> Match: UrunKodu=[" . $m->UrunKodu . "] | ID=" . $m->Id . "\n";
    }
}
