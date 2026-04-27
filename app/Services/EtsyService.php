<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EtsyService
{
    protected $baseUrl = 'https://openapi.etsy.com/v3';
    
    // Sabit Ayarlar Tablosundan Okunacaklar
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $shopId;

    public function __construct()
    {
        // Constructor'da yüklemiyoruz, sync anında DB'den taze çekmek daha güvenli
    }

    protected function loadCredentials()
    {
        $settings = DB::connection('mysql')->table('sabit_ayarlar')
            ->whereIn('Anahtar', ['etsy_client_id', 'etsy_client_secret', 'etsy_refresh_token', 'etsy_shop_id'])
            ->pluck('Deger', 'Anahtar');

        $this->clientId     = $settings['etsy_client_id'] ?? null;
        $this->clientSecret = $settings['etsy_client_secret'] ?? null;
        $this->refreshToken = $settings['etsy_refresh_token'] ?? null;
        $this->shopId       = $settings['etsy_shop_id'] ?? null;

        if (!$this->clientId || !$this->refreshToken) {
            throw new \Exception("Etsy API Anahtarları (Client ID veya Refresh Token) eksik! Lütfen Sabit Ayarlar tablosunu kontrol edin.");
        }
    }

    /**
     * Ana Senkronizasyon Metodu
     * Default: Son 15 gün, 10 sipariş (Performans ve Hız için)
     */
    public function sync($limit = 10, $days = 15)
    {
        // 1. Ayarları Yükle
        $this->loadCredentials();

        // 2. Token Yenile
        $tokens = $this->refreshAccessToken(); // Helper method returns array
        $accessToken = $tokens['access_token'];
        
        // Refresh token değiştiyse güncelle
        if (isset($tokens['refresh_token'])) {
            $this->updateSetting('etsy_refresh_token', $tokens['refresh_token']);
            $this->refreshToken = $tokens['refresh_token']; 
        }

        // 3. Shop ID Kontrolü
        if (!$this->shopId) {
            $this->shopId = $this->fetchShopId($accessToken);
            $this->updateSetting('etsy_shop_id', $this->shopId);
        }

        // 4. Siparişleri Çek
        $receipts = $this->fetchReceipts($accessToken, $this->shopId, $limit, $days);
        
        $newCount = 0;
        $updatedCount = 0;

        foreach ($receipts as $receipt) {
            $receiptId = (string)$receipt['receipt_id'];

            // Duplicate Check
            $existingOrder = DB::connection('mysql')->table('Siparisler')
                ->where('SiparisID', $receiptId)
                ->first();

            // Status Map
            $convertedStatus = $this->mapEtsyStatus($receipt);
            
            // --- Veri Mapping (Values Extraction) ---
            $shipping = $receipt['shipping_address'] ?? []; 
            
            // Ülke
            $countryIso = $receipt['country_iso'] ?? ($shipping['country_iso'] ?? '');
            
            // İsim
            $buyerName = $receipt['name'] ?? ($receipt['buyer_user_id'] ?? 'Etsy User');
            
            // İletişim & Adres
            $email      = $receipt['buyer_email'] ?? '';
            $adres      = $receipt['first_line'] ?? ($shipping['first_line'] ?? '');
            $adres2     = $receipt['second_line'] ?? ($shipping['second_line'] ?? '');
            $fullAdres  = trim($adres . ' ' . $adres2);
            $sehir      = $receipt['city'] ?? ($shipping['city'] ?? '');
            $eyalet     = $receipt['state'] ?? ($shipping['state'] ?? '');
            $zip        = $receipt['zip'] ?? ($shipping['zip'] ?? '');
            $telefon    = $shipping['phone'] ?? '';

            if ($existingOrder) {
                $updateData = [];

                // 1. Durum Kontrolü
                if ((int)$existingOrder->SiparisDurumu !== $convertedStatus) {
                    $updateData['SiparisDurumu'] = $convertedStatus;
                }

                // 2. Eksik Veri Kontrolü (Email, Telefon)
                if (empty($existingOrder->Email) && !empty($email)) {
                    $updateData['Email'] = $email;
                }
                if (empty($existingOrder->Telefon) && !empty($telefon)) {
                    $updateData['Telefon'] = $telefon;
                }
                // Adres ve detayları artık almıyoruz (API vermiyor)

                if (!empty($updateData)) {
                     DB::connection('mysql')->table('Siparisler')
                        ->where('SiparisID', $receiptId)
                        ->update($updateData);
                     $updatedCount++;
                }
                continue; 
            }

            // Yeni Sipariş Insert
            try {
                DB::connection('mysql')->transaction(function () use ($receipt, $convertedStatus, $receiptId, $buyerName, $email, $telefon, $accessToken) {
                    
                    // KUR HESABI
                    $orderDate = Carbon::createFromTimestamp($receipt['created_timestamp'])->setTimezone('Europe/Istanbul');
                    $dolarKuru = $this->getExchangeRate($orderDate);

                    // Tutar ve İndirimler (USD)
                    $tutarUSD = $this->parseMoney($receipt['grandtotal'] ?? []);
                    $kargoTutarUSD = $this->parseMoney($receipt['total_shipping_cost'] ?? []);
                    $indirimUSD = $this->parseMoney($receipt['discount_amt'] ?? []);

                    // TRY Çevirimi
                    $tutarTRY      = $tutarUSD * $dolarKuru;
                    $kargoTutarTRY = $kargoTutarUSD * $dolarKuru;
                    $indirimTRY    = $indirimUSD * $dolarKuru;

                    // 1. Siparişi Ekle
                    DB::connection('mysql')->table('Siparisler')->insert([
                        'SiparisID'     => $receiptId,
                        'AdiSoyadi'     => $buyerName,
                        'Email'         => $email,
                        'Telefon'       => $telefon,
                        'Adres'         => '', // API vermiyor
                        'Il'            => '', // API vermiyor
                        'Ilce'          => '', // API vermiyor
                        'Tarih'         => $orderDate,
                        'Tutar'         => $tutarTRY,
                        'KargoTutar'    => $kargoTutarTRY,
                        'odemeIndirimi' => $indirimTRY,
                        'SiparisNotu'   => $receipt['message_from_buyer'] ?? '',
                        'PazaryeriID'   => 3, // Etsy
                        'Onaylandi'     => 1,
                        'isUSA'         => 0, // Varsayılan 0, kullanıcı manuel düzeltecek
                        'SiparisDurumu' => $convertedStatus,
                        'SiparisNo'     => $receiptId,
                        'is_manuel'     => 0,
                    ]);

                    // 2. İşlemleri (Transactions) Çek
                    $transactions = $this->fetchTransactions($accessToken, $receiptId);

                    foreach ($transactions as $trans) {
                        $priceUSD = $this->parseMoney($trans['price'] ?? []);
                        $priceTRY = $priceUSD * $dolarKuru;
                        
                        DB::connection('mysql')->table('SiparisUrunleri')->insert([
                            'SiparisID'  => $receiptId,
                            'UrunAdi'    => $trans['title'] ?? 'Etsy Product',
                            'StokKodu'   => $trans['sku'] ?? '',
                            'Miktar'     => $trans['quantity'] ?? 1,
                            'Tutar'      => $priceTRY,
                            'BirimFiyat' => null,
                            'KdvTutari'  => 0,
                        ]);
                    }
                });
                $newCount++;
            } catch (\Exception $e) {
                Log::error("Etsy Sipariş Ekleme Hatası (ID: $receiptId): " . $e->getMessage());
            }
        }

        return "Etsy Senkronizasyonu Tamamlandı: $newCount yeni sipariş eklendi, $updatedCount sipariş güncellendi.";
    }
    
    /**
     * İlgili tarihteki Dolar kurunu çeker.
     * Bulamazsa 1 döndürür (Hata vermemek için, fakat tutarlar yanlış olur).
     */
    private function getExchangeRate($date)
    {
        $dateStr = $date->toDateString();
        
        $rate = DB::table('ayar_gecmisi')
            ->where('tarih', '<=', $dateStr)
            ->orderBy('tarih', 'desc')
            ->value('dolar_kuru');

        return $rate ? (float)$rate : 1.0;
    }


    /**
     * A1) Access Token Yenileme
     */
    private function refreshAccessToken()
    {
        $response = Http::asForm()->post('https://api.etsy.com/v3/public/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken
        ]);
        
        if ($response->failed()) {
            throw new \Exception("Etsy Token Yenileme Hatası: " . $response->body());
        }

        return $response->json();
    }
    
    private function updateTokensIfNeeded() {
         $tokens = $this->refreshAccessToken(); 
         if (isset($tokens['refresh_token'])) {
             $this->updateSetting('etsy_refresh_token', $tokens['refresh_token']);
             $this->refreshToken = $tokens['refresh_token'];
         }
         return $tokens['refresh_token'] ?? $this->refreshToken;
    }

    /**
     * Shop ID Bulma (Eğer yoksa)
     */
    private function fetchShopId($accessToken)
    {
        // 1. User ID bul
        $userResp = Http::withHeaders([
            'x-api-key' => $this->clientId . ':' . $this->clientSecret,
            'Authorization' => 'Bearer ' . $accessToken
        ])->get($this->baseUrl . '/application/users/me');

        if ($userResp->failed()) throw new \Exception("Etsy User Fetch Error: " . $userResp->body());

        $userId = $userResp->json('user_id');

        // 2. Shop ID bul
        $shopResp = Http::withHeaders([
            'x-api-key' => $this->clientId . ':' . $this->clientSecret,
            'Authorization' => 'Bearer ' . $accessToken
        ])->get($this->baseUrl . "/application/users/{$userId}/shops");

        if ($shopResp->failed()) throw new \Exception("Etsy Shop Fetch Error: " . $shopResp->body());

        // shops array döner
        $shop = $shopResp->json('shops')[0] ?? null;
        if (!$shop) throw new \Exception("Kullanıcıya ait Etsy mağazası bulunamadı.");

        return $shop['shop_id'];
    }


    /**
     * Siparişleri (Receipts) Çek
     */

    private function fetchReceipts($accessToken, $shopId, $limit = 10, $days = 15)
    {
        $minCreated = Carbon::now()->subDays($days)->timestamp;
        
        $allReceipts = [];
        $offset = 0;
        
        Log::info("Etsy Fetch Started: Limit=$limit, Days=$days, MinCreated=$minCreated");

        // Loop for Pagination
        while (count($allReceipts) < $limit) {
            // Max 100 per request
            $batchLimit = min(100, $limit - count($allReceipts));
            
            Log::info("Fetching page: Offset=$offset, Limit=$batchLimit");

            $response = Http::withHeaders([
                'x-api-key' => $this->clientId . ':' . $this->clientSecret,
                'Authorization' => 'Bearer ' . $accessToken
            ])->get($this->baseUrl . "/application/shops/{$shopId}/receipts", [
                'limit'       => $batchLimit, 
                'offset'      => $offset,
                'min_created' => $minCreated,
                'sort_on'     => 'created',
                'sort_order'  => 'desc'
            ]);

            if ($response->failed()) {
                Log::error("Etsy Fetch Fail: " . $response->body());
                throw new \Exception("Etsy Sipariş Çekme Hatası: " . $response->body());
            }
            
            $results = $response->json('results') ?? [];
            $count = count($results);
            Log::info("Fetched $count receipts.");

            if (empty($results)) break;
            
            $allReceipts = array_merge($allReceipts, $results);
            $offset += $count;
            
            // Safety break
            if ($count < $batchLimit) break;
        }

        Log::info("Total Fetched: " . count($allReceipts));
        return $allReceipts;
    }

    /**
     * Sipariş Kalemlerini (Transactions) Çek
     */
    private function fetchTransactions($accessToken, $receiptId)
    {
        // DÜZELTME: Endpoint /application/shops/{shop_id}/receipts/{receipt_id}/transactions olmalı
        $response = Http::withHeaders([
            'x-api-key' => $this->clientId . ':' . $this->clientSecret,
            'Authorization' => 'Bearer ' . $accessToken
        ])->get($this->baseUrl . "/application/shops/{$this->shopId}/receipts/{$receiptId}/transactions");

        if ($response->failed()) {
            // Transaction çekemezse sipariş de yarım kalır, throw edelim.
            throw new \Exception("Etsy Transaction Çekme Hatası ($receiptId): " . $response->body());
        }

        return $response->json('results') ?? [];
    }

    // --- HELPER METHODS ---

    private function mapEtsyStatus($receipt)
    {
        // "status": "paid", "completed", "open", "payment_processing" etc.
        // User Rules:
        // Completed -> 50
        // Canceled -> 8
        // Refunded -> 9 (veya refund flag) - API'de status "refunded" direkt olmayabilir, is_fully_refunded gibi alanlara bakmalı.
        // Paid/Open -> 0
        
        $status = strtolower($receipt['status'] ?? '');
        
        if ($status === 'completed') return 50;
        if ($status === 'canceled' || $status === 'cancelled') return 8;
        
        // Refund check (V3 receipts object fields)
        // Note: Field 'is_fully_refunded' (boolean) might exist or not depending on API version details.
        // Let's rely on status first. Some docs says 'refunded' status exists if fully refunded.
        
        if (isset($receipt['is_fully_refunded']) && $receipt['is_fully_refunded'] === true) return 9;
        
        // Default paid/open
        return 0; 
    }

    private function parseMoney($moneyObj)
    {
        // Etsy Money Object: { "amount": 1234, "divisor": 100, "currency_code": "USD" }
        $amount = $moneyObj['amount'] ?? 0;
        $divisor = $moneyObj['divisor'] ?? 100;
        
        if ($divisor == 0) $divisor = 100;

        return $amount / $divisor;
    }

    private function updateSetting($key, $value)
    {
        DB::connection('mysql')->table('sabit_ayarlar')
            ->where('Anahtar', $key)
            ->update(['Deger' => $value]);
    }

    public function debugFetchOne()
    {
        $this->loadCredentials();
        $tokens = $this->refreshAccessToken();
        $accessToken = $tokens['access_token'];
        
        if (!$this->shopId) {
            $this->shopId = $this->fetchShopId($accessToken);
        }

        $receipts = $this->fetchReceipts($accessToken, $this->shopId);
        
        if (count($receipts) > 0) {
            return $receipts[0];
        }
        return "Sipariş bulunamadı.";
    }
}

