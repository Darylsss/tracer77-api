<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enfant extends Model
{
    protected $fillable = [
        'user_id', 'family_id', 'nom', 'prenom', 'photo', 'identifiant_boitier',
        'zone_actuelle_id', 'hors_zone_depuis', 'delai_grace_courant', 'alerte_sortie_envoyee_a',
    ];

    protected $casts = [
        'hors_zone_depuis' => 'datetime',
        'alerte_sortie_envoyee_a' => 'datetime',
    ];

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
    public function places()
{
    return $this->hasMany(Place::class);
}

    // Le dernier lieu (zone de sécurité) où l'enfant a été détecté
    public function zoneActuelle()
    {
        return $this->belongsTo(Place::class, 'zone_actuelle_id');
    }
}