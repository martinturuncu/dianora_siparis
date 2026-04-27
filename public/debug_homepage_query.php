<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

// Copying the query logic from SiparisController:: index
$sql = "
    SELECT TOP 20
        s.SiparisID,
        s.AdiSoyadi, 
        s.Telefon,
        ISNULL(mc.SiparisSayisi, 0) as MusteriSiparisSayisi
    FROM Siparisler s
    LEFT JOIN (
        SELECT Telefon, COUNT(*) as SiparisSayisi 
        FROM Siparisler 
        WHERE SiparisDurumu <> 8 AND Telefon IS NOT NULL AND LEN(Telefon) > 5
        GROUP BY Telefon
    ) mc ON mc.Telefon = s.Telefon
    ORDER BY s.Tarih DESC
";

$results = DB::connection('sqlsrv')->select($sql);

echo "<table border='1'><tr><th>SiparisID</th><th>Ad Soyad</th><th>Telefon</th><th>Sayi</th></tr>";
foreach($results as $r) {
    echo "<tr>";
    echo "<td>{$r->SiparisID}</td>";
    echo "<td>{$r->AdiSoyadi}</td>";
    echo "<td>{$r->Telefon}</td>";
    echo "<td>{$r->MusteriSiparisSayisi}</td>";
    echo "</tr>";
}
echo "</table>";
