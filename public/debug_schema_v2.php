<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

$identity = DB::select("
    SELECT 
        c.name AS ColumnName
    FROM 
        sys.tables t
    JOIN 
        sys.identity_columns c ON t.object_id = c.object_id
    WHERE 
        t.name = 'FaturaBilgisi'
");

echo "Identity Column(s): ";
foreach ($identity as $row) {
    echo $row->ColumnName . " ";
}
echo "\n";

$columns = DB::select("SELECT COLUMN_NAME, ORDINAL_POSITION FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'FaturaBilgisi' ORDER BY ORDINAL_POSITION");
echo "All Columns:\n";
foreach ($columns as $col) {
    echo $col->COLUMN_NAME . " (" . $col->ORDINAL_POSITION . ")\n";
}
