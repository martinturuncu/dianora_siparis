<?php
require __DIR__ . '/../vendor/autoload.php';
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check DB Name
echo "<h3>DB Connected: " . DB::connection('sqlsrv')->getDatabaseName() . " on " . Config::get('database.connections.sqlsrv.host') . "</h3>";

// Search for specific Order No
$searchNo = 'M00037';
echo "<h3>Search for SiparisNo LIKE '%$searchNo%'</h3>";
$sql = "
    SELECT 
        SiparisID,
        SiparisNo,
        Tarih,
        PazaryeriID,
        SiparisDurumu,
        AdiSoyadi,
        Telefon
    FROM Siparisler
    WHERE SiparisNo LIKE ?
    ORDER BY Tarih DESC
";
$results = DB::connection('sqlsrv')->select($sql, ['%' . $searchNo . '%']);

echo "<table border='1'><tr><th>ID</th><th>No</th><th>Tarih</th><th>PazaryeriID</th><th>Durum</th><th>Ad</th><th>Telefon</th></tr>";
foreach($results as $r) {
    echo "<tr>
        <td>{$r->SiparisID}</td>
        <td>{$r->SiparisNo}</td>
        <td>{$r->Tarih}</td>
        <td>{$r->PazaryeriID}</td>
        <td>{$r->SiparisDurumu}</td>
        <td>{$r->AdiSoyadi}</td>
        <td>{$r->Telefon}</td>
    </tr>";
}
echo "</table>";
