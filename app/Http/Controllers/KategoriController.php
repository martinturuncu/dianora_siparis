<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoriler = DB::connection('mysql')
            ->table('Kategoriler')
            ->orderBy('KategoriAdi', 'ASC')
            ->get();

        return view('kategoriler.index', compact('kategoriler'));
    }

    public function guncelle(Request $request)
    {
        $veriler = $request->input('kategoriler');

        if (!$veriler || !is_array($veriler)) {
            return redirect()
                ->route('kategoriler.index')
                ->with('hata', 'Herhangi bir değişiklik yapılmadı.');
        }

        foreach ($veriler as $id => $deger) {

            // virgülü noktaya çevir
            $karOrani = str_replace(',', '.', trim($deger));

            if (!is_numeric($karOrani)) {
                continue;
            }

            $karOrani = (float)$karOrani;

            // sınırlar (0 - %500 arası)
            $karOrani = max(0, min($karOrani, 500));

            DB::connection('mysql')
                ->table('Kategoriler')
                ->where('Id', $id)
                ->update([
                    'KarOrani' => $karOrani
                ]);
        }

        return redirect()
            ->route('kategoriler.index')
            ->with('basari', 'Kategori kâr oranları başarıyla güncellendi!');
    }
}

