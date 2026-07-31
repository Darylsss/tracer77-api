<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
   protected $fillable = ['nom', 'created_by'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enfants()
{
    return $this->hasMany(Enfant::class);
}

}
