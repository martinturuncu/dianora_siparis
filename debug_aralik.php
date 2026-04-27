<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TAM ARALIK KONTROLU (26.11.2025 - 26.12.2025) ===\n\n";

$baslangic = '2025-11-26';
$bitis = '2025-12-26';

$start = Carbon::parse($baslangic)->startOfDay();
$end = Carbon::parse($bitis)->endOfDay();

// 1. Toplam Ürün Sayısı Kontrolü
$urunSayisi = DB::connection('sqlsrv')->table('SiparisUrunleri')
    ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
    ->whereBetween('Siparisler.Tarih', [$start, $end])
    ->where('Siparisler.SiparisDurumu', '<>', 8)
    ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
    ->sum('SiparisUrunleri.Miktar');

echo "1. Toplam Satılan Ürün: {$urunSayisi}\n\n";

// 2. Ayar Geçmişini Kontrol Et
echo "2. AYAR GEÇMİŞİ:\n";
$ayarlar = DB::table('ayar_gecmisi')
    ->select('tarih', 'reklam')
    ->orderBy('tarih', 'asc')
    ->get();

foreach ($ayarlar as $a) {
    echo "   {$a->tarih} => Reklam: {$a->reklam} TL\n";
}

// 3. Tarih Bazlı Dağılım
echo "\n3. TARİH BAZLI DAĞILIM:\n";

$dagilim = DB::connection('sqlsrv')->select("
    SELECT 
        CAST(s.Tarih as DATE) as SiparisTarihi,
        SUM(u.Miktar) as UrunSayisi,
        ISNULL(a.reklam, 0) as ReklamDegeri
    FROM SiparisUrunleri u
    JOIN Siparisler s ON s.SiparisID = u.SiparisID
    OUTER APPLY (
        SELECT TOP 1 reklam
        FROM ayar_gecmisi
        WHERE tarih <= CAST(s.Tarih as DATE)
        ORDER BY tarih DESC
    ) a
    WHERE s.Tarih BETWEEN ? AND ?
    AND s.SiparisDurumu <> 8
    AND s.AdiSoyadi != 'Dianora Piercing'
    AND u.StokKodu NOT LIKE '%hediye%'
    GROUP BY CAST(s.Tarih as DATE), a.reklam
    ORDER BY SiparisTarihi ASC
", [$start, $end]);

$toplamUrun = 0;
$toplamReklam = 0;
$urunWithZeroReklam = 0;

foreach ($dagilim as $d) {
    $reklam = $d->UrunSayisi * $d->ReklamDegeri;
    $toplamUrun += $d->UrunSayisi;
    $toplamReklam += $reklam;
    
    if ($d->ReklamDegeri == 0) {
        $urunWithZeroReklam += $d->UrunSayisi;
    }
    
    echo "   {$d->SiparisTarihi}: {$d->UrunSayisi} ürün x {$d->ReklamDegeri} TL = {$reklam} TL\n";
}

echo "\n4. ÖZET:\n";
echo "   Toplam Ürün (sorguda): {$toplamUrun}\n";
echo "   Sıfır Reklamlı Ürün: {$urunWithZeroReklam}\n";
echo "   Hesaplanan Toplam Reklam: {$toplamReklam} TL\n";
echo "   Beklenen (196 x 200): " . (196 * 200) . " TL\n";
