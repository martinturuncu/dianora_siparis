<?php
require 'c:/xampp/htdocs/dianora_siparis/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/dianora_siparis/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\KarHesapService;
use Illuminate\Support\Facades\DB;

$siparisId = '3935573659';

// 1. Check current DB value
$current = DB::connection('sqlsrv')->table('SiparisKarlar')->where('SiparisID', $siparisId)->first();
echo "Current DB GercekKar: " . ($current->GercekKar ?? 'NULL') . "\n";

// 2. Run Calc
$service = new KarHesapService();
$result = $service->hesaplaSiparis($siparisId);

// 3. Print Calc Result Keys
echo "Calculated Tax (USD): " . ($result['vergiUSD'] ?? 'NULL') . "\n";
echo "Calculated Tax (TL): " . ($result['vergi'] ?? 'NULL') . "\n";
echo "Calculated Rate Used: " . ($result['test_rate'] ?? 'NOT_SET') . "\n"; // I need to add this to service return to be sure

// 4. Check DB value again
$new = DB::connection('sqlsrv')->table('SiparisKarlar')->where('SiparisID', $siparisId)->first();
echo "New DB GercekKar: " . ($new->GercekKar ?? 'NULL') . "\n";
