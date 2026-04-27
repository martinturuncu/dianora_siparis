<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    // Create a dummy product for testing
    // Using a very distinct UrunKodu to avoid collision
    $testKodu = 'TEST_DELETE_9999';
    
    // Check if it already exists
    DB::connection('sqlsrv')->table('Urunler')->where('UrunKodu', $testKodu)->delete();

    $id = DB::connection('sqlsrv')->table('Urunler')->insertGetId([
        'UrunKodu' => $testKodu,
        'Gram' => 1.23,
        'KategoriId' => 1
    ]);

    echo "Created test product with ID: $id\n";

    // Test the DB deletion logic
    $deletedCount = DB::connection('sqlsrv')->table('Urunler')->where('Id', $id)->delete();

    if ($deletedCount > 0) {
        echo "Successfully deleted test product.\n";
    } else {
        echo "Failed to delete test product.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
