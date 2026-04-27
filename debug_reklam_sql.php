<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    echo "Testing SQL connection...\n";
    $tarih = Carbon::now()->toDateString();
    
    echo "Date: $tarih\n";

    echo "Running Daily Ad Expense Query...\n";
    
    $query = "
            SELECT SUM(u.Miktar * ISNULL(a.reklam, 0)) as ToplamReklam
            FROM SiparisUrunleri u
            JOIN Siparisler s ON s.SiparisID = u.SiparisID
            OUTER APPLY (
                SELECT TOP 1 reklam
                FROM ayar_gecmisi
                WHERE tarih <= CAST(s.Tarih as DATE)
                ORDER BY tarih DESC
            ) a
            WHERE CAST(s.Tarih as DATE) = ?
            AND s.SiparisDurumu <> 8
            AND s.AdiSoyadi != 'Dianora Piercing'
            AND u.StokKodu NOT LIKE '%hediye%'
    ";

    $results = DB::connection('sqlsrv')->select($query, [$tarih]);
    
    echo "Query Result:\n";
    print_r($results);

    if (isset($results[0])) {
        echo "Value: " . ($results[0]->ToplamReklam ?? "NULL") . "\n";
    } else {
        echo "No result row.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
