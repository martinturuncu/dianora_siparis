<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Siparis;

class MobileController extends Controller
{
    public function index(Request $request)
    {
        $tarih = now()->toDateString();

        // 1. Günlük İstatistikler
        $gunlukOzet = DB::connection('mysql')->table('Siparisler')
            ->whereDate('Tarih', $tarih)
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->selectRaw("
                COUNT(*) as toplam,
                SUM(CASE WHEN SiparisDurumu IN (8, 9) THEN 1 ELSE 0 END) as iptal,
                SUM(CASE WHEN SiparisDurumu NOT IN (8, 9) THEN 1 ELSE 0 END) as aktif
            ")
            ->first();

        $gunlukBrutCiro = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing') 
            ->selectRaw('SUM( (IFNULL(SiparisUrunleri.Tutar, 0) + IFNULL(SiparisUrunleri.KdvTutari, 0)) * SiparisUrunleri.Miktar ) AS ciro')
            ->value('ciro') ?? 0;

        $gunlukIndirimler = DB::connection('mysql')->table('Siparisler')
            ->whereDate('Tarih', $tarih)
            ->whereNotIn('SiparisDurumu', [8, 9])
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->sum('odemeIndirimi');

        $gunlukCiro = $gunlukBrutCiro - $gunlukIndirimler;

        $gunlukKar = DB::connection('mysql')->table('SiparisKarlar')
            ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereDate('Siparisler.Tarih', $tarih)
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9]) 
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing') 
            ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
            ->sum('SiparisKarlar.GercekKar');

        // 2. Son Siparişler (Basit Liste)
        $sonSiparisler = DB::connection('mysql')->table('Siparisler as s')
            ->leftJoin('SiparisUrunleri as su', 's.SiparisID', '=', 'su.SiparisID')
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
            ->select('s.*')
            ->selectRaw('SUM( (IFNULL(su.Tutar, 0) + IFNULL(su.KdvTutari, 0)) * su.Miktar ) - IFNULL(s.odemeIndirimi, 0) - IFNULL(s.HediyeCekiTutari, 0) as ToplamTutar')
            ->groupBy('s.SiparisID')
            ->orderBy('s.Tarih', 'desc')
            ->limit(20)
            ->get();

        return view('mobile', [
            'gunlukToplam' => $gunlukOzet->aktif ?? 0,
            'gunlukCiro' => $gunlukCiro,
            'gunlukKar' => $gunlukKar,
            'siparisler' => $sonSiparisler
        ]);
    }
}
