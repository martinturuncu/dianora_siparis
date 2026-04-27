<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$allCities = DB::connection('sqlsrv')->table('Siparisler as s')
    ->select('s.Il', DB::raw('COUNT(*) as SiparisSayisi'))
    ->where('s.SiparisDurumu', '<>', 8)
    ->groupBy('s.Il')
    ->get();

$plateMap = [
    'İSTANBUL' => 'TR-34',
    // ... I'll just check a few more
];

echo "CITY MATCHING DEBUG:\n";
foreach ($allCities as $city) {
    $ilRaw = trim($city->Il);
    
    // My current logic
    $ilNormalized = str_replace(
        ['ı', 'i', 'ü', 'ö', 'ç', 'ş', 'ğ'],
        ['I', 'İ', 'Ü', 'Ö', 'Ç', 'Ş', 'Ğ'],
        $ilRaw
    );
    $ilUpper = mb_strtoupper($ilNormalized, "UTF-8");
    
    $match = isset($plateMap[$ilUpper]) ? $plateMap[$ilUpper] : "NO MATCH";
    
    if (strpos(strtoupper($ilRaw), 'STANBUL') !== false) {
        echo "Raw: '" . $ilRaw . "' | Normalized: '" . $ilUpper . "' | Match: " . $match . " | Count: " . $city->SiparisSayisi . "\n";
    }
}
