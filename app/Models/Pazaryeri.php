<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pazaryeri extends Model
{
    protected $table = 'Pazaryerleri';

    protected $fillable = [
        'Ad',
        'KomisyonOrani',
    ];

    public function siparisler()
    {
        return $this->hasMany(Siparis::class, 'PazaryeriID');
    }
}
