<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiparisUrunleri extends Model
{
    protected $table = 'SiparisUrunleri';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $guarded = [];

    public function siparis()
    {
        return $this->belongsTo(Siparis::class, 'SiparisID', 'SiparisID');
    }

    public function urun()
    {
        return $this->belongsTo(Urun::class, 'StokKodu', 'UrunKodu');
    }

   
    
   

}
