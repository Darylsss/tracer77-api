<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'lat',
        'lng',
        'vitesse',
        'direction',
        'satellites',
        'batterie',
        'sos',
    ];
}