<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiyatController extends Controller
{
    /**
     * Her zaman en güncel (bugün veya en yakın geçmiş tarihli) ayarı çeker.
     */
    private function getGuncelAyar()
    {
        $today = now()->toDateString();
        $ayar = DB::table('ayar_gecmisi')
            ->where('tarih', '<=', $today)
            ->orderBy('tarih', 'desc')
            ->first();

        if (!$ayar) {
            // Ayar bulunamazsa, kullanıcıya rehberlik etmek için bir istisna fırlat.
            throw new \Exception('Sistemde ayar kaydı bulunamadı. Lütfen Ayarlar sayfasından en az bir ayar girin.');
        }
        return $ayar;
    }

    public function index()
    {
        $kategoriler = DB::connection('mysql')
            ->table('Kategoriler')
            ->select('Id', 'KategoriAdi', 'KarOrani', 'KategoriKodu')
            ->orderBy('KategoriAdi')
            ->get();

        $badges = [
            1 => ['bg' => '#212529', 'text' => 'dianorapiercing.com', 'icon' => 'fa-globe', 'image' => asset('images/dianora_icon.png'), 'image_dark' => asset('images/dianora_icon_beyaz.png')],
            2 => ['bg' => '#f27a1a', 'text' => 'Trendyol', 'icon' => '', 'image' => asset('images/trendyol_icon.png')],
            3 => ['bg' => '#F1641E', 'text' => 'Etsy', 'icon' => 'fa-etsy', 'image' => ''], // Resim yok, icon var
            4 => ['bg' => '#146eb4', 'text' => 'Hipicon', 'icon' => '', 'image' => asset('images/hipicon_icon.png')],
            5 => ['bg' => '#FF6000', 'text' => 'Hepsiburada', 'icon' => 'fa-bag-shopping', 'image' => ''],
            6 => ['bg' => '#5f259f', 'text' => 'N11', 'icon' => 'fa-n', 'image' => ''],
            99 => ['bg' => '#198754', 'text' => 'Elden / Havale', 'icon' => 'fa-hand-holding-dollar', 'image' => ''],
        ];

        // Sihirbaz özel ayarlarını getir
        $wizardAltin = DB::connection('mysql')->table('sabit_ayarlar')->where('Anahtar', 'wizard_altin_fiyat')->value('Deger');
        $wizardDolar = DB::connection('mysql')->table('sabit_ayarlar')->where('Anahtar', 'wizard_dolar_kuru')->value('Deger');

        // Eğer yoksa güncel genel ayarları varsayılan yap
        if (!$wizardAltin || !$wizardDolar) {
            $ayar = $this->getGuncelAyar();
            if (!$wizardAltin) $wizardAltin = $ayar->altin_fiyat;
            if (!$wizardDolar) $wizardDolar = $ayar->dolar_kuru;
        }

        return view('fiyat.index', compact('kategoriler', 'badges', 'wizardAltin', 'wizardDolar'));
    }

    public function urunGetir(Request $request)
    {
        $kod = trim($request->urun_kodu);
        if(!$kod) return response()->json(['status' => false]);

        $urun = DB::connection('mysql')->table('Urunler')
            ->where('UrunKodu', $kod)
            ->orWhere('UrunKodu', $kod . '-yeni')
            ->select('Gram', 'KategoriId', 'UrunKodu')
            ->first();

        return response()->json(['status' => (bool)$urun, 'urun' => $urun]);
    }

    public function hesapla(Request $request)
    {
        try {
            $ayar = $this->getGuncelAyar(); // YENİ: Her zaman güncel ayarı kullan

            $gram = (float) $request->gram;
            $kategoriId = $request->kategori_id;
            
            // --- MANUEL KAR & İNDİRİM ---
            // Eğer inputtan manuel kar oranı geldiyse onu, yoksa kategorinin oranını al
            $manuelKar = $request->input('manuel_kar_orani');
            if ($manuelKar !== null && is_numeric($manuelKar)) {
                $karOrani = (float)$manuelKar;
            } else {
                // Kategori seçili değilse ve manuel girilmediyse 0
                if ($kategoriId) {
                    $kat = DB::connection('mysql')->table('Kategoriler')->where('Id', $kategoriId)->first();
                    if($kat) $karOrani = $kat->KarOrani ?? 0;
                }
            }

            $indirimOrani = (float)($request->input('indirim_orani', 0));
            if($indirimOrani < 0) $indirimOrani = 0;
            if($indirimOrani > 100) $indirimOrani = 100;

            $pazaryerleri = DB::connection('mysql')->table('Pazaryerleri')->get();

            // --- MALİYETLER ---
            $altinFiyat = (float)$request->input('altin_fiyat');
            $dolarKuru = (float)$request->input('dolar_kuru');

            // Gelen değerleri kaydet (Kalıcı olması için)
            if ($altinFiyat > 0) {
                DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                    ['Anahtar' => 'wizard_altin_fiyat'],
                    ['Deger' => $altinFiyat, 'Aciklama' => 'Wizard Last Gold Price', 'updated_at' => now()]
                );
            }
            if ($dolarKuru > 0) {
                DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                    ['Anahtar' => 'wizard_dolar_kuru'],
                    ['Deger' => $dolarKuru, 'Aciklama' => 'Wizard Last Dollar Rate', 'updated_at' => now()]
                );
            }

            // Eğer request'ten gelmediyse (veya hatalıysa) genel ayara düş
            if ($altinFiyat <= 0) $altinFiyat = (float)$ayar->altin_fiyat;
            if ($dolarKuru <= 0)  $dolarKuru = (float)$ayar->dolar_kuru;
            if ($dolarKuru <= 0)  $dolarKuru = 1; 
            
            // --- MANUEL AYAR (MİLYEM) ---
            $inputAyar = $request->input('ayar');
            if ($inputAyar && is_numeric($inputAyar)) {
                $hamAyar = (float)$inputAyar;
                $milyem = ($hamAyar > 10) ? ($hamAyar / 1000) : $hamAyar;
            } else {
                // Varsayılan (Veritabanı)
                $milyem = ($ayar->ayar > 10) ? ($ayar->ayar / 1000) : $ayar->ayar; 
            }

            // TL Maliyeti (Vergisiz)
            $altinMaliyetiTL = round($gram * $altinFiyat * $milyem, 2);
            $giderTL = $ayar->iscilik + $ayar->kargo + $ayar->kutu + $ayar->reklam;
            $toplamMaliyetTL = $altinMaliyetiTL + $giderTL;
            
            // USD Maliyeti (Yurtdışı Kargo ile)
            $kargoYurtdisiTL = $ayar->kargo_yurtdisi ?? 0; 
            $giderYurtdisiTL = $ayar->iscilik + $kargoYurtdisiTL + $ayar->kutu + $ayar->reklam;

            // USD Altın Maliyeti (Altın TL / Dolar Kuru)
            $altinUSD = $altinFiyat / $dolarKuru;
            $altinMaliyetiUSD = round($gram * $altinUSD * $milyem, 2);
            $giderUSD = round($giderYurtdisiTL / $dolarKuru, 2);
            $toplamMaliyetUSD = $altinMaliyetiUSD + $giderUSD;

            // KDV
            $hamKdv = (float)($ayar->kdv ?? 0);
            $kdvOrani = ($hamKdv >= 1) ? ($hamKdv / 100) : $hamKdv;

            $sonuclar = [];

            foreach ($pazaryerleri as $pazar) {
                $pazarId = $pazar->id ?? $pazar->Id ?? 0;
                $pazarAd = $pazar->Ad ?? $pazar->ad ?? '';
                $hamKomisyon = (float)($pazar->KomisyonOrani ?? 0);
                $komisyonOrani = ($hamKomisyon >= 1) ? ($hamKomisyon / 100) : $hamKomisyon;
                if($komisyonOrani >= 0.99) $komisyonOrani = 0.99;

                $isEtsy = strtolower($pazarAd) === 'etsy';

                if ($isEtsy) {
                    // --- ETSY (USD) ---
                    $karliFiyatUSD = $toplamMaliyetUSD * (1 + ($karOrani / 100));
                    
                    // 1. ETSY USA
                    $shipEntegra = (float)$ayar->etsy_ship_cost;
                    $usaTaxRate = (float)$ayar->etsy_usa_tax_rate;
                    if($usaTaxRate >= 1) $usaTaxRate /= 100;

                    // Gerçek Etsy mantığı:
                    // Vergi matrahı = Satış Fiyatı - Komisyon (komisyon düşüldükten sonra)
                    // Fiyat Formülü (iki adım — KarHesapService ile uyumlu):
                    //   Step 1: vergiDahil = (hedefKar + kargo) / (1 - vergiOrani)
                    //   Step 2: satisFiyati = vergiDahil / (1 - komisyon)
                    // Böylece: netKar = fiyat × (1-k) × (1-t) - kargo - maliyet = hedefKar ✅
                    $vergiDahilFiyatUSA = ($karliFiyatUSD + $shipEntegra) / (1 - $usaTaxRate);
                    $usaSatisFiyatiHam  = $vergiDahilFiyatUSA / (1 - $komisyonOrani);

                    // İndirim Uygula
                    $usaSatisFiyati = $usaSatisFiyatiHam * (1 - ($indirimOrani / 100));

                    // Komisyon = tam fiyat üzerinden
                    $kesintiKomisyonUSA = $usaSatisFiyati * $komisyonOrani;
                    // Vergi matrahı = fiyat - komisyon (KarHesapService ile aynı mantık)
                    $usaVergiMatrahi = $usaSatisFiyati - $kesintiKomisyonUSA;
                    $hesaplananVergi  = $usaVergiMatrahi * $usaTaxRate;

                    $netKarUSA = $usaSatisFiyati - $kesintiKomisyonUSA - $hesaplananVergi - $shipEntegra - $toplamMaliyetUSD;

                    // --- ESKİ NET KAR (İndirimsiz) ---
                    $kesintiKomisyonUSAHam = $usaSatisFiyatiHam * $komisyonOrani;
                    $usaVergiMatrahiHam    = $usaSatisFiyatiHam - $kesintiKomisyonUSAHam;
                    $hesaplananVergiHam    = $usaVergiMatrahiHam * $usaTaxRate;
                    $netKarUSAHam = $usaSatisFiyatiHam - $kesintiKomisyonUSAHam - $hesaplananVergiHam - $shipEntegra - $toplamMaliyetUSD;

                    $sonuclar[] = [
                        'sort_order' => 2, // USD Pazaryerleri 2. Sırada
                        'id' => $pazarId, 'ad' => 'Etsy (USA)', 'tur' => 'USD',
                        'satis_fiyati'  => number_format($usaSatisFiyati, 2),
                        'old_price'     => number_format($usaSatisFiyatiHam, 2), // İndirimsiz Fiyat
                        'buffer_fiyati' => number_format($usaSatisFiyati * 1.10, 2),
                        'net_kar'       => number_format($netKarUSA, 2) . ' $',
                        'old_net_kar'   => number_format($netKarUSAHam, 2) . ' $', // İndirimsiz Kar
                        'kur_karsiligi' => number_format($usaSatisFiyati * $dolarKuru, 2) . ' ₺',
                        'detay_altin'     => number_format($altinMaliyetiUSD, 2),
                        'detay_gider'     => number_format($giderUSD, 2),
                        'detay_komisyon'  => number_format($kesintiKomisyonUSA, 2),
                        'detay_vergi'     => number_format($hesaplananVergi, 2),
                        'detay_kargo'     => number_format($shipEntegra, 2),
                        'detay_maliyet'   => number_format($toplamMaliyetUSD, 2),
                        'maliyet_tl'      => number_format($toplamMaliyetUSD * $dolarKuru, 2),
                        'net_kar_tl'      => number_format($netKarUSA * $dolarKuru, 2),
                    ];

                    // 2. ETSY GLOBAL
                    $euSatisFiyatiHam = $karliFiyatUSD / (1 - $komisyonOrani);
                    
                    // İndirim
                    $euSatisFiyati = $euSatisFiyatiHam * (1 - ($indirimOrani / 100));

                    $kesintiKomisyonGlobal = $euSatisFiyati * $komisyonOrani;
                    $netKarGlobal = $euSatisFiyati - $kesintiKomisyonGlobal - $toplamMaliyetUSD;

                    // --- ESKİ NET KAR ---
                    $kesintiKomisyonGlobalHam = $euSatisFiyatiHam * $komisyonOrani;
                    $netKarGlobalHam = $euSatisFiyatiHam - $kesintiKomisyonGlobalHam - $toplamMaliyetUSD;

                    $sonuclar[] = [
                        'sort_order' => 2,
                        'id' => $pazarId, 'ad' => 'Etsy (Diğer)', 'tur' => 'USD',
                        'satis_fiyati'  => number_format($euSatisFiyati, 2),
                        'old_price'     => number_format($euSatisFiyatiHam, 2), // İndirimsiz Fiyat
                        'buffer_fiyati' => number_format($euSatisFiyati * 1.10, 2),
                        'net_kar'       => number_format($netKarGlobal, 2) . ' $',
                        'old_net_kar'   => number_format($netKarGlobalHam, 2) . ' $',
                        'kur_karsiligi' => number_format($euSatisFiyati * $dolarKuru, 2) . ' ₺',
                        'detay_altin'     => number_format($altinMaliyetiUSD, 2),
                        'detay_gider'     => number_format($giderUSD, 2),
                        'detay_komisyon'  => number_format($kesintiKomisyonGlobal, 2),
                        'detay_vergi'     => '0.00',
                        'detay_kargo'     => '0.00', 
                        'detay_maliyet'   => number_format($toplamMaliyetUSD, 2),
                        'maliyet_tl'      => number_format($toplamMaliyetUSD * $dolarKuru, 2),
                        'net_kar_tl'      => number_format($netKarGlobal * $dolarKuru, 2),
                    ];

                } else {
                    // TL PAZARYERLERİ
                    $hedefNetPara = $toplamMaliyetTL * (1 + ($karOrani / 100));
                    $kdvKatsayisi = ($kdvOrani > 0) ? ($kdvOrani / (1 + $kdvOrani)) : 0;
                    
                    $bolen = 1 - $komisyonOrani - $kdvKatsayisi;
                    if ($bolen <= 0.01) $bolen = 0.01; 

                    $pay = $hedefNetPara - ($altinMaliyetiTL * $kdvKatsayisi);
                    $satisFiyatiTLHam = ($pay / $bolen) * 1.02; // %2 tolerans vb.

                    // İndirim
                    $satisFiyatiTL = $satisFiyatiTLHam * (1 - ($indirimOrani / 100));
                    
                    // Geriye Dönük Net Kar Hesabı
                    $kesilenKomisyon = $satisFiyatiTL * $komisyonOrani;
                    
                    // Vergi Matrahı Hesabı: Satis - Maliyet (Basit usul)
                    // Veya (Satis / 1.KDV) = Net Satis, Net Satis * KDV = KDV Tutari
                    // Burada kodda $vergiMatrahiBrut = $satisFiyatiTL - $altinMaliyetiTL denilmiş.
                    // Genelde kuyumda sadece iscilik kdv'si var ise farkli olur ama mevcut mantığı koruyoruz.
                    
                    $vergiMatrahiBrut = max(0, $satisFiyatiTL - $altinMaliyetiTL);
                    $hesaplananKdv = $vergiMatrahiBrut * $kdvKatsayisi;
                    
                    $netEleGecen = $satisFiyatiTL - $kesilenKomisyon - $hesaplananKdv;
                    $netKarTL = $netEleGecen - $toplamMaliyetTL;

                    // --- ESKİ NET KAR (TL) ---
                    $kesilenKomisyonHam = $satisFiyatiTLHam * $komisyonOrani;
                    $vergiMatrahiBrutHam = max(0, $satisFiyatiTLHam - $altinMaliyetiTL);
                    $hesaplananKdvHam = $vergiMatrahiBrutHam * $kdvKatsayisi;
                    $netEleGecenHam = $satisFiyatiTLHam - $kesilenKomisyonHam - $hesaplananKdvHam;
                    $netKarTLHam = $netEleGecenHam - $toplamMaliyetTL;

                    $sonuclar[] = [
                        'sort_order' => 1, // TL Pazaryerleri 1. Sırada
                        'id' => $pazarId, 'ad' => $pazarAd, 'tur' => 'TL',
                        'satis_fiyati'  => number_format($satisFiyatiTL, 2),
                        'old_price'     => number_format($satisFiyatiTLHam, 2), // İndirimsiz Fiyat
                        'buffer_fiyati' => number_format($satisFiyatiTL * 1.10, 2),
                        'net_kar'       => number_format($netKarTL, 2) . ' ₺',
                        'old_net_kar'   => number_format($netKarTLHam, 2) . ' ₺',
                        'kur_karsiligi' => '-',
                        'detay_altin'     => number_format($altinMaliyetiTL, 2),
                        'detay_gider'     => number_format($giderTL, 2),
                        'detay_komisyon'  => number_format($kesilenKomisyon, 2),
                        'detay_vergi'     => number_format($hesaplananKdv, 2),
                        'detay_kargo'     => '0.00', 
                        'detay_maliyet'   => number_format($toplamMaliyetTL, 2),
                    ];
                }
            }

            // 99. ELDEN SATIŞ
            $eldenHedefNet = $toplamMaliyetTL * (1 + ($karOrani / 100));
            $eldenNetIscilik = $eldenHedefNet - $altinMaliyetiTL;
            $eldenKdvTutari = ($eldenNetIscilik > 0) ? ($eldenNetIscilik * $kdvOrani) : 0;
            $eldenSatisFiyatiHam = $eldenHedefNet + $eldenKdvTutari;
            
            // İndirim
            $eldenSatisFiyati = $eldenSatisFiyatiHam * (1 - ($indirimOrani / 100));

            // Elden Satış için Net Kar (KDV Hariç net ele geçen - Maliyet)
            // KDV'yi geri düşmek lazım.
            // Satis = Net + (Net-Altin)*KDV
            // Bu biraz karmaşık, basitçe: Elden satışta fatura kesiliyorsa KDV devlete gider.
            // Net Kasa = Satis - KDV - Maliyet
            
            // Yeni KDV Hesabı (indirgenmiş fiyattan)
            // Varsayım: İndirim KDV matrahını düşürür.
            
            // Vergi matrahı = Satis - Altin (kuyum hesaplama mantığı genelde budur, fason+kar kdv'ye tabidir)
            $eldenYeniMatrah = max(0, $eldenSatisFiyati - $altinMaliyetiTL);
            $eldenYeniKdv = $eldenYeniMatrah * $kdvKatsayisi; // kdvKatsayisi = 0.20/1.20 filan degil, direkt oran/1+oran
            
            // Düzeltme: kdvKatsayisi yukarıda ($kdvOrani / (1 + $kdvOrani)) olarak tanımlı.
            // Bu "İçindeki KDV" katsayısıdır. Yani Brüt Rakam * Katsayı = KDV Tutarı.
            
            $eldenNetKar = $eldenSatisFiyati - $eldenYeniKdv - $toplamMaliyetTL;

            // --- ESKİ NET KAR (Elden) ---
            $eldenEskiMatrah = max(0, $eldenSatisFiyatiHam - $altinMaliyetiTL);
            $eldenEskiKdv = $eldenEskiMatrah * $kdvKatsayisi;
            $eldenNetKarHam = $eldenSatisFiyatiHam - $eldenEskiKdv - $toplamMaliyetTL;


            $sonuclar[] = [
                'sort_order' => 3, // Elden Satış En Sonda
                'id' => 99, 'ad' => 'Elden / Havale', 'tur' => 'TL',
                'satis_fiyati'  => number_format($eldenSatisFiyati, 2),
                'old_price'     => number_format($eldenSatisFiyatiHam, 2), // İndirimsiz Fiyat
                'ekstra_fiyat'  => number_format($eldenHedefNet, 2) . ' ₺',
                'buffer_fiyati' => number_format($eldenSatisFiyati * 1.05, 2),
                'net_kar'       => number_format($eldenNetKar, 2) . ' ₺',
                'old_net_kar'   => number_format($eldenNetKarHam, 2) . ' ₺',
                'kur_karsiligi' => '-',
                'detay_altin'     => number_format($altinMaliyetiTL, 2),
                'detay_gider'     => number_format($giderTL, 2),
                'detay_komisyon'  => '0.00',
                'detay_vergi'     => number_format($eldenYeniKdv, 2),
                'detay_kargo'     => '0.00',
                'detay_maliyet'   => number_format($toplamMaliyetTL, 2),
            ];

            // SIRALAMA İŞLEMİ: TL(1) -> USD(2) -> ELDEN(3)
            // Bu sayede Etsy kartları tabloda yan yana gelecektir.
            $sonuclar = collect($sonuclar)->sortBy('sort_order')->values()->all();

            return response()->json([
                'status' => true,
                'sonuclar' => $sonuclar,
                'meta' => [
                    'altin_tl' => number_format($ayar->altin_fiyat, 2),
                    'dolar'    => number_format($ayar->dolar_kuru, 4),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Hata: ' . $e->getMessage()], 404);
        }
    }

    public function topluHesapla(Request $request)
    {
        try {
            $kodlarText = $request->input('kodlar');
            if (!$kodlarText) {
                return response()->json(['status' => false, 'message' => 'Lütfen ürün kodu giriniz.']);
            }

            // Kodları temizle ve diziye çevir (satır satır)
            $kodlar = array_filter(array_map('trim', explode("\n", $kodlarText)));
            
            if (empty($kodlar)) {
                return response()->json(['status' => false, 'message' => 'Geçerli ürün kodu bulunamadı.']);
            }

            $data = $this->_calculateBulkData($kodlar);
            $sonuclar = $data['sonuclar'];
            $ayar = $data['ayar'];

            return response()->json([
                'status' => true,
                'sonuclar' => $sonuclar,
                'meta' => [
                    'altin_tl' => number_format($ayar->altin_fiyat, 2),
                    'dolar'    => number_format($ayar->dolar_kuru, 4),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Hata: ' . $e->getMessage()], 404);
        }
    }

    private function _calculateBulkData($kodlar)
    {
        $request = request();
        $ayar = $this->getGuncelAyar();

        $altinFiyat = (float)$request->input('altin_fiyat');
        $dolarKuru = (float)$request->input('dolar_kuru');

        // Gelen değerleri kaydet (Kalıcı olması için)
        if ($altinFiyat > 0) {
            DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                ['Anahtar' => 'wizard_altin_fiyat'],
                ['Deger' => $altinFiyat, 'Aciklama' => 'Wizard Last Gold Price', 'updated_at' => now()]
            );
        }
        if ($dolarKuru > 0) {
            DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                ['Anahtar' => 'wizard_dolar_kuru'],
                ['Deger' => $dolarKuru, 'Aciklama' => 'Wizard Last Dollar Rate', 'updated_at' => now()]
            );
        }

        if ($altinFiyat <= 0) $altinFiyat = (float)$ayar->altin_fiyat;
        if ($dolarKuru <= 0)  $dolarKuru = (float)$ayar->dolar_kuru;
        if ($dolarKuru <= 0)  $dolarKuru = 1; 

        $milyem = ($ayar->ayar > 10) ? ($ayar->ayar / 1000) : $ayar->ayar;

        // İndirim Oranı
        $indirimOrani = (float)($request->input('indirim_orani', 0));
        if($indirimOrani < 0) $indirimOrani = 0;
        if($indirimOrani > 100) $indirimOrani = 100;

        // Site (dianorapiercing.com) parametreleri - ID: 1
        $sitePazar = DB::connection('mysql')->table('Pazaryerleri')->where('id', 1)->first();
        $komisyonOrani = 0;
        if ($sitePazar) {
            $hamKomisyon = (float)($sitePazar->KomisyonOrani ?? 0);
            $komisyonOrani = ($hamKomisyon >= 1) ? ($hamKomisyon / 100) : $hamKomisyon;
        }

        $sonuclar = [];

        foreach ($kodlar as $kod) {
            $aranacakKodlar = [$kod]; // Orijinal hali

            // Eğer "yeni" varsa, temiz halini de ekle
            if (stripos($kod, 'yeni') !== false) {
                $temizKod = preg_replace('/[- ]?yeni/i', '', $kod);
                $aranacakKodlar[] = $temizKod;
            } else {
                // Yoksa -yeni halini ekle
                $aranacakKodlar[] = $kod . '-yeni';
                $aranacakKodlar[] = $kod . '-YENİ';
            }

            $urun = DB::connection('mysql')->table('Urunler')
                ->whereIn('UrunKodu', $aranacakKodlar)
                ->select('Gram', 'KategoriId', 'UrunKodu')
                ->first();

            if (!$urun) {
                $sonuclar[] = [
                    'kod' => $kod,
                    'durum' => 'bulunamadi',
                    'mesaj' => 'Bulunamadı'
                ];
                continue;
            }

            $gram = (float)$urun->Gram;
            
            // Kar Oranı (Kategoriden)
            $karOrani = 0;
            $kategoriAdi = '-';
            if ($urun->KategoriId) {
                $kat = DB::connection('mysql')->table('Kategoriler')->where('Id', $urun->KategoriId)->first();
                if($kat) {
                    $karOrani = $kat->KarOrani ?? 0;
                    $kategoriAdi = $kat->KategoriAdi ?? '-';
                }
            }

            // Maliyet Hesapları (TL)
            $altinMaliyetiTL = round($gram * $altinFiyat * $milyem, 2);
            $giderTL = $ayar->iscilik + $ayar->kargo + $ayar->kutu + $ayar->reklam;
            $toplamMaliyetTL = $altinMaliyetiTL + $giderTL;

            // KDV
            $hamKdv = (float)($ayar->kdv ?? 0);
            $kdvOrani = ($hamKdv >= 1) ? ($hamKdv / 100) : $hamKdv;

            // Satış Fiyatı Hesabı (TL Pazaryeri Mantığı) - Düzeltme: Site için hesap
            // Site hesabında komisyon düşülüyor mu? Evet ID:1 için komisyon var.
            
            $hedefNetPara = $toplamMaliyetTL * (1 + ($karOrani / 100));
            $kdvKatsayisi = ($kdvOrani > 0) ? ($kdvOrani / (1 + $kdvOrani)) : 0;
            
            $bolen = 1 - $komisyonOrani - $kdvKatsayisi;
            if ($bolen <= 0.01) $bolen = 0.01; 

            $pay = $hedefNetPara - ($altinMaliyetiTL * $kdvKatsayisi);
            $satisFiyatiTL = ($pay / $bolen) * 1.02; // %2 tolerans

            // --- NET KAR HESAPLAMA ---
            $kesilenKomisyon = $satisFiyatiTL * $komisyonOrani;
            $vergiMatrahiBrut = max(0, $satisFiyatiTL - $altinMaliyetiTL);
            $hesaplananKdv = $vergiMatrahiBrut * $kdvKatsayisi;
            
            $netEleGecen = $satisFiyatiTL - $kesilenKomisyon - $hesaplananKdv;
            $netKarTL = $netEleGecen - $toplamMaliyetTL;

            // Yuvarlama: Sayıyı 10'a tamamla (örn: 6525.7 -> 6530)
            $yuvarlanmisFiyat = ceil($satisFiyatiTL / 10) * 10;

            // --- İNDİRİMLİ HESAPLAMA ---
            $indirimliSatisFiyatiTL = $satisFiyatiTL * (1 - ($indirimOrani / 100));
            $indirimliKesilenKomisyon = $indirimliSatisFiyatiTL * $komisyonOrani;
            $indirimliVergiMatrahiBrut = max(0, $indirimliSatisFiyatiTL - $altinMaliyetiTL);
            $indirimliHesaplananKdv = $indirimliVergiMatrahiBrut * $kdvKatsayisi;
            $indirimliNetEleGecen = $indirimliSatisFiyatiTL - $indirimliKesilenKomisyon - $indirimliHesaplananKdv;
            $indirimliNetKarTL = $indirimliNetEleGecen - $toplamMaliyetTL;

            $sonuclar[] = [
                'kod' => $urun->UrunKodu,
                'durum' => 'ok',
                'gram' => number_format($gram, 2, ',', '.'),
                'kategori_adi' => $kategoriAdi,
                'fiyat_raw' => $satisFiyatiTL,
                'fiyat' => number_format($satisFiyatiTL, 2, ',', '.') . ' ₺',
                'fiyat_yuvarla' => number_format($yuvarlanmisFiyat, 2, ',', '.') . ' ₺',
                'net_kar' => number_format($netKarTL, 2, ',', '.') . ' ₺',
                'indirimli_fiyat' => number_format($indirimliSatisFiyatiTL, 2, ',', '.') . ' ₺',
                'indirimli_net_kar' => number_format($indirimliNetKarTL, 2, ',', '.') . ' ₺',
                'indirim_orani' => $indirimOrani,
            ];
        }

        return ['sonuclar' => $sonuclar, 'ayar' => $ayar, 'altin_fiyat' => $altinFiyat, 'dolar_kuru' => $dolarKuru];
    }
    
    // Existing helper or end of class

    public function topluHesaplaExport(Request $request) 
    {
        $kodlarText = $request->input('kodlar');
        if (!$kodlarText) {
            return redirect()->back()->with('hata', 'Lütfen ürün kodu giriniz.');
        }

        $kodlar = array_filter(array_map('trim', explode("\n", $kodlarText)));
        if (empty($kodlar)) {
            return redirect()->back()->with('hata', 'Geçerli ürün kodu bulunamadı.');
        }

        $data = $this->_calculateBulkData($kodlar);
        $sonuclar = $data['sonuclar'];
        
        // Excel (HTML) Çıktı Oluştur
        $filename = 'Toplu_Fiyat_' . date('d-m-Y_H-i') . '.xls';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"$filename\""
        ];

        return response()->streamDownload(function() use ($sonuclar) {
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1">';
            echo '<thead><tr>
                    <th>Ürün Kodu</th>
                    <th>Kategori</th>
                    <th>Gram</th>
                    <th>İndirimsiz Fiyat</th>
                    <th>Yuvarlanmış Fiyat</th>
                    <th>İndirimsiz Kâr</th>
                    <th>Durum</th>
                  </tr></thead>';
            echo '<tbody>';
            
            foreach($sonuclar as $row) {
                echo '<tr>';
                echo '<td>' . $row['kod'] . '</td>';
                
                if($row['durum'] == 'ok') {
                    echo '<td>' . ($row['kategori_adi'] ?? '-') . '</td>';
                    echo '<td>' . ($row['gram'] ?? '-') . '</td>';
                    echo '<td>' . ($row['fiyat'] ?? '-') . '</td>';
                    echo '<td>' . ($row['fiyat_yuvarla'] ?? '-') . '</td>';
                    echo '<td>' . ($row['net_kar'] ?? '-') . '</td>';
                    echo '<td style="color:green">Başarılı</td>';
                } else {
                    echo '<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>';
                    echo '<td style="color:red">'. $row['mesaj'] .'</td>';
                }
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</body></html>';
        }, $filename, $headers);
    }

    /**
     * Kurları bağımsız olarak kaydet (Hesaplama yapmadan).
     */
    public function kurKaydet(Request $request)
    {
        try {
            $altinFiyat = (float)$request->input('altin_fiyat');
            $dolarKuru = (float)$request->input('dolar_kuru');

            if ($altinFiyat > 0) {
                DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                    ['Anahtar' => 'wizard_altin_fiyat'],
                    ['Deger' => $altinFiyat, 'Aciklama' => 'Wizard Last Gold Price', 'updated_at' => now()]
                );
            }
            if ($dolarKuru > 0) {
                DB::connection('mysql')->table('sabit_ayarlar')->updateOrInsert(
                    ['Anahtar' => 'wizard_dolar_kuru'],
                    ['Deger' => $dolarKuru, 'Aciklama' => 'Wizard Last Dollar Rate', 'updated_at' => now()]
                );
            }

            return response()->json(['status' => true, 'message' => 'Kurlar kaydedildi.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Hata: ' . $e->getMessage()], 500);
        }
    }
}
