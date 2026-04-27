<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orderId = '10642';

echo "Verifying fix for SiparisID: $orderId\n";

// This mirrors the NEW controller logic
$sql = "
    SELECT 
        s.SiparisID,
        u.StokKodu,
        ur.UrunKodu as MatchedKodu,
        ur.Id as MatchedId
    FROM Siparisler s
    INNER JOIN SiparisUrunleri u ON s.SiparisID = u.SiparisID
    
    OUTER APPLY (
        SELECT TOP 1 *
        FROM Urunler matched_ur
        WHERE 
            matched_ur.UrunKodu = u.StokKodu 
            OR matched_ur.UrunKodu = u.StokKodu + '-yeni'
            OR u.StokKodu = matched_ur.UrunKodu + '-yeni'
        ORDER BY matched_ur.Id DESC
    ) ur
    
    WHERE s.SiparisID = ?
";

$results = \Illuminate\Support\Facades\DB::connection('sqlsrv')->select($sql, [$orderId]);

echo "Query returned " . count($results) . " rows (Expected: 2).\n";

foreach ($results as $row) {
    echo " - SKU: " . $row->StokKodu . " -> Matched: " . ($row->MatchedKodu ?? 'NULL') . " (ID: " . ($row->MatchedId ?? 'NULL') . ")\n";
}
