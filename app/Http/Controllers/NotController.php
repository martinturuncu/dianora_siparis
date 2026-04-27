<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Not;

class NotController extends Controller
{
    public function getNot()
    {
        // Şimdilik tek bir not varmış gibi davranacağız (User ID null veya 1 varsayalım)
        // Eğer auth varsa Auth::id() kullanılabilir ama şu an admin girişi net değil.
        // Basitlik için ilk kaydı alacağız, yoksa oluşturacağız.
        
        $not = Not::firstOrCreate(
            ['id' => 1], 
            ['icerik' => '']
        );

        return response()->json(['icerik' => $not->icerik]);
    }

    public function saveNot(Request $request)
    {
        $not = Not::firstOrCreate(['id' => 1]);
        $not->icerik = $request->input('icerik');
        $not->save();

        return response()->json(['success' => true]);
    }
}
