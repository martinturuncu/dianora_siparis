<?php
// Laravel Çekirdeğini Yükle
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\KarHesapService;
use Illuminate\Support\Facades\DB;

// Servisi Al
$karService = app(KarHesapService::class);

echo "<h1>Eski Siparişlerin Kâr Hesaplanması Başlatılıyor...</h1>";
echo "<p>Hedef: 2025-10-09 Öncesi, İptal Olmayan Siparişler</p>";
flush();

// Siparişleri Bul
$siparisler = DB::connection('sqlsrv')->table('Siparisler')
    ->select('SiparisID', 'Tarih', 'SiparisNo')
    ->where('Tarih', '<', '2025-10-09')
    ->where('SiparisDurumu', '!=', 8) // İptal olmayanlar
    ->orderBy('Tarih', 'desc')
    ->get();

$count = $siparisler->count();
echo "<p>Toplam <strong>$count</strong> adet sipariş bulundu.</p>";
echo "<ul>";

$i = 0;
foreach ($siparisler as $sip) {
    try {
        $sonuc = $karService->hesaplaSiparis($sip->SiparisID);
        $i++;
        if ($i % 50 == 0) {
            echo "<li>$i. Kayıt İşlendi (Sonuncusu: {$sip->SiparisNo})</li>";
            flush();
        }
    } catch (\Exception $e) {
        echo "<li style='color:red'>Hata (ID: {$sip->SiparisID}): " . $e->getMessage() . "</li>";
    }
}

echo "</ul>";
echo "<h3>Tüm İşlem Tamamlandı! Toplam $i adet sipariş yeniden hesaplandı.</h3>";
