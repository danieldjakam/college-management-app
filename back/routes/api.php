<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SectionController;

// Routes d'authentification
Route::prefix('auth')->group(function () {
    // Routes publiques (pas d'authentification requise)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Routes protégées (authentification JWT requise)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Route de test
Route::get('test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Routes protégées pour les sections (admin seulement)
Route::middleware('auth:api')->group(function () {
    Route::prefix('sections')->group(function () {
        Route::get('/dashboard', [SectionController::class, 'dashboard']);
        Route::get('/', [SectionController::class, 'index']);
        Route::post('/', [SectionController::class, 'store']);
        Route::get('/{section}', [SectionController::class, 'show']);
        Route::put('/{section}', [SectionController::class, 'update']);
        Route::delete('/{section}', [SectionController::class, 'destroy']);
        Route::post('/{section}/toggle-status', [SectionController::class, 'toggleStatus']);
    });
});