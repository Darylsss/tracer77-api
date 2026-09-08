<?php
// app/Policies/PlacePolicy.php

namespace App\Policies;

use App\Models\Enfant;
use App\Models\Place;
use App\Models\User;

class PlacePolicy
{
    public function viewAny(User $user, Enfant $enfant): bool
    {
        return $user->family_id === $enfant->family_id
            && $user->can('voir_positions');
    }

    public function create(User $user, Enfant $enfant): bool
    {
        return $user->family_id === $enfant->family_id
            && $user->can('ajouter_lieu');
    }

    public function update(User $user, Place $place): bool
    {
        return $user->family_id === $place->enfant->family_id
            && $user->can('modifier_lieu');
    }

    public function delete(User $user, Place $place): bool
    {
        return $user->family_id === $place->enfant->family_id
            && $user->can('supprimer_lieu');
    }
}