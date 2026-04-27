<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

echo "<h1>DB Kontrolü</h1>";

// 1. Eski bir sipariş bul
$oldOrder = DB::table('Siparisler')
    ->select('SiparisID', 'Tarih')
    ->where('Tarih', '<', '2025-10-01')
    ->where('SiparisDurumu', '!=', 8)
    ->orderBy('Tarih', 'desc')
    ->first();

if (!$oldOrder) {
    die("Eski sipariş bulunamadı!");
}

echo "Eski Sipariş ID: " . $oldOrder->SiparisID . " Tarih: " . $oldOrder->Tarih . "<br>";

// 2. SiparisKarlar tablosuna bak
$karKaydi = DB::table('SiparisKarlar')
    ->where('SiparisID', $oldOrder->SiparisID)
    ->get();

echo "<h3>SiparisKarlar Tablosu:</h3>";
if ($karKaydi->isEmpty()) {
    echo "KAYIT YOK! (Bulk script çalışmamış veya kaydetmemiş)<br>";
} else {
    foreach($karKaydi as $k) {
        echo "Kod: {$k->UrunKodu} | Kar: {$k->GercekKar} | Tarih: {$k->HesapTarihi}<br>";
    }
}
