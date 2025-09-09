<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolYear;
use App\Models\DailyAttendanceState;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileAttendanceController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Récupérer les niveaux d'une section
     */
    public function getLevelsBySection($sectionId)
    {
        try {
            $section = Section::findOrFail($sectionId);
            
            $levels = Level::where('section_id', $sectionId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'section_id']);

            return response()->json([
                'success' => true,
                'data' => $levels
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting levels by section', [
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des niveaux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les classes d'un niveau
     */
    public function getClassesByLevel($levelId)
    {
        try {
            $level = Level::findOrFail($levelId);
            
            $classes = SchoolClass::where('level_id', $levelId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'level_id']);

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting classes by level', [
                'level_id' => $levelId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les séries d'une classe
     */
    public function getSeriesByClass($classId)
    {
        try {
            $class = SchoolClass::findOrFail($classId);
            
            $series = ClassSeries::select([
                'class_series.id',
                'class_series.name',
                'class_series.class_id',
                DB::raw("CONCAT(school_classes.name, ' ', class_series.name) as full_name")
            ])
            ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
            ->where('class_series.class_id', $classId)
            ->where('class_series.is_active', true)
            ->orderBy('class_series.name')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $series
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting series by class', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les étudiants d'une série avec l'état d'appel
     */
    public function getStudentsBySeries($seriesId)
    {
        try {
            $series = ClassSeries::with(['schoolClass.level.section'])->findOrFail($seriesId);
            
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $date = request()->get('date', now()->format('Y-m-d'));
            
            // Obtenir ou créer l'état d'appel quotidien pour cette série
            $attendanceState = DailyAttendanceState::getOrCreateForSeriesAndDate(
                $seriesId, 
                $date, 
                $currentSchoolYear->id
            );

            $students = Student::where('class_series_id', $seriesId)
                ->where('school_year_id', $currentSchoolYear->id)
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get([
                    'id',
                    'student_number',
                    'first_name',
                    'last_name',
                    'name',
                    'subname',
                    'order'
                ])
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'student_number' => $student->student_number,
                        'first_name' => $student->first_name ?: $student->name,
                        'last_name' => $student->last_name ?: $student->subname,
                        'order' => $student->order ?: 1,
                        'attendance_status' => null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'series_info' => [
                        'id' => $series->id,
                        'name' => $series->name,
                        'class_name' => $series->schoolClass->name,
                        'level_name' => $series->schoolClass->level->name,
                        'section_name' => $series->schoolClass->level->section->name
                    ],
                    'attendance_state' => [
                        'date' => $date,
                        'can_take_entry' => $attendanceState->canDoEntry(),
                        'can_take_exit' => $attendanceState->canDoExit(),
                        'entry_state' => $attendanceState->entry_state,
                        'exit_state' => $attendanceState->exit_state,
                        'entry_completed_at' => $attendanceState->entry_completed_at?->format('H:i'),
                        'exit_completed_at' => $attendanceState->exit_completed_at?->format('H:i'),
                        'is_day_completed' => $attendanceState->isDayCompleted()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting students by series', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soumettre les présences d'une série complète avec gestion des états
     */
    public function submitBulkAttendance(Request $request)
    {
        $request->validate([
            'series_id' => 'required|integer|exists:class_series,id',
            'event_type' => 'required|in:entry,exit',
            'attendance_date' => 'required|date',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|integer|exists:students,id',
            'students.*.is_present' => 'required|boolean',
            'students.*.student_number' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'is_modification' => 'nullable|boolean'
        ]);

        try {
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $series = ClassSeries::with(['schoolClass.level.section'])->findOrFail($request->series_id);
            $attendanceDate = Carbon::parse($request->attendance_date);
            $studentsData = $request->students;
            $eventType = $request->event_type;
            
            // Vérifier si l'appel peut être fait
            $attendanceState = DailyAttendanceState::getOrCreateForSeriesAndDate(
                $request->series_id,
                $attendanceDate,
                $currentSchoolYear->id
            );

            $isModification = $request->input('is_modification', false);

            // Pour les modifications, on vérifie différemment
            if (!$isModification) {
                if (!DailyAttendanceState::canTakeAttendance($request->series_id, $eventType, $attendanceDate, $currentSchoolYear->id)) {
                    $errorMessage = $eventType === 'entry' 
                        ? 'L\'appel d\'entrée a déjà été effectué pour cette classe aujourd\'hui'
                        : 'L\'appel de sortie ne peut pas être effectué (entrée non complétée ou sortie déjà effectuée)';
                        
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'state' => [
                            'entry_state' => $attendanceState->entry_state,
                            'exit_state' => $attendanceState->exit_state
                        ]
                    ], 422);
                }
            } else {
                // Pour les modifications, vérifier que l'appel original existe
                if ($eventType === 'entry' && !$attendanceState->canModifyEntry()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de modifier l\'appel d\'entrée pour cette date',
                        'state' => [
                            'entry_state' => $attendanceState->entry_state,
                            'exit_state' => $attendanceState->exit_state
                        ]
                    ], 422);
                }
                
                if ($eventType === 'exit' && !$attendanceState->canModifyExit()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de modifier l\'appel de sortie pour cette date',
                        'state' => [
                            'entry_state' => $attendanceState->entry_state,
                            'exit_state' => $attendanceState->exit_state
                        ]
                    ], 422);
                }
            }
            
            $successCount = 0;
            $errorCount = 0;
            $presentCount = 0;
            $absentCount = 0;
            $errors = [];

            DB::beginTransaction();
            
            // Marquer l'état comme "en cours" seulement si ce n'est pas une modification
            if (!$isModification) {
                if ($eventType === 'entry') {
                    $attendanceState->markEntryInProgress(auth()->id());
                } else {
                    $attendanceState->markExitInProgress(auth()->id());
                }
            }

            foreach ($studentsData as $studentData) {
                try {
                    $student = Student::findOrFail($studentData['student_id']);
                    
                    if ($isModification) {
                        // Pour les modifications, chercher l'enregistrement existant
                        $existingAttendance = Attendance::where('student_id', $student->id)
                            ->whereDate('attendance_date', $attendanceDate)
                            ->where('event_type', $eventType)
                            ->where('school_year_id', $currentSchoolYear->id)
                            ->first();
                            
                        if ($existingAttendance) {
                            // Mettre à jour l'enregistrement existant
                            $existingAttendance->update([
                                'is_present' => $studentData['is_present'],
                                'notes' => $request->notes,
                                'supervisor_id' => auth()->id(),
                                'scanned_at' => now()
                            ]);
                            $attendance = $existingAttendance;
                        } else {
                            // Créer un nouvel enregistrement (présent ou absent)
                            $attendance = Attendance::create([
                                'student_id' => $student->id,
                                'supervisor_id' => auth()->id(),
                                'school_class_id' => $series->schoolClass->id,
                                'event_type' => $eventType,
                                'attendance_date' => $attendanceDate,
                                'scanned_at' => now(),
                                'is_present' => $studentData['is_present'],
                                'school_year_id' => $currentSchoolYear->id,
                                'notes' => $request->notes,
                                'parent_notified' => false
                            ]);
                        }
                    } else {
                        // Mode normal: créer uniquement pour les présents
                        if ($studentData['is_present']) {
                            $attendance = Attendance::create([
                                'student_id' => $student->id,
                                'supervisor_id' => auth()->id(),
                                'school_class_id' => $series->schoolClass->id,
                                'event_type' => $eventType,
                                'attendance_date' => $attendanceDate,
                                'scanned_at' => now(),
                                'is_present' => true,
                                'school_year_id' => $currentSchoolYear->id,
                                'notes' => $request->notes,
                                'parent_notified' => false
                            ]);
                        }
                    }
                    
                    // Envoyer notification WhatsApp au parent si présent (seulement pour nouveaux appels)
                    if ($studentData['is_present'] && isset($attendance)) {
                        // Ne pas envoyer de notification lors des modifications pour éviter les doublons
                        if (!$isModification) {
                            try {
                                $this->whatsappService->sendAttendanceNotification($attendance);
                                $attendance->update(['parent_notified' => true, 'notified_at' => now()]);
                            } catch (\Exception $e) {
                                Log::warning('WhatsApp notification failed for student', [
                                    'student_id' => $student->id,
                                    'attendance_id' => $attendance->id ?? 'unknown',
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        $presentCount++;
                    } else {
                        $absentCount++;
                    }
                    
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'student_id' => $studentData['student_id'],
                        'error' => $e->getMessage()
                    ];
                    Log::error('Error saving attendance for student', [
                        'student_id' => $studentData['student_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Marquer l'état comme "terminé" seulement si ce n'est pas une modification
            if (!$isModification) {
                if ($eventType === 'entry') {
                    $attendanceState->markEntryCompleted(auth()->id());
                } else {
                    $attendanceState->markExitCompleted(auth()->id());
                }
            }

            DB::commit();

            // Envoyer une notification WhatsApp si configuré
            try {
                $this->sendAttendanceNotification($series, $eventType, $attendanceDate, $presentCount, $absentCount);
            } catch (\Exception $e) {
                Log::warning('WhatsApp notification failed', ['error' => $e->getMessage()]);
            }

            $message = $isModification ? 'Présences modifiées avec succès' : 'Présences enregistrées avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'series_name' => $series->schoolClass->name . ' ' . $series->name,
                    'event_type' => $eventType,
                    'attendance_date' => $attendanceDate->format('d/m/Y'),
                    'total_students' => count($studentsData),
                    'present_count' => $presentCount,
                    'absent_count' => $absentCount,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                    'is_modification' => $isModification,
                    'attendance_state' => [
                        'entry_state' => $attendanceState->entry_state,
                        'exit_state' => $attendanceState->exit_state,
                        'can_take_entry' => $attendanceState->canDoEntry(),
                        'can_take_exit' => $attendanceState->canDoExit(),
                        'is_day_completed' => $attendanceState->isDayCompleted()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error submitting bulk attendance', [
                'series_id' => $request->series_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de présences avec filtres
     */
    public function getAttendanceStats(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'section_id' => 'nullable|integer|exists:sections,id',
            'level_id' => 'nullable|integer|exists:levels,id',
            'class_id' => 'nullable|integer|exists:school_classes,id',
            'series_id' => 'nullable|integer|exists:class_series,id',
        ]);

        try {
            $date = $request->input('date');
            $sectionId = $request->input('section_id');
            $levelId = $request->input('level_id');
            $classId = $request->input('class_id');
            $seriesId = $request->input('series_id');

            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            // Construction de la requête pour compter le total d'étudiants
            $totalStudentsQuery = Student::join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->where('students.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            // Appliquer les filtres
            if ($seriesId) {
                $totalStudentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $totalStudentsQuery->where('school_classes.id', $classId);
            } elseif ($levelId) {
                $totalStudentsQuery->where('levels.id', $levelId);
            } elseif ($sectionId) {
                $totalStudentsQuery->where('sections.id', $sectionId);
            }

            $totalStudents = $totalStudentsQuery->count();

            // Compter les présents pour les entrées
            $entryCountQuery = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
                ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->whereDate('attendances.attendance_date', $date)
                ->where('attendances.event_type', 'entry')
                ->where('attendances.is_present', true)
                ->where('attendances.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            // Compter les sorties
            $exitCountQuery = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
                ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->whereDate('attendances.attendance_date', $date)
                ->where('attendances.event_type', 'exit')
                ->where('attendances.is_present', true)
                ->where('attendances.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            // Appliquer les mêmes filtres aux deux requêtes
            if ($seriesId) {
                $entryCountQuery->where('students.class_series_id', $seriesId);
                $exitCountQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $entryCountQuery->where('school_classes.id', $classId);
                $exitCountQuery->where('school_classes.id', $classId);
            } elseif ($levelId) {
                $entryCountQuery->where('levels.id', $levelId);
                $exitCountQuery->where('levels.id', $levelId);
            } elseif ($sectionId) {
                $entryCountQuery->where('sections.id', $sectionId);
                $exitCountQuery->where('sections.id', $sectionId);
            }

            $entryCount = $entryCountQuery->distinct('students.id')->count();
            $exitCount = $exitCountQuery->distinct('students.id')->count();

            // Compter les sortis
            $exitedStudentsQuery = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
                ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->whereDate('attendances.attendance_date', $date)
                ->where('attendances.event_type', 'exit')
                ->where('attendances.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            // Appliquer les mêmes filtres
            if ($seriesId) {
                $exitedStudentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $exitedStudentsQuery->where('school_classes.id', $classId);
            } elseif ($levelId) {
                $exitedStudentsQuery->where('levels.id', $levelId);
            } elseif ($sectionId) {
                $exitedStudentsQuery->where('sections.id', $sectionId);
            }

            $exitedStudents = $exitedStudentsQuery->distinct('students.id')->count();

            $absentStudents = $totalStudents - $entryCount;
            $currentlyPresent = $entryCount - $exitCount;
            $attendanceRate = $totalStudents > 0 ? round(($entryCount / $totalStudents) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'total_students' => $totalStudents,
                    'entry_count' => $entryCount,
                    'exit_count' => $exitCount,
                    'attendance_rate' => $totalStudents > 0 ? round(($entryCount / $totalStudents) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting attendance stats', [
                'date' => $request->input('date'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer manuellement la présence/absence d'un étudiant
     */
    public function markStudentAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'event_type' => 'required|in:entry,exit',
            'attendance_date' => 'required|date',
            'is_present' => 'required|boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $student = Student::with(['classSeries.schoolClass.level.section'])->findOrFail($request->student_id);
            $attendanceDate = Carbon::parse($request->attendance_date);

            // Vérifier les permissions (selon les rôles autorisés)
            $user = auth()->user();
            if (!in_array($user->role, ['admin', 'teacher', 'surveillant_general', 'bibliothecaire'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à marquer les présences'
                ], 403);
            }

            // Logique de validation selon le type d'événement
            if ($request->event_type === 'entry') {
                // Vérifier qu'il n'y a pas déjà une entrée ce jour
                $existingEntry = Attendance::where('student_id', $request->student_id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->where('event_type', 'entry')
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->first();

                if ($existingEntry) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Une entrée a déjà été enregistrée pour cet étudiant aujourd\'hui',
                        'existing_entry_time' => $existingEntry->scanned_at->format('H:i')
                    ], 422);
                }
            } else {
                // Pour une sortie, vérifier qu'il y a eu une entrée
                $entryRecord = Attendance::where('student_id', $request->student_id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->where('event_type', 'entry')
                    ->where('is_present', true)
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->first();

                if (!$entryRecord) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune entrée trouvée pour cet étudiant aujourd\'hui'
                    ], 422);
                }

                // Vérifier qu'il n'y a pas déjà une sortie
                $existingExit = Attendance::where('student_id', $request->student_id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->where('event_type', 'exit')
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->first();

                if ($existingExit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Une sortie a déjà été enregistrée pour cet étudiant aujourd\'hui',
                        'existing_exit_time' => $existingExit->scanned_at->format('H:i')
                    ], 422);
                }
            }

            // Créer l'enregistrement de présence
            $attendance = Attendance::create([
                'student_id' => $request->student_id,
                'school_class_id' => $student->classSeries->class_id,
                'school_year_id' => $currentSchoolYear->id,
                'attendance_date' => $attendanceDate,
                'scanned_at' => now(),
                'is_present' => $request->is_present,
                'event_type' => $request->event_type,
                'notes' => $request->notes,
                'created_by' => auth()->id()
            ]);

            // Envoyer notification WhatsApp aux parents si présent
            if ($request->is_present) {
                try {
                    $this->whatsappService->sendAttendanceNotification($attendance);
                } catch (\Exception $e) {
                    Log::warning('WhatsApp notification failed', [
                        'attendance_id' => $attendance->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $eventLabel = $request->event_type === 'entry' ? 'Entrée' : 'Sortie';
            $statusLabel = $request->is_present ? 'Présent' : 'Absent';

            return response()->json([
                'success' => true,
                'message' => "$eventLabel marquée avec succès - $statusLabel",
                'data' => [
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'class_name' => $student->classSeries->schoolClass->name . ' ' . $student->classSeries->name,
                    'event_type' => $request->event_type,
                    'event_label' => $eventLabel,
                    'is_present' => $request->is_present,
                    'status_label' => $statusLabel,
                    'marked_at' => $attendance->scanned_at->format('H:i'),
                    'date' => $attendance->attendance_date->format('d/m/Y')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking student attendance', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la présence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer manuellement tous les étudiants absents d'une série
     */
    public function markAllAbsentInSeries(Request $request)
    {
        $request->validate([
            'series_id' => 'required|integer|exists:class_series,id',
            'attendance_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $series = ClassSeries::with(['schoolClass.level.section'])->findOrFail($request->series_id);
            $attendanceDate = Carbon::parse($request->attendance_date);

            // Récupérer tous les étudiants actifs de cette série
            $allStudents = Student::where('class_series_id', $request->series_id)
                ->where('school_year_id', $currentSchoolYear->id)
                ->where('is_active', true)
                ->get();

            // Récupérer les étudiants déjà présents (qui ont une entrée)
            $presentStudentIds = Attendance::where('school_class_id', $series->class_id)
                ->whereDate('attendance_date', $attendanceDate)
                ->where('event_type', 'entry')
                ->where('is_present', true)
                ->where('school_year_id', $currentSchoolYear->id)
                ->pluck('student_id')
                ->toArray();

            // Étudiants absents = tous - présents
            $absentStudents = $allStudents->whereNotIn('id', $presentStudentIds);

            $markedCount = 0;
            $errors = [];

            foreach ($absentStudents as $student) {
                try {
                    // Vérifier s'il n'y a pas déjà un enregistrement d'absence
                    $existingRecord = Attendance::where('student_id', $student->id)
                        ->whereDate('attendance_date', $attendanceDate)
                        ->where('school_year_id', $currentSchoolYear->id)
                        ->first();

                    if (!$existingRecord) {
                        Attendance::create([
                            'student_id' => $student->id,
                            'school_class_id' => $series->class_id,
                            'school_year_id' => $currentSchoolYear->id,
                            'attendance_date' => $attendanceDate,
                            'scanned_at' => now(),
                            'is_present' => false,
                            'event_type' => 'entry',
                            'notes' => $request->notes ?: 'Marqué absent automatiquement',
                            'created_by' => auth()->id()
                        ]);

                        $markedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "$markedCount étudiant(s) marqué(s) comme absent(s)",
                'data' => [
                    'series_name' => $series->schoolClass->name . ' ' . $series->name,
                    'total_students' => $allStudents->count(),
                    'present_students' => count($presentStudentIds),
                    'absent_students_marked' => $markedCount,
                    'attendance_date' => $attendanceDate->format('d/m/Y'),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking all absent students', [
                'series_id' => $request->series_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage des absences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut actuel d'un étudiant pour une date donnée
     */
    public function getStudentStatus(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'date' => 'nullable|date'
        ]);

        try {
            $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();

            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $student = Student::with(['classSeries.schoolClass.level.section'])
                ->findOrFail($request->student_id);

            // Récupérer les enregistrements de présence du jour
            $attendances = Attendance::where('student_id', $request->student_id)
                ->whereDate('attendance_date', $date)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at')
                ->get();

            $entryRecord = $attendances->where('event_type', 'entry')->first();
            $exitRecord = $attendances->where('event_type', 'exit')->first();

            // Déterminer le statut actuel
            $currentStatus = 'not_marked'; // Pas encore marqué
            $statusMessage = 'Aucune présence enregistrée';
            $canMarkEntry = true;
            $canMarkExit = false;

            if ($entryRecord) {
                if ($entryRecord->is_present) {
                    if ($exitRecord) {
                        $currentStatus = 'exited';
                        $statusMessage = "Sorti à " . $exitRecord->scanned_at->format('H:i');
                        $canMarkEntry = false;
                        $canMarkExit = false;
                    } else {
                        $currentStatus = 'present';
                        $statusMessage = "Présent depuis " . $entryRecord->scanned_at->format('H:i');
                        $canMarkEntry = false;
                        $canMarkExit = true;
                    }
                } else {
                    $currentStatus = 'absent';
                    $statusMessage = "Marqué absent";
                    $canMarkEntry = false;
                    $canMarkExit = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'student_number' => $student->student_number,
                        'class_name' => $student->classSeries->schoolClass->name . ' ' . $student->classSeries->name
                    ],
                    'date' => $date->format('d/m/Y'),
                    'current_status' => $currentStatus,
                    'status_message' => $statusMessage,
                    'entry_time' => $entryRecord ? $entryRecord->scanned_at->format('H:i') : null,
                    'exit_time' => $exitRecord ? $exitRecord->scanned_at->format('H:i') : null,
                    'is_present' => $entryRecord ? $entryRecord->is_present : null,
                    'can_mark_entry' => $canMarkEntry,
                    'can_mark_exit' => $canMarkExit,
                    'attendances' => $attendances->map(function ($att) {
                        return [
                            'event_type' => $att->event_type,
                            'is_present' => $att->is_present,
                            'time' => $att->scanned_at->format('H:i'),
                            'notes' => $att->notes
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting student status', [
                'student_id' => $request->student_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les états d'appel quotidiens pour une date
     */
    public function getDailyAttendanceStates(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'section_id' => 'nullable|integer|exists:sections,id',
            'level_id' => 'nullable|integer|exists:levels,id',
            'class_id' => 'nullable|integer|exists:school_classes,id'
        ]);

        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $filters = [];
            if ($request->section_id) $filters['section_id'] = $request->section_id;
            if ($request->level_id) $filters['level_id'] = $request->level_id;
            if ($request->class_id) $filters['class_id'] = $request->class_id;

            $states = DailyAttendanceState::getStatesWithSeriesInfo($date, $currentSchoolYear->id, $filters);
            $stats = DailyAttendanceState::getStatsForDate($date, $currentSchoolYear->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'states' => $states->map(function ($state) {
                        return [
                            'id' => $state->id,
                            'series_id' => $state->class_series_id,
                            'series_name' => $state->classSeries->name,
                            'class_name' => $state->classSeries->schoolClass->name,
                            'level_name' => $state->classSeries->schoolClass->level->name,
                            'section_name' => $state->classSeries->schoolClass->level->section->name,
                            'full_name' => $state->classSeries->schoolClass->name . ' ' . $state->classSeries->name,
                            'entry_state' => $state->entry_state,
                            'exit_state' => $state->exit_state,
                            'entry_completed_at' => $state->entry_completed_at?->format('H:i'),
                            'exit_completed_at' => $state->exit_completed_at?->format('H:i'),
                            'can_take_entry' => $state->canDoEntry(),
                            'can_take_exit' => $state->canDoExit(),
                            'can_reopen_entry' => $state->canReopenEntry(),
                            'can_reopen_exit' => $state->canReopenExit(),
                            'can_modify_entry' => $state->canModifyEntry(),
                            'can_modify_exit' => $state->canModifyExit(),
                            'is_day_completed' => $state->isDayCompleted(),
                            'supervisor_name' => $state->supervisor?->name
                        ];
                    }),
                    'statistics' => [
                        'total_series' => $stats->total_series ?? 0,
                        'entries_completed' => $stats->entries_completed ?? 0,
                        'exits_completed' => $stats->exits_completed ?? 0,
                        'days_completed' => $stats->days_completed ?? 0,
                        'completion_rate' => $stats->total_series > 0 
                            ? round(($stats->days_completed / $stats->total_series) * 100, 1)
                            : 0
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting daily attendance states', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des états d\'appel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'état d'appel d'une série pour une date
     */
    public function getSeriesAttendanceState($seriesId, Request $request)
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $state = DailyAttendanceState::getOrCreateForSeriesAndDate(
                $seriesId,
                $date,
                $currentSchoolYear->id
            );

            $series = ClassSeries::with(['schoolClass.level.section'])->findOrFail($seriesId);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'series_info' => [
                        'id' => $series->id,
                        'name' => $series->name,
                        'class_name' => $series->schoolClass->name,
                        'level_name' => $series->schoolClass->level->name,
                        'section_name' => $series->schoolClass->level->section->name,
                        'full_name' => $series->schoolClass->name . ' ' . $series->name
                    ],
                    'attendance_state' => [
                        'entry_state' => $state->entry_state,
                        'exit_state' => $state->exit_state,
                        'entry_completed_at' => $state->entry_completed_at?->format('H:i'),
                        'exit_completed_at' => $state->exit_completed_at?->format('H:i'),
                        'can_take_entry' => $state->canDoEntry(),
                        'can_take_exit' => $state->canDoExit(),
                        'is_day_completed' => $state->isDayCompleted(),
                        'supervisor_name' => $state->supervisor?->name
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting series attendance state', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'état d\'appel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les présences existantes d'une série pour modification
     */
    public function getExistingAttendance($seriesId, Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'event_type' => 'required|in:entry,exit'
        ]);

        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $eventType = $request->input('event_type');
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $series = ClassSeries::with(['schoolClass.level.section'])->findOrFail($seriesId);

            // Récupérer tous les étudiants de la série
            $studentsQuery = Student::where('class_series_id', $seriesId)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name');

            // Vérifier l'état d'appel pour cette série
            $attendanceState = DailyAttendanceState::getOrCreateForSeriesAndDate(
                $seriesId,
                $date,
                $currentSchoolYear->id
            );
            
            // Déterminer si l'appel a déjà été fait pour ce type d'événement
            $isCallCompleted = ($eventType === 'entry' && $attendanceState->entry_state === 'completed') ||
                              ($eventType === 'exit' && $attendanceState->exit_state === 'completed');

            $students = $studentsQuery->get()->map(function ($student) use ($date, $eventType, $currentSchoolYear, $isCallCompleted) {
                // Chercher les présences existantes pour cet élève
                $attendance = Attendance::where('student_id', $student->id)
                    ->whereDate('attendance_date', $date)
                    ->where('event_type', $eventType)
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->first();
                
                // Si pas d'enregistrement mais appel complété = élève était absent
                $attendanceStatus = null;
                if ($attendance) {
                    $attendanceStatus = $attendance->is_present ? 'present' : 'absent';
                } elseif ($isCallCompleted) {
                    $attendanceStatus = 'absent'; // Pas d'enregistrement = était absent
                }
                
                return [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_id' => $student->student_id,
                    'student_number' => $student->student_number,
                    'gender' => $student->gender,
                    'attendance_status' => $attendanceStatus,
                    'notes' => $attendance?->notes,
                    'scanned_at' => $attendance?->scanned_at?->format('H:i'),
                    'existing_attendance_id' => $attendance?->id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'event_type' => $eventType,
                    'series_info' => [
                        'id' => $series->id,
                        'name' => $series->name,
                        'class_name' => $series->schoolClass->name,
                        'level_name' => $series->schoolClass->level->name,
                        'section_name' => $series->schoolClass->level->section->name,
                        'full_name' => $series->schoolClass->name . ' ' . $series->name
                    ],
                    'students' => $students,
                    'is_modification' => true
                ],
                'message' => 'Présences existantes récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting existing attendance', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des présences existantes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialiser l'état d'appel d'une série (admin seulement)
     */
    public function resetSeriesAttendanceState($seriesId, Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'reset_type' => 'required|in:entry,exit,both'
        ]);

        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $state = DailyAttendanceState::getOrCreateForSeriesAndDate(
                $seriesId,
                $date,
                $currentSchoolYear->id
            );

            DB::beginTransaction();

            // Réinitialiser selon le type demandé
            $resetData = [];
            switch ($request->reset_type) {
                case 'entry':
                    $resetData = [
                        'entry_state' => 'not_done',
                        'entry_completed_at' => null
                    ];
                    // Supprimer les enregistrements d'entrée
                    Attendance::where('school_class_id', $state->classSeries->class_id)
                        ->whereDate('attendance_date', $date)
                        ->where('event_type', 'entry')
                        ->where('school_year_id', $currentSchoolYear->id)
                        ->delete();
                    break;
                    
                case 'exit':
                    $resetData = [
                        'exit_state' => 'not_done',
                        'exit_completed_at' => null
                    ];
                    // Supprimer les enregistrements de sortie
                    Attendance::where('school_class_id', $state->classSeries->class_id)
                        ->whereDate('attendance_date', $date)
                        ->where('event_type', 'exit')
                        ->where('school_year_id', $currentSchoolYear->id)
                        ->delete();
                    break;
                    
                case 'both':
                    $resetData = [
                        'entry_state' => 'not_done',
                        'exit_state' => 'not_done',
                        'entry_completed_at' => null,
                        'exit_completed_at' => null
                    ];
                    // Supprimer tous les enregistrements
                    Attendance::where('school_class_id', $state->classSeries->class_id)
                        ->whereDate('attendance_date', $date)
                        ->where('school_year_id', $currentSchoolYear->id)
                        ->delete();
                    break;
            }

            $state->update($resetData);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'État d\'appel réinitialisé avec succès',
                'data' => [
                    'reset_type' => $request->reset_type,
                    'new_state' => [
                        'entry_state' => $state->entry_state,
                        'exit_state' => $state->exit_state,
                        'can_take_entry' => $state->canDoEntry(),
                        'can_take_exit' => $state->canDoExit()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error resetting series attendance state', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une notification WhatsApp pour les présences
     */
    private function sendAttendanceNotification($series, $eventType, $date, $presentCount, $absentCount)
    {
        try {
            // Pour l'instant, on désactive les notifications de rapport global
            // et on se concentre sur les notifications individuelles aux parents
            
            Log::info('Rapport de présence généré', [
                'series' => $series->schoolClass->name . ' ' . $series->name,
                'event_type' => $eventType,
                'date' => $date->format('d/m/Y'),
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'total' => $presentCount + $absentCount
            ]);
            
            // TODO: Implémenter l'envoi du rapport aux administrateurs
            // Une fois que les notifications individuelles aux parents fonctionnent bien
            
        } catch (\Exception $e) {
            Log::warning('Failed to log attendance report', [
                'error' => $e->getMessage()
            ]);
        }
    }
}