<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneEtat extends Model
{
    protected $fillable = ['enfant_id', 'place_id', 'hors_zone_depuis', 'alerte_envoyee'];

    protected function casts(): array
    {
        return [
            'hors_zone_depuis' => 'datetime',
            'alerte_envoyee' => 'boolean',
        ];
    }

    public function enfant()
    {
        return $this->belongsTo(Enfant::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
}