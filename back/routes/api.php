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
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\ClassScholarshipController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SeriesSubjectController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\MainTeacherController;
use App\Http\Controllers\NeedController;


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

// Route de test pour school-settings
Route::get('test-school-settings', function () {
    try {
        $settings = App\Models\SchoolSetting::getSettings();
        return response()->json([
            'success' => true,
            'data' => $settings,
            'message' => 'School settings loaded successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Routes protégées
Route::middleware('auth:api')->group(function () {
    
    // Routes pour les sections
    Route::prefix('sections')->group(function () {
        Route::get('/dashboard', [SectionController::class, 'dashboard'])->middleware(['role:admin,accountant']);
        Route::get('/', [SectionController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/{section}', [SectionController::class, 'show'])->middleware(['role:admin,accountant']);
        
        Route::post('/', [SectionController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{section}', [SectionController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{section}', [SectionController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{section}/toggle-status', [SectionController::class, 'toggleStatus'])->middleware(['role:admin']);
    });

    // Routes pour les tranches de paiement
    Route::prefix('payment-tranches')->group(function () {
        Route::get('/', [PaymentTrancheController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/{paymentTranche}', [PaymentTrancheController::class, 'show'])->middleware(['role:admin,accountant']);
        Route::get('/{paymentTranche}/usage-stats', [PaymentTrancheController::class, 'usageStats'])->middleware(['role:admin,accountant']);

        Route::post('/', [PaymentTrancheController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{paymentTranche}', [PaymentTrancheController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{paymentTranche}', [PaymentTrancheController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/reorder', [PaymentTrancheController::class, 'reorder'])->middleware(['role:admin']);
    });

    // Routes pour les niveaux
    Route::prefix('levels')->group(function () {
        Route::get('/dashboard', [LevelController::class, 'dashboard'])->middleware(['role:admin,accountant']);
        Route::get('/', [LevelController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/{level}', [LevelController::class, 'show'])->middleware(['role:admin,accountant']);

        Route::post('/', [LevelController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{level}', [LevelController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{level}', [LevelController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{level}/toggle-status', [LevelController::class, 'toggleStatus'])->middleware(['role:admin']);
    });

    // Routes pour les classes
    Route::prefix('school-classes')->group(function () {
        Route::get('/dashboard', [SchoolClassController::class, 'dashboard'])->middleware(['role:admin,accountant']);
        Route::get('/', [SchoolClassController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/{schoolClass}', [SchoolClassController::class, 'show'])->middleware(['role:admin,accountant']);

        Route::post('/', [SchoolClassController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{schoolClass}', [SchoolClassController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{schoolClass}', [SchoolClassController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{schoolClass}/toggle-status', [SchoolClassController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/{schoolClass}/configure-payments', [SchoolClassController::class, 'configurePayments'])->middleware(['role:admin']);
    });

    // Routes pour les élèves
    Route::prefix('students')->middleware(['role:admin,accountant'])->group(function () {
        Route::get('/class-series/{seriesId}', [StudentController::class, 'getByClassSeries']);
        Route::post('/', [StudentController::class, 'store']);
        Route::put('/{student}', [StudentController::class, 'update']);
        Route::patch('/{student}/status', [StudentController::class, 'updateStatus']);
        Route::post('/{student}/update-with-photo', [StudentController::class, 'updateWithPhoto']);
        Route::post('/{student}/transfer-series', [StudentController::class, 'transferToSeries']);
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
    Route::prefix('accountant')->middleware(['role:admin,accountant'])->group(function () {
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

    // Routes pour les paiements (comptables et admins)
    Route::prefix('payments')->middleware(['role:admin,accountant'])->group(function () {
        Route::get('/student/{studentId}/info', [PaymentController::class, 'getStudentPaymentInfo']);
        Route::get('/student/{studentId}/history', [PaymentController::class, 'getStudentPaymentHistory']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{paymentId}/receipt', [PaymentController::class, 'generateReceipt']);
        Route::get('/stats', [PaymentController::class, 'getPaymentStats']);
    });

    // Routes pour les paramètres de l'école
    Route::prefix('school-settings')->group(function () {
        Route::get('/', [SchoolSettingsController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/logo', [SchoolSettingsController::class, 'getLogo'])->middleware(['role:admin,accountant']);
        
        // Routes admin uniquement
        Route::put('/', [SchoolSettingsController::class, 'update'])->middleware(['role:admin']);
        Route::post('/', [SchoolSettingsController::class, 'update'])->middleware(['role:admin']); // Pour FormData avec _method=PUT
    });

    // Routes pour les bourses de classe
    Route::prefix('class-scholarships')->middleware(['role:admin'])->group(function () {
        Route::get('/', [ClassScholarshipController::class, 'index']);
        Route::post('/', [ClassScholarshipController::class, 'store']);
        Route::get('/{id}', [ClassScholarshipController::class, 'show']);
        Route::put('/{id}', [ClassScholarshipController::class, 'update']);
        Route::delete('/{id}', [ClassScholarshipController::class, 'destroy']);
        Route::get('/class/{classId}', [ClassScholarshipController::class, 'getByClass']);
    });

    // Routes pour les rapports (comptables et admins)
    Route::prefix('reports')->middleware(['role:admin,accountant'])->group(function () {
        Route::get('/insolvable', [ReportsController::class, 'getInsolvableReport']);
        Route::get('/payments', [ReportsController::class, 'getPaymentsReport']);
        Route::get('/rame', [ReportsController::class, 'getRameReport']);
        Route::get('/recovery', [ReportsController::class, 'getRecoveryReport']);
        Route::get('/export-pdf', [ReportsController::class, 'exportPdf']);
    });


    // Routes pour la gestion des utilisateurs (admin uniquement)
    Route::prefix('user-management')->middleware(['role:admin'])->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::get('/stats', [UserManagementController::class, 'getStats']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::get('/{id}', [UserManagementController::class, 'show']);
        Route::put('/{id}', [UserManagementController::class, 'update']);
        Route::post('/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
        Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);
        Route::delete('/{id}', [UserManagementController::class, 'destroy']);
    });

    // Routes d'upload de photos
    Route::post('upload-photo', [PhotoUploadController::class, 'upload']);

    // Routes pour les matières
    Route::prefix('subjects')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [SubjectController::class, 'index'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/{subject}', [SubjectController::class, 'show'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/series/{classSeries}', [SubjectController::class, 'getForSeries'])->middleware(['role:admin,accountant,teacher']);
        
        // Routes pour administrateurs uniquement (gestion)
        Route::post('/', [SubjectController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{subject}', [SubjectController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{subject}', [SubjectController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/series/{classSeries}/configure', [SubjectController::class, 'configureForSeries'])->middleware(['role:admin']);
    });

    // Routes pour les enseignants
    Route::prefix('teachers')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [TeacherController::class, 'index'])->middleware(['role:admin,accountant']);
        Route::get('/{teacher}', [TeacherController::class, 'show'])->middleware(['role:admin,accountant']);
        Route::get('/{teacher}/stats', [TeacherController::class, 'getStats'])->middleware(['role:admin,accountant']);
        
        // Routes pour administrateurs uniquement (gestion)
        Route::post('/', [TeacherController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{teacher}', [TeacherController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{teacher}/toggle-status', [TeacherController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/{teacher}/assign-subjects', [TeacherController::class, 'assignSubjects'])->middleware(['role:admin']);
        Route::post('/{teacher}/remove-assignment', [TeacherController::class, 'removeAssignment'])->middleware(['role:admin']);
    });

    // Routes pour la configuration des matières par série
    Route::prefix('series-subjects')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [SeriesSubjectController::class, 'index'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/class/{schoolClass}', [SeriesSubjectController::class, 'getByClass'])->middleware(['role:admin,accountant,teacher']);
        
        // Routes pour administrateurs uniquement (gestion)
        Route::post('/', [SeriesSubjectController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{seriesSubject}', [SeriesSubjectController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{seriesSubject}', [SeriesSubjectController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{seriesSubject}/toggle-status', [SeriesSubjectController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/class/{schoolClass}/bulk-configure', [SeriesSubjectController::class, 'bulkConfigure'])->middleware(['role:admin']);
    });

    // Routes pour les affectations d'enseignants
    Route::prefix('teacher-assignments')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [TeacherAssignmentController::class, 'index'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/teacher/{teacher}', [TeacherAssignmentController::class, 'getByTeacher'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/teacher/{teacher}/available-subjects', [TeacherAssignmentController::class, 'getAvailableSubjects'])->middleware(['role:admin,accountant']);
        
        // Routes pour administrateurs uniquement (gestion)
        Route::post('/', [TeacherAssignmentController::class, 'store'])->middleware(['role:admin']);
        Route::delete('/{assignment}', [TeacherAssignmentController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{assignment}/toggle-status', [TeacherAssignmentController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/teacher/{teacher}/bulk-assign', [TeacherAssignmentController::class, 'bulkAssign'])->middleware(['role:admin']);
    });

    // Routes pour les professeurs principaux
    Route::prefix('main-teachers')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [MainTeacherController::class, 'index'])->middleware(['role:admin,accountant,teacher']);
        Route::get('/classes-without-main-teacher', [MainTeacherController::class, 'getClassesWithoutMainTeacher'])->middleware(['role:admin,accountant']);
        Route::get('/available-teachers', [MainTeacherController::class, 'getAvailableTeachers'])->middleware(['role:admin,accountant']);
        
        // Routes pour administrateurs uniquement (gestion)
        Route::post('/', [MainTeacherController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{mainTeacher}', [MainTeacherController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{mainTeacher}', [MainTeacherController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{mainTeacher}/toggle-status', [MainTeacherController::class, 'toggleStatus'])->middleware(['role:admin']);
    });

    // Routes pour les besoins
    Route::prefix('needs')->group(function () {
        // Routes pour tous les utilisateurs authentifiés
        Route::post('/', [NeedController::class, 'store']); // Soumettre un besoin
        Route::get('/my-needs', [NeedController::class, 'myNeeds']); // Voir ses propres besoins
        Route::get('/{need}', [NeedController::class, 'show']); // Voir un besoin spécifique (avec contrôle d'accès)
        
        // Routes pour administrateurs uniquement
        Route::get('/', [NeedController::class, 'index'])->middleware(['role:admin']); // Lister tous les besoins
        Route::post('/{need}/approve', [NeedController::class, 'approve'])->middleware(['role:admin']); // Approuver
        Route::post('/{need}/reject', [NeedController::class, 'reject'])->middleware(['role:admin']); // Rejeter
        Route::get('/statistics/summary', [NeedController::class, 'statistics'])->middleware(['role:admin']); // Statistiques
        Route::post('/test-whatsapp', [NeedController::class, 'testWhatsApp'])->middleware(['role:admin']); // Test WhatsApp
    });

});