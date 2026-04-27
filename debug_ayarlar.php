<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $ayarlar = DB::connection('sqlsrv')->table('Ayarlar')->first();
    print_r($ayarlar);
} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage();
}
