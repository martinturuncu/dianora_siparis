<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManuelSiparisController extends Controller
{
    public function create()
    {
        $pazaryerleri = DB::connection('mysql')
            ->table('Pazaryerleri')
            ->orderBy('Ad')
            ->get();

        return view('siparisler.manuel_ekle', compact('pazaryerleri'));
    }


    public function store(Request $request, \App\Services\KarHesapService $karService)
    {
        // Virgülü noktaya çevirerek validasyonun doğru çalışmasını sağla
        $this->prepareNumericInputs($request, ['BirimFiyati', 'SatisFiyati']);

        // 1) VALIDATION
        $validated = $request->validate([
            'AdiSoyadi'     => 'required|string|max:200',
            'PazaryeriID'   => 'required|exists:Pazaryerleri,id',
            'Tarih'         => 'nullable|date_format:Y-m-d\TH:i',
            'isUSA'         => 'nullable|boolean',
            'UrunKodu'      => 'required|array|min:1',
            'UrunKodu.*'    => 'required|string|max:100',
            'Adet'          => 'nullable|array',
            'Adet.*'        => 'nullable|integer|min:1',
            'BirimFiyati'   => 'nullable|array',
            'BirimFiyati.*' => 'nullable|numeric|min:0',
            'SatisFiyati'   => 'nullable|array',
            'SatisFiyati.*' => 'nullable|numeric|min:0',
        ]);

        // 3) MANUEL SİPARİŞ ID OLUŞTUR
        $prefix = "M";

        $sonManuel = DB::connection('mysql')
            ->table('Siparisler')
            ->where('SiparisID', 'LIKE', $prefix . '%')
            ->orderBy('SiparisID', 'desc')
            ->select('SiparisID')
            ->first();

        if ($sonManuel) {
            $num = (int) substr($sonManuel->SiparisID, strlen($prefix));
            $yeniNumara = $num + 1;
        } else {
            $yeniNumara = 1;
        }

        $yeniSiparisID = $prefix . str_pad($yeniNumara, 5, '0', STR_PAD_LEFT);

        // 4) TARİH — datetime-local düzeltme
        if ($request->filled('Tarih')) {
            $tarih = str_replace('T', ' ', $request->Tarih) . ':00';
        } else {
            $tarih = now()->format('Y-m-d H:i:s');
        }

        // 5) SİPARİŞ KAYDET
        DB::connection('mysql')
            ->table('Siparisler')
            ->insert([
                'SiparisID'     => $yeniSiparisID,
                'AdiSoyadi'     => $validated['AdiSoyadi'],
                'Telefon'       => '', // Bu alanlar artık formda yok
                'Email'         => '',
                'Adres'         => '',
                'Il'            => '',
                'Ilce'          => '',
                'Tarih'         => $tarih,
                'PazaryeriID'   => $validated['PazaryeriID'],
                'SiparisDurumu' => 0,
                'Onaylandi'     => 0,
                'isUSA'         => $request->has('isUSA') ? 1 : 0,
                'is_manuel'     => 1, // Manuel sipariş
            ]);

        // 6) ÜRÜN SATIRLARINI DÖNGÜ İLE KAYDET
        foreach ($validated['UrunKodu'] as $index => $urunKodu) {
            
            // Ürünü bul
            // Ürünü bul
            $girilen = trim($urunKodu);
            
            // 1. Önce tam eşleşme ara (Case Insensitive - DB Collation handled)
            $urun = DB::connection('mysql')
                ->table('Urunler as ur')
                ->leftJoin('Kategoriler as k', 'ur.KategoriId', '=', 'k.Id')
                ->whereRaw("UPPER(ur.UrunKodu) = ?", [strtoupper($girilen)]) // Kullanıcı girdisi neyse onu gönder, ama i/I sorunu olmasın diye strtoupper yapabiliriz. Fakat en temizi DB'ye bırakmaktır.
                // DÜZELTME: PHP strtoupper yerine direkt gönderip DB'nin UPPER'ını kullanmasını sağlayalım.
                ->whereRaw("UPPER(ur.UrunKodu) = UPPER(?)", [$girilen])
                ->select('ur.Gram', 'ur.UrunKodu', 'k.KategoriAdi')
                ->first();

            // 2. Bulunamazsa -yeni (küçük harf) ekleyip ara
            // Veritabanı Türkçe collation ise 'yeni' -> 'YENİ' yapar.
            // Biz 'YENI' gönderirsek 'YENI' kalır ve eşleşmez.
            if (!$urun) {
                 $urun = DB::connection('mysql')
                    ->table('Urunler as ur')
                    ->leftJoin('Kategoriler as k', 'ur.KategoriId', '=', 'k.Id')
                    ->whereRaw("UPPER(ur.UrunKodu) = UPPER(?)", [$girilen . '-yeni'])
                    ->select('ur.Gram', 'ur.UrunKodu', 'k.KategoriAdi')
                    ->first();
            }

            if (!$urun) {
                // Eğer bir ürün bulunamazsa işlemi geri al ve hata döndür
                DB::connection('mysql')->table('Siparisler')->where('SiparisID', $yeniSiparisID)->delete();
                return back()->with('hata', "Stok Kodu '{$urunKodu}' olan ürün bulunamadı. Lütfen kontrol edip tekrar deneyin.");
            }

            $adet = (int)($validated['Adet'][$index] ?? 1);
            $birimFiyatInput = isset($validated['BirimFiyati'][$index]) ? (float)$validated['BirimFiyati'][$index] : null;
            $satisFiyatiInput = isset($validated['SatisFiyati'][$index]) ? (float)$validated['SatisFiyati'][$index] : null;

            // Birim Fiyat veya Toplam Fiyattan birisi dolu olmalı.
            // Önceliği Toplam Fiyata veriyoruz, eğer o doluysa Birim Fiyatı hesaplıyoruz.
            // Eğer sadece Birim Fiyat doluysa, Toplam Fiyatı hesaplıyoruz.
            if ($satisFiyatiInput !== null) {
                $satisFiyatiToplam = $satisFiyatiInput;
                $birimFiyat = ($adet > 0) ? ($satisFiyatiToplam / $adet) : $satisFiyatiToplam;
            } elseif ($birimFiyatInput !== null) {
                $birimFiyat = $birimFiyatInput;
                $satisFiyatiToplam = $birimFiyat * $adet;
            } else {
                // Her ikisi de boşsa veya geçersizse, varsayılan olarak 0 ata.
                $birimFiyat = 0;
                $satisFiyatiToplam = 0;
            }

        // Etsy fiyatı ise USD'den TL'ye çevir
        if ($validated['PazaryeriID'] == 3) { // 3 = Etsy
            // Siparişin oluşturulduğu tarihe en yakın ayarı bul
            $siparisTarihi = \Carbon\Carbon::parse($tarih)->toDateString();
            $ayar = DB::table('ayar_gecmisi')
                ->where('tarih', '<=', $siparisTarihi)
                ->orderBy('tarih', 'desc')
                ->first();
            
            $dolarKuru = $ayar ? $ayar->dolar_kuru : 1; // Kur bulunamazsa 1 al

            $birimFiyat = $birimFiyat * $dolarKuru;
            $satisFiyatiToplam = $satisFiyatiToplam * $dolarKuru;
        }

            DB::connection('mysql')
                ->table('SiparisUrunleri')
                ->insert([
                    'SiparisID'  => $yeniSiparisID,
                    'StokKodu'   => $urun->UrunKodu,
                    'UrunAdi'    => $urun->KategoriAdi ?? 'Manuel Ürün',
                    'Miktar'     => $adet,
                    'BirimFiyat' => $birimFiyat,
                    'Tutar'      => $satisFiyatiToplam,
                    'KdvTutari'  => 0,
                ]);
        }

        // Kâr Hesapla
        $karService->hesaplaSiparis($yeniSiparisID);

        return redirect()
            ->route('siparisler.index')
            ->with('success', 'Manuel sipariş başarıyla oluşturuldu!');
    }

    /**
     * Gelen istekteki sayısal girdilerdeki virgülleri noktalara çevirir.
     * @param Request $request
     * @param array $keys
     */
    private function prepareNumericInputs(Request $request, array $keys)
    {
        foreach ($keys as $key) {
            $input = $request->input($key, []);
            if (is_array($input)) {
                array_walk($input, function (&$value) {
                    if (is_string($value)) {
                        $value = str_replace(',', '.', $value);
                    }
                });
                $request->merge([$key => $input]);
            }
        }
    }
}

