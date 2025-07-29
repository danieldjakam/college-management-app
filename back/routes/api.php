<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\PaymentTrancheController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\SchoolYearController;

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
        Route::get('/{paymentTranche}/usage-stats', [PaymentTrancheController::class, 'usageStats']);
        Route::put('/{paymentTranche}', [PaymentTrancheController::class, 'update']);
        Route::delete('/{paymentTranche}', [PaymentTrancheController::class, 'destroy']);
        Route::post('/reorder', [PaymentTrancheController::class, 'reorder']);
    });

    // Routes pour les niveaux
    Route::prefix('levels')->group(function () {
        Route::get('/dashboard', [LevelController::class, 'dashboard']);
        Route::get('/', [LevelController::class, 'index']);
        Route::post('/', [LevelController::class, 'store']);
        Route::get('/{level}', [LevelController::class, 'show']);
        Route::put('/{level}', [LevelController::class, 'update']);
        Route::delete('/{level}', [LevelController::class, 'destroy']);
        Route::post('/{level}/toggle-status', [LevelController::class, 'toggleStatus']);
    });

    // Routes pour les classes
    Route::prefix('school-classes')->group(function () {
        Route::get('/dashboard', [SchoolClassController::class, 'dashboard']);
        Route::get('/', [SchoolClassController::class, 'index']);
        Route::post('/', [SchoolClassController::class, 'store']);
        Route::get('/{schoolClass}', [SchoolClassController::class, 'show']);
        Route::put('/{schoolClass}', [SchoolClassController::class, 'update']);
        Route::delete('/{schoolClass}', [SchoolClassController::class, 'destroy']);
        Route::post('/{schoolClass}/toggle-status', [SchoolClassController::class, 'toggleStatus']);
        Route::post('/{schoolClass}/configure-payments', [SchoolClassController::class, 'configurePayments']);
    });

    // Routes pour les élèves
    Route::prefix('students')->group(function () {
        Route::get('/class-series/{seriesId}', [StudentController::class, 'getByClassSeries']);
        Route::post('/', [StudentController::class, 'store']);
        Route::put('/{student}', [StudentController::class, 'update']);
        Route::post('/{student}/update-with-photo', [StudentController::class, 'updateWithPhoto']);
        Route::delete('/{student}', [StudentController::class, 'destroy']);
        Route::get('/export-csv/{seriesId}', [StudentController::class, 'exportCsv']);
        Route::get('/export-pdf/{seriesId}', [StudentController::class, 'exportPdf']);
        Route::post('/import-csv', [StudentController::class, 'importCsv']);
        Route::get('/school-years', [StudentController::class, 'getSchoolYears']);
        Route::post('/reorder', [StudentController::class, 'reorder']);
        Route::post('/class-series/{seriesId}/sort-alphabetically', [StudentController::class, 'sortAlphabetically']);
    });

    // Routes utilisateurs (pour compatibilité)
    Route::prefix('users')->group(function () {
        Route::get('/getTeacherOrAdmin', [UserController::class, 'getTeacherOrAdmin']);
        Route::get('/getTeacherOrAdmin/', [UserController::class, 'getTeacherOrAdmin']); // With trailing slash
        Route::get('/getInfos', [UserController::class, 'getInfos']);
        Route::get('/all', [UserController::class, 'all']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
    });

    // Routes pour les comptables
    Route::prefix('accountant')->group(function () {
        Route::get('/dashboard', [AccountantController::class, 'dashboard']);
        Route::get('/classes', [AccountantController::class, 'getClasses']);
        Route::get('/classes/{classId}/series', [AccountantController::class, 'getClassSeries']);
        Route::get('/series/{seriesId}/students', [AccountantController::class, 'getSeriesStudents']);
        Route::get('/students/{studentId}', [AccountantController::class, 'getStudent']);
    });

    // Routes pour les années scolaires
    Route::prefix('school-years')->group(function () {
        // Routes accessibles aux admins et comptables (avec authentification)
        Route::get('/active', [SchoolYearController::class, 'getActiveYears']);
        Route::get('/user-working-year', [SchoolYearController::class, 'getUserWorkingYear']);
        Route::post('/set-user-working-year', [SchoolYearController::class, 'setUserWorkingYear']);
        
        // Routes pour administrateurs uniquement
        Route::get('/', [SchoolYearController::class, 'index'])->middleware('role:admin');
        Route::post('/', [SchoolYearController::class, 'store'])->middleware('role:admin');
        Route::put('/{schoolYear}', [SchoolYearController::class, 'update'])->middleware('role:admin');
        Route::post('/{schoolYear}/set-current', [SchoolYearController::class, 'setCurrent'])->middleware('role:admin');
    });
});