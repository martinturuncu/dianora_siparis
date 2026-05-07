<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealGram extends Model
{
    protected $table = 'real_grams';
    protected $primaryKey = 'siparis_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['siparis_id', 'real_gram'];

    protected $casts = [
        'real_gram' => 'decimal:2',
    ];
}
