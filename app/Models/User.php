<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'email',
        'password',
        'family_id',
        'partage_position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
    'partage_position' => 'boolean',
];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function family()
{
    return $this->belongsTo(Family::class);
} 

public function positions()
{
    return $this->morphMany(Position::class, 'trackable');
}

public function lastPosition()
{
    return $this->morphOne(Position::class, 'trackable')->latestOfMany();
}

}