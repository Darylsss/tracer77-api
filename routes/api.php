<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PositionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    function (\Illuminate\Http\Request $r) {
        return $r->user();
    });
});

// Routes positions boîtier
Route::post('/position',  [PositionController::class, 'recevoir']);
Route::get('/historique', [PositionController::class, 'historique']);
Route::get('/derniere',   [PositionController::class, 'derniere']);