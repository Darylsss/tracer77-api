<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\EnfantController;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/update-name', [AuthController::class, 'updateName']);
    Route::put('/user/update-password', [AuthController::class, 'updatePassword']);
    Route::delete('/user/delete', [AuthController::class, 'deleteAccount']);
    Route::post('/family/invite', [FamilyController::class, 'invite']);
    Route::post('/family/accept-invite', [FamilyController::class, 'acceptInvite']);
    Route::post('/family/create', [FamilyController::class, 'create']);
    Route::post('/positions', [PositionController::class, 'storeForUser']);
    Route::get('/family/members', [FamilyController::class, 'members']);
    Route::delete('/family/members/{id}', [FamilyController::class, 'removeMember']);
    Route::post('/enfants', [EnfantController::class, 'store']);
    Route::post('/user/toggle-position-sharing', [FamilyController::class, 'togglePositionSharing']);
    
});

// Routes positions boîtier
Route::post('/position',  [PositionController::class, 'recevoir']);
Route::get('/historique', [PositionController::class, 'historique']);
Route::get('/derniere',   [PositionController::class, 'derniere']);
Route::post('/devices/positions', [PositionController::class, 'storeForDevice']);