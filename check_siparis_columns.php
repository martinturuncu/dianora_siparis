<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::connection('sqlsrv')->getColumnListing('Siparisler');
print_r($columns);

echo "\n\nColumn Types:\n";
foreach ($columns as $column) {
    try {
        $type = Schema::connection('sqlsrv')->getColumnType('Siparisler', $column);
        echo "$column: $type\n";
    } catch (\Exception $e) {
        echo "$column: Error getting type (" . $e->getMessage() . ")\n";
    }
}
