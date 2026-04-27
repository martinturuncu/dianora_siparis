<?php

namespace App\Http\Controllers;

use App\Models\Pazaryeri;
use Illuminate\Http\Request;

class PazaryeriController extends Controller
{
    // ❌ index() metoduna artık gerek yok, AyarController listeliyor.

    public function store(Request $request)
    {
        $request->validate([
            'Ad' => 'required|string|max:100',
            'KomisyonOrani' => 'required|numeric|min:0|max:100',
        ]);

        // 23 → 0.23
        $komisyon = $this->normalizeKomisyon($request->KomisyonOrani);

        Pazaryeri::create([
            'Ad' => trim($request->Ad),
            'KomisyonOrani' => $komisyon,
        ]);

        // İşlem sonrası ana ayarlar sayfasına dön
        return redirect()->route('ayarlar.index')->with('success', 'Pazaryeri başarıyla eklendi.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Ad' => 'required|string|max:100',
            'KomisyonOrani' => 'required|numeric|min:0|max:100',
        ]);

        $pazaryeri = Pazaryeri::findOrFail($id);

        // Site ismi değiştirilemesin (Güvenlik)
        if (strtolower($pazaryeri->Ad) === 'site' && strtolower($request->Ad) !== 'site') {
            return redirect()->route('ayarlar.index')->with('hata', 'Site pazaryerinin adını değiştiremezsiniz.');
        }

        $komisyon = $this->normalizeKomisyon($request->KomisyonOrani);

        $pazaryeri->update([
            'Ad' => trim($request->Ad),
            'KomisyonOrani' => $komisyon,
        ]);

        return redirect()->route('ayarlar.index')->with('success', 'Pazaryeri başarıyla güncellendi.');
    }

    public function destroy($id)
    {
        $pazaryeri = Pazaryeri::findOrFail($id);

        // Site kaydı asla silinemez
        if (strtolower($pazaryeri->Ad) === 'site') {
            return redirect()->route('ayarlar.index')->with('hata', 'Site pazaryerini silemezsiniz.');
        }

        $pazaryeri->delete();

        return redirect()->route('ayarlar.index')->with('success', 'Pazaryeri silindi.');
    }

    // 🔧 Yardımcı Fonksiyon
    private function normalizeKomisyon($value)
    {
        $value = str_replace(',', '.', $value);  // 23,5 → 23.5
        $value = (float)$value;

        return $value > 1 ? $value / 100 : $value;
    }
}