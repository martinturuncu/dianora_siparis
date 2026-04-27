<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siparis extends Model
{
    protected $table = 'Siparisler';
    protected $primaryKey = 'SiparisID';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function urunler()
    {
        return $this->hasMany(SiparisUrunleri::class, 'SiparisID', 'SiparisID');
    }
    public function pazaryeri()
{
    return $this->belongsTo(\App\Models\Pazaryeri::class, 'PazaryeriID');
}

}
