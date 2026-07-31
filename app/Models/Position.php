<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'trackable_id', 'trackable_type',
        'lat', 'lng', 'vitesse', 'direction',
        'satellites', 'batterie', 'sos',
    ];

    // Relation polymorphe : cette position appartient soit à un User, soit à un Enfant
    public function trackable()
    {
        return $this->morphTo();
    }
}