<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\PaymentTrancheController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\UserController;

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

// Routes protégées
Route::middleware('auth:api')->group(function () {
    
    // Routes pour les sections
    Route::prefix('sections')->group(function () {
        Route::get('/dashboard', [SectionController::class, 'dashboard']);
        Route::get('/', [SectionController::class, 'index']);
        Route::post('/', [SectionController::class, 'store']);
        Route::get('/{section}', [SectionController::class, 'show']);
        Route::put('/{section}', [SectionController::class, 'update']);
        Route::delete('/{section}', [SectionController::class, 'destroy']);
        Route::post('/{section}/toggle-status', [SectionController::class, 'toggleStatus']);
    });

    // Routes pour les tranches de paiement
    Route::prefix('payment-tranches')->group(function () {
        Route::get('/', [PaymentTrancheController::class, 'index']);
        Route::post('/', [PaymentTrancheController::class, 'store']);
        Route::get('/{paymentTranche}', [PaymentTrancheController::class, 'show']);
        Route::put('/{paymentTranche}', [PaymentTrancheController::class, 'update']);
        Route::delete('/{paymentTranche}', [PaymentTrancheController::class, 'destroy']);
        Route::post('/reorder', [PaymentTrancheController::class, 'reorder']);
    });

    // Routes pour les niveaux
    Route::prefix('levels')->group(function () {
        Route::get('/', [LevelController::class, 'index']);
        Route::post('/', [LevelController::class, 'store']);
        Route::get('/{level}', [LevelController::class, 'show']);
        Route::put('/{level}', [LevelController::class, 'update']);
        Route::delete('/{level}', [LevelController::class, 'destroy']);
    });

    // Routes pour les classes
    Route::prefix('school-classes')->group(function () {
        Route::get('/', [SchoolClassController::class, 'index']);
        Route::post('/', [SchoolClassController::class, 'store']);
        Route::get('/{schoolClass}', [SchoolClassController::class, 'show']);
        Route::put('/{schoolClass}', [SchoolClassController::class, 'update']);
        Route::delete('/{schoolClass}', [SchoolClassController::class, 'destroy']);
        Route::post('/{schoolClass}/configure-payments', [SchoolClassController::class, 'configurePayments']);
    });

    // Routes utilisateurs (pour compatibilité)
    Route::prefix('users')->group(function () {
        Route::get('/getTeacherOrAdmin', [UserController::class, 'getTeacherOrAdmin']);
        Route::get('/getTeacherOrAdmin/', [UserController::class, 'getTeacherOrAdmin']); // With trailing slash
        Route::get('/getInfos', [UserController::class, 'getInfos']);
        Route::get('/all', [UserController::class, 'all']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
    });
});