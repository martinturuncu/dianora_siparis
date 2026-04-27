<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AyarController extends Controller
{
    public function index()
    {
        // TEK SEFERLİK GEÇİŞ İŞLEMİ (ESKİ TABLODAN YENİYE)
        if (Schema::hasTable('Ayarlar')) {
            $this->tarihselVeriGecisi();
        }

        $today = Carbon::today()->toDateString();

        // 1. Bugünün ayarını veya en son ayarı çek
        $ayar = DB::connection('mysql')->table('ayar_gecmisi')
            ->where('tarih', '<=', $today)
            ->orderBy('tarih', 'desc')
            ->first();

        // Eğer hiç ayar yoksa, boş bir nesne oluştur (ilk kurulum için)
        if (!$ayar) {
            $ayar = (object) [
                'tarih' => $today,
                'altin_fiyat' => 0, 'ayar' => 585, 'iscilik' => 0,
                'kargo' => 0, 'kutu' => 0, 'reklam' => 0, 'kdv' => 20,
                'altin_usd' => 0, 'dolar_kuru' => 0, 'kargo_yurtdisi' => 0,
                'komisyon_site' => 0.0500, 'komisyon_trendyol' => 0.2250,
                'komisyon_etsy' => 0.1600, 'komisyon_hipicon' => 0.3000,
                'hediye_kodlari' => 'crmhediye'
            ];
        }

        // Görünüm için komisyonları 100 ile çarp (Yüzde formatı)
        $ayar->komisyon_site_display = ($ayar->komisyon_site ?? 0.05) * 100;
        $ayar->komisyon_trendyol_display = ($ayar->komisyon_trendyol ?? 0.225) * 100;
        $ayar->komisyon_etsy_display = ($ayar->komisyon_etsy ?? 0.16) * 100;
        $ayar->komisyon_hipicon_display = ($ayar->komisyon_hipicon ?? 0.3) * 100;

        // 2. Pazaryerlerini Çek
        $pazaryerleri = DB::connection('mysql')->table('Pazaryerleri')->orderBy('id', 'asc')->get();

        return view('ayarlar.index', compact('ayar', 'pazaryerleri'));
    }

    public function guncelle(Request $request)
    {
        // Formdan gelen tarihi al, eğer yoksa bugünü kullan
        $tarih = $request->input('tarih', Carbon::today()->toDateString());

        $data = $request->except(['_token', 'tarih']);
        
        // Komisyonları 100'e bölerek (decimal) kaydet
        if (isset($data['komisyon_site']))     $data['komisyon_site']     = $data['komisyon_site'] / 100;
        if (isset($data['komisyon_trendyol'])) $data['komisyon_trendyol'] = $data['komisyon_trendyol'] / 100;
        if (isset($data['komisyon_etsy']))     $data['komisyon_etsy']     = $data['komisyon_etsy'] / 100;
        if (isset($data['komisyon_hipicon']))  $data['komisyon_hipicon']  = $data['komisyon_hipicon'] / 100;

        $data['updated_at'] = now();

        // Belirtilen tarihin kaydını güncelle veya oluştur
        DB::connection('mysql')->table('ayar_gecmisi')->updateOrInsert(
            ['tarih' => $tarih],
            $data
        );

        // Pazaryerleri tablosunu da güncel (en son) değerlerle senkronize et (Eğer tarih bugünse)
        if ($tarih == Carbon::today()->toDateString()) {
            if (isset($data['komisyon_site']))     DB::connection('mysql')->table('Pazaryerleri')->where('id', 1)->update(['KomisyonOrani' => $data['komisyon_site']]);
            if (isset($data['komisyon_trendyol'])) DB::connection('mysql')->table('Pazaryerleri')->where('id', 2)->update(['KomisyonOrani' => $data['komisyon_trendyol']]);
            if (isset($data['komisyon_etsy']))     DB::connection('mysql')->table('Pazaryerleri')->where('id', 3)->update(['KomisyonOrani' => $data['komisyon_etsy']]);
            if (isset($data['komisyon_hipicon']))  DB::connection('mysql')->table('Pazaryerleri')->where('id', 4)->update(['KomisyonOrani' => $data['komisyon_hipicon']]);
        }

        return redirect()->route('ayarlar.index', ['tarih' => $tarih])->with('success', 'Ayarlar başarıyla güncellendi!');
    }

    /**
     * Belirtilen tarihe ait ayarları JSON olarak döndürür.
     */
    public function getAyarByDate($tarih)
    {
        $ayar = DB::connection('mysql')->table('ayar_gecmisi')
            ->where('tarih', '<=', $tarih)
            ->orderBy('tarih', 'desc')
            ->first();

        if (!$ayar) {
            return response()->json(['hata' => 'Ayar bulunamadı'], 404);
        }

        return response()->json($ayar);
    }

    /**
     * Geçmiş ayar kayıtlarını listeler.
     */
    public function gecmis()
    {
        $gecmisAyarlar = DB::connection('mysql')->table('ayar_gecmisi')
            ->orderBy('tarih', 'desc')
            ->get();

        return view('ayarlar.gecmis', compact('gecmisAyarlar'));
    }

    /**
     * Eski Ayarlar tablosundaki veriyi yeni ayar_gecmisi'ne taşır ve eski tabloyu siler.
     * Bu fonksiyon sadece bir kez çalışmak üzere tasarlanmıştır.
     */
    private function tarihselVeriGecisi()
    {
        if (!Schema::hasTable('Ayarlar')) {
            return;
        }

        $eskiAyar = DB::connection('mysql')->table('Ayarlar')->first();

        if ($eskiAyar) {
            $yeniAyarVerisi = (array) $eskiAyar;
            
            // Yeni tabloya uygun hale getir
            unset($yeniAyarVerisi['id'], $yeniAyarVerisi['komisyon']); // komisyon pazaryerine taşındı, id gereksiz
            
            $yeniAyarVerisi['tarih'] = Carbon::parse($eskiAyar->updated_at)->toDateString();

            // Eksik alanlar varsa varsayılan değer ata
            $yeniAyarVerisi['altin_usd'] = $yeniAyarVerisi['altin_usd'] ?? 0;
            $yeniAyarVerisi['kargo_yurtdisi'] = $yeniAyarVerisi['kargo_yurtdisi'] ?? 0;


            // Yeni tarihsel tabloya ekle (eğer o tarih boşsa)
            DB::connection('mysql')->table('ayar_gecmisi')->updateOrInsert(
                ['tarih' => $yeniAyarVerisi['tarih']],
                $yeniAyarVerisi
            );
        }

        // Eski tabloyu sil
        Schema::dropIfExists('Ayarlar');
    }
}

