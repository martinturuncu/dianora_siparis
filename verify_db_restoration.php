<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$results = [
    'is_manuel_exists' => Schema::connection('sqlsrv')->hasColumn('Siparisler', 'is_manuel'),
    'ulke_exists' => Schema::connection('sqlsrv')->hasColumn('Siparisler', 'Ulke'),
    'sabit_ayarlar_exists' => Schema::connection('sqlsrv')->hasTable('sabit_ayarlar'),
    'sabit_ayarlar_count' => Schema::connection('sqlsrv')->hasTable('sabit_ayarlar') ? DB::connection('sqlsrv')->table('sabit_ayarlar')->count() : 0,
    'is_manuel_updated' => Schema::connection('sqlsrv')->hasColumn('Siparisler', 'is_manuel') ? DB::connection('sqlsrv')->table('Siparisler')->where('is_manuel', 1)->count() : 0,
];

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
