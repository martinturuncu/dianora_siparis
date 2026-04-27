<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Siparis;

class MobileController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'today');
        $now = now();
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        if ($period === 'week') {
            $start = now()->startOfWeek();
        } elseif ($period === 'month') {
            $start = now()->startOfMonth();
        } elseif ($period === 'last30') {
            $start = now()->subDays(30);
        }

        // 1. İstatistikler
        $ozet = DB::connection('mysql')->table('Siparisler')
            ->whereBetween('Tarih', [$start, $end])
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->selectRaw("
                COUNT(*) as toplam,
                SUM(CASE WHEN SiparisDurumu IN (8, 9) THEN 1 ELSE 0 END) as iptal,
                SUM(CASE WHEN SiparisDurumu NOT IN (8, 9) THEN 1 ELSE 0 END) as aktif
            ")
            ->first();

        $brutCiro = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereBetween('Siparisler.Tarih', [$start, $end])
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing') 
            ->selectRaw('SUM( (IFNULL(SiparisUrunleri.Tutar, 0) + IFNULL(SiparisUrunleri.KdvTutari, 0)) * SiparisUrunleri.Miktar ) AS ciro')
            ->value('ciro') ?? 0;

        $indirimler = DB::connection('mysql')->table('Siparisler')
            ->whereBetween('Tarih', [$start, $end])
            ->whereNotIn('SiparisDurumu', [8, 9])
            ->where('AdiSoyadi', '!=', 'Dianora Piercing')
            ->sum('odemeIndirimi');

        $toplamCiro = $brutCiro - $indirimler;

        $toplamKar = DB::connection('mysql')->table('SiparisKarlar')
            ->join('Siparisler', 'SiparisKarlar.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereBetween('Siparisler.Tarih', [$start, $end])
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9]) 
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing') 
            ->where('SiparisKarlar.UrunKodu', 'TOPLAM')
            ->sum('SiparisKarlar.GercekKar');

        // 1.B. Ürün Adedi
        $toplamUrunAdedi = DB::connection('mysql')->table('SiparisUrunleri')
            ->join('Siparisler', 'SiparisUrunleri.SiparisID', '=', 'Siparisler.SiparisID')
            ->whereBetween('Siparisler.Tarih', [$start, $end])
            ->whereNotIn('Siparisler.SiparisDurumu', [8, 9])
            ->where('Siparisler.AdiSoyadi', '!=', 'Dianora Piercing')
            ->sum('SiparisUrunleri.Miktar');

        // 2. Son Siparişler (Basit Liste)
        $sonSiparisler = DB::connection('mysql')->table('Siparisler as s')
            ->leftJoin(DB::raw('(SELECT SiparisID, SUM((IFNULL(Tutar, 0) + IFNULL(KdvTutari, 0)) * Miktar) as UrunToplam, SUM(Miktar) as UrunAdedi FROM SiparisUrunleri GROUP BY SiparisID) as su'), 's.SiparisID', '=', 'su.SiparisID')
            ->leftJoin('SiparisKarlar as sk', function($join) {
                $join->on('s.SiparisID', '=', 'sk.SiparisID')
                     ->where('sk.UrunKodu', '=', 'TOPLAM');
            })
            ->where('s.AdiSoyadi', '!=', 'Dianora Piercing')
            ->select('s.*', 
                DB::raw('IFNULL(su.UrunToplam, 0) - IFNULL(s.odemeIndirimi, 0) - IFNULL(s.HediyeCekiTutari, 0) as ToplamTutar'),
                'su.UrunAdedi',
                'sk.GercekKar as SiparisKar'
            )
            ->orderBy('s.Tarih', 'desc')
            ->limit(20)
            ->get();

        return view('mobile', [
            'gunlukToplam' => $ozet->aktif ?? 0,
            'gunlukUrunAdedi' => $toplamUrunAdedi ?? 0,
            'gunlukCiro' => $toplamCiro,
            'gunlukKar' => $toplamKar,
            'siparisler' => $sonSiparisler,
            'period' => $period
        ]);
    }
}
