<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    $conn = DB::connection('sqlsrv');
    $pdo = $conn->getPdo();
    
    echo "Testing IDENTITY_INSERT for SiparisUrunleri...\n";
    
    // Test ID
    $testId = 999999;
    
    // Ensure it doesn't exist
    $conn->table('SiparisUrunleri')->where('Id', $testId)->delete();
    
    echo "Executing combined SET and INSERT query...\n";
    $sql = "
        SET IDENTITY_INSERT dbo.SiparisUrunleri ON;
        INSERT INTO dbo.SiparisUrunleri (Id, SiparisID, UrunAdi, StokKodu, Miktar, BirimFiyat, Tutar, KdvTutari) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?);
        SET IDENTITY_INSERT dbo.SiparisUrunleri OFF;
    ";
    
    $conn->statement($sql, [
        $testId,
        'TEST_ID',
        'Test Product',
        'TEST',
        1,
        0,
        0,
        0
    ]);
    
    echo "Success! Test record inserted.\n";
    
    // Clean up
    $conn->table('SiparisUrunleri')->where('Id', $testId)->delete();

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
