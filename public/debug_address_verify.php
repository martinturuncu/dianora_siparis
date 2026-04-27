<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

try {
    echo "Debugging Etsy Address with NEW Token...\n";
    
    $settings = DB::connection('sqlsrv')->table('sabit_ayarlar')
        ->whereIn('Anahtar', ['etsy_client_id', 'etsy_refresh_token', 'etsy_shop_id'])
        ->pluck('Deger', 'Anahtar');
    $clientId = $settings['etsy_client_id'] ?? null;
    $refreshToken = $settings['etsy_refresh_token'] ?? null;
    $shopId = $settings['etsy_shop_id'] ?? null;
    
    // Refresh Token
    $resp = Http::asForm()->post('https://api.etsy.com/v3/public/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $clientId,
        'refresh_token' => $refreshToken
    ]);
    
    $accessToken = $resp->json('access_token');
    
    if (!$accessToken) die("Token failed: " . $resp->body());
    
    if (!$shopId) {
        $userResp = Http::withHeaders(['x-api-key' => $clientId, 'Authorization' => 'Bearer ' . $accessToken])
            ->get('https://openapi.etsy.com/v3/application/users/me');
        $userId = $userResp->json('user_id');
        $shopResp = Http::withHeaders(['x-api-key' => $clientId, 'Authorization' => 'Bearer ' . $accessToken])
            ->get("https://openapi.etsy.com/v3/application/users/{$userId}/shops");
        $shopId = $shopResp->json('shops')[0]['shop_id'];
    }
    
    // Fetch 5 Receipts
    $receiptsResp = Http::withHeaders([
        'x-api-key' => $clientId,
        'Authorization' => 'Bearer ' . $accessToken
    ])->get("https://openapi.etsy.com/v3/application/shops/{$shopId}/receipts", [
        'limit' => 5,
        'sort_on' => 'created', 
        'sort_order' => 'desc'
    ]);
    
    $results = $receiptsResp->json('results');
    foreach ($results as $r) {
        echo "ID: " . $r['receipt_id'] . "\n";
        echo "First Line: " . ($r['first_line'] ?? 'NULL') . "\n";
        echo "City: " . ($r['city'] ?? 'NULL') . "\n";
        echo "Format Addr: " . ($r['formatted_address'] ?? 'NULL') . "\n";
        echo "----------------\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
