<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\Place;
use App\Policies\PlacePolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Place::class, PlacePolicy::class);
    }
}