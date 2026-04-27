<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $first = DB::connection('sqlsrv')->table('Pazaryerleri')->first();
    echo "Columns: " . implode(', ', array_keys((array)$first)) . "\n\n";

    $pazaryerleri = DB::connection('sqlsrv')->table('Pazaryerleri')->get();
    foreach ($pazaryerleri as $p) {
        // Dinamik olarak property erişimi veya array cast
        $pArr = (array)$p;
        $id = $pArr['id'] ?? $pArr['Id'] ?? $pArr['ID'] ?? 'N/A';
        $ad = $pArr['Ad'] ?? $pArr['ad'] ?? $pArr['Name'] ?? 'N/A';
        echo "ID: {$id} - Ad: {$ad}\n";
    }
} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage();
}
