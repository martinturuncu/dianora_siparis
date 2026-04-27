<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

// Find a customer with mixed currency orders if possible, or just > 2 orders
$sql = "
    SELECT TOP 1 Telefon, AdiSoyadi, COUNT(*) as SiparisSayisi 
    FROM Siparisler 
    WHERE SiparisDurumu <> 8 AND Telefon IS NOT NULL AND LEN(Telefon) > 5
    GROUP BY Telefon, AdiSoyadi 
    HAVING COUNT(*) >= 2
    ORDER BY SiparisSayisi DESC
";

$customer = DB::connection('sqlsrv')->selectOne($sql);

if (!$customer) {
    die("No suitable customer found.");
}

echo "<h3>Analysing Customer: {$customer->AdiSoyadi} ({$customer->Telefon})</h3>";

$orders = DB::connection('sqlsrv')->table('Siparisler')
    ->where('Telefon', $customer->Telefon)
    ->where('SiparisDurumu', '<>', 8)
    ->get();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>SiparisID</th><th>PazaryeriID</th><th>Tarih</th><th>SiparisUrunleri Toplam</th><th>Para Birimi</th></tr>";

$grandTotalRaw = 0;
$currencyTotals = ['TL' => 0, 'USD' => 0];

foreach ($orders as $order) {
    $items = DB::connection('sqlsrv')->table('SiparisUrunleri')
        ->where('SiparisID', $order->SiparisID)
        ->get();

    $orderSum = 0;
    foreach($items as $item) {
        $orderSum += ($item->Tutar + $item->KdvTutari) * $item->Miktar;
    }
    
    $currency = ($order->PazaryeriID == 3) ? 'USD' : 'TL';
    
    echo "<tr>";
    echo "<td>{$order->SiparisID}</td>";
    echo "<td>{$order->PazaryeriID}</td>";
    echo "<td>{$order->Tarih}</td>";
    echo "<td>" . number_format($orderSum, 2) . "</td>";
    echo "<td>{$currency}</td>";
    echo "</tr>";

    $grandTotalRaw += $orderSum;
    $currencyTotals[$currency] += $orderSum;
}
echo "</table>";

echo "<h3>Controller Logic Calculation (Raw Sum): " . number_format($grandTotalRaw, 2) . "</h3>";
echo "<h3>Correct Broken Down Totals:</h3>";
print_r($currencyTotals);
