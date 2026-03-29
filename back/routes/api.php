<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PrincipalDashboardController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\PaymentTrancheController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DocumentaryFeeController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\ClassScholarshipController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SeriesSubjectController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\MainTeacherController;
use App\Http\Controllers\NeedController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\StudentRameController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\ClassesSeriesController;
use App\Http\Controllers\TeacherAttendanceController;
use App\Http\Controllers\TeacherImportController;
use App\Http\Controllers\TeacherFixController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\StaffAttendanceReportController;
use App\Http\Controllers\DemandeExplicationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\AcademicPeriodController;
use App\Http\Controllers\EvaluationConfigController;
use App\Http\Controllers\GeolocationZoneController;
use App\Http\Controllers\GradingScaleController;
use App\Http\Controllers\SequenceController;
use App\Http\Controllers\TrimesterController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MobileAttendanceController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\MarkSheetController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\CompetenceController;
use App\Http\Controllers\StudentCardController;
use App\Http\Controllers\HonorRollController;


// Routes d'authentification
Route::prefix('auth')->group(function () {
    // Routes publiques (pas d'authentification requise)
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Routes protégées (authentification JWT requise)
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('change-password', [AuthController::class, 'changePassword']);
    Route::put('update-profile', [AuthController::class, 'updateProfile']);
});

// Dashboard Admin (accès complet sauf finances)
Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard/stats-by-period', [AdminDashboardController::class, 'getStatsByPeriod']);
});

// Routes pour le tableau de bord du principal
Route::prefix('principal')->middleware(['auth:api', 'role:principal'])->group(function () {
    Route::get('/dashboard', [PrincipalDashboardController::class, 'index']);
});

// Route de test
Route::get('test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Routes de test pour debug authentification et scan QR
Route::prefix('test')->group(function () {
    // Test simple sans auth
    Route::post('scan-qr-no-auth', function (Illuminate\Http\Request $request) {
        \Log::info('=== TEST SCAN QR SANS AUTH ===', [
            'timestamp' => now()->toISOString(),
            'ip_address' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_headers' => $request->headers->all(),
            'request_data' => $request->all(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Test endpoint accessible',
            'data' => [
                'received_data' => $request->all(),
                'timestamp' => now()->toISOString(),
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'inconnu'
            ]
        ]);
    });
    
    // Test avec middleware debug JWT
    Route::post('scan-qr-with-debug-auth', function (Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authentification JWT réussie',
            'authenticated_user' => auth('api')->user(),
            'request_data' => $request->all()
        ]);
    })->middleware('debug.jwt');
    
    // Test endpoint staff attendance avec logs détaillés
    Route::post('staff-attendance-debug', [App\Http\Controllers\StaffAttendanceController::class, 'scanQR'])
        ->middleware('debug.jwt');
        
    // Endpoint pour obtenir tous les staff types disponibles (pour la liste déroulante)
    Route::get('staff-types', function () {
        try {
            // Récupérer tous les staff_types distincts depuis les attendances
            $staffTypesFromAttendances = App\Models\StaffAttendance::distinct('staff_type')
                ->whereNotNull('staff_type')
                ->pluck('staff_type');
                
            // Récupérer tous les rôles des utilisateurs actifs
            $rolesFromUsers = App\Models\User::where('is_active', true)
                ->whereIn('role', ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'])
                ->distinct('role')
                ->pluck('role');
                
            // Mapper les rôles vers des labels français
            $roleLabels = [
                'principal' => 'Principal',
                'teacher' => 'Enseignant',
                'accountant' => 'Comptable',
                'admin' => 'Administrateur',
                'surveillant_general' => 'Surveillant Général',
                'comptable_superieur' => 'Comptable Supérieur',
                'general_accountant' => 'Comptable Général',
                'secretaire' => 'Secrétaire',
                'responsable_pedagogique' => 'Responsable Pédagogique',
                'dean_of_studies' => 'Doyen des Études',
                'censeur_esg' => 'Censeur ESG',
                'censeur' => 'Censeur',
                'surveillant_secteur' => 'Surveillant de Secteur',
                'caissiere' => 'Caissière',
                'bibliothecaire' => 'Bibliothécaire',
                'chef_travaux' => 'Chef des Travaux',
                'chef_securite' => 'Chef Sécurité',
                'reprographe' => 'Reprographe'
            ];
            
            $allRoles = $rolesFromUsers->unique()->values()->map(function($role) use ($roleLabels) {
                return [
                    'value' => $role,
                    'label' => $roleLabels[$role] ?? ucfirst($role)
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'staff_types_from_attendances' => $staffTypesFromAttendances,
                    'available_roles' => $allRoles,
                    'role_labels' => $roleLabels
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des types de personnel: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Test endpoint pour voir les présences journalières sans auth
    Route::get('daily-attendance-debug', function () {
        try {
            $date = request()->get('date', now()->format('Y-m-d'));
            
            \Log::info('=== TEST DAILY ATTENDANCE DEBUG ===', [
                'date' => $date,
                'request_params' => request()->all()
            ]);
            
            $attendances = App\Models\StaffAttendance::with(['user', 'supervisor'])
                ->whereDate('attendance_date', $date)
                ->orderBy('scanned_at', 'desc')
                ->get();
                
            $allStaffTypes = App\Models\StaffAttendance::distinct('staff_type')
                ->whereNotNull('staff_type')
                ->pluck('staff_type');
                
            // Grouper par type de personnel
            $groupedAttendances = $attendances->groupBy('staff_type');
            
            \Log::info('DAILY ATTENDANCE RESULTS', [
                'total_attendances' => $attendances->count(),
                'staff_types_found' => $allStaffTypes->toArray(),
                'grouped_count' => $groupedAttendances->map->count(),
                'sample_attendance' => $attendances->first() ? [
                    'user_name' => $attendances->first()->user->name ?? 'N/A',
                    'staff_type' => $attendances->first()->staff_type,
                    'event_type' => $attendances->first()->event_type,
                    'scanned_at' => $attendances->first()->scanned_at,
                ] : null
            ]);
            
            return response()->json([
                'success' => true,
                'debug_info' => [
                    'date' => $date,
                    'total_attendances' => $attendances->count(),
                    'staff_types_available' => $allStaffTypes,
                    'grouped_by_type' => $groupedAttendances->map->count(),
                ],
                'data' => [
                    'attendances' => $attendances->map(function($att) {
                        return [
                            'id' => $att->id,
                            'user_name' => $att->user->name ?? 'Utilisateur inconnu',
                            'staff_type' => $att->staff_type,
                            'event_type' => $att->event_type,
                            'scanned_at' => $att->scanned_at,
                            'is_present' => $att->is_present,
                            'late_minutes' => $att->late_minutes,
                        ];
                    }),
                    'stats' => [
                        'total_present' => $attendances->where('is_present', true)->count(),
                        'by_staff_type' => $groupedAttendances->map(function($typeAttendances) {
                            return [
                                'total' => $typeAttendances->count(),
                                'present' => $typeAttendances->where('is_present', true)->count(),
                            ];
                        })
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('DAILY ATTENDANCE DEBUG ERROR', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur debug: ' . $e->getMessage(),
                'error_details' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    });
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

// Test route pour inventory sans auth
Route::get('test-inventory', function () {
    try {
        return response()->json([
            'success' => true,
            'message' => 'Route inventory accessible',
            'items_count' => \App\Models\InventoryItem::count(),
            'timestamp' => now()->toDateTimeString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/user-management/{id}/professional-card', function () {
    return response('', 204)
        ->header('Access-Control-Allow-Origin', 'http://admin.cpb-douala.com')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Max-Age', '1728000');
});
Route::get('/students', function () {
    return response('', 204)
        ->header('Access-Control-Allow-Origin', 'http://admin.cpb-douala.com')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Max-Age', '1728000');
});
Route::post('/students', function () {
    return response('', 201)
        ->header('Access-Control-Allow-Origin', 'http://admin.cpb-douala.com')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Max-Age', '1728000');
});
// Routes protégées
Route::middleware('auth:api')->group(function () {

    // Routes pour les sections
    Route::prefix('sections')->group(function () {
        Route::get('/dashboard', [SectionController::class, 'dashboard'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/', [SectionController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/{section}', [SectionController::class, 'show'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

        // Export routes
        Route::get('/export/excel', [SectionController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/csv', [SectionController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/pdf', [SectionController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/importable', [SectionController::class, 'exportImportable'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/template/download', [SectionController::class, 'downloadTemplate'])->middleware(['role:admin']);


        Route::post('/', [SectionController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{section}', [SectionController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{section}', [SectionController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{section}/toggle-status', [SectionController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/import/csv', [SectionController::class, 'importCsv'])->middleware(['role:admin']);
    });

    // Routes pour les tranches de paiement
    Route::prefix('payment-tranches')->group(function () {
        Route::get('/', [PaymentTrancheController::class, 'index'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/{paymentTranche}', [PaymentTrancheController::class, 'show'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/{paymentTranche}/usage-stats', [PaymentTrancheController::class, 'usageStats'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);

        Route::post('/', [PaymentTrancheController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{paymentTranche}', [PaymentTrancheController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{paymentTranche}', [PaymentTrancheController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/reorder', [PaymentTrancheController::class, 'reorder'])->middleware(['role:admin']);
    });

    // Routes pour les niveaux
    Route::prefix('levels')->group(function () {
        Route::get('/dashboard', [LevelController::class, 'dashboard'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/', [LevelController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,surveillant_general,surveillant_secteur']);
        Route::get('/{level}', [LevelController::class, 'show'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/{level}/series', [LevelController::class, 'getSeries'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

        // Export routes
        Route::get('/export/excel', [LevelController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/csv', [LevelController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/pdf', [LevelController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/importable', [LevelController::class, 'exportImportable'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/template/download', [LevelController::class, 'downloadTemplate'])->middleware(['role:admin']);

        Route::post('/', [LevelController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{level}', [LevelController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{level}', [LevelController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{level}/toggle-status', [LevelController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/import/csv', [LevelController::class, 'importCsv'])->middleware(['role:admin']);
    });

    // Routes pour les classes
    Route::prefix('school-classes')->group(function () {
        Route::get('/dashboard', [SchoolClassController::class, 'dashboard'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/', [SchoolClassController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/{id}/students', [SchoolClassController::class, 'getStudents'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/{id}/series', [SchoolClassController::class, 'getSeries'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/{schoolClass}', [SchoolClassController::class, 'show'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);

        // Export routes
        Route::get('/export/excel', [SchoolClassController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/csv', [SchoolClassController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/pdf', [SchoolClassController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/importable', [SchoolClassController::class, 'exportImportable'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/template/download', [SchoolClassController::class, 'downloadTemplate'])->middleware(['role:admin']);

        Route::post('/', [SchoolClassController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{schoolClass}', [SchoolClassController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{schoolClass}', [SchoolClassController::class, 'destroy'])->middleware(['role:admin']);
        Route::post('/{schoolClass}/toggle-status', [SchoolClassController::class, 'toggleStatus'])->middleware(['role:admin']);
        Route::post('/{schoolClass}/configure-payments', [SchoolClassController::class, 'configurePayments'])->middleware(['role:admin']);
        Route::post('/import/csv', [SchoolClassController::class, 'importCsv'])->middleware(['role:admin']);
    });

    // Routes pour les séries de classes (ClassSeries model)
    Route::prefix('class-series')->group(function () {
        Route::get('/', [\App\Http\Controllers\ClassSeriesController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/{id}', [\App\Http\Controllers\ClassSeriesController::class, 'show'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::post('/', [\App\Http\Controllers\ClassSeriesController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{id}', [\App\Http\Controllers\ClassSeriesController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{id}', [\App\Http\Controllers\ClassSeriesController::class, 'destroy'])->middleware(['role:admin']);
    });

    // Routes pour les séries
    Route::prefix('series')->group(function () {
        Route::get('/', [SeriesController::class, 'index'])->middleware(['role:admin,secretaire,accountant,comptable']);

        // Export routes
        Route::get('/export/excel', [SeriesController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/csv', [SeriesController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/pdf', [SeriesController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/importable', [SeriesController::class, 'exportImportable'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/template/download', [SeriesController::class, 'downloadTemplate'])->middleware(['role:admin']);

        Route::post('/import/csv', [SeriesController::class, 'importCsv'])->middleware(['role:admin']);
    });

    // Routes pour les classes et séries combinées
    Route::prefix('classes-series')->group(function () {
        // Export routes
        Route::get('/export/excel', [ClassesSeriesController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/csv', [ClassesSeriesController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/export/pdf', [ClassesSeriesController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable']);
        Route::get('/template/download', [ClassesSeriesController::class, 'downloadTemplate'])->middleware(['role:admin']);
        Route::post('/import/csv', [ClassesSeriesController::class, 'importCsv'])->middleware(['role:admin']);
    });

    // Route spéciale pour les enseignants pour voir les élèves de leurs classes (AVANT le groupe)
    Route::get('/students/class/{classId}', [StudentController::class, 'getByClass'])->middleware(['role:admin,secretaire,accountant,comptable_superieur,teacher,bibliothecaire,surveillant_general,surveillant_secteur,id_card_manager']);

    // Routes pour les élèves
    Route::prefix('students')->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,teacher'])->group(function () {
        Route::get('/getAll', [StudentController::class, 'getAll']);
        Route::get('/class-series/{seriesId}', [StudentController::class, 'getByClassSeries']);

        // Export routes - amélioration des routes existantes
        Route::get('/export/excel', [StudentController::class, 'exportStudentsExcel']);
        Route::get('/export/csv', [StudentController::class, 'exportStudentsCsv']);
        Route::get('/export/pdf', [StudentController::class, 'exportStudentsPdf']);
        Route::get('/export/csv/{seriesId}', [StudentController::class, 'exportCsv']);
        Route::get('/export/excel/{seriesId}', [StudentController::class, 'exportExcel']);
        Route::get('/export/pdf/{seriesId}', [StudentController::class, 'exportPdf']);
        Route::get('/export/importable', [StudentController::class, 'exportImportable']);
        Route::get('/template/download', [StudentController::class, 'downloadTemplate']);

        // Carte scolaire
        Route::get('/{id}/card', [StudentController::class, 'generateStudentCard']);

        // Servir les photos avec CORS headers
        Route::get('/{id}/photo', [StudentController::class, 'getPhoto']);

        Route::post('/', [StudentController::class, 'store']);
        Route::put('/{student}', [StudentController::class, 'update']);
        Route::patch('/{student}/status', [StudentController::class, 'updateStatus']);
        Route::post('/{student}/update-with-photo', [StudentController::class, 'updateWithPhoto']);
        Route::post('/{student}/transfer-series', [StudentController::class, 'transferToSeries']);
        Route::post('/{student}/transfer-within-class', [StudentController::class, 'transferWithinClass']);
        Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware(['role:admin,principal,accountant,comptable_superieur']);
        Route::post('/import/csv', [StudentController::class, 'importCsv']);
        Route::post('/import/excel', [StudentController::class, 'importExcel']);
        Route::post('/series/{seriesId}/import', [StudentController::class, 'importForSeries']);
        Route::post('/series/{seriesId}/import/csv', [StudentController::class, 'importCsvForSeries']);
        Route::get('/school-years', [StudentController::class, 'getSchoolYears']);
        Route::post('/reorder', [StudentController::class, 'reorder']);
        Route::post('/class-series/{seriesId}/sort-alphabetically', [StudentController::class, 'sortAlphabetically']);
        Route::post('/bulk-upload-photos', [StudentController::class, 'bulkUploadPhotos']);
    });

    // Routes pour les cartes d'identité scolaires
    Route::prefix('student-cards')->middleware(['role:admin,principal,secretaire,id_card_manager'])->group(function () {
        // Générer les cartes pour une classe entière (10 par page)
        Route::post('/class/{classId}/generate', [StudentCardController::class, 'generateClassCards']);

        // Générer une carte individuelle
        Route::post('/student/{studentId}/generate', [StudentCardController::class, 'generateSingleCard']);

        // Prévisualiser la carte d'un élève
        Route::post('/student/{studentId}/preview', [StudentCardController::class, 'previewCard']);

        // Vérifier une carte via QR Code (route publique pour scan)
        Route::get('/verify/{matricule}', [StudentCardController::class, 'verifyCard'])->withoutMiddleware(['role:admin,principal,secretaire,id_card_manager']);
    });

    // Routes pour les paramètres de mise en page des cartes
    Route::prefix('card-layout-settings')->middleware(['role:admin,principal,secretaire,id_card_manager'])->group(function () {
        Route::get('/', [App\Http\Controllers\CardLayoutSettingController::class, 'index']);
        Route::post('/update', [App\Http\Controllers\CardLayoutSettingController::class, 'update'])->middleware(['role:admin,principal,id_card_manager']);
        Route::post('/reset', [App\Http\Controllers\CardLayoutSettingController::class, 'reset'])->middleware(['role:admin,principal,id_card_manager']);
    });

    // Routes utilisateurs (pour compatibilité)
    Route::prefix('users')->group(function () {
        Route::get('/getTeacherOrAdmin', [UserController::class, 'getTeacherOrAdmin']);
        Route::get('/getTeacherOrAdmin/', [UserController::class, 'getTeacherOrAdmin']); // With trailing slash
        Route::get('/getInfos', [UserController::class, 'getInfos']);
        Route::get('/all', [UserController::class, 'all']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/change-password', [UserController::class, 'changePassword']);
    });

    // Routes pour les gestionnaires de cartes d'identité
    Route::prefix('id-card-manager')->middleware(['role:id_card_manager'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\IdCardManagerController::class, 'dashboard']);
        Route::get('/classes', [\App\Http\Controllers\IdCardManagerController::class, 'getClasses']);
        Route::get('/classes/{classId}/students', [\App\Http\Controllers\IdCardManagerController::class, 'getClassStudents']);
        Route::get('/students/{studentId}', [\App\Http\Controllers\IdCardManagerController::class, 'getStudent']);
        Route::post('/students/{studentId}/update-photo', [\App\Http\Controllers\IdCardManagerController::class, 'updateStudentPhoto']);
    });

    // Routes pour les comptables
    Route::prefix('accountant')->middleware(['role:admin,secretaire,accountant,comptable_superieur'])->group(function () {
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
    Route::prefix('payments')->middleware(['role:admin,secretaire,accountant,comptable_superieur'])->group(function () {
        Route::get('/student/{studentId}/info', [PaymentController::class, 'getStudentPaymentInfo']);
        Route::get('/student/{studentId}/info-with-discount', [PaymentController::class, 'getStudentPaymentInfoWithDiscount']);
        Route::get('/student/{studentId}/history', [PaymentController::class, 'getStudentPaymentHistory']);
        Route::post('/student/{studentId}/calculate-with-date', [PaymentController::class, 'calculatePaymentWithDate']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{paymentId}/receipt', [PaymentController::class, 'generateReceipt']);
        Route::get('/{paymentId}/receipt/pdf', [PaymentController::class, 'downloadReceiptPDF']);
        Route::get('/stats', [PaymentController::class, 'getPaymentStats']);
        Route::post('/listing-report', [PaymentController::class, 'generatePaymentListingReport']);
        Route::post('/tranche-lists-report', [PaymentController::class, 'generateTrancheListsReport']);
        Route::get('/tranches', [PaymentController::class, 'getPaymentTranches']);

        // Routes de gestion des paiements pour comptables et comptable_superieur
        Route::middleware(['role:accountant,comptable_superieur'])->group(function () {
            Route::get('/pending', [PaymentController::class, 'getPendingPayments']);
            Route::get('/management', [PaymentController::class, 'getAllPaymentsForManagement']);
            Route::put('/{paymentId}', [PaymentController::class, 'update']);
            Route::post('/{paymentId}/validate', [PaymentController::class, 'validatePayment']);
            Route::post('/{paymentId}/cancel', [PaymentController::class, 'cancelPayment']);
            Route::delete('/student/{studentId}/history', [PaymentController::class, 'deleteStudentPaymentHistory']);
        });
    });

    // Routes pour les frais de dossiers et pénalités (comptables et admins)
    Route::prefix('documentary-fees')->middleware(['role:admin,secretaire,accountant,comptable_superieur'])->group(function () {
        Route::get('/', [DocumentaryFeeController::class, 'index']);
        Route::post('/', [DocumentaryFeeController::class, 'store']);
        Route::get('/statistics', [DocumentaryFeeController::class, 'getStatistics']);
        Route::post('/report/period', [DocumentaryFeeController::class, 'generatePeriodReport']);
        Route::get('/{id}', [DocumentaryFeeController::class, 'show']);
        Route::put('/{id}', [DocumentaryFeeController::class, 'update']);
        Route::delete('/{id}', [DocumentaryFeeController::class, 'destroy']);
        Route::get('/{id}/receipt', [DocumentaryFeeController::class, 'generateReceipt']);
    });

    // Route pour la recherche d'étudiants dans les frais de dossiers
    Route::get('/students/search', [DocumentaryFeeController::class, 'searchStudents'])
        ->middleware(['role:admin,secretaire,accountant,comptable_superieur']);

    // Routes pour les paramètres de l'école
    Route::prefix('school-settings')->group(function () {
        Route::get('/', [SchoolSettingsController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable,surveillant_general,comptable_superieur,teacher,id_card_manager']);
        Route::get('/logo', [SchoolSettingsController::class, 'getLogo'])->middleware(['role:admin,principal,secretaire,accountant,comptable,surveillant_general,comptable_superieur,teacher,id_card_manager']);

        // Routes admin uniquement
        Route::put('/', [SchoolSettingsController::class, 'update'])->middleware(['role:admin']);
        Route::post('/', [SchoolSettingsController::class, 'update'])->middleware(['role:admin']); // Pour FormData avec _method=PUT
        Route::post('/test-whatsapp', [SchoolSettingsController::class, 'testWhatsApp'])->middleware(['role:admin']); // Test WhatsApp
    });

    // Route spécifique pour servir les fichiers logos de manière sécurisée (pour les exports PDF)
    Route::get('/school/logo/{filename}', function($filename) {
        $logoPath = storage_path('app/public/logos/' . $filename);
        
        if (!file_exists($logoPath)) {
            abort(404, 'Logo not found');
        }

        $mimeType = mime_content_type($logoPath);
        if (!str_starts_with($mimeType, 'image/')) {
            abort(403, 'Invalid file type');
        }

        return response()->file($logoPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
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
    Route::prefix('reports')->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur'])->group(function () {
        Route::get('/insolvable', [ReportsController::class, 'getInsolvableReport']);
        Route::get('/solvable', [ReportsController::class, 'getSolvableReport']);
        Route::get('/payments', [ReportsController::class, 'getPaymentsReport']);
        Route::get('/rame', [ReportsController::class, 'getRameReport']);
        Route::get('/recovery', [ReportsController::class, 'getRecoveryReport']);
        Route::get('/collection-summary', [ReportsController::class, 'getCollectionSummaryReport']);
        Route::get('/payment-details', [ReportsController::class, 'getPaymentDetailsReport']);
        Route::get('/scholarships-discounts', [ReportsController::class, 'getScholarshipsDiscountsReport']);
        Route::get('/collection-details', [ReportsController::class, 'getCollectionDetailsReport']);
        Route::get('/series-collection-summary', [ReportsController::class, 'getSeriesCollectionSummary']);

        // Nouveau rapport de détail des paiements des frais de scolarité
        Route::get('/school-fee-payment-details', [ReportsController::class, 'getSchoolFeePaymentDetails']);
        Route::get('/school-fee-payment-details/export-pdf', [ReportsController::class, 'exportSchoolFeePaymentDetailsPdf']);

        // Rapport d'état de recouvrement
        Route::get('/recovery-status', [ReportsController::class, 'getRecoveryStatus']);

        // Certificats de scolarité
        Route::get('/school-certificates', [ReportsController::class, 'generateSchoolCertificates']);
        Route::get('/school-certificate/preview/{studentId}', [ReportsController::class, 'previewSchoolCertificate']);
        Route::get('/school-certificates/download', [ReportsController::class, 'downloadSchoolCertificates']);

        // Nouveaux rapports financiers
        Route::get('/detailed-collection', [ReportsController::class, 'getDetailedCollectionReport']);
        Route::get('/class-school-fees', [ReportsController::class, 'getClassSchoolFeesReport']);
        Route::get('/class-school-fees/export-pdf', [ReportsController::class, 'exportClassSchoolFeesPdf']);

        Route::get('/export-pdf', [ReportsController::class, 'exportPdf']);
        Route::get('/download-pdf', [ReportsController::class, 'downloadPdf']);
    });


    // Routes pour la gestion des utilisateurs (admin, principal et secrétaire)
    Route::prefix('user-management')->middleware(['auth:api', 'role:admin,principal,secretaire'])->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::get('/stats', [UserManagementController::class, 'getStats']);

        // DEBUG: Route temporaire pour chercher Mr Boum
        Route::get('/find-mr-boum', [UserManagementController::class, 'findMrBoum']);
        
        // Routes d'export (AVANT les routes avec ID)
        Route::get('/export/administrative-staff/pdf', [UserManagementController::class, 'exportAdministrativeStaffPdf']);
        Route::get('/generate-all-staff-badges', [UserManagementController::class, 'generateAllStaffBadges']);
        
        Route::post('/', [UserManagementController::class, 'store']);
        
        // Routes avec paramètres ID (APRÈS les routes spécifiques)
        Route::get('/{id}', [UserManagementController::class, 'show']);
        Route::put('/{id}', [UserManagementController::class, 'update']);
        Route::post('/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
        Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);
        Route::delete('/{id}', [UserManagementController::class, 'destroy']);
        Route::get('/{id}/qr-code', [UserManagementController::class, 'getUserQR']);
        Route::get('/{id}/badge', [UserManagementController::class, 'generateIndividualBadge']);
    });

    // Routes d'upload de photos
    Route::post('upload-photo', [PhotoUploadController::class, 'upload']);

    // Routes de recherche globale
    Route::prefix('search')->group(function () {
        Route::get('/', [App\Http\Controllers\SearchController::class, 'globalSearch']);
        Route::get('/quick', [App\Http\Controllers\SearchController::class, 'quickSearch']);
    });

    // Routes pour les statistiques
    Route::prefix('stats')->group(function () {
        Route::get('/global', [App\Http\Controllers\StatsController::class, 'getGlobalStats']);
    });

    // Routes pour les matières
    Route::prefix('subjects')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [SubjectController::class, 'index'])->middleware(['role:admin,secretaire,accountant,comptable,teacher']);
        Route::get('/{subject}', [SubjectController::class, 'show'])->middleware(['role:admin,secretaire,accountant,comptable,teacher']);
        Route::get('/series/{classSeries}', [SubjectController::class, 'getForSeries'])->middleware(['role:admin,secretaire,accountant,comptable,teacher']);

        // Routes pour administrateurs et secrétaires (gestion)
        Route::post('/', [SubjectController::class, 'store'])->middleware(['role:admin,secretaire']);
        Route::put('/{subject}', [SubjectController::class, 'update'])->middleware(['role:admin,secretaire']);
        Route::delete('/{subject}', [SubjectController::class, 'destroy'])->middleware(['role:admin,secretaire']);
        Route::post('/{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])->middleware(['role:admin,secretaire']);
        Route::post('/series/{classSeries}/configure', [SubjectController::class, 'configureForSeries'])->middleware(['role:admin,secretaire']);
    });

    // Routes pour les enseignants
    Route::prefix('teachers')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/dashboard', [TeacherController::class, 'dashboard'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/', [TeacherController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/{teacher}', [TeacherController::class, 'show'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/{teacher}/stats', [TeacherController::class, 'getStats'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

        // Routes pour les badges d'enseignants
        Route::get('/{teacher}/generate-badge', [TeacherController::class, 'generateBadge'])->middleware(['role:admin']);
        Route::post('/generate-multiple-badges', [TeacherController::class, 'generateMultipleBadges'])->middleware(['role:admin']);

        // Export routes
        Route::get('/export/excel', [TeacherController::class, 'exportExcel'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/export/csv', [TeacherController::class, 'exportCsv'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/export/pdf', [TeacherController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/export/importable', [TeacherController::class, 'exportImportable'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/template/download', [TeacherController::class, 'downloadTemplate'])->middleware(['role:admin']);

        // Routes pour administrateurs, principal et secrétaires (gestion)
        Route::post('/', [TeacherController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::put('/{teacher}', [TeacherController::class, 'update'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{teacher}/toggle-status', [TeacherController::class, 'toggleStatus'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{teacher}/assign-subjects', [TeacherController::class, 'assignSubjects'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{teacher}/remove-assignment', [TeacherController::class, 'removeAssignment'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{teacher}/create-user-account', [TeacherController::class, 'createUserAccount'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{teacher}/remove-user-account', [TeacherController::class, 'removeUserAccount'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/import/csv', [TeacherImportController::class, 'importCsv'])->middleware(['role:admin,principal,secretaire']);
        Route::get('/template/csv', [TeacherImportController::class, 'downloadTemplate'])->middleware(['role:admin,principal,secretaire']);
        
        // Routes pour corriger les utilisateurs enseignants
        Route::post('/fix-contacts', [TeacherFixController::class, 'fixTeacherContacts'])->middleware(['role:admin']);
        Route::post('/fix-contact/{user_id}', [TeacherFixController::class, 'fixSpecificTeacher'])->middleware(['role:admin']);
    });

    // Routes pour la configuration des matières par série (ClassSeries: 6ème A, 6ème B, etc.)
    Route::prefix('series-subjects')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [SeriesSubjectController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable,teacher']);
        Route::get('/{id}', [SeriesSubjectController::class, 'show'])->middleware(['role:admin,principal,secretaire,accountant,comptable,teacher']);
        Route::get('/series/{seriesId}', [SeriesSubjectController::class, 'getBySeries'])->middleware(['role:admin,principal,secretaire,accountant,comptable,teacher']);

        // Routes pour administrateurs, principal et secrétaires (gestion)
        Route::post('/', [SeriesSubjectController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::put('/{id}', [SeriesSubjectController::class, 'update'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{id}', [SeriesSubjectController::class, 'destroy'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{id}/toggle-status', [SeriesSubjectController::class, 'toggleStatus'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/series/{seriesId}/bulk-configure', [SeriesSubjectController::class, 'bulkConfigure'])->middleware(['role:admin,principal,secretaire']);
    });

    // Routes pour les affectations d'enseignants
    Route::prefix('teacher-assignments')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [TeacherAssignmentController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,teacher']);
        Route::get('/teacher/{teacher}', [TeacherAssignmentController::class, 'getByTeacher'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,teacher']);
        Route::get('/teacher/{teacher}/available-subjects', [TeacherAssignmentController::class, 'getAvailableSubjects'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

        // Routes pour administrateurs, principal et secrétaires (gestion)
        Route::post('/', [TeacherAssignmentController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{assignment}', [TeacherAssignmentController::class, 'destroy'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{assignment}/toggle-status', [TeacherAssignmentController::class, 'toggleStatus'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/teacher/{teacher}/bulk-assign', [TeacherAssignmentController::class, 'bulkAssign'])->middleware(['role:admin,principal,secretaire']);

        // Routes pour envoyer récapitulatif WhatsApp
        Route::post('/teacher/{teacher}/send-summary', [TeacherAssignmentController::class, 'sendAssignmentsSummary'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/send-summary-by-phone', [TeacherAssignmentController::class, 'sendAssignmentsSummaryByPhone'])->middleware(['role:admin,principal,secretaire']);

        // Route pour modifier les identifiants de connexion d'un enseignant
        Route::put('/teacher/{teacher}/update-credentials', [TeacherAssignmentController::class, 'updateCredentials'])->middleware(['role:admin,principal,secretaire']);
    });

    // Routes pour les professeurs principaux
    Route::prefix('main-teachers')->group(function () {
        // Routes accessibles aux admins et comptables (consultation)
        Route::get('/', [MainTeacherController::class, 'index'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,teacher']);
        Route::get('/classes-without-main-teacher', [MainTeacherController::class, 'getClassesWithoutMainTeacher'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
        Route::get('/available-teachers', [MainTeacherController::class, 'getAvailableTeachers'])->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

        // Routes pour administrateurs, principal et secrétaires (gestion)
        Route::post('/', [MainTeacherController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::put('/{mainTeacher}', [MainTeacherController::class, 'update'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{mainTeacher}', [MainTeacherController::class, 'destroy'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{mainTeacher}/toggle-status', [MainTeacherController::class, 'toggleStatus'])->middleware(['role:admin,principal,secretaire']);
    });

    // Routes pour les compétences évaluées
    Route::prefix('subject-competences')->group(function () {
        // Routes pour les enseignants
        Route::get('/', [CompetenceController::class, 'index'])->middleware(['role:teacher']);
        Route::get('/class/{classSeriesId}/subjects', [CompetenceController::class, 'getTeacherSubjectsInClass'])->middleware(['role:teacher']);
        Route::get('/{classSeriesId}/{classSeriesSubjectId}/{trimesterId}', [CompetenceController::class, 'show'])->middleware(['role:teacher']);
        Route::post('/', [CompetenceController::class, 'store'])->middleware(['role:teacher']);
        Route::delete('/{id}', [CompetenceController::class, 'destroy'])->middleware(['role:teacher']);

        // Route système pour récupérer les compétences lors de la génération des bulletins
        Route::get('/for-bulletin/{classSeriesSubjectId}/{trimesterId}', [CompetenceController::class, 'getForBulletin']);
    });

    // Routes pour les besoins
    Route::prefix('needs')->group(function () {
        // Routes pour tous les utilisateurs authentifiés
        Route::post('/', [NeedController::class, 'store']); // Soumettre un besoin
        Route::get('/my-needs', [NeedController::class, 'myNeeds']); // Voir ses propres besoins
        Route::put('/{need}', [NeedController::class, 'update']); // Modifier un besoin (propriétaire uniquement)
        Route::delete('/{need}', [NeedController::class, 'destroy']); // Supprimer un besoin (propriétaire uniquement)
        Route::get('/{need}', [NeedController::class, 'show']); // Voir un besoin spécifique (avec contrôle d'accès)

        // Routes pour administrateurs et comptables supérieurs
        Route::get('/', [NeedController::class, 'index'])->middleware(['role:admin,principal,comptable_superieur,surveillant_general,surveillant_secteur']); // Lister tous les besoins
        Route::post('/{need}/approve', [NeedController::class, 'approve'])->middleware(['role:admin,comptable_superieur']); // Approuver
        Route::post('/{need}/reject', [NeedController::class, 'reject'])->middleware(['role:admin,comptable_superieur']); // Rejeter
        Route::get('/statistics/summary', [NeedController::class, 'statistics'])->middleware(['role:admin,principal,comptable_superieur,surveillant_general,surveillant_secteur']); // Statistiques
        Route::post('/test-whatsapp', [NeedController::class, 'testWhatsApp'])->middleware(['role:admin']); // Test WhatsApp (admin uniquement)

        // Routes d'export pour administrateurs et comptables supérieurs
        Route::get('/export/pdf', [NeedController::class, 'exportPdf'])->middleware(['role:admin,principal,comptable_superieur,surveillant_general,surveillant_secteur']); // Export PDF
        Route::get('/export/excel', [NeedController::class, 'exportExcel'])->middleware(['role:admin,principal,comptable_superieur,surveillant_general,surveillant_secteur']); // Export Excel
        Route::get('/export/word', [NeedController::class, 'exportWord'])->middleware(['role:admin,principal,comptable_superieur,surveillant_general,surveillant_secteur']); // Export Word

        // Routes pour validation/rejet en masse
        Route::post('/bulk/approve', [NeedController::class, 'bulkApprove'])->middleware(['role:admin,comptable_superieur']); // Approuver en masse
        Route::post('/bulk/reject', [NeedController::class, 'bulkReject'])->middleware(['role:admin,comptable_superieur']); // Rejeter en masse

        // Route pour rapport des besoins approuvés
        Route::get('/report/approved', [NeedController::class, 'approvedReport'])->middleware(['role:admin,comptable_superieur']); // Rapport PDF des approuvés
    });

    // Routes pour les surveillants généraux
    Route::prefix('supervisors')->group(function () {
        // Routes pour administrateurs uniquement (gestion des affectations)
        Route::post('/assign-to-class', [SupervisorController::class, 'assignSupervisorToClass'])->middleware(['role:admin']);
        Route::get('/all-assignments', [SupervisorController::class, 'getAllAssignments'])->middleware(['role:admin']);
        Route::delete('/assignments/{assignmentId}', [SupervisorController::class, 'deleteAssignment'])->middleware(['role:admin']);
        Route::get('/{supervisorId}/assignments', [SupervisorController::class, 'getSupervisorAssignments'])->middleware(['role:admin,surveillant_general']);
        Route::get('/{supervisorId}/available-classes', [SupervisorController::class, 'getAvailableClasses'])->middleware(['role:admin,surveillant_general']);

        // Routes pour bibliothécaires (scanner QR et voir présences étudiants)
        Route::post('/scan-qr', [SupervisorController::class, 'scanStudentQR'])->middleware(['role:admin,bibliothecaire']);
        Route::get('/daily-attendance', [SupervisorController::class, 'getDailyAttendance'])->middleware(['role:admin,bibliothecaire,accountant,comptable_superieur']);
        Route::get('/attendance-range', [SupervisorController::class, 'getAttendanceRange'])->middleware(['role:admin,bibliothecaire,accountant,comptable_superieur']);
        Route::get('/entry-exit-stats', [SupervisorController::class, 'getEntryExitStats'])->middleware(['role:admin,bibliothecaire,accountant,comptable_superieur']);
        Route::post('/student-status', [SupervisorController::class, 'getStudentCurrentStatus'])->middleware(['role:admin,bibliothecaire,accountant,comptable_superieur']);
        Route::post('/mark-absent-students', [SupervisorController::class, 'markAbsentStudents'])->middleware(['role:admin,bibliothecaire']);
        Route::post('/mark-all-absent-students', [SupervisorController::class, 'markAllAbsentStudents'])->middleware(['role:admin,bibliothecaire']);

        // Routes pour génération codes QR
        Route::get('/generate-qr/{studentId}', [SupervisorController::class, 'generateStudentQR'])->middleware(['role:admin']);
        Route::get('/generate-all-qrs', [SupervisorController::class, 'generateAllStudentQRs'])->middleware(['role:admin']);
    });

    // Module Réductions (réduction manuelle sur scolarité, jamais sur inscription)
    Route::prefix('manual-discounts')->middleware(['role:admin,secretaire,accountant,comptable,comptable_superieur'])->group(function () {
        Route::get('/', [\App\Http\Controllers\StudentManualDiscountController::class, 'index']);
        Route::get('/student/{studentId}', [\App\Http\Controllers\StudentManualDiscountController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\StudentManualDiscountController::class, 'upsert']);
        Route::delete('/student/{studentId}', [\App\Http\Controllers\StudentManualDiscountController::class, 'destroy']);
    });

    // Routes pour la gestion RAME (pour RameStatusToggle)
    Route::prefix('student-rame')->middleware(['role:admin,secretaire,accountant,comptable_superieur'])->group(function () {
        Route::get('/student/{studentId}/status', [StudentRameController::class, 'getRameStatus']);
        Route::post('/student/{studentId}/update', [StudentRameController::class, 'updateRameStatus']);
        Route::get('/class-series/{classSeriesId}', [StudentRameController::class, 'getClassRameStatus']);
    });

    // Routes pour l'inventaire scolaire
    Route::prefix('inventory')->middleware(['role:admin,secretaire,accountant,comptable_superieur'])->group(function () {
        // Routes de consultation (routes spécifiques d'abord)
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/dashboard', [InventoryController::class, 'dashboard']);
        Route::get('/config', [InventoryController::class, 'config']);
        Route::get('/export', [InventoryController::class, 'export']);
        Route::get('/movements/recent', [InventoryController::class, 'getRecentMovements']);

        // Routes pour les alertes WhatsApp
        Route::get('/low-stock-items', [InventoryController::class, 'getLowStockItems']);
        Route::post('/send-low-stock-alert', [InventoryController::class, 'sendLowStockAlert'])->middleware(['role:admin']);
        Route::post('/test-whatsapp', [InventoryController::class, 'testWhatsAppConfig'])->middleware(['role:admin']);

        // Routes avec paramètres (après les routes spécifiques)
        Route::get('/{inventoryItem}', [InventoryController::class, 'show']);
        Route::get('/{inventoryItem}/movements', [InventoryController::class, 'getMovements']);

        // Routes de gestion (admin uniquement)
        Route::post('/', [InventoryController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{inventoryItem}', [InventoryController::class, 'update'])->middleware(['role:admin']);
        Route::patch('/{inventoryItem}/quantity', [InventoryController::class, 'updateQuantity'])->middleware(['role:admin']);
        Route::post('/{inventoryItem}/movements', [InventoryController::class, 'recordMovement'])->middleware(['role:admin']);
        Route::delete('/{inventoryItem}', [InventoryController::class, 'destroy'])->middleware(['role:admin']);
    });

    // Routes pour le module Cahier des pièces jointes
    Route::prefix('documents')->group(function () {
        // Routes pour les dossiers
        Route::prefix('folders')->group(function () {
            Route::get('/', [DocumentFolderController::class, 'index']); // Lister les dossiers
            Route::get('/tree', [DocumentFolderController::class, 'getTree']); // Arborescence des dossiers
            Route::get('/types', [DocumentFolderController::class, 'getFolderTypes']); // Types de dossiers
            Route::get('/search', [DocumentFolderController::class, 'search']); // Rechercher dans les dossiers
            Route::post('/', [DocumentFolderController::class, 'store']); // Créer un dossier
            Route::get('/{documentFolder}', [DocumentFolderController::class, 'show']); // Voir un dossier
            Route::put('/{documentFolder}', [DocumentFolderController::class, 'update']); // Modifier un dossier
            Route::delete('/{documentFolder}', [DocumentFolderController::class, 'destroy']); // Supprimer un dossier
        });

        // Routes pour les documents
        Route::get('/', [DocumentController::class, 'index']); // Lister les documents
        Route::get('/statistics', [DocumentController::class, 'statistics']); // Statistiques des documents
        Route::get('/types', [DocumentController::class, 'getTypes']); // Types de documents
        Route::get('/visibility-types', [DocumentController::class, 'getVisibilityTypes']); // Types de visibilité
        Route::post('/', [DocumentController::class, 'store']); // Uploader un document
        Route::get('/{document}', [DocumentController::class, 'show']); // Voir un document
        Route::put('/{document}', [DocumentController::class, 'update']); // Modifier un document
        Route::delete('/{document}', [DocumentController::class, 'destroy']); // Supprimer un document
        Route::get('/{document}/download', [DocumentController::class, 'download']); // Télécharger un document
        Route::post('/{document}/toggle-archive', [DocumentController::class, 'toggleArchive']); // Archiver/désarchiver
    });

    // Routes pour les présences du personnel (remplace teacher-attendance)
    Route::prefix('staff-attendance')->group(function () {
        // Routes pour scan QR du personnel
        Route::post('/scan-qr', [StaffAttendanceController::class, 'scanQR'])->middleware(['role:admin,bibliothecaire,surveillant_secteur,surveillant_general']);
        Route::post('/scan-qr-with-class', [StaffAttendanceController::class, 'scanQRWithClass'])->middleware(['role:admin,bibliothecaire,surveillant_secteur,surveillant_general']);
        Route::post('/scan-qr-with-classes', [StaffAttendanceController::class, 'scanQRWithClasses'])->middleware(['role:admin,bibliothecaire,surveillant_secteur,surveillant_general']);
        Route::get('/today-entry-classes/{staffId}', [StaffAttendanceController::class, 'getTodayEntryClasses'])->middleware(['role:admin,bibliothecaire']);
        Route::get('/daily-attendance', [StaffAttendanceController::class, 'getDailyAttendance'])->middleware(['role:admin,secretaire,comptable_superieur,accountant,bibliothecaire']);
        Route::get('/daily', [StaffAttendanceController::class, 'getDailyStaffAttendance'])->middleware(['role:admin,secretaire,comptable_superieur,accountant,bibliothecaire']);
        Route::get('/entry-exit-stats', [StaffAttendanceController::class, 'getEntryExitStats'])->middleware(['role:admin,secretaire,comptable_superieur,accountant,bibliothecaire']);
        
        // Route pour supprimer les scans de test (développement seulement)
        Route::delete('/clear-today-scans', [StaffAttendanceController::class, 'clearTodayScans']);

        // Routes pour gestion des QR codes personnel
        Route::post('/generate-qr', [StaffAttendanceController::class, 'generateQRCode'])->middleware(['role:admin']);
        Route::get('/staff-with-qr', [StaffAttendanceController::class, 'getStaffWithQR'])->middleware(['role:admin,secretaire,comptable_superieur,accountant,bibliothecaire']);

        // Routes pour rapports et statistiques
        Route::get('/staff/{staffId}/report', [StaffAttendanceController::class, 'getStaffReport'])->middleware(['role:admin,secretaire,comptable_superieur,accountant,bibliothecaire']);

        // Routes pour badges multiples
        Route::post('/generate-multiple-badges', [StaffAttendanceController::class, 'generateMultipleBadges'])->middleware(['role:admin']);

        // Routes d'export PDF
        Route::get('/export/pdf', [StaffAttendanceController::class, 'exportStaffAttendancePDF'])->middleware(['role:admin,accountant,comptable_superieur,bibliothecaire']);
    });

    // Routes de compatibilité pour teacher-attendance (à supprimer plus tard)
    Route::prefix('teacher-attendance')->group(function () {
        Route::post('/scan-qr', [TeacherAttendanceController::class, 'scanQR'])->middleware(['role:admin,surveillant_general']);
        Route::get('/daily-attendance', [TeacherAttendanceController::class, 'getDailyAttendance'])->middleware(['role:admin,surveillant_general']);
        Route::get('/entry-exit-stats', [TeacherAttendanceController::class, 'getEntryExitStats'])->middleware(['role:admin,surveillant_general']);
        Route::post('/generate-qr', [TeacherAttendanceController::class, 'generateQRCode'])->middleware(['role:admin']);
        Route::get('/teachers-with-qr', [TeacherAttendanceController::class, 'getTeachersWithQR'])->middleware(['role:admin,surveillant_general']);
        Route::get('/teacher/{teacherId}/report', [TeacherAttendanceController::class, 'getTeacherReport'])->middleware(['role:admin,surveillant_general']);
        Route::get('/teacher/{teacherId}/detailed-stats', [TeacherAttendanceController::class, 'getDetailedTeacherStats'])->middleware(['role:admin,surveillant_general']);
        Route::get('/teacher/{teacherId}/day-movements', [TeacherAttendanceController::class, 'getDayMovements'])->middleware(['role:admin,surveillant_general']);
        Route::put('/teacher/{teacherId}/work-schedule', [TeacherAttendanceController::class, 'updateWorkSchedule'])->middleware(['role:admin']);
    });

    // Routes pour les présences étudiants - Comptables
    Route::prefix('attendance')->group(function () {
        // Appel manuel des étudiants
        Route::post('/manual', [StudentAttendanceController::class, 'saveManualAttendance'])->middleware(['role:admin,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/class-daily', [StudentAttendanceController::class, 'getDailyAttendanceByClass'])->middleware(['role:admin,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/class-stats', [StudentAttendanceController::class, 'getClassAttendanceStats'])->middleware(['role:admin,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        Route::get('/class-report', [StudentAttendanceController::class, 'getClassAttendanceReport'])->middleware(['role:admin,accountant,comptable_superieur,bibliothecaire,surveillant_general,surveillant_secteur']);
        
        // Anciennes routes (à migrer ou supprimer)
        Route::get('/students', [StudentAttendanceController::class, 'getStudentAttendance'])->middleware(['role:admin,accountant,comptable_superieur']);
        Route::get('/students/stats', [StudentAttendanceController::class, 'getAttendanceStats'])->middleware(['role:admin,accountant,comptable_superieur']);
        Route::get('/students/export/pdf', [StudentAttendanceController::class, 'exportStudentAttendancePDF'])->middleware(['role:admin,accountant,comptable_superieur']);
        
        // Routes pour l'application mobile
        Route::post('/students/submit', [MobileAttendanceController::class, 'submitBulkAttendance'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/students/mobile/stats', [MobileAttendanceController::class, 'getAttendanceStats'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        
        // Routes pour la gestion manuelle des présences
        Route::post('/students/mark', [MobileAttendanceController::class, 'markStudentAttendance'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::post('/students/mark-absent-series', [MobileAttendanceController::class, 'markAllAbsentInSeries'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/students/status', [MobileAttendanceController::class, 'getStudentStatus'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        
        // Routes pour la gestion des états d'appel quotidiens
        Route::get('/daily-states', [MobileAttendanceController::class, 'getDailyAttendanceStates'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/series/{seriesId}/state', [MobileAttendanceController::class, 'getSeriesAttendanceState'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/series/{seriesId}/existing-attendance', [MobileAttendanceController::class, 'getExistingAttendance'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::post('/series/{seriesId}/reset-state', [MobileAttendanceController::class, 'resetSeriesAttendanceState'])->middleware(['role:admin']);
    });

    // Routes de navigation hiérarchique pour mobile
    Route::prefix('mobile')->middleware(['auth:api'])->group(function () {
        Route::get('/sections/{sectionId}/levels', [MobileAttendanceController::class, 'getLevelsBySection'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/levels/{levelId}/classes', [MobileAttendanceController::class, 'getClassesByLevel'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/classes/{classId}/series', [MobileAttendanceController::class, 'getSeriesByClass'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
        Route::get('/students/series/{seriesId}', [MobileAttendanceController::class, 'getStudentsBySeries'])->middleware(['role:admin,teacher,surveillant_general,bibliothecaire,surveillant_secteur']);
    });

    // Routes pour les départements
    Route::prefix('departments')->group(function () {
        // Routes de consultation (admin et comptables)
        Route::get('/', [DepartmentController::class, 'index'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);
        Route::get('/{department}', [DepartmentController::class, 'show'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);

        // Routes d'export
        Route::get('/export/pdf', [DepartmentController::class, 'exportPdf'])->middleware(['role:admin,secretaire,accountant,comptable_superieur']);

        // Routes de gestion (admin uniquement)
        Route::post('/', [DepartmentController::class, 'store'])->middleware(['role:admin']);
        Route::put('/{department}', [DepartmentController::class, 'update'])->middleware(['role:admin']);
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware(['role:admin']);

        // Routes pour la gestion des enseignants dans les départements
        Route::post('/{department}/assign-teacher', [DepartmentController::class, 'assignTeacher'])->middleware(['role:admin']);
        Route::post('/{department}/remove-teacher', [DepartmentController::class, 'removeTeacher'])->middleware(['role:admin']);
        Route::post('/{department}/set-head', [DepartmentController::class, 'setHead'])->middleware(['role:admin']);
    });

    // Routes pour les demandes d'explication (D.E)
    Route::prefix('demandes-explication')->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire,teacher,surveillant_general,surveillant_secteur'])->group(function () {
        Route::get('/', [App\Http\Controllers\DemandeExplicationController::class, 'index']);
        Route::post('/', [App\Http\Controllers\DemandeExplicationController::class, 'store']);
        Route::get('/personnel', [App\Http\Controllers\DemandeExplicationController::class, 'getPersonnel']);
        Route::get('/statistiques', [App\Http\Controllers\DemandeExplicationController::class, 'statistiques']);
        Route::get('/{id}', [App\Http\Controllers\DemandeExplicationController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\DemandeExplicationController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\DemandeExplicationController::class, 'destroy']);
        Route::post('/{id}/repondre', [App\Http\Controllers\DemandeExplicationController::class, 'repondre']);
        Route::post('/{id}/cloturer', [App\Http\Controllers\DemandeExplicationController::class, 'cloturer']);
    });

    // Routes pour les rapports de recouvrement et certificats
    Route::prefix('reports')->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur,bibliothecaire'])->group(function () {
        // État de recouvrement
        Route::get('/recovery-status', [ReportsController::class, 'getRecoveryStatus']);
        Route::get('/recovery-status/export-pdf', [ReportsController::class, 'exportRecoveryStatusPdf']);

        // État général de recouvrement
        Route::get('/general-recovery-status/export-pdf', [ReportsController::class, 'exportGeneralRecoveryStatusToPdf']);
        // Certificats de scolarité
        Route::get('/school-certificates', [ReportsController::class, 'generateSchoolCertificates']);
        Route::get('/school-certificate/preview/{studentId}', [ReportsController::class, 'previewSchoolCertificate']);
        Route::get('/school-certificates/download', [ReportsController::class, 'downloadSchoolCertificates']);

        // Rapport mensuel de présence du personnel
        Route::get('/staff-attendance-monthly', [StaffAttendanceReportController::class, 'getStaffAttendanceMonthlyReport']);
        Route::get('/staff-attendance-monthly/export-pdf', [StaffAttendanceReportController::class, 'exportStaffAttendanceMonthlyPdf']);
        Route::get('/staff-attendance-monthly/export-excel', [StaffAttendanceReportController::class, 'exportStaffAttendanceMonthlyExcel']);

        // Rapport spécifique vacataires avec détail classes
        Route::get('/vacataire-attendance', [StaffAttendanceReportController::class, 'getVacataireAttendanceReport'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/vacataire-attendance/export-pdf', [StaffAttendanceReportController::class, 'exportVacataireAttendancePdf'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/vacataire-attendance/export-excel', [StaffAttendanceReportController::class, 'exportVacataireAttendanceExcel'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/vacataires-list', [StaffAttendanceReportController::class, 'getVacatairesList'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);

        // Rapport mensuel calendrier pour vacataires (format In/Out par jour)
        Route::get('/vacataire-attendance-calendar', [StaffAttendanceReportController::class, 'getVacataireMonthlyCalendarReport'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/vacataire-attendance-calendar/export-pdf', [StaffAttendanceReportController::class, 'exportVacataireCalendarPdf'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/vacataire-attendance-calendar/export-excel', [StaffAttendanceReportController::class, 'exportVacataireCalendarExcel'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);

        // Rapport mensuel calendrier pour personnel permanent (format In/Out par jour)
        Route::get('/staff-attendance-calendar', [StaffAttendanceReportController::class, 'getStaffMonthlyCalendarReport'])
            ->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/staff-attendance-calendar/export-pdf', function (Illuminate\Http\Request $request) {
            $request->merge(['format' => 'pdf']);
            return app(StaffAttendanceReportController::class)->getStaffMonthlyCalendarReport($request);
        })->middleware(['role:admin,principal,comptable_superieur,accountant']);
        Route::get('/staff-attendance-calendar/export-excel', function (Illuminate\Http\Request $request) {
            $request->merge(['format' => 'excel']);
            return app(StaffAttendanceReportController::class)->getStaffMonthlyCalendarReport($request);
        })->middleware(['role:admin,principal,comptable_superieur,accountant']);

        // Rapports PDF supplémentaires
        Route::get('/detailed-collection/export-pdf', [ReportsController::class, 'exportDetailedCollectionPdf']);
        Route::get('/class-school-fees/export-pdf', [ReportsController::class, 'exportClassSchoolFeesPdf']);
        
        // Rapport d'état des recouvrements complet
        Route::get('/recovery-status-report', [ReportsController::class, 'getRecoveryStatusReport']);
        Route::get('/recovery-status/export-pdf', [ReportsController::class, 'exportRecoveryStatusPdf']);
    });

    // Routes pour la PAIE (comptables uniquement)
    Route::prefix('payroll')->middleware(['role:accountant,comptable_superieur,admin'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [PayrollController::class, 'getDashboard']);
        
        // Gestion des employés
        Route::prefix('employees')->group(function () {
            Route::get('/', [PayrollController::class, 'getEmployees']);
            Route::post('/', [PayrollController::class, 'createEmployee']);
            Route::put('/{id}', [PayrollController::class, 'updateEmployee']);
            Route::get('/available-users', [PayrollController::class, 'getAvailableUsers']);
            Route::get('/{employeeId}/payslips', [PayrollController::class, 'getEmployeePayslips']);
        });

        // Périodes de paie
        Route::prefix('periods')->group(function () {
            Route::get('/', [PayrollController::class, 'getPeriods']);
            Route::post('/', [PayrollController::class, 'createPeriod']);
            Route::get('/{id}', [PayrollController::class, 'getPeriodDetails']);
            Route::post('/{id}/calculate', [PayrollController::class, 'calculatePayroll']);
            Route::post('/{id}/validate', [PayrollController::class, 'validatePeriod']);
            Route::post('/{id}/mark-available', [PayrollController::class, 'markSalariesAvailable']);
            Route::get('/{id}/payslips', [PayrollController::class, 'getPayslips']);
        });

        // Coupures de salaire
        Route::prefix('salary-cuts')->group(function () {
            Route::get('/', [PayrollController::class, 'getSalaryCuts']);
            Route::post('/', [PayrollController::class, 'createSalaryCut']);
            Route::post('/{id}/cancel', [PayrollController::class, 'cancelSalaryCut']);
        });

        // Bulletins de paie
        Route::prefix('payslips')->group(function () {
            Route::post('/{id}/mark-retired', [PayrollController::class, 'markSalaryAsRetired']);
        });

        // Notifications WhatsApp
        Route::prefix('notifications')->group(function () {
            Route::get('/', [PayrollController::class, 'getNotifications']);
            Route::post('/{id}/retry', [PayrollController::class, 'retryNotification']);
        });

        // Génération PDF
        Route::prefix('pdf')->group(function () {
            Route::get('/payslip/{id}', [PayrollController::class, 'generatePayslipPDF']);
            Route::get('/period/{id}/payslips', [PayrollController::class, 'generatePeriodPayslipsPDF']);
            Route::get('/period/{id}/summary', [PayrollController::class, 'generatePeriodSummaryPDF']);
        });
    });

    // Routes pour la gestion des périodes académiques (semestres/trimestres)
    Route::prefix('academic-periods')->middleware(['role:admin,principal,secretaire'])->group(function () {
        // Configuration du système
        Route::get('/config', [AcademicPeriodController::class, 'getConfig']);
        Route::post('/config', [AcademicPeriodController::class, 'updateConfig']);
        
        // Gestion des périodes
        Route::get('/', [AcademicPeriodController::class, 'index']);
        Route::post('/', [AcademicPeriodController::class, 'store']);
        Route::get('/{academicPeriod}', [AcademicPeriodController::class, 'show']);
        Route::put('/{academicPeriod}', [AcademicPeriodController::class, 'update']);
        Route::delete('/{academicPeriod}', [AcademicPeriodController::class, 'destroy']);
        
        // Validation
        Route::post('/validate-year', [AcademicPeriodController::class, 'validateYear']);
    });
});

// Evaluation Configurations (Admin only) 
Route::middleware(['auth:api', 'role:admin'])->prefix('evaluation-configs')->group(function () {
    Route::get('/', [EvaluationConfigController::class, 'index']);
    Route::post('/', [EvaluationConfigController::class, 'store']);
    Route::get('/{evaluationConfig}', [EvaluationConfigController::class, 'show']);
    Route::put('/{evaluationConfig}', [EvaluationConfigController::class, 'update']);
    Route::delete('/{evaluationConfig}', [EvaluationConfigController::class, 'destroy']);
    Route::patch('/{evaluationConfig}/toggle-status', [EvaluationConfigController::class, 'toggleStatus']);
});

// Grading Scales (Admin only)
Route::middleware(['auth:api', 'role:admin,principal,secretaire'])->prefix('grading-scales')->group(function () {
    Route::get('/', [GradingScaleController::class, 'index']);
    Route::post('/', [GradingScaleController::class, 'store']);
    Route::get('/{gradingScale}', [GradingScaleController::class, 'show']);
    Route::put('/{gradingScale}', [GradingScaleController::class, 'update']);
    Route::delete('/{gradingScale}', [GradingScaleController::class, 'destroy']);
    Route::post('/create-default', [GradingScaleController::class, 'createDefaultScale']);
    Route::post('/get-grade-for-score', [GradingScaleController::class, 'getGradeForScore']);
});

// Routes pour le système d'évaluation (Séquences et Trimestres)
Route::middleware(['auth:api'])->group(function () {
    
    // Routes pour les séquences
    Route::prefix('sequences')->group(function () {
        // Consultation (enseignants, admins, comptables, principal)
        Route::get('/', [SequenceController::class, 'index'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/current', [SequenceController::class, 'getCurrentSequence'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/{sequence}', [SequenceController::class, 'show'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/{sequence}/stats', [SequenceController::class, 'getStats'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        
        // Gestion (admin, principal et secrétaire)
        Route::post('/', [SequenceController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{sequence}/activate', [SequenceController::class, 'activate'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{sequence}/mark-completed', [SequenceController::class, 'markCompleted'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{sequence}/mark-incomplete', [SequenceController::class, 'markIncomplete'])->middleware(['role:admin,principal,secretaire']);
    });

    // Routes pour les trimestres
    Route::prefix('trimesters')->group(function () {
        // Consultation (enseignants, admins, comptables, principal)
        Route::get('/', [TrimesterController::class, 'index'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/teacher', [TrimesterController::class, 'getTeacherTrimesters'])->middleware(['role:teacher']);
        Route::get('/current', [TrimesterController::class, 'getCurrentTrimester'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/{trimester}', [TrimesterController::class, 'show'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/{trimester}/stats', [TrimesterController::class, 'getStats'])->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire,surveillant_general,surveillant_secteur']);
        Route::get('/{trimester}/ds-details', [TrimesterController::class, 'getDSDetails'])->middleware(['role:teacher']);
        
        // Gestion (admin, principal et secrétaires)
        Route::post('/', [TrimesterController::class, 'store'])->middleware(['role:admin,principal,secretaire']);
        Route::put('/{trimester}', [TrimesterController::class, 'update'])->middleware(['role:admin,principal,secretaire']);
        Route::delete('/{trimester}', [TrimesterController::class, 'destroy'])->middleware(['role:admin,principal,secretaire']);
        Route::post('/{trimester}/activate', [TrimesterController::class, 'activate'])->middleware(['role:admin,principal,secretaire']);
    });

    // Routes pour les évaluations
    Route::prefix('evaluations')->group(function () {
        // Consultation (enseignants, admins, comptables)
        Route::get('/', [EvaluationController::class, 'index'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
        Route::get('/dashboard', [EvaluationController::class, 'dashboard'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
        Route::get('/types', [EvaluationController::class, 'getTypes'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
        Route::get('/{evaluation}/stats', [EvaluationController::class, 'getStats'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
        
        // Création et gestion (enseignants et admins)
        Route::post('/', [EvaluationController::class, 'store'])->middleware(['role:admin,teacher']);
        Route::delete('/{evaluation}', [EvaluationController::class, 'destroy'])->middleware(['role:admin,teacher']);
    });

    // Routes pour les notes
    Route::prefix('grades')->group(function () {
        // Consultation des notes par évaluation
        Route::get('/evaluation/{evaluation}', [GradeController::class, 'getGradesByEvaluation'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
        
        // Saisie et gestion des notes (enseignants, admins et secrétaires)
        Route::post('/', [GradeController::class, 'saveGrade'])->middleware(['role:admin,teacher,secretaire']);
        Route::post('/bulk', [GradeController::class, 'saveBulkGrades'])->middleware(['role:admin,teacher,secretaire']);
        Route::delete('/{grade}', [GradeController::class, 'deleteGrade'])->middleware(['role:admin,teacher,secretaire']);
        
        // Statistiques
        Route::get('/evaluation/{evaluation}/stats', [GradeController::class, 'getEvaluationStats'])->middleware(['role:admin,teacher,accountant,comptable_superieur,secretaire']);
    });

    // Routes pour les zones de géolocalisation
    Route::prefix('geolocation-zones')->group(function () {
        // Routes pour tous les utilisateurs authentifiés
        Route::get('/enabled', [GeolocationZoneController::class, 'getEnabledZones']);
        Route::post('/validate-position', [GeolocationZoneController::class, 'validatePosition']);
        
        // Routes CRUD pour les admins
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/', [GeolocationZoneController::class, 'index']);
            Route::post('/', [GeolocationZoneController::class, 'store']);
            Route::get('/{zone}', [GeolocationZoneController::class, 'show']);
            Route::put('/{zone}', [GeolocationZoneController::class, 'update']);
            Route::delete('/{zone}', [GeolocationZoneController::class, 'destroy']);
            Route::post('/{zone}/toggle-status', [GeolocationZoneController::class, 'toggleStatus']);
        });
    });
    
    // Routes pour les notifications parent (admin uniquement)
    Route::middleware(['auth:api', 'role:admin'])->prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/parents', [NotificationController::class, 'getParents']);
        Route::get('/students', [NotificationController::class, 'getStudents']);
        Route::get('/classes', [NotificationController::class, 'getClasses']);
        Route::get('/stats', [NotificationController::class, 'stats']);
        Route::get('/{id}/status', [NotificationController::class, 'getStatus']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Schedule routes for admin
    Route::middleware(['auth:api', 'role:admin'])->prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::post('/bulk', [ScheduleController::class, 'bulkStore']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    });
});

// Routes publiques pour les parents
Route::prefix('parent')->group(function () {
    Route::post('/login', [ParentController::class, 'login']);
});

// Routes protégées pour les parents (avec authentification Sanctum)
Route::middleware('auth:sanctum')->prefix('parent')->group(function () {
    Route::get('/dashboard', [ParentController::class, 'dashboard']);
    Route::get('/children', [ParentController::class, 'getChildren']);
    Route::get('/notifications', [ParentController::class, 'getNotifications']);
    Route::put('/notifications/{id}/read', [ParentController::class, 'markNotificationAsRead']);
    Route::get('/calendar/events', [ParentController::class, 'getCalendarEvents']);
    Route::get('/schedules', [ScheduleController::class, 'getParentChildrenSchedules']);
});

// Routes pour les bulletins scolaires
Route::middleware(['auth:api'])->prefix('bulletins')->group(function () {
    // Consultation des bulletins disponibles
    Route::get('/available/{studentId}', [BulletinController::class, 'availableBulletins'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Génération de bulletins (Admin et Teachers)
    Route::post('/generate', [BulletinController::class, 'generate'])
        ->middleware(['role:admin,principal,teacher,secretaire']);

    Route::post('/batch-generate', [BulletinController::class, 'batchGenerate'])
        ->middleware(['role:admin,principal,teacher,secretaire']);

    // Progression de la génération en lot
    Route::get('/batch-progress/{progressKey}', [BulletinController::class, 'getBatchProgress'])
        ->middleware(['role:admin,principal,teacher,secretaire']);

    // Téléchargement de bulletins
    Route::get('/download/{bulletinId}', [BulletinController::class, 'download'])
        ->name('bulletins.download')
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Visualisation des bulletins générés automatiquement (Admin)
    Route::get('/generated-bulletins', [BulletinController::class, 'getGeneratedBulletins'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Structure hiérarchique pour l'interface organisée
    Route::get('/hierarchical-structure', [BulletinController::class, 'getHierarchicalStructure'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Statut des bulletins par série/classe
    Route::get('/students-status/{seriesId}', [BulletinController::class, 'getStudentsBulletinStatus'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Timeline académique actuelle
    Route::get('/academic-timeline', [BulletinController::class, 'getAcademicTimeline'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);
    
    // Prévisualisation des bulletins
    Route::post('/preview', [BulletinController::class, 'previewBulletin'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);

    // Téléchargement direct de bulletin PDF (génère à la volée)
    Route::post('/download-direct', [BulletinController::class, 'downloadDirect'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);

    // Forcer la régénération (Admin uniquement)
    Route::post('/force-regenerate', [BulletinController::class, 'forceRegenerate'])
        ->middleware(['role:admin']);

    // ⚡ Fusion de bulletins en un seul PDF
    Route::post('/merge', [BulletinController::class, 'mergeBulletins'])
        ->middleware(['role:admin,principal,secretaire']);

    // Progression de la fusion
    Route::get('/merge-progress/{jobId}', [BulletinController::class, 'getMergeProgress'])
        ->middleware(['role:admin,principal,secretaire']);

    // Téléchargement du PDF fusionné
    Route::get('/merged/{mergedId}/download', [BulletinController::class, 'downloadMergedBulletin'])
        ->middleware(['role:admin,principal,secretaire']);

    // Génération batch synchrone (optimisée pour QUEUE_CONNECTION=sync)
    Route::post('/batch-generate-sync', [BulletinController::class, 'batchGenerateSync'])
        ->middleware(['role:admin']);

    // 🚀 ULTRA-OPTIMIZED: Génération batch trimestre (360× PLUS RAPIDE!)
    // Charge TOUTES les données EN UNE FOIS au lieu de 58 fois
    // ~30 secondes au lieu de 19+ minutes pour 58 étudiants
    Route::post('/batch-generate-trimester-optimized', [BulletinController::class, 'batchGenerateTrimesterOptimized'])
        ->middleware(['role:admin']);

    // Récupérer la progression d'une génération batch
    Route::get('/progress/{progressKey}', [BulletinController::class, 'getBatchProgress'])
        ->middleware(['role:admin,principal,teacher,secretaire']);

    // Téléchargement groupé de tous les bulletins
    Route::post('/download-all', [BulletinController::class, 'downloadAllBulletins'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);

    // Gestion des templates (Admin uniquement)
    Route::prefix('templates')->middleware(['role:admin'])->group(function () {
        Route::get('/', [BulletinController::class, 'getTemplates']);
        Route::post('/', [BulletinController::class, 'createTemplate']);
        Route::put('/{templateId}', [BulletinController::class, 'updateTemplate']);
        Route::delete('/{templateId}', [BulletinController::class, 'deleteTemplate']);
        Route::post('/{templateId}/toggle-status', [BulletinController::class, 'toggleTemplateStatus']);
    });

    // Fusion de bulletins en PDF unique (pour impression)
    Route::post('/merge', [BulletinController::class, 'mergeBulletins'])
        ->middleware(['role:admin,principal,secretaire']);

    Route::get('/merge-progress/{jobId}', [BulletinController::class, 'getMergeProgress'])
        ->middleware(['role:admin,principal,secretaire']);

    Route::get('/merged', [BulletinController::class, 'listMergedBulletins'])
        ->middleware(['role:admin,principal,secretaire']);

    Route::get('/merged/{mergedId}/download', [BulletinController::class, 'downloadMergedBulletin'])
        ->middleware(['role:admin,principal,teacher,accountant,comptable_superieur,secretaire']);

    Route::delete('/merged/{mergedId}', [BulletinController::class, 'deleteMergedBulletin'])
        ->middleware(['role:admin,principal']);
});

// Routes pour les PV (Procès-Verbaux)
Route::middleware(['auth:api'])->prefix('pv')->group(function () {
    // Liste des séries de classes
    Route::get('/class-series', [App\Http\Controllers\PVController::class, 'getClassSeries'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // Évaluations disponibles pour une série
    Route::get('/evaluations/{classSeriesId}', [App\Http\Controllers\PVController::class, 'getAvailableEvaluations'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // Générer le PV en PDF (séquence/composition)
    Route::get('/generate/{classSeriesId}/{evaluationId}', [App\Http\Controllers\PVController::class, 'generate'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // Prévisualiser le PV (HTML - séquence/composition)
    Route::get('/preview/{classSeriesId}/{evaluationId}', [App\Http\Controllers\PVController::class, 'preview'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // === ROUTES POUR PV DE TRIMESTRE ===

    // Liste des trimestres disponibles pour une série
    Route::get('/trimesters/{classSeriesId}', [App\Http\Controllers\PVController::class, 'getAvailableTrimesters'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // Générer le PV de trimestre en PDF
    Route::get('/trimester/generate/{classSeriesId}/{trimesterId}', [App\Http\Controllers\PVController::class, 'generateTrimester'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);

    // Prévisualiser le PV de trimestre (HTML)
    Route::get('/trimester/preview/{classSeriesId}/{trimesterId}', [App\Http\Controllers\PVController::class, 'previewTrimester'])
        ->middleware(['role:admin,principal,secretaire,comptable_superieur']);
});

// Routes pour les Fiches de Report de Notes (Mark Sheets)
Route::middleware(['auth:api'])->prefix('mark-sheets')->group(function () {
    // Générer une fiche vierge pour une matière spécifique
    Route::post('/generate-blank', [MarkSheetController::class, 'generateBlankMarkSheet'])
        ->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

    // Générer une fiche avec les notes réelles déjà saisies
    Route::post('/generate-filled', [MarkSheetController::class, 'generateFilledMarkSheet'])
        ->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);

    // Lister toutes les matières d'une classe pour génération
    Route::post('/generate-all', [MarkSheetController::class, 'generateAllBlankMarkSheetsForClass'])
        ->middleware(['role:admin,principal,secretaire,accountant,comptable_superieur']);
});

// Routes pour la gestion des tickets de bus
Route::middleware(['auth:api'])->prefix('bus')->group(function () {
    // Configuration du tarif (Admin et Comptables)
    Route::get('/settings', [BusController::class, 'getSettings'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    Route::put('/settings', [BusController::class, 'updateSettings'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Gestion des abonnements (Admin et Comptable)
    Route::post('/subscriptions', [BusController::class, 'createSubscription'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    Route::get('/subscriptions', [BusController::class, 'listSubscriptions'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Téléchargement des tickets PDF
    Route::get('/subscriptions/{subscriptionId}/download', [BusController::class, 'downloadTickets'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Rapport financier transport
    Route::get('/report', [BusController::class, 'getTransportReport'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);

    // Scanner un ticket QR (pour le chauffeur ou surveillant)
    Route::post('/scan-ticket', [BusController::class, 'scanTicket'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire,teacher,supervisor']);

    // ============ TICKETS JOURNALIERS (NOUVEAU SYSTÈME) ============

    // Génération de lots de tickets
    Route::post('/tickets/generate-batch', [App\Http\Controllers\BusTicketController::class, 'generateBatch'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Télécharger le lot complet de tickets en PDF
    Route::get('/tickets/batches/{batchId}/download', [App\Http\Controllers\BusTicketController::class, 'downloadBatchTickets'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Gestion des lots
    Route::get('/tickets/batches', [App\Http\Controllers\BusTicketController::class, 'getBatches'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    Route::get('/tickets/batches/today', [App\Http\Controllers\BusTicketController::class, 'getTodayBatches'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    Route::post('/tickets/batches/{batchId}/deactivate', [App\Http\Controllers\BusTicketController::class, 'deactivateBatch'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Vente de tickets
    Route::post('/tickets/sell', [App\Http\Controllers\BusTicketController::class, 'sellTicket'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Gestion des ventes
    Route::get('/tickets/sales', [App\Http\Controllers\BusTicketController::class, 'getSales'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);

    Route::get('/tickets/sales/today', [App\Http\Controllers\BusTicketController::class, 'getTodaySales'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Rapports
    Route::get('/tickets/daily-report', [App\Http\Controllers\BusTicketController::class, 'getDailyReport'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);

    // Télécharger un ticket PDF
    Route::get('/tickets/{saleId}/download', [App\Http\Controllers\BusTicketController::class, 'downloadTicket'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Validation de ticket (scan)
    Route::post('/tickets/validate', [App\Http\Controllers\BusTicketController::class, 'validateTicket'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire,teacher,supervisor']);
});

// ============================================
// UNIFORM SALES (VENTE DE TENUES SCOLAIRES)
// ============================================
Route::prefix('uniforms')->middleware('auth:api')->group(function () {

    // ============ STOCK MANAGEMENT ============

    // Get all uniform items with variants
    Route::get('/items', [App\Http\Controllers\UniformController::class, 'getItems'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Create new uniform item
    Route::post('/items', [App\Http\Controllers\UniformController::class, 'createItem'])
        ->middleware(['role:admin,comptable_superieur']);

    // Update uniform item
    Route::put('/items/{id}', [App\Http\Controllers\UniformController::class, 'updateItem'])
        ->middleware(['role:admin,comptable_superieur']);

    // Delete uniform item
    Route::delete('/items/{id}', [App\Http\Controllers\UniformController::class, 'deleteItem'])
        ->middleware(['role:admin,comptable_superieur']);

    // Create variant for an item
    Route::post('/items/{itemId}/variants', [App\Http\Controllers\UniformController::class, 'createVariant'])
        ->middleware(['role:admin,comptable_superieur']);

    // Update variant stock
    Route::put('/variants/{variantId}/stock', [App\Http\Controllers\UniformController::class, 'updateVariantStock'])
        ->middleware(['role:admin,comptable_superieur']);

    // Get low stock items
    Route::get('/low-stock', [App\Http\Controllers\UniformController::class, 'getLowStockItems'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // ============ ORDER MANAGEMENT ============

    // Place new order
    Route::post('/orders', [App\Http\Controllers\UniformController::class, 'placeOrder'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Get orders with filters
    Route::get('/orders', [App\Http\Controllers\UniformController::class, 'getOrders'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);

    // Get single order details
    Route::get('/orders/{orderId}', [App\Http\Controllers\UniformController::class, 'getOrderDetails'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);

    // Mark order as ready
    Route::post('/orders/{orderId}/ready', [App\Http\Controllers\UniformController::class, 'markOrderReady'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Cancel order
    Route::post('/orders/{orderId}/cancel', [App\Http\Controllers\UniformController::class, 'cancelOrder'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Get pending orders summary
    Route::get('/orders-summary', [App\Http\Controllers\UniformController::class, 'getPendingOrdersSummary'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // ============ PICKUP & PAYMENT ============

    // Process pickup and payment
    Route::post('/orders/{orderId}/pickup', [App\Http\Controllers\UniformController::class, 'processPickup'])
        ->middleware(['role:admin,accountant,comptable_superieur']);

    // Download receipt PDF
    Route::get('/sales/{saleId}/receipt', [App\Http\Controllers\UniformController::class, 'downloadReceipt'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // ============ REPORTS ============

    // Get sales report
    Route::get('/sales-report', [App\Http\Controllers\UniformController::class, 'getSalesReport'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal']);
});

// ============================================
// CLASS SCHOOL FEES SHEET (FICHE DE SCOLARITÉ PAR CLASSE)
// ============================================
Route::prefix('class-fees-sheet')->middleware('auth:api')->group(function () {

    // Get school classes (ex: 6ème, 5ème)
    Route::get('/school-classes', [App\Http\Controllers\ClassSchoolFeesSheetController::class, 'getSchoolClasses'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Get class series for a specific school class (ex: 6ème A, 6ème B)
    Route::get('/class-series', [App\Http\Controllers\ClassSchoolFeesSheetController::class, 'getClassSeries'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Get class fees data for a specific class series
    Route::get('/{classSeriesId}/data', [App\Http\Controllers\ClassSchoolFeesSheetController::class, 'getClassFeesData'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);

    // Download class fees PDF for a specific class series
    Route::get('/{classSeriesId}/download', [App\Http\Controllers\ClassSchoolFeesSheetController::class, 'downloadClassFeesPDF'])
        ->middleware(['role:admin,accountant,comptable_superieur,principal,secretaire']);
});

// ============================================
// SUBJECT GROUPS - Gestion des groupes de matières
// ============================================
Route::prefix('subject-groups')->group(function () {
    // Get all subject groups (lecture seule pour secretaire)
    Route::get('/groups', [App\Http\Controllers\Api\SubjectGroupController::class, 'getAllGroups'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Create a new subject group
    Route::post('/groups', [App\Http\Controllers\Api\SubjectGroupController::class, 'createGroup'])
        ->middleware(['role:admin,principal,directeur_etudes']);

    // Update a subject group (name, code, colors, etc.)
    Route::put('/groups/{id}', [App\Http\Controllers\Api\SubjectGroupController::class, 'updateGroupName'])
        ->middleware(['role:admin,principal,directeur_etudes']);

    // Delete a subject group
    Route::delete('/groups/{id}', [App\Http\Controllers\Api\SubjectGroupController::class, 'deleteGroup'])
        ->middleware(['role:admin,principal,directeur_etudes']);

    // Get all subjects with their groups (lecture seule pour secretaire)
    Route::get('/', [App\Http\Controllers\Api\SubjectGroupController::class, 'index'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Update a single subject's group
    Route::put('/{id}', [App\Http\Controllers\Api\SubjectGroupController::class, 'updateGroup'])
        ->middleware(['role:admin,principal,directeur_etudes']);

    // Bulk update subject groups
    Route::post('/bulk-update', [App\Http\Controllers\Api\SubjectGroupController::class, 'bulkUpdate'])
        ->middleware(['role:admin,principal,directeur_etudes']);
});


// ============================================
// HONOR ROLL - Tableau d'Honneur
// ============================================
Route::prefix('honor-rolls')->middleware('auth:api')->group(function () {
    // Get all filters (sections, levels, classes, series, trimesters)
    Route::get('/filters', [HonorRollController::class, 'getFilters'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Get eligible students for honor roll (moyenne >= 12/20)
    Route::post('/eligible-students', [HonorRollController::class, 'getEligibleStudents'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Generate honor roll certificate for a student
    Route::post('/generate-certificate', [HonorRollController::class, 'generateCertificate'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Download honor roll certificate
    Route::get('/download/{filename}', [HonorRollController::class, 'downloadCertificate'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire,parent']);

    // Batch generate certificates for multiple students
    Route::post('/batch-generate', [HonorRollController::class, 'batchGenerateCertificates'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Merge certificates into single PDF
    Route::post('/merge', [HonorRollController::class, 'mergeCertificates'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Get merge progress
    Route::get('/merge-progress/{jobId}', [HonorRollController::class, 'getMergeProgress'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);

    // Download merged certificate PDF
    Route::get('/merged/{mergedId}/download', [HonorRollController::class, 'downloadMergedCertificate'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire,parent']);

    // List all merged certificate PDFs
    Route::get('/merged', [HonorRollController::class, 'listMergedCertificates'])
        ->middleware(['role:admin,principal,directeur_etudes,secretaire']);
});


// ============================================
// STUDENT DISCIPLINE - Gestion de la discipline
// ============================================
Route::prefix('discipline')->middleware('auth:api')->group(function () {
    // Get discipline data for a student
    Route::get('/student/{studentId}', [App\Http\Controllers\StudentDisciplineController::class, 'show'])
        ->middleware(['role:admin,principal,secretaire,surveillant_general,surveillant_secteur']);

    // Get discipline data for all students in a class
    Route::get('/class', [App\Http\Controllers\StudentDisciplineController::class, 'getByClass'])
        ->middleware(['role:admin,principal,secretaire,surveillant_general,surveillant_secteur']);

    // Store or update discipline data for a student
    Route::post('/store', [App\Http\Controllers\StudentDisciplineController::class, 'store'])
        ->middleware(['role:admin,principal,secretaire,surveillant_general,surveillant_secteur']);

    // Bulk update discipline data
    Route::post('/bulk-store', [App\Http\Controllers\StudentDisciplineController::class, 'bulkStore'])
        ->middleware(['role:admin,principal,secretaire,surveillant_general,surveillant_secteur']);

    // Delete discipline record
    Route::delete('/{id}', [App\Http\Controllers\StudentDisciplineController::class, 'destroy'])
        ->middleware(['role:admin,principal']);
});
