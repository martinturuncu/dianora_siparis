<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Siparis;
use App\Models\SiparisUrun;
use App\Models\AyarGecmisi;

class AylikNetController extends Controller
{
    public function index()
    {
        // 1. Siparişleri Çek (Ekim 2025 ve sonrası, İptal olmayanlar)
        // Table: Siparisler, Col: SiparisID, Tarih, SiparisDurumu (8=İptal)
        // 1. Siparişleri ve Kârları Çek (Tek Sorgu)
        // Ekim 2025 ve sonrası, İptal olmayanlar
        $siparisler = DB::table('Siparisler as s')
            ->leftJoin('SiparisKarlar as k', function($join) {
                $join->on('s.SiparisID', '=', 'k.SiparisID')
                     ->where('k.UrunKodu', '=', 'TOPLAM');
            })
            ->select('s.SiparisID', 's.Tarih', 's.odemeIndirimi', 'k.GercekKar')
            // ->where('s.Tarih', '>=', '2025-10-01') <-- KALDIRILDI
            ->whereNotIn('s.SiparisDurumu', [8, 9]) // İptal ve İade hariç
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing') // Test siparişi hariç
            ->get();

        $veriler = [];

        // Sipariş ID Listesi (Ürünler için)
        $siparisIds = $siparisler->pluck('SiparisID')->toArray();

        // 2. Ürünleri Çek (Ciro ve Reklam Payı Hesabı İçin)
        // SQL Server 2100 parametre limiti nedeniyle chunk'lara böl
        $urunler = collect();
        if (!empty($siparisIds)) {
            foreach (array_chunk($siparisIds, 2000) as $chunk) {
                $chunkResult = DB::table('SiparisUrunleri')
                    ->whereIn('SiparisID', $chunk)
                    ->get();
                $urunler = $urunler->merge($chunkResult);
            }
            $urunler = $urunler->groupBy('SiparisID');
        }

        // $karlar dizisine gerek kalmadı çünkü $siparisler içinde 'GercekKar' artık var.

        // 4. Ayar Geçmişini Çek (Reklam Geliri Hesabı İçin)
        // Tüm ayarları çekip bellekte filtrelemek, her sipariş için DB'ye gitmekten iyidir.
        $ayarlar = DB::table('ayar_gecmisi')->orderBy('tarih', 'desc')->get();

        // Güncel Hediye Kodlarını Çek (Global)
        $sonAyar = DB::table('ayar_gecmisi')
            ->orderBy('tarih', 'desc')
            ->first();
        $globalHediyeKodlari = [];
        if ($sonAyar && isset($sonAyar->hediye_kodlari)) {
            $globalHediyeKodlari = array_map('trim', explode(',', $sonAyar->hediye_kodlari));
        } else {
            $globalHediyeKodlari = ['crmhediye'];
        }

        // 5. Döngü ve Hesaplama
        foreach ($siparisler as $sip) {
            $tarih = \Carbon\Carbon::parse($sip->Tarih);
            $yil = $tarih->year;
            $ay = $tarih->month;
            // Sıralama (krsort) düzgün çalışması için ayı 2 hane yap (2025-01 gibi)
            $key = sprintf('%d-%02d', $yil, $ay);

            if (!isset($veriler[$key])) {
                $veriler[$key] = [
                    'yil' => $yil,
                    'ay' => $ay,
                'ciro' => 0,
                    'adet' => 0,
                    'hediye_adedi' => 0, // Yeni sayaç
                    'urun_kari' => 0,
                    'reklam_payi' => 0,
                    'reklam_google' => 0,
                    'reklam_meta' => 0,
                    'reklam_gideri_toplam' => 0,
                    'net_kalan' => 0
                ];
            }

            // O tarihteki reklam ayarını bul (Hediye kodları için de kullanılacak)
            $gecerliAyar = $ayarlar->first(function($item) use ($sip) {
                return $item->tarih <= $sip->Tarih;
            });
            if (!$gecerliAyar && $ayarlar->isNotEmpty()) {
                $gecerliAyar = $ayarlar->last();
            }

            // Hediye kodlarını ayarla (Global)
            $hediyeKodlari = $globalHediyeKodlari;

            // Sipariş Ürünleri
            $sipUrunler = $urunler[$sip->SiparisID] ?? collect();

            // Ciro ve Satış Adedi (Miktar)
            foreach ($sipUrunler as $su) {
                // Hediye Ayrıştırma
                $isHediye = false;
                foreach ($hediyeKodlari as $hk) {
                    if (strcasecmp($su->StokKodu, $hk) === 0) {
                        $isHediye = true;
                        break;
                    }
                }

                if ($isHediye) {
                     $veriler[$key]['hediye_adedi'] += $su->Miktar;
                } else {
                     $veriler[$key]['adet'] += $su->Miktar;
                }
                
            // Ciro: (Tutar + KDV) * Miktar
                // Not: Tutar veritabanında genelde birim fiyattır.
                $veriler[$key]['ciro'] += ($su->Tutar + ($su->KdvTutari ?? 0)) * $su->Miktar;
            }
            
            // Ödeme İndirimi Düş (TRY)
            $veriler[$key]['ciro'] -= ($sip->odemeIndirimi ?? 0);

            // Ürün Kârı (Doğrudan tablodan - Join ile geldi)
            $veriler[$key]['urun_kari'] += $sip->GercekKar ?? 0;

            $birimReklam = $gecerliAyar->reklam ?? 0;
            $birimIscilik = $gecerliAyar->iscilik ?? 0; // İşçilik maliyeti

            // Toplam Miktar * Birim Reklam
            $toplamMiktar = $sipUrunler->sum('Miktar');
            $veriler[$key]['reklam_payi'] += ($birimReklam * $toplamMiktar);
            
            // İşçilik Payı (Gider/Gelir gösterimi için)
            // User request: "onu da en son aylık nete hesaba katmadan + diye gösterelim"
            if (!isset($veriler[$key]['iscilik_payi'])) {
                $veriler[$key]['iscilik_payi'] = 0;
            }
            $veriler[$key]['iscilik_payi'] += ($birimIscilik * $toplamMiktar);
        }

        // 6. Giderleri Eşle ve Olmayan Ayları Ekle
        $giderler = DB::table('aylik_giderler')->get();
        foreach ($giderler as $gid) {
            $k = sprintf('%d-%02d', $gid->yil, $gid->ay);
            
            if (!isset($veriler[$k])) {
                // Sipariş yok ama Gider var -> Satırı oluştur
                $veriler[$k] = [
                    'yil' => $gid->yil,
                    'ay' => $gid->ay,
                    'ciro' => 0,
                    'adet' => 0,
                    'hediye_adedi' => 0,
                    'urun_kari' => 0,
                    'reklam_payi' => 0,
                    'reklam_google' => 0,
                    'reklam_meta' => 0,
                    'reklam_gideri_toplam' => 0,
                    'net_kalan' => 0,
                    'iscilik_payi' => 0
                ];
            }

            $veriler[$k]['reklam_google'] = $gid->reklam_google;
            $veriler[$k]['reklam_meta'] = $gid->reklam_meta;
            $veriler[$k]['reklam_gideri_toplam'] = $gid->reklam_google + $gid->reklam_meta;
        }

        // Finalize
        // Önce yılları al (Filtreleme öncesi tüm yılları bilmemiz lazım)
        $tumVeriler = collect($veriler);
        // Benzersiz Yıllar
        $mevcutYillar = $tumVeriler->pluck('yil')->unique()->sort()->values()->all();

        // Filtreleme
        $secilenYil = request('yil', 'all'); // Default: Tümü

        if ($secilenYil && $secilenYil !== 'all') {
            $tumVeriler = $tumVeriler->where('yil', (int)$secilenYil);
        }

        // Sıralama (Yeniden diziye çevirip krsort yapalım veya collection sort kullanalım)
        // Collection kullanarak tarihe göre tersten sırala:
        $tumVeriler = $tumVeriler->sortByDesc(function ($item, $key) {
            return sprintf('%d-%02d', $item['yil'], $item['ay']);
        });

        $aylarIsim = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];

        $finalVeriler = [];
        foreach ($tumVeriler as $v) {
            $net = ($v['urun_kari'] + $v['reklam_payi']) - $v['reklam_gideri_toplam'];
            $v['net_kalan'] = $net;
            $v['tarih_format'] = $aylarIsim[$v['ay']] . ' ' . $v['yil'];
            $finalVeriler[] = $v;
        }

        return view('aylik_net.index', [
            'veriler' => $finalVeriler,
            'mevcutYillar' => $mevcutYillar,
            'secilenYil' => $secilenYil
        ]);
    }

    public function update(Request $request)
    {
        $yil = $request->yil;
        $ay = $request->ay;
        $tip = $request->tip;
        $deger = $request->deger;

        if (!$yil || !$ay || !$tip) {
            return response()->json(['status' => false]);
        }
        
        $col = ($tip == 'google') ? 'reklam_google' : 'reklam_meta';

        $mevcut = DB::table('aylik_giderler')
            ->where('yil', $yil)
            ->where('ay', $ay)
            ->first();

        if ($mevcut) {
            DB::table('aylik_giderler')
                ->where('id', $mevcut->id)
                ->update([
                    $col => $deger,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('aylik_giderler')->insert([
                'yil' => $yil,
                'ay' => $ay,
                'reklam_google' => ($tip == 'google' ? $deger : 0),
                'reklam_meta' => ($tip == 'meta' ? $deger : 0),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['status' => true]);
    }
}
