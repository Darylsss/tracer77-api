<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enfant extends Model
{
    protected $fillable = ['user_id', 'family_id', 'nom', 'prenom', 'photo', 'identifiant_boitier'];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Toutes les positions de cet enfant
    public function positions()
    {
        return $this->morphMany(Position::class, 'trackable');
    }

    // Sa position la plus récente uniquement
    public function lastPosition()
    {
        return $this->morphOne(Position::class, 'trackable')->latestOfMany();
    }
}