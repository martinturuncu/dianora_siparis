<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SiparisController extends Controller
{
    public function sync(\App\Services\SiparisSyncService $syncService)
    {
        try {
            $mode = request('mode', 'both'); // 'push', 'pull', or 'both'
            
            $resultPush = "";
            $resultPull = "";

            if ($mode === 'push' || $mode === 'both') {
                $resultPush = $syncService->pushToRemote();
            }

            if ($mode === 'pull' || $mode === 'both') {
                $resultPull = $syncService->pullFromRemote(); 
            }

            // Hata kontrolü
            $hasError = \Illuminate\Support\Str::startsWith($resultPush, 'Hata:') || \Illuminate\Support\Str::startsWith($resultPull, 'Hata:');
            
            if ($hasError) {
                $statusMsg = "Senkronizasyon Sırasında Hata Oluştu!";
                $finalSuccess = false;
            } else {
                $statusMsg = "Senkronizasyon Başarılı!";
                $finalSuccess = true;
            }

            $message = "$statusMsg\n$resultPush\n$resultPull";

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => $finalSuccess,
                    'message' => nl2br($message)
                ], $finalSuccess ? 200 : 422); // Hata durumunda 422 dönebiliriz veya 200 dönüp success:false işlemesi yapabiliriz. JS code 200 bekliyor olabilir, success:false yeterli.
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = 'Senkronizasyon Hatası: ' . $e->getMessage();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('hata', $errorMessage);
        }
    }

    /**
     * Real gram degerini onayla, kari yeniden hesapla.
     */
    public function realGramOnayla($id, \App\Services\KarHesapService $karService)
    {
        try {
            $siparis = DB::connection('mysql')->table('Siparisler')->where('SiparisID', (string)$id)->first();
            if (!$siparis) {
                return response()->json(['success' => false, 'message' => 'Siparis bulunamadi'], 404);
            }

            $realGram = DB::connection('mysql')->table('real_grams')->where('siparis_id', (string)$id)->first();
            if (!$realGram || (float)$realGram->real_gram <= 0) {
                return response()->json(['success' => false, 'message' => 'Bu siparis icin gecerli real_gram verisi yok'], 422);
            }

            DB::connection('mysql')->table('Siparisler')->where('SiparisID', (string)$id)->update([
                'RealGramOnaylandi' => 1,
                'RealGramReddedildi' => 0,
            ]);

            $sonuc = $karService->hesaplaSiparis((string)$id);
            $yeniKar = $sonuc['kar'] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Real gram onaylandi, kar yeniden hesaplandi',
                'yeni_kar' => $yeniKar,
                'real_gram' => (float)$realGram->real_gram,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('realGramOnayla error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Real gram degerini reddet, mevcut kar bozulmasin.
     */
    public function realGramReddet($id)
    {
        try {
            $siparis = DB::connection('mysql')->table('Siparisler')->where('SiparisID', (string)$id)->first();
            if (!$siparis) {
                return response()->json(['success' => false, 'message' => 'Siparis bulunamadi'], 404);
            }

            DB::connection('mysql')->table('Siparisler')->where('SiparisID', (string)$id)->update([
                'RealGramReddedildi' => 1,
                'RealGramOnaylandi' => 0,
            ]);

            return response()->json(['success' => true, 'message' => 'Real gram reddedildi']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('realGramReddet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * morfingen.info'dan real_grams tablosunu çeker (manuel tetik).
     */
    public function syncRealGrams(\App\Services\SiparisSyncService $syncService)
    {
        try {
            $result = $syncService->pullRealGrams();
            $hasError = \Illuminate\Support\Str::startsWith($result, 'Hata:');

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => !$hasError,
                    'message' => $result,
                ], $hasError ? 422 : 200);
            }

            return $hasError
                ? back()->with('hata', $result)
                : back()->with('success', $result);

        } catch (\Exception $e) {
            $errorMessage = 'Real Gram Senkronizasyon Hatası: ' . $e->getMessage();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return back()->with('hata', $errorMessage);
        }
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $platform_id = $request->input('platform');
        $durum = $request->input('durum');
        $not_durumu = $request->input('not_durumu');

        // 1. ADIM: Filtrelere uygun Sipariş ID'lerini bul (Pagination için)
        // Base Query
        $query = DB::connection('mysql')->table('Siparisler as s')
            ->select('s.SiparisID');

        // Search Filter (Join gerekli olabilir)
        if ($search) {
            $query->leftJoin('SiparisUrunleri as u', 's.SiparisID', '=', 'u.SiparisID')
                  ->leftJoin('Pazaryerleri as p', 's.PazaryeriID', '=', 'p.id')
                  ->where(function($q) use ($search) {
                      $q->where('s.SiparisID', 'LIKE', "%$search%")
                        ->orWhere('s.SiparisNo', 'LIKE', "%$search%")
                        ->orWhere('s.AdiSoyadi', 'LIKE', "%$search%")
                        ->orWhere('u.UrunAdi', 'LIKE', "%$search%")
                        ->orWhere('u.StokKodu', 'LIKE', "%$search%")
                        ->orWhere('p.Ad', 'LIKE', "%$search%");
                  });
        }

        // Diğer Filtreler
        if ($platform_id) {
            $query->where('s.PazaryeriID', $platform_id);
        }
        if ($durum !== null && $durum !== '') {
            $query->where('s.SiparisDurumu', $durum);
        }
        if ($not_durumu === 'olan') {
            // Notu olanları filtrele (Exists subquery daha performanslı olabilir ama join de olur)
            // Mevcut yapıda joinli gidiyorduk, burada distinct önemli.
            $query->join('SiparisNotlari as sn', 's.SiparisID', '=', 'sn.SiparisID');
        }
        
        // Specific exclude
        $query->where('s.AdiSoyadi', '!=', 'Dianora Piercing');

        // Toplam Sayı (Distinct SiparisID)
        $total = $query->distinct()->count('s.SiparisID');

        // Pagination setup
        $perPage = 50;
        $page = max(1, (int)$request->input('page', 1));
        $offset = ($page - 1) * $perPage;
        $lastPage = max(1, ceil($total / $perPage));

        // Bu sayfanın ID'lerini çek
        // Order by Tarih DESC
        // Not: Distinct kullandığımız için order by sütunu select listesinde olmayabilir veya group by gerekebilir.
        // Laravel query builder 'distinct' ile order by çakışabilir. 
        // En sağlamı: Group By SiparisID, Tarih yapıp Tarih'e göre sıralamak.
        
        // Bu sayfanın ID'lerini çek
        // SQL Server Hatası: DISTINCT varsa ORDER BY sütunları SELECT içinde olmalı.
        $pageIds = $query->orderBy('s.Tarih', 'desc')
            ->select('s.SiparisID', 's.Tarih') // Select'i burada tekrar belirtelim veya üstüne yazalım
            ->distinct() 
            ->skip($offset)
            ->take($perPage)
            ->get() // Builder'dan çalıştır (Collection döner)
            ->pluck('SiparisID') // Collection'dan ID'yi al
            ->toArray();

        // 2. ADIM: Bu ID'ler için detaylı veriyi çek (Mevcut SQL yapısı)
        // Eğer ID yoksa boş dön
        if (empty($pageIds)) {
            $siparisler = collect([]);
        } else {
            // ID listesini string hale getir (SQL IN logiği için binding kullanacağız gerçi)
            $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
            
            $sql = "
            SELECT 
                s.SiparisID,
                s.SiparisNo,
                s.Tarih, 
                s.AdiSoyadi AS MusteriAdi, 
                s.PazaryeriID,
                p.Ad AS PazaryeriAd,
                p.KomisyonOrani AS KomisyonOrani,
                DATE_FORMAT(s.Tarih, '%d.%m.%Y %H:%i:%s') AS SiparisTarihi,
                s.HediyeCekiTutari, 
                s.odemeIndirimi, 
                sn.NotSayisi,
                son_not.SonNot,
                s.SiparisDurumu,
                s.isUSA,
                s.is_manuel,
                s.OdemeTipi,
                s.RealGramOnaylandi,
                s.RealGramReddedildi,
                rg.real_gram AS RealGramValue,

                u.UrunAdi,
                u.StokKodu, 
                u.Durum AS Durum,
                k.KategoriAdi AS UrunKategori,
                ur.Gram AS UrunGram,
                u.Miktar, 
                u.Tutar, 
                u.KdvTutari,

                IFNULL(sk.GercekKar, 0) AS SiparisKar,
                IFNULL(se.ToplamEkstra, 0) AS SiparisEkstra,
                IFNULL(mc.SiparisSayisi, 1) as MusteriSiparisSayisi,
                IFNULL(mc.IptalSayisi, 0) as MusteriIptalSayisi

            FROM Siparisler s
            INNER JOIN SiparisUrunleri u ON s.SiparisID = u.SiparisID
            
            LEFT JOIN (
                SELECT u1.SiparisID, u1.StokKodu, ur1.Gram, ur1.KategoriId,
                       ROW_NUMBER() OVER(PARTITION BY u1.SiparisID, u1.StokKodu ORDER BY ur1.Id DESC) as rn
                FROM SiparisUrunleri u1
                JOIN Urunler ur1 ON (ur1.UrunKodu = u1.StokKodu OR ur1.UrunKodu = CONCAT(u1.StokKodu, '-yeni') OR u1.StokKodu = CONCAT(ur1.UrunKodu, '-yeni'))
                WHERE u1.SiparisID IN ($placeholders)
            ) ur ON ur.SiparisID = s.SiparisID AND ur.StokKodu = u.StokKodu AND ur.rn = 1
            
            LEFT JOIN Kategoriler k ON ur.KategoriID = k.Id
            
            LEFT JOIN SiparisKarlar sk ON sk.SiparisID = s.SiparisID AND sk.UrunKodu = 'TOPLAM'
            LEFT JOIN Pazaryerleri p ON p.id = s.PazaryeriID
            LEFT JOIN real_grams rg ON rg.siparis_id = s.SiparisID

            LEFT JOIN (
                SELECT 
                    Telefon, 
                    SUM(CASE WHEN SiparisDurumu NOT IN (8, 9) THEN 1 ELSE 0 END) as SiparisSayisi,
                    SUM(CASE WHEN SiparisDurumu IN (8, 9) THEN 1 ELSE 0 END) as IptalSayisi
                FROM Siparisler 
                WHERE Telefon IS NOT NULL AND LENGTH(Telefon) > 5
                GROUP BY Telefon
            ) mc ON mc.Telefon = s.Telefon

            LEFT JOIN (
                SELECT SiparisID, SUM(CASE WHEN Tur = 'GELIR' THEN Tutar ELSE Tutar * -1 END) as ToplamEkstra
                FROM SiparisEkstralar GROUP BY SiparisID
            ) se ON se.SiparisID = s.SiparisID

            LEFT JOIN (
                SELECT SiparisID, COUNT(ID) as NotSayisi FROM SiparisNotlari GROUP BY SiparisID
            ) sn ON sn.SiparisID = s.SiparisID
            
            LEFT JOIN (
                SELECT SiparisID, `Not` as SonNot
                FROM (
                    SELECT SiparisID, `Not`, ROW_NUMBER() OVER(PARTITION BY SiparisID ORDER BY Tarih DESC) as rn
                    FROM SiparisNotlari
                ) as notlar_sirali
                WHERE rn = 1
            ) son_not ON son_not.SiparisID = s.SiparisID

            WHERE s.SiparisID IN ($placeholders)
            ORDER BY s.Tarih DESC
            ";

            $siparisler = collect(DB::connection('mysql')->select($sql, array_merge($pageIds, $pageIds)));
        }

        // Hediye Kodlarını Çek (Görünüm ve Kontrol İçin - EN GÜNCEL OLANLAR)
        $ayar = DB::connection('mysql')->table('ayar_gecmisi')
            ->orderBy('tarih', 'desc')
            ->first();

        $pazaryerleri = DB::connection('mysql')->table('Pazaryerleri')->get();
        
        $hediyeKodlari = [];
        if ($ayar && isset($ayar->hediye_kodlari)) {
            $hediyeKodlari = array_map('trim', explode(',', $ayar->hediye_kodlari));
        } else {
            $hediyeKodlari = ['crmhediye'];
        }

        $pagination = [
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];

        return view('siparisler.index', compact('siparisler', 'pazaryerleri', 'pagination', 'hediyeKodlari'));
    }
    // 🟢 Sipariş Detay Sayfası
    public function show($id, \App\Services\KarHesapService $karService)
    {
        // 1. Sipariş Başlığını Çek
        $siparis = DB::connection('mysql')->table('Siparisler as s')
            ->leftJoin('Pazaryerleri as p', 'p.id', '=', 's.PazaryeriID')
            ->select(
                's.*',
                's.PazaryeriID', // Explicit selection to avoid undefined property
                'p.Ad as PazaryeriAd',
                'p.KomisyonOrani'
            )
            ->where('s.SiparisID', $id)
            ->first();

        if (!$siparis) {
            return back()->with('hata', 'Sipariş bulunamadı.');
        }

        // Tarihsel Ayarı Çek (Hediye kodları ve maliyetler için)
        $siparisTarihi = \Carbon\Carbon::parse($siparis->Tarih)->toDateString();
        $ayar = DB::connection('mysql')->table('ayar_gecmisi')
            ->where('tarih', '<=', $siparisTarihi)
            ->orderBy('tarih', 'desc')
            ->first();

        // Hediye kodlarını ayarla (HER ZAMAN GÜNCEL OLANLARI KULLAN)
        $sonAyar = DB::connection('mysql')->table('ayar_gecmisi')
            ->orderBy('tarih', 'desc')
            ->first();
            
        $hediyeKodlari = [];
        if ($sonAyar && isset($sonAyar->hediye_kodlari)) {
            $hediyeKodlari = array_map('trim', explode(',', $sonAyar->hediye_kodlari));
        } else {
            $hediyeKodlari = ['crmhediye'];
        }



        // 2. Ürünleri Çek
        $urunler = DB::connection('mysql')->table('SiparisUrunleri as u')
            ->where('u.SiparisID', $id)
            ->select('u.*')
            ->get();

        // Ürün Detaylarını (Gram, Kategori) ve Kârları ayrı çekip mapleyelim
        $stokKodlari = $urunler->pluck('StokKodu')->unique();
        
        // 1. Orijinal
        // 2. -yeni eklenmiş
        // 3. -yeni/-eski vb. temizlenmiş
        // 4. Tire öncesi kök kod (H017-YENİ -> H017)
        $allKodlar = collect();
        foreach ($stokKodlari as $kod) {
            $allKodlar->push($kod);
            $allKodlar->push($kod . '-yeni');
            $allKodlar->push($kod . '-YENİ');
            
            // Strateji 1: Temizleme (Regex ile case-insensitive)
            $temiz = preg_replace('/[- ]?(yeni|eski|YENI|YENİ|ESKI|ESKİ)/iu', '', $kod);
            $allKodlar->push($temiz);

            // Strateji 2: Kök Kod (Tireden öncesi)
            $parts = explode('-', $kod);
            if(count($parts) > 0) {
                $allKodlar->push($parts[0]);
            }
        }
        $allKodlar = $allKodlar->unique()->values();

        // Urun Bilgileri (UPPERCASE KEYING for Safety)
        $urunBilgileri = DB::connection('mysql')->table('Urunler as ur')
            ->leftJoin('Kategoriler as k', 'ur.KategoriId', '=', 'k.Id')
            ->whereIn('ur.UrunKodu', $allKodlar)
            ->select('ur.UrunKodu', 'ur.Gram', 'k.KategoriAdi')
            ->get()
            ->keyBy(function($item) {
                return mb_strtoupper($item->UrunKodu, 'UTF-8');
            });
        
        // Kâr Bilgileri (Normalization for lookup)
        $karlar = DB::connection('mysql')->table('SiparisKarlar')
            ->where('SiparisID', $id)
            ->whereIn('UrunKodu', $allKodlar)
            ->select('UrunKodu', 'GercekKar')
            ->get();

        // Ürünleri zenginleştir
        foreach ($urunler as $urun) {
            // Hediye Kontrolü
            $isHediye = false;
            foreach ($hediyeKodlari as $hk) {
                if (strcasecmp($urun->StokKodu, $hk) === 0) {
                    $isHediye = true;
                    break;
                }
            }
            
            if ($isHediye) {
                $urun->isHediye = true;
                $urun->Gram = 0;
                $urun->UrunKategori = 'Hediye';
                $urun->UrunKar = 0;
                continue; // Diğer detaylara gerek yok
            }
            $urun->isHediye = false; // Default

            $stok = $urun->StokKodu;
            $stokYeni = $stok . '-yeni'; // Varsayılan suffix
            
            $stokTemiz = preg_replace('/[- ]?(yeni|eski|YENI|YENİ|ESKI|ESKİ)/iu', '', $stok);
            $rootKod = explode('-', $stok)[0];

            // Adayları belirle
            $adaylarRaw = array_unique(array_filter([$stok, $stokYeni, $stokTemiz, $rootKod]));
            
            // Adayların büyük harf versiyonlarını çıkar (Match için)
            $adaylarUpper = array_map(fn($val) => mb_strtoupper($val, 'UTF-8'), $adaylarRaw);
            
            $bulunanBilgi = null;
            // 1. Gramı olanı ara
            foreach ($adaylarUpper as $aday) {
                if (isset($urunBilgileri[$aday]) && $urunBilgileri[$aday]->Gram > 0) {
                    $bulunanBilgi = $urunBilgileri[$aday];
                    break; 
                }
            }
            
            // 2. Gramlı bulamadıysak herhangi birini al
            if (!$bulunanBilgi) {
                 foreach ($adaylarUpper as $aday) {
                    if (isset($urunBilgileri[$aday])) {
                        $bulunanBilgi = $urunBilgileri[$aday];
                        break;
                    }
                }
            }

            $urun->Gram = $bulunanBilgi->Gram ?? 0;
            $urun->UrunKategori = $bulunanBilgi->KategoriAdi ?? '';

            // Kar Eşle - Adaylardan herhangi biriyle eşleşen kar kaydı (Case-insensitive check)
            // $karlar collection olduğu için büyük harf key yok, loop ile kontrol edelim.
            $kar = $karlar->first(function($k) use ($adaylarUpper) {
                return in_array(mb_strtoupper($k->UrunKodu, 'UTF-8'), $adaylarUpper); 
            });
            $urun->UrunKar = $kar->GercekKar ?? 0;
        }

        // (Ayar yukarı taşındı)

        // 🔥 Badge'de görünecek komisyon oranını tarihsel ayardan eşitle (View'da p.KomisyonOrani yerine bunu kullansın)
        if ($ayar) {
            $pzId = (int)$siparis->PazaryeriID;
            if ($pzId === 1) $siparis->KomisyonOrani = (float)$ayar->komisyon_site;
            elseif ($pzId === 2) $siparis->KomisyonOrani = (float)$ayar->komisyon_trendyol;
            elseif ($pzId === 3) $siparis->KomisyonOrani = (float)$ayar->komisyon_etsy;
            elseif ($pzId === 4) $siparis->KomisyonOrani = (float)$ayar->komisyon_hipicon;
        }

        // 4. Maliyet Hesaplamaları
        $toplamAltinMaliyeti = 0;
        $toplamGider = 0;
        $toplamKomisyon = 0;
        $toplamVergi = 0;
        $toplamGram = 0;

        // Detaylı Gider Takibi
        $detayliGiderler = [
            'iscilik' => 0,
            'reklam' => 0,
            'kargo' => 0,
            'kutu' => 0,
            'kargo_yurtdisi' => 0
        ];

        foreach ($urunler as $urun) {
            // Hediye Kontrolü
            $isHediye = false;
            foreach ($hediyeKodlari as $hk) {
                if (strcasecmp($urun->StokKodu, $hk) === 0) {
                    $isHediye = true;
                    break;
                }
            }

            if ($isHediye) {
                $urun->isHediye = true;
                $urun->detay = [
                    'altinMaliyeti' => 0,
                    'gider' => 0,
                    'komisyon' => 0,
                    'vergi' => 0,
                    'gercekKar' => 0,
                    'toplamMaliyet' => 0
                ];
                continue; // Hesaplamaya katma
            }
            $urun->isHediye = false; // Default

            // Service için gerekli parent verileri aktar
            $urun->Tarih = $siparis->Tarih;
            $urun->PazaryeriID = $siparis->PazaryeriID;
            $urun->PazaryeriAdi = $siparis->PazaryeriAd;
            $urun->isUSA = $siparis->isUSA;
            $urun->PazarKomisyon = $siparis->KomisyonOrani;
            $urun->ayar_orani = $siparis->ayar_orani;
            
            // Hesaplama yap
            $sonuc = $karService->hesapla($urun);
            $urun->detay = $sonuc; // Detayı view'da kullanmak için sakla
            
            // Gramajı güncelle
            if($urun->Gram > 0) {
                 $toplamGram += $urun->Gram * $urun->Miktar;
            }

            // Sonuçları topla
            if (!empty($sonuc)) {
                $toplamAltinMaliyeti += $sonuc['altinMaliyeti'];
                
                // Gider Hesaplama
                if (isset($sonuc['gider_detay'])) {
                    $detayliGiderler['iscilik'] += $sonuc['gider_detay']['iscilik'];
                    $detayliGiderler['reklam'] += $sonuc['gider_detay']['reklam'];
                    $toplamGider += $sonuc['gider_detay']['iscilik'] + $sonuc['gider_detay']['reklam'];
                } else {
                    $toplamGider += $sonuc['gider'];
                }

                // Komisyon Hesabı
                $provisionalKomisyon = $sonuc['odenenKomisyon'] ?? 0;
                
                if($provisionalKomisyon == 0 && isset($sonuc['komisyon'])) {
                     $brutSatis = ($urun->Tutar + ($urun->KdvTutari ?? 0)) * $urun->Miktar;
                     if($siparis->PazaryeriID == 3) { 
                        if(isset($sonuc['gercekSatis'])) {
                            $toplamKomisyon += $sonuc['gercekSatis'] * $sonuc['komisyon'];
                        }
                     } else {
                         $toplamKomisyon += $brutSatis * $sonuc['komisyon'];
                     }
                } else {
                    $toplamKomisyon += $provisionalKomisyon;
                }

                $toplamVergi += $sonuc['vergi'] ?? 0;
            }
        }
        
        // 5. Ekstraları Çek
        $ekstralar = DB::connection('mysql')->table('SiparisEkstralar')
            ->where('SiparisID', $id)
            ->get();

        $toplamEkstra = $ekstralar->sum(fn($e) => $e->Tur == 'GELIR' ? $e->Tutar : -$e->Tutar);

        // 6. Toplam Karı Servis Üzerinden Hesapla ve Çek
        $siparisKar = $karService->hesaplaSiparis($id);
        
        $toplamCiro = $siparisKar['toplamCiro'] ?? 0;
        $toplamKar = $siparisKar['kar'] ?? 0;
        $toplamGram = $siparisKar['gram'] ?? 0;
        $toplamAltinMaliyeti = $siparisKar['altin'] ?? 0; // Detay için
        $toplamGider = $siparisKar['gider'] ?? 0;
        $toplamKomisyon = $siparisKar['komisyon'] ?? 0;
        $toplamVergi = $siparisKar['vergi'] ?? 0;

        // Servisten gelen detaylı giderleri kullan
        $detayliGiderler = $siparisKar['detayliGiderler'] ?? [
            'iscilik' => 0, 'reklam' => 0, 'kargo' => 0, 'kutu' => 0, 'kargo_yurtdisi' => 0
        ];

        // İptal/İade durumunda manuel düzeltme (Service yapısınında dönen detayı baz alır)
        if ((int)$siparis->SiparisDurumu === 8 || (int)$siparis->SiparisDurumu === 9) {
             // Sadece İade (9) ise kargo masrafı yansıt
             if ((int)$siparis->SiparisDurumu === 9) {
                 $detayliGiderler['kargo_gidis'] = $siparisKar['gider'] / 2;
                 $detayliGiderler['kargo_donus'] = $siparisKar['gider'] / 2;
             } else {
                 $detayliGiderler['kargo_gidis'] = 0;
                 $detayliGiderler['kargo_donus'] = 0;
             }
        }

        // 7. Notları Çek
        $notlar = DB::connection('mysql')->table('SiparisNotlari')
            ->where('SiparisID', $id)
            ->orderBy('Tarih', 'desc')
            ->get();

        // 8. Müşteri Analizi (CRM)
        $musteriGecmisi = [
            'toplamSiparis' => 0,
            'toplamUrun' => 0,
            'toplamIptal' => 0,
            'oncekiSiparisler' => collect()
        ];

        // Müşteriyi belirle (Telefon > AdSoyad)
        $musteriIdentifier = $siparis->Telefon;
        $musteriField = 'Telefon';

        if(empty($musteriIdentifier)) {
            $musteriIdentifier = $siparis->AdiSoyadi;
            $musteriField = 'AdiSoyadi';
        }

        if(!empty($musteriIdentifier) && $musteriIdentifier != '0' && $musteriIdentifier != '-') {
            // 1. TAMAMLANAN (Veya İşlem Gören) Siparişler
            // İptal (8) ve İade (9) HARİÇ olanlar "Sadakat" sayılır.
            $oncekiSiparisQuery = DB::connection('mysql')->table('Siparisler as s')
                ->where($musteriField, $musteriIdentifier)
                // ->where('s.SiparisID', '!=', $id) // Şu anki siparişi de katarak genel toplam istendi
                ->whereNotIn('s.SiparisDurumu', [8, 9]) 
                ->orderBy('s.Tarih', 'desc');

            $musteriGecmisi['toplamSiparis'] = $oncekiSiparisQuery->count();

            // 2. İPTAL ve İADE EDİLEN Siparişler (Risk Analizi)
            $iptalSiparisCount = DB::connection('mysql')->table('Siparisler')
                ->where($musteriField, $musteriIdentifier)
                ->whereIn('SiparisDurumu', [8, 9])
                ->count();
            
            $musteriGecmisi['toplamIptal'] = $iptalSiparisCount;
            
            // Toplam Ürün Adedi (Sadece geçerli siparişler için)
            $musteriSiparisIDs = $oncekiSiparisQuery->pluck('SiparisID');
            
            if($musteriSiparisIDs->isNotEmpty()) {
                // PazaryeriID'ye göre değil, direkt TOPLAM ÜRÜN ADEDİ (Miktar) lazım
                // Ciro hesaplaması yerine toplam kaç adet ürün aldığına bakıyoruz
                
                $toplamUrunAdedi = DB::connection('mysql')->table('SiparisUrunleri')
                    ->whereIn('SiparisID', $musteriSiparisIDs)
                    ->sum('Miktar');
                
                $musteriGecmisi['toplamUrun'] = $toplamUrunAdedi;
                
                // Son 5 siparişi detay için çek
                $musteriGecmisi['oncekiSiparisler'] = DB::connection('mysql')->table('Siparisler')
                    ->whereIn('SiparisID', $musteriSiparisIDs->take(5))
                    ->orderBy('Tarih', 'desc')
                    ->select('SiparisID', 'Tarih', 'SiparisNo', 'PazaryeriID') // Basit bilgiler
                    ->get();
            }
        }

        return view('siparisler.show', compact(
            'siparis', 'urunler', 'ekstralar', 'notlar', 'ayar', 'toplamEkstra',
            'toplamAltinMaliyeti', 'toplamGider', 'toplamKomisyon', 'toplamVergi', 'toplamGram',
            'toplamKar', 'detayliGiderler', 'siparisKar', 'musteriGecmisi'
        ));
    }

    public function destroy($id)
    {
        // Siparişi sil
        DB::connection('mysql')->table('Siparisler')
            ->where('SiparisID', $id)
            ->delete();

        // Sipariş ürünlerini sil
        DB::connection('mysql')->table('SiparisUrunleri')
            ->where('SiparisID', $id)
            ->delete();

        return back()->with('success', 'Manuel sipariş başarıyla silindi!');
    }

    // 🟢 Manuel Sipariş Durumu Güncelleme
    public function durumGuncelle(Request $request, $id)
    {
        $siparis = DB::connection('mysql')->table('Siparisler')->where('SiparisID', $id)->first();

        if (!$siparis) {
            return back()->with('hata', 'Sipariş bulunamadı.');
        }

        $yeniDurum = $request->input('durum');

        DB::connection('mysql')->table('Siparisler')
            ->where('SiparisID', $id)
            ->update(['SiparisDurumu' => $yeniDurum]);

        return back()->with('success', 'Sipariş durumu güncellendi.');
    }

    public function updateAyarOrani(Request $request, $id)
    {
        $siparis = DB::connection('mysql')->table('Siparisler')->where('SiparisID', $id)->first();

        if (!$siparis) {
            return back()->with('hata', 'Sipariş bulunamadı.');
        }

        $request->validate([
            'ayar_orani' => 'required|numeric|min:0|max:1',
        ]);

        $ayarOrani = $request->input('ayar_orani');

        DB::connection('mysql')->table('Siparisler')
            ->where('SiparisID', $id)
            ->update(['ayar_orani' => $ayarOrani]);

        return back()->with('success', 'Altın ayar oranı güncellendi.');
    }

    public function istatistikler(Request $request)
    {
        // Varsayılan Tarihler
        $tarih     = $request->input('tarih', now()->toDateString());
        $baslangic = $request->input('baslangic');
        $bitis     = $request->input('bitis');

        // ==========================================================
        // 📅 1. GÜNLÜK VERİLER
        // ==========================================================
        
        // A) Sayıları Tek Sorguda Çek
        $gunlukOzet = DB::connection('mysql')->table('Siparisler')
            ->whereDate('Tarih', $tarih)
            ->where('AdiSoyadi', '!=', 'Dianora Piercing') // 🔥 FİLTRE EKLENDİ
            ->selectRaw("
                COUNT(*) as toplam,
                SUM(CASE WHEN SiparisDurumu IN (8, 9) THEN 1 ELSE 0 END) as iptal,
                SUM(CASE WHEN SiparisDurumu NOT IN (8, 9) THEN 1 ELSE 0 END) as aktif
            ")
            ->first();

        $gunlukToplamSiparis = $gunlukOzet->toplam ?? 0;
        $gunlukIptalSayisi   = $gunlukOzet->iptal ?? 0;
        $gunlukSiparisSayisi = $gunlukOzet->aktif ?? 0;

        // B) Ürün Sayısı (Sadece İptal Olmayanlar, Hediyeler Hariç)
        $gunlukUrunSayisi = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisUrunleri.StokKodu', 'NOT LIKE', '%hediye%')
            ->where('SiparisUrunleri.Durum', 0)
            ->sum('SiparisUrunleri.Miktar');

        // B2) Hediye Ürün Sayısı
        $gunlukHediyeSayisi = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisUrunleri.StokKodu', 'LIKE', '%hediye%')
            ->where('SiparisUrunleri.Durum', 0)
            ->sum('SiparisUrunleri.Miktar');

        // C) Günlük Kâr (İptaller Hariç)
        $gunlukKar = DB::connection('mysql')->table('SiparisKarlar')
            ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
            ->sum('SiparisKarlar.GercekKar');

        // D) Günlük Ciro (Sadece İptal Olmayanlar)
        $gunlukBrutCiro = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisUrunleri.Durum', 0)
            ->selectRaw('SUM( (IFNULL(SiparisUrunleri.Tutar, 0) + IFNULL(SiparisUrunleri.KdvTutari, 0)) * SiparisUrunleri.Miktar ) AS ciro')
            ->value('ciro');

        $gunlukIndirimler = DB::connection('mysql')->table('Siparisler')
            ->whereDate('Tarih', $tarih)
            ->whereNotIn('SiparisDurumu', [8, 9])
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->sum('odemeIndirimi');

        $gunlukCiro = $gunlukBrutCiro - $gunlukIndirimler;

        // E) Günlük Reklam Gideri (Historical)
        $gunlukReklamGideri = DB::connection('mysql')->select("
            SELECT SUM(u.Miktar * IFNULL((
                SELECT reklam
                FROM ayar_gecmisi
                WHERE tarih <= CAST(s.Tarih as DATE)
                ORDER BY tarih DESC
                LIMIT 1
            ), 0)) as ToplamReklam
            FROM SiparisUrunleri u
            JOIN Siparisler s ON s.SiparisID = u.SiparisID
            WHERE CAST(s.Tarih as DATE) = ?
            AND s.SiparisDurumu NOT IN (8, 9)
            AND s.AdiSoyadi != 'Dianora Piercing'
            AND u.StokKodu NOT LIKE '%hediye%'
            AND u.Durum = 0
        ", [$tarih]);
        
        $gunlukReklamGideri = $gunlukReklamGideri[0]->ToplamReklam ?? 0;


        // ==========================================================
        // 📆 2. TARİH ARALIĞI VERİLERİ
        // ==========================================================
        
        $aralikVerisi = null;
        $aralikCiro   = 0;
        $aralikReklamGideri = 0;
        $aralikVergi = 0;

        if ($baslangic && $bitis) {
            
            if ($baslangic > $bitis) {
                [$baslangic, $bitis] = [$bitis, $baslangic];
            }

            $start = Carbon::parse($baslangic)->startOfDay();
            $end   = Carbon::parse($bitis)->endOfDay();

            // A) Aralık Özeti (Sayılar)
            $aralikOzet = DB::connection('mysql')->table('Siparisler')
                ->whereBetween('Tarih', [$start, $end])
                ->where('AdiSoyadi', '!=', 'Dianora Piercing') 
                ->selectRaw("
                    COUNT(*) as toplam,
                    SUM(CASE WHEN SiparisDurumu IN (8, 9) THEN 1 ELSE 0 END) as iptal,
                    SUM(CASE WHEN SiparisDurumu NOT IN (8, 9) THEN 1 ELSE 0 END) as aktif
                ")
                ->first();

            // B) Aralık Ürün Sayısı (Aktifler, Hediyeler Hariç)
            $aralikUrunSayisi = DB::connection('mysql')->table('SiparisUrunleri')
                ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
                ->whereBetween('Siparisler.Tarih', [$start, $end])
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisUrunleri.StokKodu', 'NOT LIKE', '%hediye%')
                ->where('SiparisUrunleri.Durum', 0)
                ->sum('SiparisUrunleri.Miktar');

            // B2) Aralık Hediye Sayısı
            $aralikHediyeSayisi = DB::connection('mysql')->table('SiparisUrunleri')
                ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
                ->whereBetween('Siparisler.Tarih', [$start, $end])
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisUrunleri.StokKodu', 'LIKE', '%hediye%')
                ->where('SiparisUrunleri.Durum', 0)
                ->sum('SiparisUrunleri.Miktar');

            // C) Aralık Kâr (İptaller Hariç)
            $aralikKar = DB::connection('mysql')->table('SiparisKarlar')
                ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
                ->whereBetween('Siparisler.Tarih', [$start, $end])
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
                ->sum('SiparisKarlar.GercekKar');
            
            // D) Aralık Ciro (Aktifler)
            $aralikBrutCiro = DB::connection('mysql')->table('SiparisUrunleri')
                ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
                ->whereBetween('Siparisler.Tarih', [$start, $end])
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisUrunleri.Durum', 0)
                ->selectRaw('SUM( (IFNULL(SiparisUrunleri.Tutar, 0) + IFNULL(SiparisUrunleri.KdvTutari, 0)) * SiparisUrunleri.Miktar ) AS ciro')
                ->value('ciro');

            $aralikIndirimler = DB::connection('mysql')->table('Siparisler')
                ->whereBetween('Tarih', [$start, $end])
                ->whereNotIn('SiparisDurumu', [8, 9])
                ->where('AdiSoyadi', '!=', 'Dianora Piercing')
                ->sum('odemeIndirimi');

            $aralikCiro = $aralikBrutCiro - $aralikIndirimler;

             // E) Aralık Reklam Gideri (Historical)
            $aralikReklamQuery = DB::connection('mysql')->select("
                SELECT SUM(u.Miktar * IFNULL((
                    SELECT reklam
                    FROM ayar_gecmisi
                    WHERE tarih <= CAST(s.Tarih as DATE)
                    ORDER BY tarih DESC
                    LIMIT 1
                ), 0)) as ToplamReklam
                FROM SiparisUrunleri u
                JOIN Siparisler s ON s.SiparisID = u.SiparisID
                WHERE s.Tarih BETWEEN ? AND ?
                AND s.SiparisDurumu NOT IN (8, 9)
                AND s.AdiSoyadi != 'Dianora Piercing'
                AND u.StokKodu NOT LIKE '%hediye%'
                AND u.Durum = 0
            ", [$start, $end]);

            $aralikReklamGideri = $aralikReklamQuery[0]->ToplamReklam ?? 0;

            // F) Aralık Vergi (Sadece Türkiye - Etsy Hariç)
            $aralikVergi = DB::connection('mysql')->table('SiparisKarlar')
                ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
                ->whereBetween('Siparisler.Tarih', [$start, $end])
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('Siparisler.PazaryeriID', '!=', 3) // Etsy Hariç
                ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
                ->sum('SiparisKarlar.Vergi');

            // Veriyi paketle
            $aralikVerisi = [
                'toplam' => $aralikOzet->toplam ?? 0,
                'aktif'  => $aralikOzet->aktif ?? 0,
                'iptal'  => $aralikOzet->iptal ?? 0,
                'urun'   => $aralikUrunSayisi ?? 0,
                'hediye' => $aralikHediyeSayisi ?? 0,
                'kar'    => $aralikKar ?? 0,
            ];
        }

            // F) Son 15 Günlük Satış Verileri (Grafik/Tablo İçin)
            // 1. Satış Adetleri
            $satislar = DB::connection('mysql')->table('SiparisUrunleri')
                ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
                ->selectRaw("
                    DATE_FORMAT(Siparisler.Tarih, '%d.%m.%Y') as TarihOzel,
                    CAST(Siparisler.Tarih as DATE) as TarihRaw,
                    SUM(SiparisUrunleri.Miktar) as ToplamUrun
                ")
                ->where('Siparisler.Tarih', '>=', now()->subDays(15)->startOfDay())
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9]) // İptaller ve İadeler satış adedine girmez
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisUrunleri.StokKodu', 'NOT LIKE', '%hediye%')
                ->where('SiparisUrunleri.Durum', 0)
                ->groupByRaw("DATE_FORMAT(Siparisler.Tarih, '%d.%m.%Y'), CAST(Siparisler.Tarih as DATE)")
                ->get()
                ->keyBy('TarihOzel');

            // 2. Kârlar (İptaller Hariç, Hediye Hariç) - Satış Adetleri ile tutarlı olmalı
            $karlar = DB::connection('mysql')->table('SiparisKarlar')
                ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
                ->selectRaw("
                    DATE_FORMAT(Siparisler.Tarih, '%d.%m.%Y') as TarihOzel,
                    SUM(SiparisKarlar.GercekKar) as ToplamKar
                ")
                ->where('Siparisler.Tarih', '>=', now()->subDays(15)->startOfDay())
                ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
                ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
                ->groupByRaw("DATE_FORMAT(Siparisler.Tarih, '%d.%m.%Y')")
                ->get()
                ->keyBy('TarihOzel');

            // 3. Birleştirme (Son 15 günün her biri için)
            $son15Gun = collect();
            for ($i = 0; $i < 15; $i++) {
                $tarihObj = now()->subDays($i);
                $key = $tarihObj->format('d.m.Y');
                
                $adet = isset($satislar[$key]) ? $satislar[$key]->ToplamUrun : 0;
                $kar  = isset($karlar[$key]) ? $karlar[$key]->ToplamKar : 0;

                // Sadece veri varsa ekle (veya her günü eklemesi tercih edilebilir, şu an hepsini ekleyelim boş olsa da)
                // "alt alta görelim" dediği için boş günleri göstermek görsel bütünlük sağlar.
                // Veya sadece dolu günleri? Genelde 15 günlük liste istenir.
                // Şimdilik sadece veri (adet veya kar) olan günleri ekleyelim, tablo çok uzun boş kalmasın.
                $son15Gun->push((object)[
                    'TarihOzel' => $key,
                    'ToplamUrun' => $adet,
                    'ToplamKar' => $kar
                ]);
            }


        return view('siparisler.istatistikler', compact(
            'tarih', 
            'gunlukToplamSiparis', 
            'gunlukSiparisSayisi', 
            'gunlukIptalSayisi',
            'gunlukUrunSayisi', 
            'gunlukHediyeSayisi',
            'gunlukKar', 
            'gunlukCiro',
            'gunlukReklamGideri',
            'aralikVerisi', 
            'aralikCiro', 
            'aralikReklamGideri',
            'aralikVergi',
            'baslangic', 
            'bitis',
            'son15Gun'
        ));
    }



    // 🟢 Lider Tablosu (Leaderboard) Sayfası
    public function liderTablosu()
    {
        // 1. En Çok Sipariş Veren Müşteriler (Adet Bazlı)
        $topMusterilerSiparis = DB::connection('mysql')->table('Siparisler')
            ->select('Telefon', 'AdiSoyadi', DB::raw('COUNT(*) as SiparisSayisi'))
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisDurumu', '!=', 8) // İptaller hariç
            ->groupBy('Telefon', 'AdiSoyadi')
            ->orderByDesc('SiparisSayisi')
            ->take(10)
            ->get();

        // 2. En Çok Ürün Alan Müşteriler (Miktar Bazlı)
        $topMusterilerUrun = DB::connection('mysql')->table('SiparisUrunleri as su')
            ->join('Siparisler as s', 's.SiparisID', '=', 'su.SiparisID')
            ->select('s.Telefon', 's.AdiSoyadi', DB::raw('SUM(su.Miktar) as ToplamUrun'))
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('s.SiparisDurumu', '!=', 8)
            ->where('su.StokKodu', 'NOT LIKE', '%hediye%')
            ->where('su.Durum', 0)
            ->groupBy('s.Telefon', 's.AdiSoyadi')
            ->orderByDesc('ToplamUrun')
            ->take(10)
            ->get();

        // 3. En Çok Satılan Ürünler
        $topUrunler = DB::connection('mysql')->table('SiparisUrunleri as su')
            ->join('Siparisler as s', 's.SiparisID', '=', 'su.SiparisID')
            ->select('su.StokKodu', 'su.UrunAdi', DB::raw('SUM(su.Miktar) as SatilanMiktar'))
            ->where('s.SiparisDurumu', '!=', 8)
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('su.StokKodu', 'NOT LIKE', '%hediye%')
            ->where('su.Durum', 0)
            ->groupBy('su.StokKodu', 'su.UrunAdi')
            ->orderByDesc('SatilanMiktar')
            ->take(10)
            ->get();

        // 4. Müşteri Analizi (İlk Alım vs Tekrarlı Alım)
        $totalUniqueCustomers = DB::connection('mysql')->table('Siparisler')
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisDurumu', '!=', 8)
            ->distinct('Telefon')
            ->count('Telefon');

        $repeatBuyersCount = DB::connection('mysql')->table('Siparisler')
            ->select('Telefon')
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->where('SiparisDurumu', '!=', 8)
            ->groupBy('Telefon')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $firstTimeBuyersCount = $totalUniqueCustomers - $repeatBuyersCount;
        
        $retentionStats = [
            'total' => $totalUniqueCustomers,
            'repeat_count' => $repeatBuyersCount,
            'first_time_count' => $firstTimeBuyersCount,
            'repeat_percent' => $totalUniqueCustomers > 0 ? round(($repeatBuyersCount / $totalUniqueCustomers) * 100, 1) : 0,
            'first_time_percent' => $totalUniqueCustomers > 0 ? round(($firstTimeBuyersCount / $totalUniqueCustomers) * 100, 1) : 0,
        ];

        return view('siparisler.lider_tablosu', compact('topMusterilerSiparis', 'topMusterilerUrun', 'topUrunler', 'retentionStats'));
    }
    
    // 🟢 Satış Takvimi (Sales Calendar) Sayfası
    public function satisTakvimi(Request $request)
    {
        $allTime = $request->has('all_time');
        $ay = $request->input('ay', now()->month);
        $yil = $request->input('yil', now()->year);

        if ($allTime) {
            // 1. Tüm Zamanlar Satış Adetleri
            $satislar = DB::connection('mysql')->table('SiparisUrunleri as su')
                ->join('Siparisler as s', 's.SiparisID', '=', 'su.SiparisID')
                ->selectRaw("
                    CAST(s.Tarih as DATE) as TarihRaw,
                    SUM(su.Miktar) as ToplamUrun
                ")
                ->whereNotIn('s.SiparisDurumu', [8, 9])
                ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('su.StokKodu', 'NOT LIKE', '%hediye%')
                ->where('su.Durum', 0)
                ->groupByRaw("CAST(s.Tarih as DATE)")
                ->get()
                ->keyBy('TarihRaw');

            // 2. Tüm Zamanlar Kârlar
            $karlar = DB::connection('mysql')->table('SiparisKarlar as sk')
                ->join('Siparisler as s', 's.SiparisID', '=', 'sk.SiparisID')
                ->selectRaw("
                    CAST(s.Tarih as DATE) as TarihRaw,
                    SUM(sk.GercekKar) as ToplamKar
                ")
                ->whereNotIn('s.SiparisDurumu', [8, 9])
                ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('sk.UrunKodu', 'TOPLAM')
                ->groupByRaw("CAST(s.Tarih as DATE)")
                ->get()
                ->keyBy('TarihRaw');

            $takvim = [];
            $allDates = $satislar->keys()->merge($karlar->keys())->unique()->sort();
            
            foreach ($allDates as $dateStr) {
                $takvim[$dateStr] = [
                    'gun' => \Carbon\Carbon::parse($dateStr)->day,
                    'ay'  => \Carbon\Carbon::parse($dateStr)->month,
                    'yil' => \Carbon\Carbon::parse($dateStr)->year,
                    'adet' => isset($satislar[$dateStr]) ? $satislar[$dateStr]->ToplamUrun : 0,
                    'kar' => isset($karlar[$dateStr]) ? $karlar[$dateStr]->ToplamKar : 0,
                ];
            }
            $start = null;
        } else {
            $start = \Carbon\Carbon::createFromDate($yil, $ay, 1)->startOfMonth();
            $end = \Carbon\Carbon::createFromDate($yil, $ay, 1)->endOfMonth();

            // 1. Günlük Satış Adetleri
            $satislar = DB::connection('mysql')->table('SiparisUrunleri as su')
                ->join('Siparisler as s', 's.SiparisID', '=', 'su.SiparisID')
                ->selectRaw("
                    CAST(s.Tarih as DATE) as TarihRaw,
                    SUM(su.Miktar) as ToplamUrun
                ")
                ->whereBetween('s.Tarih', [$start, $end])
                ->whereNotIn('s.SiparisDurumu', [8, 9])
                ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('su.StokKodu', 'NOT LIKE', '%hediye%')
                ->where('su.Durum', 0)
                ->groupByRaw("CAST(s.Tarih as DATE)")
                ->get()
                ->keyBy('TarihRaw');

            // 2. Günlük Kârlar
            $karlar = DB::connection('mysql')->table('SiparisKarlar as sk')
                ->join('Siparisler as s', 's.SiparisID', '=', 'sk.SiparisID')
                ->selectRaw("
                    CAST(s.Tarih as DATE) as TarihRaw,
                    SUM(sk.GercekKar) as ToplamKar
                ")
                ->whereBetween('s.Tarih', [$start, $end])
                ->whereNotIn('s.SiparisDurumu', [8, 9])
                ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
                ->where('sk.UrunKodu', 'TOPLAM')
                ->groupByRaw("CAST(s.Tarih as DATE)")
                ->get()
                ->keyBy('TarihRaw');

            $takvim = [];
            $tempDate = $start->copy();
            while($tempDate <= $end) {
                $dateStr = $tempDate->toDateString();
                $takvim[$dateStr] = [
                    'gun' => $tempDate->day,
                    'ay'  => $tempDate->month,
                    'yil' => $tempDate->year,
                    'adet' => isset($satislar[$dateStr]) ? $satislar[$dateStr]->ToplamUrun : 0,
                    'kar' => isset($karlar[$dateStr]) ? $karlar[$dateStr]->ToplamKar : 0,
                ];
                $tempDate->addDay();
            }
        }

        return view('siparisler.takvim', compact('takvim', 'ay', 'yil', 'start', 'allTime'));
    }



    // 🟢 Siparişe Ek Gelir/Gider Ekleme
    public function ekstraEkle(Request $request)
    {
        $request->validate([
            'siparis_id'  => 'required|string|exists:mysql.Siparisler,SiparisID',
            'tur'         => 'required|in:GELIR,GIDER',
            'tutar'       => 'required|numeric|min:0',
            'para_birimi' => 'required|in:TL,USD',
            'aciklama'    => 'nullable|string|max:255',
        ]);

        $tutar = $request->tutar;
        $aciklama = $request->aciklama;

        // Dolar seçildiyse Kur ile çarpıp TL'ye çevir
        if ($request->para_birimi == 'USD') {
            // Siparişin kendi tarihindeki kuru bul
            $siparis = DB::connection('mysql')->table('Siparisler')->where('SiparisID', $request->siparis_id)->first();
            $siparisTarihi = \Carbon\Carbon::parse($siparis->Tarih)->toDateString();
            
            $ayar = DB::connection('mysql')->table('ayar_gecmisi')
                ->where('tarih', '<=', $siparisTarihi)
                ->orderBy('tarih', 'desc')
                ->first();

            $dolarKuru = $ayar ? $ayar->dolar_kuru : 1; // Kur bulunamazsa 1 al

            $tlKarsiligi = $tutar * $dolarKuru;
            
            // Açıklamaya not ekle
            $aciklama = $aciklama . " ({$tutar} USD - Kur: {$dolarKuru})";
            
            // Kaydedilecek tutarı güncelle
            $tutar = $tlKarsiligi;
        }

        DB::connection('mysql')->table('SiparisEkstralar')->insert([
            'SiparisID' => $request->siparis_id,
            'Tur'       => $request->tur,
            'Tutar'     => $tutar,
            'Aciklama'  => $aciklama,
            'Tarih'     => now()
        ]);

        // Mesajı belirle
        $turMesaj = $request->tur == 'GELIR' ? 'Ek Gelir' : 'Ek Gider';
        
        return back()->with('success', "Siparişe {$request->tutar} {$request->para_birimi} {$turMesaj} eklendi.");
    }

    public function ekstraSil($id)
    {
        $ekstra = DB::connection('mysql')->table('SiparisEkstralar')->where('Id', $id)->first();
        if($ekstra) {
            DB::connection('mysql')->table('SiparisEkstralar')->where('Id', $id)->delete();
            return back()->with('success', 'Ekstra işlem silindi.');
        }
        return back()->with('hata', 'Kayıt bulunamadı.');
    }

    // 🟢 Sipariş Notları
    public function notlariGetir($id)
    {
        $notlar = DB::connection('mysql')->table('SiparisNotlari')
            ->select('ID', 'SiparisID', DB::raw('`Not`'), 'Tarih')->where('SiparisID', $id)
            ->orderBy('Tarih', 'desc')
            ->get();

        return response()->json($notlar);
    }

    public function notEkle(Request $request, $id)
    {
        $request->validate([
            'not' => 'required|string|max:1000',
        ]);

        DB::connection('mysql')->table('SiparisNotlari')->insert([
            'SiparisID' => $id,
            'Not' => $request->not,
            'Tarih' => now(),
        ]);

        return back()->with('success', 'Sipariş notu başarıyla eklendi.');
    }

    public function notSil($id, $notId)
    {
        $not = DB::connection('mysql')->table('SiparisNotlari')
            ->where('ID', $notId)
            ->where('SiparisID', $id)
            ->first();

        if (!$not) {
            return back()->with('hata', 'Not bulunamadı.');
        }

        DB::connection('mysql')->table('SiparisNotlari')->where('ID', $notId)->delete();

        return back()->with('success', 'Not başarıyla silindi.');
    }

    
 public function haritaVerileri(Request $request)
    {
        // 1. Sorguyu Hazırla
        $query = DB::connection('mysql')->table('Siparisler as s')
            ->join('SiparisUrunleri as u', 's.SiparisID', '=', 'u.SiparisID')
            ->select(
                's.Il',
                's.Ilce',
                DB::raw('SUM(u.Miktar) as ToplamUrun')
            )
            ->whereNotIn('s.SiparisDurumu', [8, 9]) // İptal ve İade olmayanlar
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing') // Özel filtre
            ->where('u.Durum', 0); // Ürün bazında iptal edilenleri hariç tut

        // 2. Tarih Filtresi (Eğer tarih seçildiyse)
        if ($request->filled('baslangic') && $request->filled('bitis')) {
            $query->whereBetween('s.Tarih', [
                $request->baslangic . ' 00:00:00', 
                $request->bitis . ' 23:59:59'
            ]);
        }

        // 3. Veriyi Çek
        $rawStats = $query->groupBy('s.Il', 's.Ilce')->get();

        // 4. Türkiye Plaka Kodları Eşleştirmesi
        $plateMap = [
            'ADANA' => 'TR-01', 'ADIYAMAN' => 'TR-02', 'AFYONKARAHİSAR' => 'TR-03', 'AFYON' => 'TR-03',
            'AĞRI' => 'TR-04', 'AMASYA' => 'TR-05', 'ANKARA' => 'TR-06', 'ANTALYA' => 'TR-07',
            'ARTVİN' => 'TR-08', 'AYDIN' => 'TR-09', 'BALIKESİR' => 'TR-10', 'BİLECİK' => 'TR-11',
            'BİNGÖL' => 'TR-12', 'BİTLİS' => 'TR-13', 'BOLU' => 'TR-14', 'BURDUR' => 'TR-15',
            'BURSA' => 'TR-16', 'ÇANAKKALE' => 'TR-17', 'ÇANKIRI' => 'TR-18', 'ÇORUM' => 'TR-19',
            'DENİZLİ' => 'TR-20', 'DİYARBAKIR' => 'TR-21', 'EDİRNE' => 'TR-22', 'ELAZIĞ' => 'TR-23',
            'ERZİNCAN' => 'TR-24', 'ERZURUM' => 'TR-25', 'ESKİŞEHİR' => 'TR-26', 'GAZİANTEP' => 'TR-27',
            'GİRESUN' => 'TR-28', 'GÜMÜŞHANE' => 'TR-29', 'HAKKARİ' => 'TR-30', 'HATAY' => 'TR-31',
            'ISPARTA' => 'TR-32', 'MERSİN' => 'TR-33', 'İÇEL' => 'TR-33', 'İSTANBUL' => 'TR-34',
            'İZMİR' => 'TR-35', 'KARS' => 'TR-36', 'KASTAMONU' => 'TR-37', 'KAYSERİ' => 'TR-38',
            'KIRKLARELİ' => 'TR-39', 'KIRŞEHİR' => 'TR-40', 'KOCAELİ' => 'TR-41', 'İZMİT' => 'TR-41',
            'KONYA' => 'TR-42', 'KÜTAHYA' => 'TR-43', 'MALATYA' => 'TR-44', 'MANİSA' => 'TR-45',
            'KAHRAMANMARAŞ' => 'TR-46', 'MARAŞ' => 'TR-46', 'MARDİN' => 'TR-47', 'MUĞLA' => 'TR-48',
            'MUŞ' => 'TR-49', 'NEVŞEHİR' => 'TR-50', 'NİĞDE' => 'TR-51', 'ORDU' => 'TR-52',
            'RİZE' => 'TR-53', 'SAKARYA' => 'TR-54', 'ADAPAZARI' => 'TR-54', 'SAMSUN' => 'TR-55',
            'SİİRT' => 'TR-56', 'SİNOP' => 'TR-57', 'SİVAS' => 'TR-58', 'TEKİRDAĞ' => 'TR-59',
            'TOKAT' => 'TR-60', 'TRABZON' => 'TR-61', 'TUNCELİ' => 'TR-62', 'ŞANLIURFA' => 'TR-63',
            'URFA' => 'TR-63', 'UŞAK' => 'TR-64', 'VAN' => 'TR-65', 'YOZGAT' => 'TR-66',
            'ZONGULDAK' => 'TR-67', 'AKSARAY' => 'TR-68', 'BAYBURT' => 'TR-69', 'KARAMAN' => 'TR-70',
            'KIRIKKALE' => 'TR-71', 'BATMAN' => 'TR-72', 'ŞIRNAK' => 'TR-73', 'BARTIN' => 'TR-74',
            'ARDAHAN' => 'TR-75', 'IĞDIR' => 'TR-76', 'YALOVA' => 'TR-77', 'KARABÜK' => 'TR-78',
            'KİLİS' => 'TR-79', 'OSMANİYE' => 'TR-80', 'DÜZCE' => 'TR-81',
            // Alternatif yazımlar
            'MERSİN(İÇEL)' => 'TR-33', 'KAHRAMAN MARAŞ' => 'TR-46',
            'AFYONKARAHİSAR' => 'TR-03', 'AFYON' => 'TR-03'
        ];

        $finalData = [];

        // ASCII fallback: 'Istanbul' (ASCII I) → 'İSTANBUL' gibi eşleşmeler için
        $toAscii = fn($s) => str_replace(
            ['İ', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç'],
            ['I', 'S', 'G', 'U', 'O', 'C'],
            mb_strtoupper($s, 'UTF-8')
        );
        $asciiPlateMap = [];
        foreach ($plateMap as $k => $v) {
            $asciiPlateMap[$toAscii($k)] = $v;
        }

        foreach ($rawStats as $stat) {
            $ilRaw = trim($stat->Il);
            if (empty($ilRaw)) continue;

            $ilNormalized = str_replace(
                ['ı', 'i', 'ü', 'ö', 'ç', 'ş', 'ğ'],
                ['I', 'İ', 'Ü', 'Ö', 'Ç', 'Ş', 'Ğ'],
                $ilRaw
            );
            $ilUpper = mb_strtoupper($ilNormalized, "UTF-8");
            $ilUpper = preg_replace('/\s*\(\s*/', '(', $ilUpper);
            $ilUpper = preg_replace('/\s*\)\s*/', ')', $ilUpper);

            // Önce direkt eşleştir, bulamazsa ASCII normalize ederek dene
            $mapId = $plateMap[$ilUpper] ?? $asciiPlateMap[$toAscii($ilUpper)] ?? null;

            if ($mapId) {

                // Eğer bu şehir dizide yoksa oluştur
                if (!isset($finalData[$mapId])) {
                    
                    $finalData[$mapId] = [
                        'id' => $mapId,
                        'value' => 0,
                        'districts_raw' => [] 
                    ];
                }

                // Şehir toplamını artır
                $finalData[$mapId]['value'] += (int)$stat->ToplamUrun;

                // İlçe İşlemleri (Aynı ilçenin farklı yazımları olabilir, birleştirelim)
                if ($stat->Ilce) {
                    $ilceRaw = $stat->Ilce;
                    
                    // DÜZELTME: İlçe isimleri harita verisinde "Title Case" (Örn: Kadıköy) olduğu için 
                    // biz de veriyi o formata çeviriyoruz. "KADIKÖY" -> "Kadıköy"
                    $ilceFormatted = mb_convert_case($ilceRaw, MB_CASE_TITLE, "UTF-8");
                    
                    if (!isset($finalData[$mapId]['districts_raw'][$ilceFormatted])) {
                        $finalData[$mapId]['districts_raw'][$ilceFormatted] = 0;
                    }
                    $finalData[$mapId]['districts_raw'][$ilceFormatted] += (int)$stat->ToplamUrun;
                }
            }
        }

        // Son formatı oluştur (Key'leri temizle, array_values yap)
        foreach ($finalData as $key => $cityData) {
            $districtsFormatted = [];
            foreach ($cityData['districts_raw'] as $dName => $dVal) {
                $districtsFormatted[] = [
                    'category' => $dName, // Bu isim artık "Kadıköy" formatında, haritayla eşleşecek.
                    'value' => $dVal
                ];
            }
            // Geçici key'i sil, gerçek districts dizisini ata
            unset($finalData[$key]['districts_raw']);
            $finalData[$key]['districts'] = $districtsFormatted;
        }

        // View'a veriyi gönder
        return view('siparisler.harita', [
            'mapData' => array_values($finalData)
        ]);
    }
    public function toggleUSA($id)
    {
        $order = DB::connection('mysql')->table('Siparisler')->where('SiparisID', $id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Sipariş bulunamadı.']);
        }

        $newState = $order->isUSA ? 0 : 1;
        
        DB::connection('mysql')->table('Siparisler')
            ->where('SiparisID', $id)
            ->update(['isUSA' => $newState]);

        return response()->json([
            'success' => true, 
            'newState' => $newState,
            'message' => $newState ? 'Amerika siparişi olarak işaretlendi.' : 'Amerika işareti kaldırıldı.'
        ]);
    }

    // 🟢 Ürün Bazlı İptal
    public function urunIptalEt($siparisId, $urunId)
    {
        $urun = DB::connection('mysql')->table('SiparisUrunleri')
            ->where('Id', $urunId)
            ->where('SiparisID', $siparisId)
            ->first();

        if (!$urun) {
            return back()->with('hata', 'Ürün bulunamadı.');
        }

        if (($urun->Durum ?? 0) == 1) {
            return back()->with('hata', 'Bu ürün zaten iptal edilmiş.');
        }

        DB::connection('mysql')->table('SiparisUrunleri')
            ->where('Id', $urunId)
            ->update(['Durum' => 1]);

        // Sistem notu ekle
        DB::connection('mysql')->table('SiparisNotlari')->insert([
            'SiparisID' => $siparisId,
            'Not' => 'SİSTEM: Ürün iptal edildi → ' . $urun->UrunAdi . ' (' . $urun->StokKodu . ')',
            'Tarih' => now(),
        ]);

        return back()->with('success', 'Ürün iptal edildi: ' . $urun->UrunAdi);
    }

    // 🟢 Ürün İptal Geri Alma
    public function urunIptalGeriAl($siparisId, $urunId)
    {
        $urun = DB::connection('mysql')->table('SiparisUrunleri')
            ->where('Id', $urunId)
            ->where('SiparisID', $siparisId)
            ->first();

        if (!$urun) {
            return back()->with('hata', 'Ürün bulunamadı.');
        }

        if (($urun->Durum ?? 0) == 0) {
            return back()->with('hata', 'Bu ürün zaten aktif.');
        }

        DB::connection('mysql')->table('SiparisUrunleri')
            ->where('Id', $urunId)
            ->update(['Durum' => 0]);

        // Sistem notu ekle
        DB::connection('mysql')->table('SiparisNotlari')->insert([
            'SiparisID' => $siparisId,
            'Not' => 'SİSTEM: Ürün iptali geri alındı → ' . $urun->UrunAdi . ' (' . $urun->StokKodu . ')',
            'Tarih' => now(),
        ]);

        return back()->with('success', 'Ürün iptali geri alındı: ' . $urun->UrunAdi);
    }
}

