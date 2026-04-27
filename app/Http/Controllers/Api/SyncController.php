<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Siparis;
use App\Models\SiparisUrunleri;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    // Safelist (SiparisSyncService ile aynı)
    protected $validSiparisColumns = [
        'Id', 'SiparisID', 'AdiSoyadi', 'Email', 'Telefon', 'Adres', 'Il', 'Ilce', 
        'Tarih', 'Tutar', 'KargoTutar', 'ToplamKdv', 'SiparisDurumu', 'IPAdresi', 
        'SiparisNotu', 'SiparisKaynak', 'UyeAdi', 'UyeSoyadi', 'OdemeTipi', 'Onaylandi', 
        'PazaryeriID', 'isUSA', 'SiparisNo', 'HediyeCekiTutari', 'odemeIndirimi', 'odemeDetay'
    ];

    protected $validUrunColumns = [
        'Id', 'SiparisID', 'UrunAdi', 'StokKodu', 'Miktar', 'BirimFiyat', 'Tutar', 'KdvTutari'
    ];

    protected $validFaturaColumns = [
        'ID', 'SiparisID', 'FaturaAdresID', 'Adres', 'AliciTelefon', 'FirmaAdi', 
        'Il', 'IlId', 'IlKodu', 'Ilce', 'IlceId', 'IlceKodu', 'UlkeKodu', 
        'VergiDairesi', 'VergiNo', 'IsKurumsal'
    ];

    /**
     * İstemciden (Local) gelen siparişleri karşılar ve veritabanına yazar (Upsert).
     */
    public function upload(Request $request)
    {
        // Debugging Token Mismatch
        $sentToken = $request->header('X-Sync-Token');
        $serverToken = env('SYNC_TOKEN', 'varsayilan_guvensiz_token');
        
        if ($sentToken !== $serverToken) {
            Log::warning("Sync 401 Unauthorized: Sent [$sentToken] vs Server [$serverToken]");
            // Eğer config cache varsa env() null dönebilir!
            if (empty($serverToken) && function_exists('config')) {
                 Log::warning("Server token is empty from env(), checking config...");
            }
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'orders' => 'required|array',
            'orders.*.SiparisID' => 'required',
            'order_items' => 'required|array',
            'invoice_infos' => 'nullable|array',
        ]);

        try {
            $connection = DB::connection(); // Use default connection
            $driver = $connection->getDriverName();
            
            $connection->beginTransaction();

            // SİPARİŞLERİ KAYDET (Upsert)
            $ordersUpserted = 0;
            $ordersData = $request->input('orders');
            
            foreach ($ordersData as $orderData) {
                // Safelist uygula
                $cleanOrderData = \Illuminate\Support\Arr::only($orderData, $this->validSiparisColumns);
                
                Siparis::updateOrInsert(
                    ['SiparisID' => $cleanOrderData['SiparisID']],
                    $cleanOrderData
                );
                $ordersUpserted++;
            }

            // SİPARİŞ ÜRÜNLERİNİ KAYDET
            $itemsUpserted = 0;
            $itemsData = $request->input('order_items');

            if ($driver === 'mysql') {
                foreach ($itemsData as $itemData) {
                    $cleanItemData = \Illuminate\Support\Arr::only($itemData, $this->validUrunColumns);
                    if (isset($cleanItemData['Id'])) {
                        $id = $cleanItemData['Id'];
                        $updateItemData = \Illuminate\Support\Arr::except($cleanItemData, ['Id']);
                        
                        $exists = SiparisUrunleri::where('Id', $id)->exists();
                        if ($exists) {
                            SiparisUrunleri::where('Id', $id)->update($updateItemData);
                        } else {
                            $columns = array_keys($cleanItemData);
                            $columnList = implode(', ', array_map(function($c) { return "[$c]"; }, $columns));
                            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                            
                            $sql = "
                                SET IDENTITY_INSERT [SiparisUrunleri] ON;
                                INSERT INTO [SiparisUrunleri] ($columnList) VALUES ($placeholders);
                                SET IDENTITY_INSERT [SiparisUrunleri] OFF;
                            ";
                            $connection->statement($sql, array_values($cleanItemData));
                        }
                        $itemsUpserted++;
                    }
                }
            } else {
                foreach ($itemsData as $itemData) {
                    $cleanItemData = \Illuminate\Support\Arr::only($itemData, $this->validUrunColumns);
                    if (isset($cleanItemData['Id'])) {
                        SiparisUrunleri::updateOrInsert(['Id' => $cleanItemData['Id']], $cleanItemData);
                        $itemsUpserted++;
                    }
                }
            }

            // FATURA BİLGİLERİNİ KAYDET
            $invoicesUpserted = 0;
            $invoicesData = $request->input('invoice_infos', []);

            if ($driver === 'mysql') {
                foreach ($invoicesData as $invoiceData) {
                    $cleanInvoiceData = \Illuminate\Support\Arr::only($invoiceData, $this->validFaturaColumns);
                    if (isset($cleanInvoiceData['ID'])) {
                        $id = $cleanInvoiceData['ID'];
                        $updateInvoiceData = \Illuminate\Support\Arr::except($cleanInvoiceData, ['ID']);

                        $exists = DB::table('FaturaBilgisi')->where('ID', $id)->exists();
                        if ($exists) {
                             DB::table('FaturaBilgisi')->where('ID', $id)->update($updateInvoiceData);
                        } else {
                            $columns = array_keys($cleanInvoiceData);
                            $columnList = implode(', ', array_map(function($c) { return "[$c]"; }, $columns));
                            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                            
                            $sql = "
                                SET IDENTITY_INSERT [FaturaBilgisi] ON;
                                INSERT INTO [FaturaBilgisi] ($columnList) VALUES ($placeholders);
                                SET IDENTITY_INSERT [FaturaBilgisi] OFF;
                            ";
                            $connection->statement($sql, array_values($cleanInvoiceData));
                        }
                        $invoicesUpserted++;
                    }
                }
            } else {
                foreach ($invoicesData as $invoiceData) {
                    $cleanInvoiceData = \Illuminate\Support\Arr::only($invoiceData, $this->validFaturaColumns);
                    if (isset($cleanInvoiceData['ID'])) {
                        DB::table('FaturaBilgisi')->updateOrInsert(['ID' => $cleanInvoiceData['ID']], $cleanInvoiceData);
                        $invoicesUpserted++;
                    }
                }
            }

            $connection->commit();

            return response()->json([
                'status' => 'success', 
                'orders_processed' => $ordersUpserted,
                'items_processed' => $itemsUpserted,
                'invoices_processed' => $invoicesUpserted,
                'debug_first_order_keys' => count($ordersData) > 0 ? array_keys((array)$ordersData[0]) : 'empty',
                'debug_data_count' => count($ordersData)
            ]);

        } catch (\Exception $e) {
            if (isset($connection)) $connection->rollBack();
            Log::error('Sync Upload Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * İstemciye (Local) son tarihten sonraki siparişleri gönderir.
     */
    public function download(Request $request)
    {
        if ($request->header('X-Sync-Token') !== env('SYNC_TOKEN', 'varsayilan_guvensiz_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $lastSyncDate = $request->input('last_sync_date'); // Format: Y-m-d H:i:s

        $query = Siparis::query();

        if ($lastSyncDate) {
            $query->where('Tarih', '>', $lastSyncDate);
        }

        // Limit koyalım ki çok şişmesin
        $siparisler = $query->orderBy('Tarih', 'asc')->limit(50)->get();
        
        // Siparişleri safelist ile filtrele (Select ile çekmek daha performanslı olurdu ama code consistency için map ile de olur, 
        // veya burada eloquent select de kullanabiliriz ama modelde guarded/hidden varsa dikkat)
        // Burada response olarak döneceği için transform etmek en iyisidir.
        $siparisler->transform(function ($item) {
            return $item->only($this->validSiparisColumns);
        });

        $pluckedSiparisIDs = $siparisler->pluck('SiparisID');
        
        // Sadece valid columnları ÇEK
        $siparisUrunleri = SiparisUrunleri::whereIn('SiparisID', $pluckedSiparisIDs)
            ->select($this->validUrunColumns)
            ->get();

        return response()->json([
            'orders' => $siparisler,
            'order_items' => $siparisUrunleri,
            'latest_date' => $siparisler->max('Tarih'), // İstemci bunu bir sonraki istekte kullanacak
            'has_more' => $siparisler->count() >= 50
        ]);
    }
}


