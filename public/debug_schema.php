<?php
// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    // For SQL Server, we can query sys.identity_columns
    $identity = DB::select("
        SELECT 
            t.name AS TableName, 
            c.name AS ColumnName
        FROM 
            sys.tables t
        JOIN 
            sys.identity_columns c ON t.object_id = c.object_id
        WHERE 
            t.name = 'SiparisUrunleri'
    ");

    echo "<h1>Identity Column Check</h1>";
    echo "<pre>";
    print_r($identity);
    echo "</pre>";

    echo "<h2>All Columns</h2>";
     $columns = DB::select("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'SiparisUrunleri'");
     echo "<pre>";
     print_r($columns);
     echo "</pre>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
