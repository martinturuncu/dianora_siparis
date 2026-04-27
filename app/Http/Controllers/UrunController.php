<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
// use Yajra\DataTables\Facades\DataTables;

class UrunController extends Controller
{
    /**
     * Ürünlerin listelendiği ana sayfayı gösterir.
     */
    public function index()
    {
        $kategoriler = DB::connection('sqlsrv')->table('Kategoriler')->orderBy('KategoriAdi')->get();
        return view('urunler.yonetim.index', compact('kategoriler'));
    }

    /**
     * Ürün ekleme formunu gösterir.
     */
    public function create()
    {
        $kategoriler = DB::connection('sqlsrv')->table('Kategoriler')->orderBy('KategoriAdi')->get();
        return view('urunler.yonetim.create', compact('kategoriler'));
    }

    /**
     * Yeni ürünü veritabanına kaydeder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'UrunKodu'      => 'required|string|max:50|unique:sqlsrv.Urunler,UrunKodu',
            'KategoriId'    => 'nullable|exists:sqlsrv.Kategoriler,Id',
            'Gram'          => 'nullable|numeric|min:0',
        ]);

        DB::connection('sqlsrv')->table('Urunler')->insert([
            'UrunKodu'   => $validated['UrunKodu'],
            'KategoriId' => $validated['KategoriId'],
            'Gram'       => $validated['Gram'] ?? 0,
        ]);

        return redirect()->route('urunler.index')->with('success', 'Yeni ürün başarıyla eklendi!');
    }

    /**
     * Belirtilen ürünü düzenleme formunu gösterir.
     */
    public function edit($id)
    {
        $urun = DB::connection('sqlsrv')->table('Urunler')->where('Id', $id)->first();
        $kategoriler = DB::connection('sqlsrv')->table('Kategoriler')->orderBy('KategoriAdi')->get();

        if (!$urun) {
            abort(404, 'Ürün bulunamadı.');
        }

        return view('urunler.yonetim.edit', compact('urun', 'kategoriler'));
    }

    /**
     * Belirtilen ürünü veritabanında günceller.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'UrunKodu'      => 'required|string|max:50',
            'KategoriId'    => 'nullable|exists:sqlsrv.Kategoriler,Id',
            'Gram'          => 'nullable|numeric|min:0',
        ]);

        DB::connection('sqlsrv')->table('Urunler')->where('Id', $id)->update($validated);

        return redirect()->route('urunler.index')->with('success', 'Ürün başarıyla güncellendi!');
    }

    public function getData(Request $request)
    {
        try {
            $query = DB::connection('sqlsrv')->table('Urunler as u')
                ->leftJoin('Kategoriler as k', 'u.KategoriId', '=', 'k.Id')
                ->select('u.Id', 'u.UrunKodu', 'k.KategoriAdi', 'u.Gram');

            // 0. Kategori Filtresi
            if ($request->has('kategori') && !empty($request->input('kategori'))) {
                $query->where('k.Id', $request->input('kategori'));
            }

            // 1. Arama (Search)
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $searchValue = $request->input('search.value');
                $query->where(function($q) use ($searchValue) {
                    $q->where('u.UrunKodu', 'LIKE', "%{$searchValue}%")
                      ->orWhere('k.KategoriAdi', 'LIKE', "%{$searchValue}%");
                });
            }

            // Toplam Kayıt Sayısı (Filtreli)
            $filteredRecords = $query->count();
            
            // 2. Sıralama (Ordering) - Varsayılan: Alfabetik (UrunKodu ASC)
            if ($request->has('order')) {
                $orderColumnIndex = $request->input('order.0.column');
                $orderDirection = $request->input('order.0.dir', 'asc'); // asc/desc
                
                // Sütun İsimleri Haritası (Index -> DB Column)
                $columns = [
                    0 => 'u.Id',
                    1 => 'u.UrunKodu',
                    2 => 'k.KategoriAdi',
                    3 => 'u.Gram'
                ];

                if (isset($columns[$orderColumnIndex])) {
                    $query->orderBy($columns[$orderColumnIndex], $orderDirection);
                }
            } else {
                // Varsayılan: Alfabetik sıralama
                $query->orderBy('u.UrunKodu', 'asc');
            }

            // 3. Sayfalama (Pagination)
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            $data = $query->skip($start)->take($length)->get();
            
            // Toplam Kayıt (Filtresiz)
            $totalRecords = DB::connection('sqlsrv')->table('Urunler')->count();

            return response()->json([
                'draw' => (int)$request->input('draw', 1),
                'recordsTotal' => $totalRecords, 
                'recordsFiltered' => $filteredRecords,
                'data' => $data->map(function($item) {
                     return [
                        'Id' => $item->Id,
                        'UrunKodu' => $item->UrunKodu,
                        'KategoriAdi' => $item->KategoriAdi,
                        'Gram' => number_format($item->Gram ?? 0, 2, ',', '.') . ' gr',
                        'action' => '' // Frontend hallediyor
                     ];
                })
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Urunler Manual Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sipariş detay ve kâr analizi sayfası
     */
    public function detay($siparis_id, $stok_kodu)
    {
        $urun = DB::connection('sqlsrv')->table('SiparisUrunleri as u')
            ->leftJoin('Urunler as ur', 'u.StokKodu', '=', 'ur.UrunKodu')
            ->leftJoin('Kategoriler as k', 'ur.KategoriId', '=', 'k.Id')
            ->leftJoin('Siparisler as s', 's.SiparisID', '=', 'u.SiparisID')
            ->leftJoin('Pazaryerleri as p', 'p.id', '=', 's.PazaryeriID')
            ->select(
                'u.*',
                'ur.Gram', // Gram bilgisi Urunler tablosundan geliyor
                'k.KategoriAdi',
                's.PazaryeriID',
                's.Tarih',
                's.isUSA',
                'p.Ad as PazaryeriAdi',
                'p.KomisyonOrani as PazarKomisyon'
            )
            ->where('u.SiparisID', $siparis_id)
            ->where('u.StokKodu', $stok_kodu)
            ->first();

        if (!$urun) {
            return back()->with('hata', 'Ürün bulunamadı.');
        }

        // AKILLI GRAM EŞLEŞTİRME (DETAY SAYFASI İÇİN)
        if (empty($urun->Gram) || $urun->Gram <= 0) {
            // Strateji 1: Temizleme
            $temizKod = str_replace(['-YENI', '-YENİ', '-ESKI', '-ESKİ', ' '], '', $urun->StokKodu);
            
            // Strateji 2: Tire ile bölüp ilk parçayı alma (H017-YENİ -> H017)
            $parts = explode('-', $urun->StokKodu);
            $kokKod = $parts[0]; 

            // Veritabanında ara (Gramı > 0 olanı önceliklendir)
            $yedekUrun = DB::connection('sqlsrv')->table('Urunler')
                ->whereIn('UrunKodu', [$temizKod, $kokKod])
                ->where('Gram', '>', 0)
                ->orderByDesc('Gram')
                ->first();

            if ($yedekUrun) {
                $urun->Gram = $yedekUrun->Gram;
            }
        }

        // Tarihsel Ayarı Çek (View'da göstermek için)
        $siparisTarihi = \Carbon\Carbon::parse($urun->Tarih)->toDateString();
        $ayar = DB::table('ayar_gecmisi')
                    ->where('tarih', '<=', $siparisTarihi)
                    ->orderBy('tarih', 'desc')
                    ->first();

        // Eğer ayar bulunamazsa, boş bir obje ata (hataları önlemek için)
        if (!$ayar) $ayar = (object)[];
        
        // Kâr Hesaplama Servisini Çalıştır (Dinamik Hediye Kontrolünü İçinde Barındırır)
        $sonuclar = app('App\Services\KarHesapService')->hesapla($urun);
        
        if (empty($sonuclar)) {
            // Hata Durumu (Ayar bulunamadı vs) için boş değerler
            $sonuclar = [
                'miktar' => $urun->Miktar ?? 1,
                'isHediye' => false,
                'gercekKar' => 0,
                'karTL' => 0
                // ... diğer alanlar gerekirse eklenebilir ama template geneldeisset kontrolü yapar
            ];
        }

        $ekstralar = DB::connection('sqlsrv')->table('SiparisEkstralar')
            ->where('SiparisID', $siparis_id)
            ->selectRaw("
                SUM(CASE WHEN Tur = 'GELIR' THEN Tutar ELSE 0 END) as ToplamGelir,
                SUM(CASE WHEN Tur = 'GIDER' THEN Tutar ELSE 0 END) as ToplamGider
            ")
            ->first();

        return view('urunler.detay', compact('urun', 'ayar', 'sonuclar', 'ekstralar'));
    }

    /**
     * Toplu sipariş güncelleme ve kâr hesaplama fonksiyonu
     */
    public function siparisGuncelleVeKar(\Illuminate\Http\Request $request)
    {
        try {
            $kapsam = $request->get('kapsam'); // 'site', 'etsy' veya null (hepsi)
            $gun = (int) $request->get('gun', 5); // Varsayılan 5 gün

            $mesajLog = [];
            $detayLog = []; // Sipariş bazlı detay (Kaydedildi/Güncellendi)

            // 1. SİTE ENTEGRASYONU (PHP SOAP — ConsoleApp4.exe yerine)
            if (!$kapsam || $kapsam == 'site') {
                try {
                    $entegrasyonCtrl = app(\App\Http\Controllers\SiparisEntegrasyonController::class);
                    $ajaxRequest = \Illuminate\Http\Request::create('/admin/siparis-cek', 'GET', ['gun' => $gun]);
                    $ajaxRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
                    $response = $entegrasyonCtrl->senkronizeEt($ajaxRequest);
                    $resultData = json_decode($response->getContent(), true);
                    $mesajLog[] = "Site: " . ($resultData['message'] ?? 'senkronize edildi');
                    if (!empty($resultData['log']) && is_array($resultData['log'])) {
                        $detayLog = array_merge($detayLog, $resultData['log']);
                    }
                } catch (\Exception $eSite) {
                    \Illuminate\Support\Facades\Log::error("Site Sync Hatası: " . $eSite->getMessage());
                    $mesajLog[] = "Site Hatası: " . $eSite->getMessage();
                }
            }

            // --- 2. ETSY ENTEGRASYONU (YENİ) ---
            if (!$kapsam || $kapsam == 'etsy') {
                try {
                    // Etsy servisini başlat ve senkronize et
                    $etsyService = new \App\Services\EtsyService();
                    $etsySonuc = $etsyService->sync();
                    $mesajLog[] = "Etsy: " . $etsySonuc;
                } catch (\Exception $eEtsy) {
                    // Etsy hatası tüm akışı bozmasın, loglayıp devam edelim veya mesaja ekleyelim
                    \Illuminate\Support\Facades\Log::error("Etsy Sync Hatası: " . $eEtsy->getMessage());
                    $mesajLog[] = "Etsy Hatası: " . $eEtsy->getMessage();
                }
            }

            // --- 3. KÂR HESAPLAMA (SADECE ETSY KAPSAMINDA) ---
            // Site entegrasyonu kendi içinde kâr hesaplıyor, tekrar yapmaya gerek yok
            $count = 0;
            if ($kapsam == 'etsy' || (!$kapsam)) {
                $query = DB::connection('sqlsrv')->table('Siparisler')
                    ->where('SiparisDurumu', '!=', 8)
                    ->where('PazaryeriID', 3) // Sadece Etsy
                    ->orderBy('Tarih', 'desc')
                    ->take(50); // Etsy sipariş sayısı az, 50 yeterli

                $siparisIds = $query->pluck('SiparisID');

                $service = app('App\Services\KarHesapService');

                foreach ($siparisIds as $siparisId) {
                    $service->hesaplaSiparis($siparisId);
                    $count++;
                }
            }

            $finalMesaj = implode(' | ', $mesajLog) . ". ($count sipariş kârı güncellendi)";

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $finalMesaj,
                    'log'     => $detayLog, // Sipariş bazlı detay
                ]);
            }

            return back()->with('mesaj', $finalMesaj);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()], 500);
            }
            return back()->with('hata', 'Hata oluştu: ' . $e->getMessage());
        }
    }

    public function bulkStore(Request $request)
    {
        $data = $request->input('data');
        if (empty($data)) {
            return back()->with('hata', 'Veri boş olamaz.');
        }

        $lines = explode("\n", str_replace("\r", "", trim($data)));
        $added = 0;
        $updated = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $cols = explode("\t", $line);
            if (count($cols) < 1) continue;

            $urunKodu = trim($cols[0]);
            if (empty($urunKodu)) continue;

            // Yeni Sıra: Ürün Kodu | Gram | Kategori ID
            $gram = isset($cols[1]) ? str_replace(',', '.', trim($cols[1])) : null;
            $kategoriId = isset($cols[2]) ? trim($cols[2]) : null;

            if ($kategoriId !== null && !is_numeric($kategoriId)) {
                $kategoriId = null; // ID sayısal değilse null bırak
            }

            try {
                $exists = DB::connection('sqlsrv')->table('Urunler')->where('UrunKodu', $urunKodu)->first();

                if ($exists) {
                    $updateData = [];
                    if ($kategoriId !== null) $updateData['KategoriId'] = $kategoriId;
                    if ($gram !== null && is_numeric($gram)) $updateData['Gram'] = $gram;

                    if (!empty($updateData)) {
                        DB::connection('sqlsrv')->table('Urunler')->where('Id', $exists->Id)->update($updateData);
                        $updated++;
                    }
                } else {
                    DB::connection('sqlsrv')->table('Urunler')->insert([
                        'UrunKodu'   => $urunKodu,
                        'KategoriId' => $kategoriId,
                        'Gram'       => (is_numeric($gram) ? $gram : 0),
                    ]);
                    $added++;
                }
            } catch (\Exception $e) {
                $errors[] = "Satır " . ($index + 1) . " ($urunKodu): " . $e->getMessage();
            }
        }

        $msg = "$added yeni ürün eklendi, $updated ürün güncellendi.";
        if (!empty($errors)) {
            $hataMsg = $msg . " Bazı hatalar oluştu: " . implode(" | ", array_slice($errors, 0, 3));
            if (count($errors) > 3) $hataMsg .= "...";
            return back()->with('hata', $hataMsg);
        }

        return back()->with('success', $msg);
    }

    public function destroy($id)
    {
        try {
            $urun = DB::connection('sqlsrv')->table('Urunler')->where('Id', $id)->first();
            
            if (!$urun) {
                return response()->json(['success' => false, 'message' => 'Ürün bulunamadı.'], 404);
            }

            DB::connection('sqlsrv')->table('Urunler')->where('Id', $id)->delete();

            return response()->json(['success' => true, 'message' => 'Ürün başarıyla silindi.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()], 500);
        }
    }
}