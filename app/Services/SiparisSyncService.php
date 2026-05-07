<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Siparis;
use App\Models\SiparisUrunleri;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SiparisSyncService
{
    protected $remoteUrl;
    protected $token;
    protected $lastSyncFile;

    public function __construct()
    {
        // Uzak sunucu adresi ve Token .env dosyasından alınmalı
        // Örn: REMOTE_SYNC_URL=https://www.morfingen.info
        $this->remoteUrl = rtrim(env('REMOTE_SYNC_URL', 'https://www.morfingen.info'), '/');
        $this->token = env('SYNC_TOKEN', 'varsayilan_guvensiz_token');
        
        // Son senkronizasyon zamanını saklayacak dosya
        $this->lastSyncFile = storage_path('app/last_sync_date.txt');
    }

    protected $validSiparisColumns = [
        'Id', 'SiparisID', 'AdiSoyadi', 'Email', 'Telefon', 'Adres', 'Il', 'Ilce', 
        'Tarih', 'Tutar', 'KargoTutar', 'ToplamKdv', 'SiparisDurumu', 'IPAdresi', 
        'SiparisNotu', 'SiparisKaynak', 'UyeAdi', 'UyeSoyadi', 'OdemeTipi', 'Onaylandi', 
        'PazaryeriID', 'isUSA', 'SiparisNo', 'HediyeCekiTutari', 'odemeIndirimi', 'odemeDetay'
    ];

    protected $validUrunColumns = [
        'SiparisID', 'UrunAdi', 'StokKodu', 'Miktar', 'BirimFiyat', 'Tutar', 'KdvTutari'
    ];

    protected $validFaturaColumns = [
        'SiparisID', 'FaturaAdresID', 'Adres', 'AliciTelefon', 'FirmaAdi', 
        'Il', 'IlId', 'IlKodu', 'Ilce', 'IlceId', 'IlceKodu', 'UlkeKodu', 
        'VergiDairesi', 'VergiNo', 'IsKurumsal'
    ];

    /**
     * Yerel verileri uzak sunucuya gönderir (PUSH).
     */
    public function pushToRemote()
    {
        $lastSyncDate = $this->getLastSyncDate('push');
        
        // Son 30 gün içindeki tüm siparişleri gönder (Hem yeniler hem güncellenenler için)
        $orders = Siparis::where('Tarih', '>=', now()->subDays(30))
            ->orderBy('Tarih', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return "Gönderilecek yeni sipariş yok.";
        }

        // Sipariş verilerini safelist'e göre filtrele ve formatla
        $payloadOrders = $orders->map(function ($order) {
            $data = [];
            foreach ($this->validSiparisColumns as $col) {
                // isUSA gibi boolean/integer dönüşümü gerekenler varsa burada ele alınabilir
                if ($col === 'isUSA') {
                    $data[$col] = (int)$order->$col;
                } else {
                    $data[$col] = $order->$col;
                }
            }
            return $data;
        })->toArray();

        $orderIds = $orders->pluck('SiparisID')->map(function($id) {
            return (string) $id;
        })->unique()->toArray();
        
        // Ürün verilerini sadece geçerli sütunları seçerek çek
        $orderItems = SiparisUrunleri::whereIn('SiparisID', $orderIds)
            ->select($this->validUrunColumns) // Sadece izin verilen sütunlar
            ->get();

        // Fatura bilgilerini çek - SQL Server conversion hatalarını önlemek için string olarak zorla
        $invoiceInfos = \Illuminate\Support\Facades\DB::connection('mysql')->table('FaturaBilgisi')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('CAST(SiparisID AS CHAR)'), $orderIds)
            ->get();

        $payload = [
            'orders' => $payloadOrders,
            'order_items' => $orderItems->toArray(),
            'invoice_infos' => $invoiceInfos->map(function ($item) {
                return \Illuminate\Support\Arr::only((array)$item, $this->validFaturaColumns);
            })->toArray()
        ];


        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->post($this->remoteUrl . '/api/sync/upload', $payload);

            if ($response->successful()) {
                // Başarılı olursa son senkronizasyon tarihini güncelle
                $latestDate = $orders->max('Tarih');
                $this->saveLastSyncDate('push', $latestDate);
                
                $serverResp = $response->json();
                $invoiceCount = count($payload['invoice_infos']);
                $firstInvoice = $invoiceCount > 0 ? json_encode(\Illuminate\Support\Arr::only($payload['invoice_infos'][0], ['ID', 'SiparisID']), JSON_UNESCAPED_UNICODE) : '-';
                
                return "Başarılı: " . $orders->count() . " sipariş ve " . $invoiceCount . " fatura bilgisi gönderildi.\n(İlk Fatura Örnek: $firstInvoice)";
            } else {
                Log::error('Sync Push Failed: ' . $response->body());
                $errorMsg = $response->body();
                 if (strlen($errorMsg) > 500) $errorMsg = substr($errorMsg, 0, 500) . '...';
                return "Hata: Uzak sunucu yanıtı başarısız.\nKod: " . $response->status() . "\nMesaj: " . $errorMsg;
            }

        } catch (\Exception $e) {
            Log::error('Sync Push Exception: ' . $e->getMessage());
            return "Hata: Bağlantı sorunu. " . $e->getMessage();
        }
    }

    /**
     * Uzak sunucudan verileri çeker (PULL).
     */
    public function pullFromRemote()
    {
        // En son ne zaman çektik?
        $lastPullDate = $this->getLastSyncDate('pull');
        $lastPullDate = $lastPullDate ?: '2000-01-01 00:00:00';

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->get($this->remoteUrl . '/api/sync/download', [
                'last_sync_date' => $lastPullDate
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $orders = $data['orders'] ?? [];
                $orderItems = $data['order_items'] ?? [];
                $invoiceInfos = $data['invoice_infos'] ?? [];
                $latestDate = $data['latest_date'] ?? null;

                if (empty($orders)) {
                    return "Çekilecek yeni sipariş yok.";
                }

                // Verileri yerel veritabanına kaydet
                // Transaction kullanarak bütünlük sağla
                \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($orders, $orderItems, $invoiceInfos) {
                    
                    // 1. Siparişleri Kaydet
                    foreach ($orders as $orderData) {
                        // Gelen veriyi valid sütunlara göre filtrele (Bilinmeyen sütunları at)
                        $cleanOrderData = \Illuminate\Support\Arr::only($orderData, $this->validSiparisColumns);
                        
                        // ID kontrolü
                        if (isset($cleanOrderData['SiparisID'])) {
                            // Primary Key (Id) update edilemez, sadece diğer alanları güncelle
                            $updateData = \Illuminate\Support\Arr::except($cleanOrderData, ['Id']);
                            
                            Siparis::updateOrInsert(
                                ['SiparisID' => $cleanOrderData['SiparisID']],
                                $updateData
                            );
                        }
                    }
                    
                    // 2. Sipariş Ürünlerini Kaydet
                    foreach ($orderItems as $itemData) {
                        // Gelen veriyi valid sütunlara göre filtrele
                        $cleanItemData = \Illuminate\Support\Arr::only($itemData, $this->validUrunColumns);
                        
                        if (isset($cleanItemData['Id']) && isset($cleanItemData['SiparisID'])) {
                             // Identity column (Id) update edilemez
                             $updateItemData = \Illuminate\Support\Arr::except($cleanItemData, ['Id']);
                             $id = $cleanItemData['Id'];
                             
                             // Check if exists using explicit connection
                             $exists = \Illuminate\Support\Facades\DB::connection('mysql')->table('SiparisUrunleri')->where('Id', $id)->exists();

                             if ($exists) {
                                 \Illuminate\Support\Facades\DB::connection('mysql')->table('SiparisUrunleri')->where('Id', $id)->update($updateItemData);
                             } else {
                                 // Insert with Identity Insert using COMBINED raw SQL to ensure same session
                                 $columns = array_keys($cleanItemData);
                                 $columnList = implode(', ', array_map(function($c) { return "`$c`"; }, $columns));
                                 $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                                 
                                 $sql = "INSERT INTO SiparisUrunleri ($columnList) VALUES ($placeholders)";

                                 try {
                                     \Illuminate\Support\Facades\DB::connection('mysql')->statement($sql, array_values($cleanItemData));
                                 } catch (\Exception $e) {
                                     Log::error("SiparisUrunleri Raw Insert Error ID: $id - " . $e->getMessage());
                                     throw $e;
                                 }
                             }
                        }
                    }
                    
                    // 3. Fatura Bilgilerini Kaydet
                    foreach ($invoiceInfos as $invoiceData) {
                        $cleanInvoiceData = \Illuminate\Support\Arr::only($invoiceData, $this->validFaturaColumns);
                        
                        if (isset($cleanInvoiceData['ID'])) {
                            $updateInvoiceData = \Illuminate\Support\Arr::except($cleanInvoiceData, ['ID']);
                            $id = $cleanInvoiceData['ID'];

                            // Check if exists
                            $exists = \Illuminate\Support\Facades\DB::connection('mysql')->table('FaturaBilgisi')->where('ID', $id)->exists();

                            if ($exists) {
                                \Illuminate\Support\Facades\DB::connection('mysql')->table('FaturaBilgisi')
                                    ->where('ID', $id)
                                    ->update($updateInvoiceData);
                            } else {
                                // Insert with Identity Insert using COMBINED raw SQL
                                $columns = array_keys($cleanInvoiceData);
                                $columnList = implode(', ', array_map(function($c) { return "`$c`"; }, $columns));
                                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                                
                                $sql = "
                                    
                                    INSERT INTO FaturaBilgisi ($columnList) VALUES ($placeholders);
                                    
                                ";

                                try {
                                    \Illuminate\Support\Facades\DB::connection('mysql')->statement($sql, array_values($cleanInvoiceData));
                                } catch (\Exception $e) {
                                     Log::error("FaturaBilgisi Raw Insert Error ID: $id - " . $e->getMessage());
                                     throw $e;
                                }
                            }
                        }
                    }
                });

                if ($latestDate) {
                    $this->saveLastSyncDate('pull', $latestDate);
                }

                return "Başarılı: " . count($orders) . " sipariş çekildi.";

            } else {
                Log::error('Sync Pull Failed: ' . $response->body());
                return "Hata: Uzak sunucu yanıtı başarısız. " . $response->status();
            }


        } catch (\Exception $e) {
             Log::error('Sync Pull Exception: ' . $e->getMessage());
            return "Hata: Bağlantı sorunu. " . $e->getMessage();
        }
    }

    /**
     * morfingen.info'dan real_grams tablosunu tamamen çeker (full refresh).
     */
    public function pullRealGrams()
    {
        try {
            $response = Http::timeout(60)->withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->get($this->remoteUrl . '/api/sync/real-grams');

            if (!$response->successful()) {
                Log::error('RealGrams Pull Failed: ' . $response->body());
                return "Hata: Uzak sunucu yanıtı başarısız. Kod: " . $response->status();
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($items) {
                \Illuminate\Support\Facades\DB::connection('mysql')->table('real_grams')->truncate();

                if (empty($items)) {
                    return;
                }

                $rows = array_map(function ($item) {
                    return [
                        'siparis_id' => isset($item['siparis_id']) ? (string) $item['siparis_id'] : null,
                        'real_gram'  => $item['real_gram'] ?? null,
                    ];
                }, $items);

                $rows = array_values(array_filter($rows, function ($r) {
                    return !empty($r['siparis_id']);
                }));

                foreach (array_chunk($rows, 1000) as $chunk) {
                    \Illuminate\Support\Facades\DB::connection('mysql')->table('real_grams')->insert($chunk);
                }
            });

            return "Başarılı: " . count($items) . " real_gram kaydı senkronize edildi.";

        } catch (\Exception $e) {
            Log::error('RealGrams Pull Exception: ' . $e->getMessage());
            return "Hata: Bağlantı sorunu. " . $e->getMessage();
        }
    }

    private function getLastSyncDate($type)
    {
        $file = $this->lastSyncFile . '.' . $type;
        if (File::exists($file)) {
            return File::get($file);
        }
        // Varsayılan başlangıç tarihi
        return '2024-01-01 00:00:00';
    }

    private function saveLastSyncDate($type, $date)
    {
        $file = $this->lastSyncFile . '.' . $type;
        File::put($file, $date);
    }
}

