<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

$results = DB::select("
    SELECT 
        name AS ColumnName,
        is_identity AS IsIdentity
    FROM 
        sys.columns
    WHERE 
        object_id = OBJECT_ID('SiparisUrunleri')
");

echo "SiparisUrunleri Columns:\n";
foreach ($results as $row) {
    echo "{$row->ColumnName}: " . ($row->IsIdentity ? "IDENTITY" : "normal") . "\n";
}

$results2 = DB::select("
    SELECT 
        name AS ColumnName,
        is_identity AS IsIdentity
    FROM 
        sys.columns
    WHERE 
        object_id = OBJECT_ID('Siparisler')
");

echo "\nSiparisler Columns:\n";
foreach ($results2 as $row) {
    echo "{$row->ColumnName}: " . ($row->IsIdentity ? "IDENTITY" : "normal") . "\n";
}
