<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ajouter Sanctum aux middleware API
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
        
        
    $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

       
        
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'sanctum/csrf-cookie',
            'login',
            'logout',
            'register'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();