<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'Kategoriler'; // SQL tablo adı
    protected $primaryKey = 'Id'; // birincil anahtar sütunu
    public $timestamps = false; // created_at, updated_at yok

    // İlişki: Bir kategori birden fazla ürüne sahip olabilir
    public function urunler()
    {
        return $this->hasMany(Urun::class, 'KategoriId', 'Id');
    }
}
