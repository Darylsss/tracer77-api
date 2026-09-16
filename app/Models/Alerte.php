<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    protected $fillable = ['family_id', 'enfant_id', 'type', 'message', 'lu'];

    protected function casts(): array
    {
        return [
            'lu' => 'boolean',
        ];
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function enfant()
    {
        return $this->belongsTo(Enfant::class);
    }
}