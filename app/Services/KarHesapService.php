<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class KarHesapService
{
    private $cachedHediyeKodlari = null;
    /**
     * Ürün karını hesaplar ve sonucu veritabanına kaydeder.
     * Stok kodu veritabanında -yeni uzantılı olarak kayıtlı olabilir, bu durum kontrol edilir.
     * Ayrıca gramaj eksikse, alternatif stok kodlarından (H070 <-> H070-yeni) gramaj tamamlanır.
     * Hediye mantığı kaldırılmıştır; her ürün hesaplamaya dahil edilir.
     *
     * @param object $urun Sipariş ürün detayları
     * @return array Hesaplama sonuçları
     */
    public function hesapla($urun)
    {
        // =================================================================
        // 🚀 YENİ SİSTEM: TARİHSEL AYARLARI ÇEKME
        // =================================================================
        $siparisTarihi = \Carbon\Carbon::parse($urun->Tarih)->toDateString();
        
        $ayar = DB::table('ayar_gecmisi')
            ->where('tarih', '<=', $siparisTarihi)
            ->orderBy('tarih', 'desc')
            ->first();

        // Eğer o tarihe uygun ayar bulunamazsa, hesaplamayı durdur.
        if (!$ayar) {
            // Kar tablosuna karı 0 olarak kaydet.
            DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
                ['SiparisID' => $urun->SiparisID, 'UrunKodu' => $urun->StokKodu],
                ['GercekKar' => 0, 'HesapTarihi' => now()]
            );

            // Sipariş notlarına sistem notu olarak ekle.
            DB::connection('sqlsrv')->table('SiparisNotlari')->insert([
                'SiparisID' => $urun->SiparisID,
                'Not'       => 'SİSTEM: Kâr hesaplanamadı. Sipariş tarihinde geçerli bir ayar kaydı bulunamadı.',
                'Tarih'     => now()
            ]);

            return []; // Boş array döndürerek hatayı belirtiyoruz.
        }

        // Hediye Kontrolü
        if ($this->isHediye($urun->StokKodu, $ayar)) {
            return [
                'isHediye' => true,
                'etsy' => (int)$urun->PazaryeriID === 3,
                'gercekKar' => 0,
                'karTL' => 0,
                'miktar' => $urun->Miktar,
                'pazaryeri' => $urun->PazaryeriAdi ?? 'Hediye',
                'gercekSatis' => 0,
                'altinMaliyeti' => 0,
                'toplamMaliyet' => 0,
                'gider' => 0,
                'vergi' => 0,
                'odenenKomisyon' => 0,
                'gercekNetSatis' => 0,
                'karUSD' => 0,
                'karTL' => 0
            ];
        }

        // 🔥 TARİH KISITLAMASI: 9 Ekim 2025 Öncesi Hesaplama Yapma (Kâr = 0)
        // User Request: "9 ekim 2025 den öncesinin karı hesaplanmayacak"
        // 🔥 TARİH KISITLAMASI KALDIRILDI
        // if ($siparisTarihi < '2025-10-09') { ... } bloğu silindi.


        // =================================================================
        // 🛠️ 0. ADIM: EKSİK GRAMAJ TAMAMLAMA
        // =================================================================
        if (!isset($urun->Gram) || $urun->Gram <= 0) {
            $aranacakKodlar = [];

            // Eğer gelen kodda "yeni" ifadesi varsa, temizleyip saf halini (H070) arayacağız.
            if (stripos($urun->StokKodu, 'yeni') !== false) {
                $temizKod = preg_replace('/[- ]?yeni/i', '', $urun->StokKodu);
                $aranacakKodlar[] = $temizKod;
            } 
            // Eğer "yeni" yoksa, belki veritabanında "-yeni"li hali vardır
            else {
                $aranacakKodlar[] = $urun->StokKodu . '-yeni';
                $aranacakKodlar[] = $urun->StokKodu . '-YENİ';
            }

            // Urunler tablosundan gramajı sorgula
            if (!empty($aranacakKodlar)) {
                $anaUrun = DB::connection('sqlsrv')
                    ->table('Urunler')
                    ->whereIn('UrunKodu', $aranacakKodlar)
                    ->where('Gram', '>', 0)
                    ->select('Gram')
                    ->first();

                if ($anaUrun) {
                    $urun->Gram = $anaUrun->Gram; // Bulunan gramajı ata
                }
            }
        }

        // =================================================================
        // STOK KODU KONTROL FONKSİYONU (SiparisKarlar Tablosu İçin)
        // =================================================================
        $getStokKodu = function ($siparisId, $stokKodu) {
            $stokKoduYeni = $stokKodu . '-yeni';
            
            // 1. Veritabanında "-yeni" eklenmiş hali var mı?
            $kayitYeni = DB::connection('sqlsrv')
                ->table('SiparisKarlar')
                ->where('SiparisID', $siparisId)
                ->where('UrunKodu', $stokKoduYeni)
                ->first();

            if ($kayitYeni) {
                return $stokKoduYeni; 
            }
            
            // 2. Veritabanında orijinal hali var mı?
            $kayitOrijinal = DB::connection('sqlsrv')
                ->table('SiparisKarlar')
                ->where('SiparisID', $siparisId)
                ->where('UrunKodu', $stokKodu)
                ->first();

            if ($kayitOrijinal) {
                return $stokKodu; 
            }

            // 3. Hiçbiri yoksa orijinali dön
            return $stokKodu;
        };

        // ---------------------------------------------
        // 🔥 1) ETSY SİPARİŞİ
        // ---------------------------------------------
        if ((int)$urun->PazaryeriID === 3) {
            
            $finalStokKodu = $getStokKodu($urun->SiparisID, $urun->StokKodu);

            $miktar         = (int)$urun->Miktar;
            $gram           = (float)$urun->Gram; // Gram yoksa 0 olarak hesaplanır
            $dolarKuru      = (float)$ayar->dolar_kuru;
            $altinUSD       = (float)$ayar->altin_usd;
            
            // Komisyonu ayar_gecmisi tablosundan al
            $komisyonOrani = (float)$ayar->komisyon_etsy;
            
            $isUSA          = (int)$urun->isUSA;

            // Satış rakamları
            // NOT: is_manuel=0 ise API'den gelmiştir ve Tutar birimdir.
            // is_manuel=1 ise Elle girilmiştir ve Tutar toplamdır.
            $birimTL = $urun->Tutar + ($urun->KdvTutari ?? 0);
            
            if (isset($urun->is_manuel) && (int)$urun->is_manuel === 0) {
                 $satirToplamTL = $birimTL * $miktar; 
            } else {
                 $satirToplamTL = $birimTL; // Manuel ise direkt toplam
            }
            $satisUSD = $satirToplamTL / $dolarKuru;

            // Komisyon kes
            $netUSD = $satisUSD - ($satisUSD * $komisyonOrani);

            // USA vergileri
            if ($isUSA === 1) {
                $usaVergi = $satisUSD * (float)$ayar->etsy_usa_tax_rate;
                $netUSD  -= $usaVergi;
                $netUSD  -= (float)$ayar->etsy_ship_cost;
            }

            // Altın maliyeti (USD)
            $goldPurity = isset($urun->ayar_orani) ? (float)$urun->ayar_orani : 0.585;
            $altinMaliyetUSD = ($gram * $altinUSD * $goldPurity) * $urun->Miktar;

            // TL giderleri (İşçilik, kutu vb.)
            $giderTL =
                 $ayar->iscilik
               + $ayar->kutu
               + $ayar->reklam
               + $ayar->kargo_yurtdisi;

            // Giderleri USD'ye dönüştür (adet dahil toplam)
            $giderUSD = ($giderTL / $dolarKuru) * $urun->Miktar;

            // Toplam maliyet (USD)
            $toplamMaliyetUSD = $altinMaliyetUSD + $giderUSD;

            // Kâr
            $karUSD = $netUSD - $toplamMaliyetUSD;
            $karTL  = round($karUSD * $dolarKuru, 2);

            DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
                ['SiparisID' => $urun->SiparisID, 'UrunKodu' => $finalStokKodu],
                ['GercekKar' => $karTL, 'Vergi' => 0, 'HesapTarihi' => now()]
            );

            // 🔥 ETSY RETURN - EKSİK KEYLER DOLDURULDU VE KUR ÇEVİRİMİ YAPILDI
            return [
                'etsy'             => true,
                'pazaryeri'        => 'Etsy',
                'komisyon'         => $komisyonOrani,
                'gercekKar'        => $karTL,
                'isUSA'            => $isUSA,
                'netUSD'           => $netUSD,
                'altinMaliyetUSD'  => $altinMaliyetUSD,
                'giderUSD'         => $giderUSD,
                'toplamMaliyetUSD' => $toplamMaliyetUSD,
                'karUSD'           => $karUSD,
                'karTL'            => $karTL,
                'miktar'           => $miktar,
                
                // --- EKRANDA (0,00 TL) YAZMAMASI İÇİN KUR İLE ÇARPIP GÖNDERİYORUZ ---
                'altinMaliyeti'    => $altinMaliyetUSD * $dolarKuru, // Toplam TL Altın Maliyeti
                'gider'            => $giderUSD * $dolarKuru,        // Toplam TL Gider
                'gercekSatis'      => $satisUSD * $dolarKuru,        // Toplam TL Satış (Brüt)
                'toplamMaliyet'    => $toplamMaliyetUSD * $dolarKuru,// Toplam TL Maliyet
                
                // Bunlar Etsy'de olmadığı için 0 kalabilir (Hata vermemesi için gerekli)
                'vergi'            => 0,
                'odenenKomisyon'   => 0,
                'gercekNetSatis'   => 0,
                'gider_detay'      => [
                    'iscilik' => (float)$ayar->iscilik * $miktar,
                    'reklam'  => (float)$ayar->reklam * $miktar,
                    'kargo_yurtdisi' => (float)$ayar->kargo_yurtdisi * $miktar,
                    'kutu'    => (float)$ayar->kutu * $miktar,
                ],
            ];
        }

        // ---------------------------------------------
        // 🔥 2) NORMAL (TL) SİPARİŞ
        // ---------------------------------------------
        
        $finalStokKodu = $getStokKodu($urun->SiparisID, $urun->StokKodu);

        $miktar          = (int)$urun->Miktar;
        // Müşteri Talebi: Site (ID: 1) ise çarp, değilse çarpma.
        $isSite = false;
        
        // 1. ID Kontrolü
        if (isset($urun->PazaryeriID) && (int)$urun->PazaryeriID === 1) {
            $isSite = true;
        }

        // 2. İsim Kontrolü
        if (!$isSite && isset($urun->PazaryeriAdi)) {
             if (stripos($urun->PazaryeriAdi, 'dianora') !== false || stripos($urun->PazaryeriAdi, 'Site') !== false) {
                 $isSite = true;
             }
        }

        $satirToplamNet  = (float)$urun->Tutar;
        $satirToplamKdv  = (float)$urun->KdvTutari;
        
        if ($isSite) {
            $totalSatisKdvli = ($satirToplamNet + $satirToplamKdv) * $miktar;
        } else {
            $totalSatisKdvli = ($satirToplamNet + $satirToplamKdv);
        }
        
        $gram            = (float)$urun->Gram; // Gram yoksa 0 olarak hesaplanır

        // Maliyetler (Maliyetler birim bazlı olduğu için çarpılmalı)
        $goldPurity = isset($urun->ayar_orani) ? (float)$urun->ayar_orani : 0.585;
        $altinMaliyet  = $gram * $ayar->altin_fiyat * $goldPurity * $miktar;
        $gider         = ($ayar->iscilik + $ayar->kargo + $ayar->kutu + $ayar->reklam) * $miktar;
        $toplamMaliyet = $altinMaliyet + $gider;

        // KDV
        $kdvOrani = $ayar->kdv / 100;
        
        $netVergiHaric = ($totalSatisKdvli - $altinMaliyet) / 1.2 ;
        $vergi = ($totalSatisKdvli - $altinMaliyet) - (($totalSatisKdvli - $altinMaliyet) / 1.2) ;
      
        // Komisyon
        $komisyon = 0;
        $pzId = (int)$urun->PazaryeriID;
        if ($pzId === 1) $komisyon = (float)$ayar->komisyon_site;
        elseif ($pzId === 2) $komisyon = (float)$ayar->komisyon_trendyol;
        elseif ($pzId === 3) $komisyon = (float)$ayar->komisyon_etsy;
        elseif ($pzId === 4) $komisyon = (float)$ayar->komisyon_hipicon;
        else $komisyon = (float)($urun->PazarKomisyon ?? 0);
        
        $havaleIndirimi = 0;
        $indirimsizSatis = $totalSatisKdvli;

        // Havale İndirimi Kontrolü (Ödeme Tipi 1 ise)
        if (isset($urun->OdemeTipi) && (int)$urun->OdemeTipi === 1) {
            // Veritabanındaki fiyat %5 indirimli halidir. İndirimsiz halini bul:
            $indirimsizSatis = $totalSatisKdvli / 0.95; 
            $havaleIndirimi = $indirimsizSatis - $totalSatisKdvli;
            
            // Havale olduğu için komisyon alınmaz
            $komisyon = 0;
        }

        $odenenKomisyon = $totalSatisKdvli * $komisyon;

        // Net satış
        $gercekNetSatisBirim = $totalSatisKdvli - ($vergi + $odenenKomisyon);
        $gercekKarToplam = round($gercekNetSatisBirim - $toplamMaliyet, 2);
        
        // Veritabanına kaydet
        DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
            ['SiparisID' => $urun->SiparisID, 'UrunKodu' => $finalStokKodu],
            ['GercekKar' => $gercekKarToplam, 'Vergi' => $vergi, 'HesapTarihi' => now()]
        );

        // 🔥 NORMAL RETURN - EKSİK KEYLER DOLDURULDU
        return [
            'etsy'             => false,
            'pazaryeri'        => $urun->PazaryeriAdi,
            'komisyon'         => $komisyon,
            'gercekKar'        => $gercekKarToplam,
            'miktar'           => $miktar,
            'altinMaliyeti'    => $altinMaliyet,
            'toplamMaliyet'    => $toplamMaliyet,
            'kdvOrani'         => $kdvOrani,
            'vergi'            => $vergi,
            'odenenKomisyon'   => $odenenKomisyon,
            'gercekNetSatis'   => $gercekNetSatisBirim,
            // UI toplamı (Toplam ciro)
            'gercekSatis'      => $totalSatisKdvli, 
            'gider'            => $gider,
            'gider_detay'      => [
                'iscilik' => $ayar->iscilik * $miktar,
                'reklam'  => $ayar->reklam * $miktar,
                'kargo'   => $ayar->kargo * $miktar, 
                'kutu'    => $ayar->kutu * $miktar,
                'tekil_kargo' => $ayar->kargo,
                'tekil_kutu'  => $ayar->kutu,
            ],
            'havale_indirimi'  => $havaleIndirimi,
            'indirimsiz_satis' => $indirimsizSatis,
            
            // USD Keyleri (Hata vermemesi için 0)
            'isUSA'            => false,
            'netUSD'           => 0,
            'altinMaliyetUSD'  => 0,
            'giderUSD'         => 0,
            'toplamMaliyetUSD' => 0,
            'karUSD'           => 0,
            'karTL'            => $gercekKarToplam, 
        ];
    }

    /**
     * Sipariş bazlı tam kâr analizi yapar. 
     * Hediye çeklerini düşer, toplam havuzdan maliyetleri çıkarır ve tek bir sonuç üretir.
     */
    public function hesaplaSiparis($siparisId)
    {
        // 1. Sipariş Detaylarını Çek
        $siparis = DB::connection('sqlsrv')->table('Siparisler as s')
            ->leftJoin('Pazaryerleri as p', 'p.id', '=', 's.PazaryeriID')
            ->select('s.*', 'p.KomisyonOrani', 'p.Ad as PazaryeriAdi', 's.HediyeCekiTutari')
            ->where('s.SiparisID', $siparisId)
            ->first();

        if (!$siparis) return null;

        // 2. Sipariş Ürünlerini Çek
        $urunler = DB::connection('sqlsrv')->table('SiparisUrunleri as u')
            ->leftJoin('Urunler as main', 'main.UrunKodu', '=', 'u.StokKodu') 
            ->where('u.SiparisID', $siparisId)
            ->select('u.*', 'main.Gram as AnaGram')
            ->get();

        // 3. Geçmiş Ayarı Bul (Sipariş Tarihine Göre)
        $siparisTarihi = \Carbon\Carbon::parse($siparis->Tarih)->toDateString();
        $ayar = DB::table('ayar_gecmisi')
            ->where('tarih', '<=', $siparisTarihi)
            ->orderBy('tarih', 'desc')
            ->first();

        if (!$ayar) {
            return null;
        }

        // 🔥 TARİH KISITLAMASI: 9 Ekim 2025 Öncesi Hesaplama Yapma (Kâr = 0)
        // 🔥 TARİH KISITLAMASI KALDIRILDI
        // if ($siparisTarihi < '2025-10-09') { ... } bloğu silindi.


        // 4. Değişkenler
        $toplamNetSatis = 0; // TL ya da USD (Pazaryeri moduna göre)
        $toplamBrutSatis = 0; // Vergi Hesabı için Brüt (Komisyon düşülmemiş)
        $toplamAltinMaliyeti = 0;
        $toplamEkstraGider = 0; 
        $toplamVergi = 0;
        $toplamKomisyon = 0;
        $toplamGram = 0;
        $toplamHavaleIndirimi = 0;
        $gramEksik = false;

        // --- İPTAL ve İADE DURUMU KONTROLÜ (Status 8 & 9) ---
        if ((int)$siparis->SiparisDurumu === 8 || (int)$siparis->SiparisDurumu === 9) {
            // İade/İptal durumunda kargo gidiş-geliş zararı
            
            // Etsy ve Yurtiçi için kargo maliyeti (TL)
            // Etsy kargo maliyeti (Gider) veritabanında "kargo_yurtdisi" olarak tutuluyor.
            // Yurtiçi için "kargo".
            
            $isEtsy = (int)$siparis->PazaryeriID === 3;
            $dolarKuru = (float)$ayar->dolar_kuru;

            $kargoMaliyetiTL = $isEtsy ? (float)$ayar->kargo_yurtdisi : (float)$ayar->kargo;
            
            // Zarar: 2 x Kargo (Gidiş + Geliş)
            // Kullanıcı talebi: İade (9) ise x2 kargo parası zarar olmalı, İptal (8) ise hiç kargoya verilmediği için 0.
            $zararTL = ((int)$siparis->SiparisDurumu === 9) ? (2 * $kargoMaliyetiTL) : 0; 
            
            // Ekstraları dahil et (İptal olsa bile ek gelir/gider olabilir)
            $ekstralar = DB::connection('sqlsrv')->table('SiparisEkstralar')
                ->where('SiparisID', $siparisId)
                ->get();
            $ekstraNet = $ekstralar->sum(fn($e) => $e->Tur == 'GELIR' ? $e->Tutar : -$e->Tutar);

            $gercekKarTL = round(-$zararTL + $ekstraNet, 2);
            
            // DB Update (TOPLAM satırına ekle)
            DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
                ['SiparisID' => $siparisId, 'UrunKodu' => 'TOPLAM'],
                ['GercekKar' => $gercekKarTL, 'Vergi' => 0, 'HesapTarihi' => now()]
            );
            
            // Return yapısı (Controller ve View uyumlu)
            return [
                'etsy' => $isEtsy,
                'pazaryeri' => $siparis->PazaryeriAdi,
                
                // Kar Bilgisi (Zarar olduğu için negatif)
                'kar' => $gercekKarTL,
                'karUSD' => $isEtsy && $dolarKuru > 0 ? ($gercekKarTL / $dolarKuru) : 0,
                
                // Maliyet Gösterimi (Toplam Maliyet = Zarar)
                'gider' => $zararTL, 
                'toplamMaliyet' => $zararTL,
                'toplamMaliyetUSD' => $isEtsy && $dolarKuru > 0 ? ($zararTL / $dolarKuru) : 0,

                'iptal' => true, // Flag
                
                // Diğer değerler 0 (Satış gerçekleşmedi)
                'netSalesUSD'=> 0,
                'altinMaliyetUSD' => 0,
                'giderUSD'   => $isEtsy && $dolarKuru > 0 ? ($zararTL / $dolarKuru) : 0,
                'gram'       => 0,
                'altin'      => 0,
                'komisyonUSD'=> 0, 
                'vergiUSD'   => 0,
                'komisyon'   => 0,
                'vergi'      => 0,
                'netCiro'    => 0,
                'toplamCiro' => 0,
                
                // KUR BİLGİLERİ
                'dolarKuru'  => $dolarKuru,
                'altinBirimUSD' => (float)$ayar->altin_usd, // Birim fiyat ($/gr)
                'altinTL'    => $ayar->altin_fiyat,
                'hesaplanabilir' => true
            ];
        }

        // Etsy Kontrolü
        $isEtsy = (int)$siparis->PazaryeriID === 3;
        $dolarKuru = (float)$ayar->dolar_kuru;
        $altinUSD  = (float)$ayar->altin_usd;

        $iscilikBirim = $ayar->iscilik;
        $kutuBirim    = $ayar->kutu;
        $kargoBirim   = $ayar->kargo;
        $reklamBirim  = $ayar->reklam;
        $etsyShipCost = (float)$ayar->etsy_ship_cost; // Etsy için fixed kargo USD

        // Detaylı Giderler Birikimi
        $detayliGiderler = [
            'iscilik' => 0,
            'kutu'    => 0,
            'reklam'  => 0,
            'kargo'   => 0,
            'kargo_yurtdisi' => 0,
        ];

        // 5. Ürünleri Döngüyle Topla
        foreach ($urunler as $u) {
            if ($this->isHediye($u->StokKodu, $ayar)) continue;

            $miktar = $u->Miktar;
            
            // Gram Kontrolü
            $gram = ($u->Gram ?? 0) > 0 ? $u->Gram : ($u->AnaGram ?? 0);
            
            if ($gram <= 0) {
                 $temizKod = preg_replace('/[- ]?(yeni|eski|YENI|YENİ|ESKI|ESKİ)/iu', '', $u->StokKodu);
                 $kokKod = explode('-', $u->StokKodu)[0];
                 
                 $yedekUrun = DB::connection('sqlsrv')->table('Urunler')
                    ->whereIn('UrunKodu', [$temizKod, $kokKod, $u->StokKodu . '-yeni'])
                    ->where('Gram', '>', 0)
                    ->select('Gram')
                    ->first();
                    
                 if ($yedekUrun) $gram = $yedekUrun->Gram;
            }
            
            if ($gram <= 0) {
                $gramEksik = true;
            }
            
            $toplamGram += $gram * $miktar;

            // --- FİNANSAL HESAPLAMALAR ---
            if ($isEtsy) {
                // USD BAZLI
                // FIX: is_manuel=0 (Auto) ise Tutar Birimdir -> Çarp
                //      is_manuel=1 (Manual) ise Tutar Toplamdır -> Çarpma
                
                $birimTL = ($u->Tutar + ($u->KdvTutari ?? 0));
                
                if ((int)($siparis->is_manuel ?? 0) === 0) {
                     $satirToplamTL = $birimTL * $miktar;
                } else {
                     $satirToplamTL = $birimTL;
                }
                
                $satirToplamUSD = $satirToplamTL / $dolarKuru;
                
                // Komisyonu ayar_gecmisi tablosundan al
                $komisyonOrani = (float)$ayar->komisyon_etsy;
                $komisyonTutariUSD = $satirToplamUSD * $komisyonOrani;
                
                $toplamKomisyon += $komisyonTutariUSD; // Miktar ile çarpmıyoruz, zaten dahil
                $netUSD = $satirToplamUSD - $komisyonTutariUSD;
                
                $toplamNetSatis += $netUSD; // Miktar ile çarpmıyoruz, zaten dahil
                $toplamBrutSatis += $satirToplamUSD; // Brüt Birikim (Vergi için)
                $goldPurity = isset($siparis->ayar_orani) ? (float)$siparis->ayar_orani : 0.585;
                $toplamAltinMaliyeti += ($gram * $altinUSD * $goldPurity) * $miktar; // Gram bazlı, miktar ile çarp

                // Giderler (USD Bazlı birikir) - Kargo ve Kutu hariç
                $toplamEkstraGider += (($iscilikBirim + $reklamBirim) * $miktar) / $dolarKuru;
                
                // Detaylı Birikim (USD) - Kargo ve Kutu hariç
                $detayliGiderler['iscilik'] += ($iscilikBirim * $miktar) / $dolarKuru;
                $detayliGiderler['reklam']  += ($reklamBirim * $miktar) / $dolarKuru;
            } else {
                // TL BAZLI
                
                // Müşteri Talebi: 
                // "sipariş siteden gelmişse 2 ile miktar ile çarpmalı sadece site diğerlerinde çarpmicak"
                // Site (ID: 1) -> Tutar Birim Fiyattır -> Çarp
                // Diğerleri (Hipicon, Trendyol vb) -> Tutar Satır Toplamıdır -> Çarpma
                
                $isSite = false;
                
                // 1. ID Kontrolü (En Garantisi)
                if ((isset($siparis->PazaryeriID) && (int)$siparis->PazaryeriID === 1)) {
                    $isSite = true;
                }
                
                // 2. İsim Kontrolü (Fallback)
                if (!$isSite) {
                    $pazaryeriAdi = $siparis->PazaryeriAdi ?? '';
                    if (stripos($pazaryeriAdi, 'dianora') !== false || stripos($pazaryeriAdi, 'Site') !== false) {
                        $isSite = true;
                    }
                }

                if ($isSite) {
                     $satirToplamTL = ($u->Tutar + ($u->KdvTutari ?? 0)) * $miktar;
                } else {
                     $satirToplamTL = ($u->Tutar + ($u->KdvTutari ?? 0));
                }
                
                // Havale İndirimi Logic
                $isHavale = (isset($siparis->OdemeTipi) && (int)$siparis->OdemeTipi === 1);
                
                if ($isHavale) {
                    $komisyonOrani = 0;
                    // Veritabanındaki fiyat indirimli. İndirimsiz halini bul ve farkı havale indirimi olarak topla
                    $indirimsiz = $satirToplamTL / 0.95;
                    $toplamHavaleIndirimi += ($indirimsiz - $satirToplamTL);
                } else {
                     // Komisyonu ayar_gecmisi tablosundan al
                     $pzId = (int)$siparis->PazaryeriID;
                     $komisyonOrani = 0;
                     if ($pzId === 1) $komisyonOrani = (float)$ayar->komisyon_site;
                     elseif ($pzId === 2) $komisyonOrani = (float)$ayar->komisyon_trendyol;
                     elseif ($pzId === 3) $komisyonOrani = (float)$ayar->komisyon_etsy;
                     elseif ($pzId === 4) $komisyonOrani = (float)$ayar->komisyon_hipicon;
                     else $komisyonOrani = (float)($u->PazarKomisyon ?? 0) ?: (float)$siparis->KomisyonOrani;
                }

                $komisyonTutari = $satirToplamTL * $komisyonOrani;
                $toplamKomisyon += $komisyonTutari;

                $netTL = $satirToplamTL - $komisyonTutari;
                
                $goldPurity = isset($siparis->ayar_orani) ? (float)$siparis->ayar_orani : 0.585;
                $altinMaliyetiBuUrun = ($gram * $ayar->altin_fiyat * $goldPurity) * $miktar;
                $toplamAltinMaliyeti += $altinMaliyetiBuUrun;
                
                // Vergi Hesabı
                $vergiMatrahi = max(0, $satirToplamTL - $altinMaliyetiBuUrun); 
                $vergiTutari = $vergiMatrahi - ($vergiMatrahi / 1.2);
                $toplamVergi += $vergiTutari;

                // FIX: Toplam Satış BRÜT olmalı.
                $toplamNetSatis += $satirToplamTL; 
                
                // Kutu hariç, diğerleri per item
                $toplamEkstraGider += ($iscilikBirim + $reklamBirim) * $miktar;
                
                $detayliGiderler['iscilik'] += ($iscilikBirim * $miktar);
                $detayliGiderler['reklam']  += ($reklamBirim * $miktar);
            }
        }

        // 6. SİPARİŞ GENEL GİDERLERİ & KARGO & KUTU
        if ($urunler->count() > 0) {
            if ($isEtsy) {
                // Etsy için yurtdışı kargo - sipariş başına bir kez
                $kargoYurtdisiUSD = $ayar->kargo_yurtdisi / $dolarKuru;
                $kutuUSD          = $kutuBirim / $dolarKuru; // Kutu da sipariş başına 1 kez

                $toplamEkstraGider += ($kargoYurtdisiUSD + $kutuUSD);
                
                $detayliGiderler['kargo_yurtdisi'] = $kargoYurtdisiUSD; 
                $detayliGiderler['kutu']           = $kutuUSD;
                
                // Ödeme İndirimi Düş (TL olarak DB'de kayıtlı, USD'ye çevirip düş)
                $odemeIndirimiTL = (float)($siparis->odemeIndirimi ?? 0);
                $odemeIndirimiUSD = 0;
                
                if ($odemeIndirimiTL > 0) {
                    $odemeIndirimiUSD = $odemeIndirimiTL / $dolarKuru;
                }

                if ($odemeIndirimiUSD > 0) {
                    $toplamNetSatis -= $odemeIndirimiUSD;
                    
                    // Komisyon da düşülen tutardan hesaplanmalı (Less Commission)
                    // Komisyon Oranı bir üründen alınabilir veya genel siparişten
                    $genelKomisyonOrani = (float)$siparis->KomisyonOrani;
                    $savedCommission = ($odemeIndirimiUSD * $genelKomisyonOrani);
                    
                    $toplamKomisyon -= $savedCommission;
                    // !!! EKLENDİ: Azalan komisyon kadar cebimize para kalır, satışı artır !!!
                    $toplamNetSatis += $savedCommission; 
                }

                if ((int)$siparis->isUSA === 1) {
                    // FIX: Vergi Hesabı (User Request: Satış - İndirim - Komisyon)
                    // Hem Manuel hem Otomatik için aynı mantığı uyguluyoruz ki tutarsızlık olmasın.
                    
                    $vergiMatrahi = $toplamBrutSatis - $odemeIndirimiUSD - $toplamKomisyon;
                    
                    if ($vergiMatrahi < 0) $vergiMatrahi = 0;
                    
                    $usaVergi = $vergiMatrahi * (float)$ayar->etsy_usa_tax_rate;
                    $toplamVergi += $usaVergi; 
                    $toplamNetSatis -= $usaVergi;
                    $toplamNetSatis -= $etsyShipCost; 
                }
            } else {
                // TL: Kargo ve Kutu (Sipariş Başına 1 Kez)
                $toplamEkstraGider += ($kargoBirim + $kutuBirim);
                
                $detayliGiderler['kargo'] += $kargoBirim;
                $detayliGiderler['kutu']  += $kutuBirim;

                // Ödeme İndirimi Düş (TL)
                $odemeIndirimiTL = (float)($siparis->odemeIndirimi ?? 0);
                if ($odemeIndirimiTL > 0) {
                     $toplamNetSatis -= $odemeIndirimiTL;
                     
                     // Komisyon İndirimi
                     if((int)$siparis->OdemeTipi !== 1) { // Havale değilse
                         $genelKomisyonOrani = (float)$siparis->KomisyonOrani;
                         $savedCommission = ($odemeIndirimiTL * $genelKomisyonOrani);
                         
                         $toplamKomisyon -= $savedCommission;
                         $toplamNetSatis += $savedCommission;
                     }
                }
            }
        }

        // 7. Ekstralar
        $ekstralar = DB::connection('sqlsrv')->table('SiparisEkstralar')
            ->where('SiparisID', $siparisId)
            ->get();
        // Şimdilik mantığı bozmadan sona ekliyoruz.
        $ekstraNet = $ekstralar->sum(fn($e) => $e->Tur == 'GELIR' ? $e->Tutar : -$e->Tutar);


        // 8. FINAL KAR & DB KAYDI
        if ($isEtsy) {
            // USD -> TL Çevirimi
            $karUSD = $toplamNetSatis - ($toplamAltinMaliyeti + $toplamEkstraGider);
            // Etsy'de 'NetSatis' zaten komisyon ve vergi düşülmüş hali, o yüzden tekrar düşmüyoruz.
            
            $gercekNetKar = round(($karUSD * $dolarKuru) + $ekstraNet, 2);
            
            // Gram eksikse kârı 0 olarak kaydet
            if ($gramEksik) {
                $karUSD = 0;
                $gercekNetKar = 0;
            }
            
            // DB Kayıt
            DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
                ['SiparisID' => $siparisId, 'UrunKodu' => 'TOPLAM'],
                ['GercekKar' => $gercekNetKar, 'Vergi' => 0, 'HesapTarihi' => now()]
            );

            return [
                'etsy'       => true,
                'kar'        => $gercekNetKar,
                'karUSD'     => $karUSD,
                'netSalesUSD'=> $toplamNetSatis, // Vergi/Kargo düşülmüş net
                'altinMaliyetUSD' => $toplamAltinMaliyeti, // Toplam altın maliyeti (USD)
                'giderUSD'   => $toplamEkstraGider,
                'gram'       => $toplamGram,
                // UI uyumluluğu için TL karşılıkları
                'altin'      => $toplamAltinMaliyeti * $dolarKuru,
                'gider'      => $toplamEkstraGider * $dolarKuru,
                
                // Komisyon ve Vergi
                'komisyonUSD'=> $toplamKomisyon, 
                'vergiUSD'   => $toplamVergi,
                'komisyon'   => $toplamKomisyon * $dolarKuru,
                'vergi'      => $toplamVergi * $dolarKuru,
                
                // DETAYLI GİDERLER
                'detayliGiderler' => $detayliGiderler,
                'odemeIndirimiUSD' => isset($odemeIndirimiUSD) ? $odemeIndirimiUSD : 0,
                'odemeIndirimiTL'  => isset($odemeIndirimiTL) ? $odemeIndirimiTL : 0,
                
                // KUR BİLGİLERİ (Sistemden çekilen - birim fiyatlar)
                'dolarKuru'  => $dolarKuru,
                'altinBirimUSD' => $altinUSD, // Birim fiyat ($/gr)
                'altinTL'    => $ayar->altin_fiyat,
                'etsyShipCost' => isset($etsyShipCost) && (int)$siparis->isUSA === 1 ? $etsyShipCost : 0, // UI için gönder
                'hesaplanabilir' => !$gramEksik
            ];

        } else {
             // TL BAZLI ve YENİ HAVALE MANTIĞI
             $isHavale = (isset($siparis->OdemeTipi) && (int)$siparis->OdemeTipi === 1);
             
             // 1. Önce İndirimsiz (Liste) Fiyatını Bul
             // USER FIX: Tutar zaten liste fiyatıdır (0.95'e bölmeye gerek yok)
             $toplamIndirimsizSatis = $toplamNetSatis;

             // 2. Hediye Çeki Düşümü
             $hediyeCeki = (float)($siparis->HediyeCekiTutari ?? 0);
             $matrah = $toplamIndirimsizSatis - $hediyeCeki;
             
             // Matrah negatif olamaz
             if($matrah < 0) $matrah = 0;
             
             // 3. Havale İndirimi Hesabı (%5) - (Gift düştükten sonra)
             $toplamHavaleIndirimi = 0;
             if ($isHavale) {
                 $toplamHavaleIndirimi = $matrah * 0.05; // %5 indirim
             }

             // 4. Müşterinin Ödediği Son Rakam (Ciro)
             // Yeni Kural: (List - Gift) - Havaleİndirimi
             $sonOdenen = $matrah - $toplamHavaleIndirimi;
             
             $netCiro = $sonOdenen;


             // VERGİ HESABI (YENİ CİRO ÜZERİNDEN)
             $vergiMatrahi = $netCiro - $toplamAltinMaliyeti;
             $vergiMatrahi = max(0, $vergiMatrahi); // Negatif vergi olmaz
             
             // İç yüzde yöntemiyle KDV (KDV dahil tutardan KDV ayırma)
             // Matrah / 1.2 = KDV hariç tutar
             // KDV = Matrah - (Matrah/1.2)
             $toplamVergi = $vergiMatrahi - ($vergiMatrahi / 1.2);

             $toplamMaliyet = $toplamAltinMaliyeti + $toplamEkstraGider + $toplamKomisyon + $toplamVergi;
             $gercekNetKar = round($netCiro - $toplamMaliyet + $ekstraNet, 2);

             // Gram eksikse kârı 0 olarak kaydet
             if ($gramEksik) {
                 $gercekNetKar = 0;
             }

             DB::connection('sqlsrv')->table('SiparisKarlar')->updateOrInsert(
                 ['SiparisID' => $siparisId, 'UrunKodu' => 'TOPLAM'],
                 ['GercekKar' => $gercekNetKar, 'Vergi' => $toplamVergi, 'HesapTarihi' => now()]
             );

            return [
                 'etsy'       => false,
                 'toplamCiro' => $netCiro + $hediyeCeki, // Raporlar için (Gift dahil ciro görünebilir, ama kar hesabında Gift cepten çıkar)
                 'netCiro'    => $netCiro,
                 'kar'        => $gercekNetKar,
                 'gram'       => $toplamGram,
                 'altin'      => $toplamAltinMaliyeti,
                 'gider'      => $toplamEkstraGider,
                 'komisyon'   => $toplamKomisyon,
                 'vergi'      => $toplamVergi,
                 'havale_indirimi' => $toplamHavaleIndirimi,
                 'indirimsizToplam' => $toplamIndirimsizSatis, // UI için
                 'detayliGiderler' => $detayliGiderler,

                 // KUR BİLGİLERİ
                 'dolarKuru'  => $dolarKuru,
                 'altinBirimUSD' => $altinUSD, // Birim fiyat ($/gr)
                 'altinTL'    => $ayar->altin_fiyat,
                 'hesaplanabilir' => !$gramEksik
             ];
        }
    }

    /**
     * Stok kodunun hediye olup olmadığını kontrol eder.
     * Ayarlar tablosundaki hediye_kodlari alanına bakar.
     */
    private function isHediye($stokKodu, $ayar = null)
    {
        if (!$stokKodu) return false;
        
        if ($this->cachedHediyeKodlari === null) {
            // En güncel ayarı çek (Tarihten bağımsız olarak en son kaydedilen)
            $sonAyar = DB::table('ayar_gecmisi')
                ->orderBy('tarih', 'desc')
                ->first();
                
            $hediyeKodlariStr = $sonAyar->hediye_kodlari ?? 'crmhediye';
            $this->cachedHediyeKodlari = array_map('trim', explode(',', $hediyeKodlariStr));
        }
        
        foreach ($this->cachedHediyeKodlari as $kod) {
            if (strcasecmp($stokKodu, $kod) === 0) {
                return true;
            }
        }
        
        return false;
    }
}