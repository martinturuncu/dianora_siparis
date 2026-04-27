<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use SoapClient;
use Exception;

class SiparisEntegrasyonController extends Controller
{
    private $uyeKodu;
    private $wsdlUrl;

    public function __construct()
    {
        $this->uyeKodu = config('services.ticimax.uye_kodu');
        $this->wsdlUrl = config('services.ticimax.wsdl_url');
    }

    private function progressPath()
    {
        return storage_path('app/sync_progress.log');
    }

    private function clearProgress()
    {
        @file_put_contents($this->progressPath(), '');
    }

    private function appendProgress($msg)
    {
        @file_put_contents($this->progressPath(), $msg . "\n", FILE_APPEND);
    }

    public function getProgress(Request $request)
    {
        $path = $this->progressPath();
        $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        return response()->json(['lines' => $lines]);
    }

    public function senkronizeEt(Request $request)
    {
        // Session lock'ını serbest bırak (poll request'lerinin beklememesi için)
        if ($request->hasSession()) {
            $request->session()->save();
        }

        // Kaç günlük sipariş çekeceğiz? (default 5, max 90)
        $gun = (int) $request->get('gun', 5);
        if ($gun < 1) $gun = 1;
        if ($gun > 90) $gun = 90;

        // Progress dosyasını sıfırla
        $this->clearProgress();
        $this->appendProgress("🚀 Senkronizasyon başlatılıyor (son {$gun} gün)...");

        try {
            // 1. SOAP İstemcisi Başlat
            $this->appendProgress("🔌 SOAP servisine bağlanılıyor...");
            $client = new SoapClient($this->wsdlUrl, [
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_BOTH,
                'connection_timeout' => 10,
                'keep_alive' => true,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            ]);

            // 2. Filtreleri Hazırla (C# ile aynı mantık)
            $sayfalama = [
                'BaslangicIndex' => 0,
                'KayitSayisi'    => 200
            ];

            // Aktif Siparişler (son {$gun} gün)
            $filtreAktif = [
                'SiparisTarihiBas' => Carbon::today()->subDays($gun)->toIso8601String(),
                'SiparisTarihiSon' => Carbon::today()->addDays(1)->subSeconds(1)->toIso8601String(),
                'OdemeDurumu'      => 1,
                'OdemeTipi'        => -1,
                'SiparisDurumu'    => -1,
                'SiparisNo'        => ""
            ];

            // İptal/İade Siparişler (son {$gun} gün)
            $filtreIptal = [
                'SiparisTarihiBas' => Carbon::today()->subDays($gun)->toIso8601String(),
                'SiparisTarihiSon' => Carbon::today()->addDays(1)->subSeconds(1)->toIso8601String(),
                'OdemeDurumu'      => 1,
                'OdemeTipi'        => -1,
                'SiparisDurumu'    => 8,
                'SiparisNo'        => ""
            ];

            // 3. Siparişleri Çek
            $this->appendProgress("📥 Aktif siparişler çekiliyor (son {$gun} gün)...");
            $aktifSiparisler = $this->guvenliSiparisGetir($client, $filtreAktif, $sayfalama);
            $this->appendProgress("   → " . count($aktifSiparisler) . " aktif sipariş bulundu");

            $this->appendProgress("📥 İptal/iade siparişler çekiliyor (son {$gun} gün)...");
            $iptalSiparisler = $this->guvenliSiparisGetir($client, $filtreIptal, $sayfalama);
            $this->appendProgress("   → " . count($iptalSiparisler) . " iptal/iade bulundu");

            // Listeleri birleştir
            $tumSiparisler = array_merge($aktifSiparisler, $iptalSiparisler);

            // GÜVENLİK: ID'si olmayan veya hatalı gelen kayıtları temizle
            $tumSiparisler = array_filter($tumSiparisler, function($item) {
                return is_object($item) && isset($item->ID) && !empty($item->ID);
            });

            // ID'ye göre gruplayıp tekrar edenleri temizle (C#'taki GroupBy -> First mantığı)
            $uniqueSiparisler = collect($tumSiparisler)->unique('ID');

            $sayac = 0;
            $yeniSayac = 0;
            $guncelSayac = 0;
            $islenenSiparisler = []; // Log için
            $detayLog = []; // Sipariş bazlı detay log (ConsoleApp4'teki gibi)

            foreach ($uniqueSiparisler as $s) {
                try {
                    // --- VERİ HAZIRLAMA --- //
                    
                    // Telefon Bulma
                    $tel = $this->telefonBul($s->FaturaAdresi ?? null);
                    if (empty($tel)) {
                        $tel = $this->telefonBul($s->TeslimatAdresi ?? null);
                    }

                    // E-Posta
                    $email = $s->Mail ?? ($s->Email ?? "");

                    // Ödeme Tipi ve Detay Mantığı
                    $odemeBilgisi = $this->odemeBilgisiCozumle($s->Odemeler ?? []);
                    $odemeTipi = $odemeBilgisi['tip'];
                    $odemeDetay = $odemeBilgisi['detay'];

                    // Sayısal Değerler (SOAP field isimleri: SiparisToplamTutari, KargoTutari, vb.)
                    $tutar = (float)($s->SiparisToplamTutari ?? ($s->ToplamTutar ?? 0));
                    $kdv = (float)($s->ToplamKdv ?? 0);
                    $kargo = (float)($s->KargoTutari ?? 0);
                    $hediyeCeki = (float)($s->HediyeCekiTutari ?? 0);
                    $odemeIndirimi = (float)($s->OdemeIndirimi ?? ($s->Odemeindirimi ?? 0));

                    // Veritabanı Verisi
                    $data = [
                        'SiparisNo'        => $s->SiparisNo ?? "",
                        'AdiSoyadi'        => $s->AdiSoyadi ?? "",
                        'Email'            => $email,
                        'Telefon'          => $tel,
                        'Adres'            => $s->FaturaAdresi->Adres ?? "",
                        'Il'               => $s->FaturaAdresi->Il ?? "",
                        'Ilce'             => $s->FaturaAdresi->Ilce ?? "",
                        'Tarih'            => Carbon::parse($s->SiparisTarihi),
                        'IPAdresi'         => $s->IPAdresi ?? "",
                        'SiparisNotu'      => $s->SiparisNotu ?? "",
                        'SiparisKaynak'    => $s->SiparisKaynagi ?? "",
                        'KargoTutar'       => $kargo,
                        'ToplamKdv'        => $kdv,
                        'Tutar'            => $tutar,
                        'HediyeCekiTutari' => $hediyeCeki,
                        'odemeIndirimi'    => $odemeIndirimi,
                        'odemeDetay'       => $odemeDetay,
                        'UyeAdi'           => $s->UyeAdi ?? "",
                        'UyeSoyadi'        => $s->UyeSoyadi ?? "",
                        'SiparisDurumu'    => $s->Durum ?? 0,
                        'OdemeTipi'        => $odemeTipi,
                        'PazaryeriID'      => 1, // C# kodunda sabit 1 verilmiş
                        'is_manuel'        => 0, // API (Ticimax)
                    ];

                    // --- SİPARİŞİ KAYDET / GÜNCELLE (Upsert) --- //
                    // SiparisID kolonu varchar (manuel için 'M00001' gibi), string olarak sorgula
                    $siparisIdStr = (string)$s->ID;
                    $kayitVar = DB::connection('mysql')->table('Siparisler')
                        ->where('SiparisID', $siparisIdStr)
                        ->exists();

                    DB::connection('mysql')->table('Siparisler')->updateOrInsert(
                        ['SiparisID' => $siparisIdStr],
                        $data
                    );

                    if ($kayitVar) {
                        $guncelSayac++;
                        $msg = "🔄 Güncellendi: " . ($s->SiparisNo ?? $s->ID);
                    } else {
                        $yeniSayac++;
                        $msg = "✅ Kaydedildi: " . ($s->SiparisNo ?? $s->ID);
                    }
                    $detayLog[] = $msg;
                    $this->appendProgress($msg);

                    // --- FATURA BİLGİSİ SENKRONİZASYONU --- //
                    if (!empty($s->FaturaAdresi)) {
                        $fa = $s->FaturaAdresi;
                        $ulkeKod = $fa->Ulke->Alpha2Code ?? "";

                        DB::connection('mysql')->table('FaturaBilgisi')
                            ->where('SiparisID', $siparisIdStr)
                            ->delete();

                        DB::connection('mysql')->table('FaturaBilgisi')->insert([
                            'SiparisID'     => $siparisIdStr,
                            'FaturaAdresID' => (int)($fa->ID ?? 0),
                            'Adres'         => $fa->Adres ?? "",
                            'AliciTelefon'  => $fa->AliciTelefon ?? "",
                            'FirmaAdi'      => $fa->FirmaAdi ?? "",
                            'Il'            => $fa->Il ?? "",
                            'IlId'          => (int)($fa->IlId ?? 0),
                            'IlKodu'        => $fa->IlKodu ?? "",
                            'Ilce'          => $fa->Ilce ?? "",
                            'IlceId'        => (int)($fa->IlceId ?? 0),
                            'IlceKodu'      => $fa->IlceKodu ?? "",
                            'UlkeKodu'      => $ulkeKod,
                            'VergiDairesi'  => $fa->VergiDairesi ?? "",
                            'VergiNo'       => $fa->VergiNo ?? "",
                            'IsKurumsal'    => !empty($fa->isKurumsal) ? 1 : 0,
                        ]);
                    }

                    // --- ÜRÜNLERİ SENKRONİZE ET --- //

                    // Önce eski ürünleri sil
                    DB::connection('mysql')->table('SiparisUrunleri')->where('SiparisID', $siparisIdStr)->delete();

                    // Ürünleri API'den Çek
                    $urunlerResponse = $client->SelectSiparisUrun([
                        'UyeKodu' => $this->uyeKodu,
                        'siparisId' => $s->ID,
                        'dil' => 'tr'
                    ]);

                    // İlk siparişte ürün yanıt yapısını debug logla
                    static $urunDebugged = false;
                    if (!$urunDebugged) {
                        @file_put_contents(storage_path('app/soap_debug.log'),
                            "=== ÜRÜN YANIT (SiparisID {$s->ID}) ===\n" .
                            print_r($urunlerResponse, true) . "\n\n",
                            FILE_APPEND);
                        $urunDebugged = true;
                    }

                    $apiUrunler = $urunlerResponse->SelectSiparisUrunResult ?? [];

                    // Wrapper açma: {WebSiparisUrun: [...]} gibi sarmalanabilir
                    if (is_object($apiUrunler)) {
                        if (isset($apiUrunler->UrunAdi) || isset($apiUrunler->StokKodu)) {
                            // Tek ürün objesi
                            $apiUrunler = [$apiUrunler];
                        } else {
                            // Wrapper → içindeki array/object property'yi bul
                            $bulundu = [];
                            foreach (get_object_vars($apiUrunler) as $val) {
                                if (is_array($val)) {
                                    $bulundu = $val;
                                    break;
                                }
                                if (is_object($val)) {
                                    $bulundu = [$val];
                                    break;
                                }
                            }
                            $apiUrunler = $bulundu;
                        }
                    } elseif (!is_array($apiUrunler)) {
                        $apiUrunler = [];
                    }

                    foreach ($apiUrunler as $u) {
                        DB::connection('mysql')->table('SiparisUrunleri')->insert([
                            'SiparisID'  => $siparisIdStr,
                            'UrunAdi'    => $u->UrunAdi ?? "",
                            'StokKodu'   => $u->StokKodu ?? "",
                            'Miktar'     => $u->Adet ?? 0,
                            'BirimFiyat' => $u->SatisAniSatisFiyat ?? 0,
                            'Tutar'      => $u->Tutar ?? 0,
                            'KdvTutari'  => $u->KdvTutari ?? 0
                        ]);
                    }

                    $sayac++;
                    $islenenSiparisler[] = $s->SiparisNo ?? $s->ID;

                } catch (Exception $eSiparis) {
                    Log::error("Siparis ID: {$s->ID} Hatası: " . $eSiparis->getMessage());
                    continue; // Bir sipariş patlarsa diğerine geç
                }
            }

            // --- 4. KÂR HESAPLAMA (SADECE SENKRONİZE EDİLEN SİPARİŞLER İÇİN) ---
            if (count($islenenSiparisler) > 0) {
                $this->appendProgress("💰 Kâr analizi başlatılıyor ({$sayac} senkronize sipariş)...");

                $karService = app('App\Services\KarHesapService');
                $karSayac = 0;

                // Sadece bu sync'te işlenen siparişlerin kârını hesapla
                foreach ($uniqueSiparisler as $s) {
                    try {
                        $karService->hesaplaSiparis((string)$s->ID);
                        $karSayac++;
                    } catch (Exception $eKar) {
                        Log::error("Kar Hesaplama Hatası (SiparisID: {$s->ID}): " . $eKar->getMessage());
                        continue; 
                    }
                }
                $this->appendProgress("   → {$karSayac} siparişin kârı hesaplandı");
            } else {
                $this->appendProgress("💰 Yeni/güncellenen sipariş yok, kâr hesaplama atlandı.");
                $karSayac = 0;
            }

            $ozetMesaj = "✅ {$yeniSayac} yeni sipariş kaydedildi, 🔄 {$guncelSayac} sipariş güncellendi. 💰 {$karSayac} sipariş için kâr hesaplandı.";
            $this->appendProgress("🎯 TAMAMLANDI — " . $ozetMesaj);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $ozetMesaj,
                    'log'     => $detayLog, // Sipariş bazlı detay (ConsoleApp4 çıktısı gibi)
                    'details' => [
                        'synced_count'  => $sayac,
                        'new_count'     => $yeniSayac,
                        'updated_count' => $guncelSayac,
                        'profit_count'  => $karSayac,
                        'synced_orders' => array_slice($islenenSiparisler, 0, 20)
                    ]
                ]);
            }

            return redirect()->route('siparisler.index')->with('success', $ozetMesaj);

        } catch (Exception $ex) {
            Log::error("Genel SOAP Hatası: " . $ex->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "API Bağlantı Hatası: " . $ex->getMessage()
                ], 500);
            }

            return redirect()->route('siparisler.index')->with('error', "API Bağlantı Hatası: " . $ex->getMessage());
        }
    }

    // --- YARDIMCI FONKSİYONLAR ---

    private function guvenliSiparisGetir($client, $filtre, $sayfalama)
    {
        try {
            $params = [
                'UyeKodu'   => $this->uyeKodu,
                'f'         => $filtre,
                's'         => $sayfalama
            ];
            
            $response = $client->SelectSiparis($params);

            // DEBUG: Raw response yapısını logla
            @file_put_contents(storage_path('app/soap_debug.log'),
                "=== " . date('Y-m-d H:i:s') . " ===\n" .
                "Filtre: " . json_encode($filtre) . "\n" .
                "Response: " . print_r($response, true) . "\n\n",
                FILE_APPEND);

            if (isset($response->SelectSiparisResult)) {
                $sonuc = $response->SelectSiparisResult;

                // Bazı SOAP yanıtlarında sonuç {Siparis: [...]} veya {SiparisBilgi: [...]} şeklinde sarmalanır
                if (is_object($sonuc)) {
                    // ID varsa tek kayıt, yoksa wrapper olabilir
                    if (!isset($sonuc->ID)) {
                        // Wrapper: içindeki ilk array/object property'yi bul
                        foreach (get_object_vars($sonuc) as $key => $val) {
                            if (is_array($val)) {
                                return $val;
                            }
                            if (is_object($val) && isset($val->ID)) {
                                return [$val];
                            }
                        }
                        return [];
                    }
                    return [$sonuc];
                }

                return is_array($sonuc) ? $sonuc : [];
            }
            return [];

        } catch (Exception $ex) {
            if (str_contains($ex->getMessage(), "Tedarikçiye bağlı siparişler bulunamadı")) {
                return [];
            }
            Log::warning("API Çekme Hatası: " . $ex->getMessage());
            return [];
        }
    }

    private function telefonBul($adres)
    {
        if (!$adres) return "";
        return $adres->AliciTelefon 
            ?? ($adres->Telefon 
            ?? ($adres->CepTel 
            ?? ""));
    }

    private function odemeBilgisiCozumle($odemeler)
    {
        // Odemeler bazen array, bazen tek obje, bazen null gelir. Standartlaştıralım.
        if (empty($odemeler)) return ['tip' => 0, 'detay' => ''];
        if (!is_array($odemeler)) $odemeler = [$odemeler];

        $seciliOdeme = null;

        // 1. Kural: "Havale" geçen ödeme
        foreach ($odemeler as $o) {
            $tip = $o->OdemeTipleri->OdemeTipiAciklamasi ?? ($o->OdemeTipiAciklamasi ?? "");
            if (stripos($tip, 'havale') !== false) {
                $seciliOdeme = $o;
                break;
            }
        }

        // 2. Kural: Onaylı/Başarılı Olan
        if (!$seciliOdeme) {
            foreach ($odemeler as $o) {
                $durumAciklama = $o->DurumAciklama ?? ($o->OdemeDurumuAciklama ?? "");
                $durumId = $o->DurumId ?? ($o->OdemeDurumu ?? 0);
                
                if (stripos($durumAciklama, 'onay') !== false || 
                    stripos($durumAciklama, 'basar') !== false || 
                    stripos($durumAciklama, 'başar') !== false || 
                    $durumId == 1) 
                {
                    $seciliOdeme = $o;
                    break;
                }
            }
        }

        // 3. Kural: En son tarihli (Varsayılan olarak sonuncuyu alıyoruz)
        if (!$seciliOdeme && count($odemeler) > 0) {
            // Ticimax genelde eskiden yeniye veya tam tersi döndürür, 
            // garanti olsun diye array'in ilk elemanını alalım (veya sort edilebilir)
            $seciliOdeme = $odemeler[0];
        }

        if ($seciliOdeme) {
            $tipIsmi = $seciliOdeme->OdemeTipleri->OdemeTipiAciklamasi ?? "";
            $not = $seciliOdeme->OdemeNotu ?? "";
            return [
                'tip'   => $seciliOdeme->OdemeTipi ?? 0,
                'detay' => trim("$tipIsmi $not")
            ];
        }

        return ['tip' => 0, 'detay' => ''];
    }
}
