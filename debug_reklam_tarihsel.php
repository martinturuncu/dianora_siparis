<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

echo "=== REKLAM GİDERİ TARİHSEL KONTROL ===\n\n";

// 1. Ayar Geçmişini Kontrol Et
echo "1. AYAR GEÇMİŞİ (reklam değerleri):\n";
$ayarlar = DB::table('ayar_gecmisi')
    ->select('tarih', 'reklam')
    ->orderBy('tarih', 'desc')
    ->limit(10)
    ->get();

foreach ($ayarlar as $a) {
    echo "   Tarih: {$a->tarih} => Reklam: {$a->reklam} TL\n";
}

echo "\n2. SON 7 GÜNLÜK SİPARİŞ DAĞILIMI:\n";

// Hangi tarihlerde kaç ürün satılmış ve o tarihteki reklam değeri ne
$dagilim = DB::connection('sqlsrv')->select("
    SELECT 
        CAST(s.Tarih as DATE) as SiparisTarihi,
        SUM(u.Miktar) as UrunSayisi,
        ISNULL(a.reklam, 0) as ReklamDegeri,
        SUM(u.Miktar * ISNULL(a.reklam, 0)) as ToplamReklam
    FROM SiparisUrunleri u
    JOIN Siparisler s ON s.SiparisID = u.SiparisID
    OUTER APPLY (
        SELECT TOP 1 reklam
        FROM ayar_gecmisi
        WHERE tarih <= CAST(s.Tarih as DATE)
        ORDER BY tarih DESC
    ) a
    WHERE s.Tarih >= DATEADD(day, -7, GETDATE())
    AND s.SiparisDurumu <> 8
    AND s.AdiSoyadi != 'Dianora Piercing'
    AND u.StokKodu NOT LIKE '%hediye%'
    GROUP BY CAST(s.Tarih as DATE), a.reklam
    ORDER BY SiparisTarihi DESC
");

foreach ($dagilim as $d) {
    echo "   {$d->SiparisTarihi}: {$d->UrunSayisi} ürün x {$d->ReklamDegeri} TL = {$d->ToplamReklam} TL\n";
}

echo "\n3. TOPLAM HESAPLAMA:\n";
$toplam = 0;
foreach ($dagilim as $d) {
    $toplam += $d->ToplamReklam;
}
echo "   Son 7 gün toplam: {$toplam} TL\n";
