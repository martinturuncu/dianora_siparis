<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Urun extends Model
{
    protected $table = 'Urunler';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    // İlişki: Ürün bir kategoriye aittir
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'KategoriId', 'Id');
    }
}
