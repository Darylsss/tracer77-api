<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PositionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/update-name', [AuthController::class, 'updateName']);
    Route::put('/user/update-password', [AuthController::class, 'updatePassword']);
    Route::delete('/user/delete', [AuthController::class, 'deleteAccount']);
});

// Routes positions boîtier
Route::post('/position',  [PositionController::class, 'recevoir']);
Route::get('/historique', [PositionController::class, 'historique']);
Route::get('/derniere',   [PositionController::class, 'derniere']);