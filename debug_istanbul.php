<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$rawStats = DB::connection('sqlsrv')->table('Siparisler as s')
    ->join('SiparisUrunleri as u', 's.SiparisID', '=', 'u.SiparisID')
    ->select(
        's.Il',
        DB::raw('SUM(u.Miktar) as ToplamUrun'),
        DB::raw('COUNT(DISTINCT s.SiparisID) as SiparisSayisi')
    )
    ->where('s.SiparisDurumu', '<>', 8)
    ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
    ->where(function($q) {
        $q->where('s.Il', 'LIKE', '%STANBUL%')
          ->orWhere('s.Il', 'LIKE', '%stanbul%');
    })
    ->groupBy('s.Il')
    ->get();

echo "ISTANBUL DATA INVESTIGATION:\n";
foreach ($rawStats as $stat) {
    echo "Raw DB Value: '" . $stat->Il . "' | Hex: " . bin2hex($stat->Il) . " | Total Products: " . $stat->ToplamUrun . " | Order Count: " . $stat->SiparisSayisi . "\n";
    
    // Test normalization
    $ilRaw = trim($stat->Il);
    $ilNormalized = str_replace(
        ['ı', 'i', 'ü', 'ö', 'ç', 'ş', 'ğ'],
        ['I', 'İ', 'Ü', 'Ö', 'Ç', 'Ş', 'Ğ'],
        $ilRaw
    );
    $ilUpper = mb_strtoupper($ilNormalized, "UTF-8");
    $ilUpper = preg_replace('/\s*\(\s*/', '(', $ilUpper);
    $ilUpper = preg_replace('/\s*\)\s*/', ')', $ilUpper);
    
    echo "Normalized: '" . $ilUpper . "'\n";
    echo "----------------------------\n";
}
