<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\SequenceController;
use App\Http\Controllers\TrimesterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DownloadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!', 'timestamp' => now()]);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'GSBPL School Management API is running',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});

// ===================================================================
// Authentication Routes (no middleware)
// ===================================================================
Route::prefix('users')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected user routes
    Route::middleware(['auth.user'])->group(function () {
        Route::get('/getInfos', [AuthController::class, 'getInfos']);
        Route::get('/getTeacherOrAdmin', [AuthController::class, 'getTeacherOrAdmin']);
        Route::post('/confirmAdminPassword', [AuthController::class, 'confirmAdminPassword']);
        Route::put('/edit', [AuthController::class, 'updateUserOrAdmin']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    
    // Admin only routes
    Route::middleware(['auth.user', 'auth.admin'])->group(function () {
        Route::get('/all', [AuthController::class, 'getAllAdmin']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::delete('/{id}', [AuthController::class, 'deleteAdmin']);
    });
});

// ===================================================================
// Section Routes
// ===================================================================
Route::prefix('sections')->group(function () {
    Route::get('/all', [SectionController::class, 'getAllSection']);
    Route::get('/{id}', [SectionController::class, 'getOneSection']);
    Route::get('/getNberOfClass/{id}', [SectionController::class, 'getNberOfClass']);
    Route::post('/store', [SectionController::class, 'addSection']);
    Route::put('/{id}', [SectionController::class, 'updateSection']);
    Route::delete('/{id}', [SectionController::class, 'deleteSection']);
});

// ===================================================================
// Class Routes
// ===================================================================
Route::prefix('class')->group(function () {
    Route::get('/getAll', [ClassController::class, 'getAllClass']);
    Route::get('/getOAll/{section_id}', [ClassController::class, 'getAllOClass']);
    Route::get('/special/{id}', [ClassController::class, 'getSpecialClass']);
    Route::get('/{id}', [ClassController::class, 'getOneClass']);
    Route::put('/{id}', [ClassController::class, 'updateClass']);
    Route::post('/add', [ClassController::class, 'addClass']);
    Route::delete('/{id}', [ClassController::class, 'deleteClass']);
});

// ===================================================================
// Student Routes
// ===================================================================
Route::prefix('students')->group(function () {
    Route::get('/gs', [StudentController::class, 'gAFC']);
    Route::get('/getAll', [StudentController::class, 'getAllStudent']);
    Route::get('/total', [StudentController::class, 'getTotal']);
    Route::get('/getOrdonnedStudents/{id}', [StudentController::class, 'getOrdonnedStudents']);
    Route::get('/one/{id}', [StudentController::class, 'getOneStudent']);
    Route::get('/payments/{id}', [StudentController::class, 'getPayements']);
    Route::get('/{id}', [StudentController::class, 'getSpecificStudents']);
    Route::post('/add/{id}', [StudentController::class, 'addStudent']);
    Route::put('/transfert-to', [StudentController::class, 'transfertToAotherClass']);
    Route::put('/{id}', [StudentController::class, 'updateStudent']);
    Route::delete('/{id}', [StudentController::class, 'deleteStudent']);
});

// ===================================================================
// Teacher Routes
// ===================================================================
Route::prefix('teachers')->group(function () {
    Route::get('/getAll', [TeacherController::class, 'getAllTeachers']);
    Route::get('/{id}', [TeacherController::class, 'getOneTeacher']);
    Route::post('/add', [TeacherController::class, 'addTeacher']);
    Route::put('/{id}', [TeacherController::class, 'updateTeacher']);
    Route::delete('/{id}', [TeacherController::class, 'deleteTeacher']);
    Route::get('/regeneratePassword', [TeacherController::class, 'generateNewPasswords']);
    Route::get('/downloadTeachersPassword/{payload}', [TeacherController::class, 'downloadTeachersPassword']);
});

// ===================================================================
// Subject Routes (Matières)
// ===================================================================
Route::prefix('subjects')->group(function () {
    Route::get('/all', [SubjectController::class, 'getAllSubjects']);
    Route::get('/all2/{type}', [SubjectController::class, 'all2']);
    Route::get('/{id}', [SubjectController::class, 'getOneSubject']);
    Route::post('/add', [SubjectController::class, 'addSubject']);
    Route::put('/{id}', [SubjectController::class, 'updateSubject']);
    Route::delete('/{id}', [SubjectController::class, 'deleteSubject']);
});

// ===================================================================
// Domain Routes
// ===================================================================
Route::prefix('domains')->group(function () {
    Route::get('/all', [DomainController::class, 'getAllDomains']);
    Route::get('/all2/{type}', [DomainController::class, 'all2']);
    Route::get('/{id}', [DomainController::class, 'getOneDomain']);
    Route::post('/add', [DomainController::class, 'addDomain']);
    Route::put('/{id}', [DomainController::class, 'updateDomain']);
    Route::delete('/{id}', [DomainController::class, 'deleteDomain']);
});

// ===================================================================
// Notes Routes
// ===================================================================
Route::prefix('notes')->group(function () {
    Route::get('/getByTrim/{id}', [NoteController::class, 'getNotesByTrim']);
    Route::get('/getByTrimPeople/{trim_id}/{student_id}', [NoteController::class, 'getNotesByTrimPeoPle']);
    Route::get('/getAll', [NoteController::class, 'getAllNotes']);
    Route::get('/gt/{exam_id}/{class_id}', [NoteController::class, 'getTotalPoints']);
    Route::get('/all/{class_id}/{exam_id}', [NoteController::class, 'getNotes']);
    Route::post('/addOrUpdate', [NoteController::class, 'addOrUpdateNote']);
    Route::get('/all2/{class_id}/{exam_id}', [NoteController::class, 'getNotes2']);
    Route::post('/addOrUpdate2', [NoteController::class, 'addOrUpdateNotes2']);
    Route::get('/all3/{class_id}/{exam_id}', [NoteController::class, 'getNotes3']);
    Route::post('/addOrUpdate3', [NoteController::class, 'addOrUpdateNotes3']);
    Route::post('/addOrUpdateStats', [NoteController::class, 'addOrUpdateStats']);
    Route::post('/calTrimNotes2', [NoteController::class, 'calTrimNotes2']);
});

// ===================================================================
// Sequence Routes
// ===================================================================
Route::prefix('seq')->group(function () {
    Route::get('/getAll', [SequenceController::class, 'getAllSequences']);
    Route::get('/{id}', [SequenceController::class, 'getOneSequence']);
    Route::post('/add', [SequenceController::class, 'addSequence']);
    Route::put('/{id}', [SequenceController::class, 'updateSequence']);
    Route::delete('/{id}', [SequenceController::class, 'deleteSequence']);
});

// ===================================================================
// Trimester Routes
// ===================================================================
Route::prefix('trim')->group(function () {
    Route::get('/getAll', [TrimesterController::class, 'getAllTrimesters']);
    Route::get('/{id}', [TrimesterController::class, 'getOneTrimester']);
    Route::post('/add', [TrimesterController::class, 'addTrimester']);
    Route::put('/{id}', [TrimesterController::class, 'updateTrimester']);
    Route::delete('/{id}', [TrimesterController::class, 'deleteTrimester']);
});

// ===================================================================
// Settings Routes
// ===================================================================
Route::prefix('settings')->group(function () {
    Route::get('/getSettings', [SettingController::class, 'getAllSettings']);
    Route::post('/setSettings', [SettingController::class, 'updateMultipleSettings']);
});

// ===================================================================
// Download Routes
// ===================================================================
Route::prefix('download')->group(function () {
    Route::get('/csv/students/{id}', [DownloadController::class, 'downloadClassList']);
    Route::get('/pdf/students/{id}/{array}', [DownloadController::class, 'downloadStudentsPdf']);
    Route::get('/table/students/{id}', [DownloadController::class, 'downloadTable']);
    Route::get('/pdf/insolvables/{id}/{type}/{payload}', [DownloadController::class, 'downloadInsolvablesList']);
    Route::get('/pdf/bul5/{class_id}/{student_id}/{exam_id}', [DownloadController::class, 'downloadBulletin5']);
    Route::get('/pdf/bul4/{class_id}/{student_id}/{exam_id}', [DownloadController::class, 'downloadBulletin4']);
    Route::get('/pdf/bul2/{class_id}/{student_id}/{exam_id}', [DownloadController::class, 'downloadBulletin2']);
    Route::get('/pdf/bul1/{class_id}/{student_id}/{exam_id}', [DownloadController::class, 'downloadBulletin1']);
    Route::get('/pdf/bul/{class_id}/{student_id}/{exam_id}', [DownloadController::class, 'downloadStudentBulletin']);
    Route::get('/pdf/bul/{class_id}/{exam_id}', [DownloadController::class, 'downloadClassBulletin']);
    Route::get('/recu/{student_id}/{amount}/{diff}/{payload}', [DownloadController::class, 'downloadRecu']);
    Route::get('/recu2/{student_id}/{amount}/{diff}/{payload}', [DownloadController::class, 'downloadRecu2']);
    Route::get('/recette/{type}/{date}/{to}', [DownloadController::class, 'downloadRecette']);
    Route::get('/etat', [DownloadController::class, 'etat']);
    Route::get('/recu/{recu_name}', [DownloadController::class, 'getRecu']);
    Route::get('/note/{class_id}/{section}', [DownloadController::class, 'getCsvNoteImport']);
});