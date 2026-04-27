<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Not extends Model
{
    protected $table = 'notlar';
    protected $fillable = ['user_id', 'icerik'];
}
