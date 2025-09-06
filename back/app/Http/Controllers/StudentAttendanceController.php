<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    /**
     * Enregistrer les présences manuelles d'une classe
     */
    public function saveManualAttendance(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:school_classes,id',
                'attendance_date' => 'required|date',
                'attendance_records' => 'required|array|min:1',
                'attendance_records.*.student_id' => 'required|exists:students,id',
                'attendance_records.*.is_present' => 'required|boolean'
            ]);

            $classId = $request->class_id;
            $attendanceDate = $request->attendance_date;
            $attendanceRecords = $request->attendance_records;

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $savedRecords = [];
            $updatedRecords = [];
            $whatsAppService = new WhatsAppService();

            DB::beginTransaction();

            foreach ($attendanceRecords as $record) {
                $studentId = $record['student_id'];
                $isPresent = $record['is_present'];

                // Récupérer l'étudiant pour la notification
                $student = \App\Models\Student::find($studentId);

                // Vérifier si un enregistrement existe déjà
                $existingRecord = StudentAttendance::where('student_id', $studentId)
                    ->where('attendance_date', $attendanceDate)
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->first();

                if ($existingRecord) {
                    // Mettre à jour l'enregistrement existant
                    $existingRecord->update([
                        'is_present' => $isPresent,
                        'updated_at' => now(),
                        'marked_by' => auth()->id()
                    ]);
                    $updatedRecords[] = $existingRecord;
                    
                    // Envoyer notification WhatsApp pour la mise à jour
                    if ($student) {
                        try {
                            $whatsAppService->sendManualAttendanceNotification($existingRecord, $student);
                        } catch (\Exception $e) {
                            \Log::warning('Erreur envoi notification WhatsApp (mise à jour)', [
                                'student_id' => $studentId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    // Créer un nouvel enregistrement
                    $attendance = StudentAttendance::create([
                        'student_id' => $studentId,
                        'school_class_id' => $classId,
                        'attendance_date' => $attendanceDate,
                        'is_present' => $isPresent,
                        'school_year_id' => $currentSchoolYear->id,
                        'marked_by' => auth()->id(),
                        'attendance_type' => 'manual' // Marquer comme appel manuel
                    ]);
                    $savedRecords[] = $attendance;
                    
                    // Envoyer notification WhatsApp pour le nouvel enregistrement
                    if ($student) {
                        try {
                            $whatsAppService->sendManualAttendanceNotification($attendance, $student);
                        } catch (\Exception $e) {
                            \Log::warning('Erreur envoi notification WhatsApp (nouveau)', [
                                'student_id' => $studentId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Présences enregistrées avec succès',
                'data' => [
                    'saved' => count($savedRecords),
                    'updated' => count($updatedRecords),
                    'total' => count($savedRecords) + count($updatedRecords),
                    'date' => $attendanceDate,
                    'class_id' => $classId
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur sauvegarde présences manuelles:', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les présences d'une classe pour une date donnée
     */
    public function getDailyAttendanceByClass(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:school_classes,id',
                'date' => 'required|date'
            ]);

            $classId = $request->class_id;
            $date = $request->date;

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            // Récupérer les présences de la classe pour la date donnée
            $attendances = StudentAttendance::with(['student:id,first_name,last_name,name,student_number'])
                ->where('school_class_id', $classId)
                ->where('attendance_date', $date)
                ->where('school_year_id', $currentSchoolYear->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'meta' => [
                    'class_id' => $classId,
                    'date' => $date,
                    'total_records' => $attendances->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur récupération présences classe:', [
                'error' => $e->getMessage(),
                'params' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de présence d'une classe
     */
    public function getClassAttendanceStats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:school_classes,id',
                'date' => 'required|date'
            ]);

            $classId = $request->class_id;
            $date = $request->date;

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            // Compter les présents et absents
            $stats = StudentAttendance::where('school_class_id', $classId)
                ->where('attendance_date', $date)
                ->where('school_year_id', $currentSchoolYear->id)
                ->selectRaw('
                    COUNT(CASE WHEN is_present = 1 THEN 1 END) as total_present,
                    COUNT(CASE WHEN is_present = 0 THEN 1 END) as total_absent,
                    COUNT(*) as total_records
                ')
                ->first();

            // Obtenir le nombre total d'étudiants dans la classe
            $totalStudents = SchoolClass::find($classId)->students()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_present' => $stats->total_present ?? 0,
                    'total_absent' => $stats->total_absent ?? 0,
                    'total_records' => $stats->total_records ?? 0,
                    'total_students_in_class' => $totalStudents,
                    'attendance_rate' => $totalStudents > 0 ? round(($stats->total_present ?? 0) / $totalStudents * 100, 2) : 0,
                    'class_id' => $classId,
                    'date' => $date
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur statistiques présences classe:', [
                'error' => $e->getMessage(),
                'params' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le rapport détaillé des présences d'une classe sur une période
     */
    public function getClassAttendanceReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:school_classes,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $classId = $request->class_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            // Récupérer toutes les présences de la période
            $attendances = StudentAttendance::with(['student:id,first_name,last_name,name,student_number'])
                ->where('school_class_id', $classId)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('attendance_date', 'desc')
                ->orderBy('student_id')
                ->get();

            // Grouper par date
            $attendancesByDate = $attendances->groupBy('attendance_date');

            // Calculer les statistiques globales
            $totalDays = $attendancesByDate->count();
            $totalPresences = $attendances->where('is_present', true)->count();
            $totalAbsences = $attendances->where('is_present', false)->count();
            $totalRecords = $attendances->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances_by_date' => $attendancesByDate,
                    'stats' => [
                        'total_days' => $totalDays,
                        'total_presences' => $totalPresences,
                        'total_absences' => $totalAbsences,
                        'total_records' => $totalRecords,
                        'attendance_rate' => $totalRecords > 0 ? round($totalPresences / $totalRecords * 100, 2) : 0
                    ],
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'class_id' => $classId
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur rapport présences classe:', [
                'error' => $e->getMessage(),
                'params' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}