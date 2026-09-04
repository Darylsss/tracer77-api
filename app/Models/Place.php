<?php
// app/Models/Place.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = ['enfant_id', 'created_by', 'type', 'nom', 'latitude', 'longitude', 'rayon'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'rayon' => 'integer',
    ];

    public function enfant()
    {
        return $this->belongsTo(Enfant::class);
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}