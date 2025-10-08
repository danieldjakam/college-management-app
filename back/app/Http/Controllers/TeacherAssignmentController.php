<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\ClassSeriesSubject;
use App\Models\MainTeacher;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentController extends Controller
{
    /**
     * Lister toutes les affectations
     */
    public function index(Request $request)
    {
        try {
            // Construire la requête avec Eloquent pour utiliser les relations
            $query = TeacherAssignment::with([
                'teacher',
                'classSeriesSubject.subject',
                'classSeriesSubject.classSeries.schoolClass.level',
                'schoolYear'
            ]);

            // Filtrer par enseignant
            if ($request->has('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            // Filtrer par année scolaire
            if ($request->has('school_year_id')) {
                $query->where('school_year_id', $request->school_year_id);
            } else {
                // Par défaut, filtrer par l'année scolaire courante
                $currentYear = SchoolYear::where('is_current', true)->first();
                if ($currentYear) {
                    $query->where('school_year_id', $currentYear->id);
                }
            }

            // Filtrer par matière via la relation
            if ($request->has('subject_id')) {
                $query->whereHas('classSeriesSubject', function($q) use ($request) {
                    $q->where('subject_id', $request->subject_id);
                });
            }

            // Filtrer par série via la relation
            if ($request->has('class_series_id')) {
                $query->whereHas('classSeriesSubject', function($q) use ($request) {
                    $q->where('class_series_id', $request->class_series_id);
                });
            }

            // Filtrer par statut actif
            if ($request->has('active')) {
                $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }

            $assignments = $query->orderBy('teacher_id')
                                ->orderBy('class_series_subject_id')
                                ->get();

            // Transformer les données pour ajouter l'alias "series_subject"
            // (pour compatibilité avec le frontend)
            $assignments->transform(function($assignment) {
                $assignment->series_subject = $assignment->classSeriesSubject;
                return $assignment;
            });

            return response()->json([
                'success' => true,
                'data' => $assignments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des affectations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les affectations d'un enseignant
     */
    public function getByTeacher(Teacher $teacher, Request $request)
    {
        try {
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;

            $assignments = TeacherAssignment::where('teacher_id', $teacher->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->with([
                    'classSeriesSubject.subject',
                    'classSeriesSubject.classSeries.schoolClass.level',
                    'schoolYear'
                ])
                ->get();

            // Ajouter les informations sur les classes où il est professeur principal
            $mainTeacherClasses = MainTeacher::where('teacher_id', $teacher->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->with(['schoolClass.level'])
                ->get();

            // Récupérer les informations de l'année scolaire
            $schoolYear = \App\Models\SchoolYear::find($schoolYearId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'assignments' => $assignments,
                    'main_teacher_classes' => $mainTeacherClasses,
                    'school_year' => $schoolYear
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des affectations de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affecter un enseignant à une matière dans une série
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:teachers,id',
                'class_series_subject_id' => 'required|exists:class_series_subjects,id',
                'school_year_id' => 'nullable|exists:school_years,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Utiliser l'année scolaire courante si non spécifiée
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;
            
            if (!$schoolYearId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Année scolaire non trouvée'
                ], 422);
            }

            // Vérifier si cette matière est déjà assignée à UN AUTRE enseignant dans cette classe
            $existingAssignment = TeacherAssignment::where('class_series_subject_id', $request->class_series_subject_id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->with(['teacher', 'classSeriesSubject.subject', 'classSeriesSubject.classSeries'])
                ->first();

            if ($existingAssignment) {
                // Si c'est le même enseignant, message spécifique
                if ($existingAssignment->teacher_id == $request->teacher_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet enseignant est déjà affecté à cette matière dans cette classe'
                    ], 422);
                }

                // Si c'est un autre enseignant, indiquer qui enseigne déjà cette matière
                $teacherName = $existingAssignment->teacher->full_name ??
                               ($existingAssignment->teacher->first_name . ' ' . $existingAssignment->teacher->last_name);
                $subjectName = $existingAssignment->classSeriesSubject->subject->name ?? 'cette matière';
                $className = $existingAssignment->classSeriesSubject->classSeries->name ?? 'cette classe';

                return response()->json([
                    'success' => false,
                    'message' => "{$subjectName} en {$className} est déjà enseigné(e) par {$teacherName}"
                ], 422);
            }

            $assignment = TeacherAssignment::create([
                'teacher_id' => $request->teacher_id,
                'class_series_subject_id' => $request->class_series_subject_id,
                'school_year_id' => $schoolYearId,
                'is_active' => true
            ]);

            $assignment->load([
                'teacher',
                'classSeriesSubject.subject',
                'classSeriesSubject.classSeries.schoolClass.level',
                'schoolYear'
            ]);

            // Envoyer notification WhatsApp à l'enseignant
            try {
                $whatsAppService = app(WhatsAppService::class);
                $whatsAppService->sendTeacherAssignmentNotification($assignment);
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas faire échouer l'affectation
                \Log::error('Erreur envoi notification WhatsApp affectation enseignant', [
                    'assignment_id' => $assignment->id,
                    'teacher_id' => $assignment->teacher_id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Enseignant affecté avec succès',
                'data' => $assignment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'affectation de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une affectation
     */
    public function destroy(TeacherAssignment $assignment)
    {
        try {
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Affectation supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'affectation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver une affectation
     */
    public function toggleStatus(TeacherAssignment $assignment)
    {
        try {
            $assignment->update([
                'is_active' => !$assignment->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut de l\'affectation mis à jour avec succès',
                'data' => $assignment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affecter un enseignant à plusieurs matières en lot
     */
    public function bulkAssign(Request $request, Teacher $teacher)
    {
        try {
            $validator = Validator::make($request->all(), [
                'school_year_id' => 'nullable|exists:school_years,id',
                'class_series_subjects' => 'required|array',
                'class_series_subjects.*' => 'exists:class_series_subjects,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Utiliser l'année scolaire courante si non spécifiée
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;
            
            if (!$schoolYearId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Année scolaire non trouvée'
                ], 422);
            }

            DB::beginTransaction();

            $assignments = [];

            foreach ($request->class_series_subjects as $classSeriesSubjectId) {
                // Vérifier si l'affectation existe déjà
                $existingAssignment = TeacherAssignment::where('teacher_id', $teacher->id)
                    ->where('class_series_subject_id', $classSeriesSubjectId)
                    ->where('school_year_id', $schoolYearId)
                    ->first();

                if (!$existingAssignment) {
                    $assignment = TeacherAssignment::create([
                        'teacher_id' => $teacher->id,
                        'class_series_subject_id' => $classSeriesSubjectId,
                        'school_year_id' => $schoolYearId,
                        'is_active' => true
                    ]);
                    $assignments[] = $assignment;
                }
            }

            DB::commit();

            // Charger les relations et envoyer notifications
            $whatsAppService = app(WhatsAppService::class);
            foreach ($assignments as $assignment) {
                $assignment->load([
                    'teacher',
                    'classSeriesSubject.subject',
                    'classSeriesSubject.classSeries.schoolClass.level',
                    'schoolYear'
                ]);
                
                // Envoyer notification WhatsApp à l'enseignant
                try {
                    $whatsAppService->sendTeacherAssignmentNotification($assignment);
                } catch (\Exception $e) {
                    // Log l'erreur mais continuer avec les autres notifications
                    \Log::error('Erreur envoi notification WhatsApp affectation en lot', [
                        'assignment_id' => $assignment->id,
                        'teacher_id' => $assignment->teacher_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Affectations créées avec succès',
                'data' => $assignments
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors des affectations en lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les matières disponibles pour un enseignant (matières configurées mais non affectées)
     */
    public function getAvailableSubjects(Teacher $teacher, Request $request)
    {
        try {
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;

            // Obtenir les matières déjà affectées à cet enseignant
            $assignedClassSeriesSubjectIds = TeacherAssignment::where('teacher_id', $teacher->id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->pluck('class_series_subject_id');

            // Obtenir toutes les matières configurées mais non affectées
            $availableClassSeriesSubjects = ClassSeriesSubject::whereNotIn('id', $assignedClassSeriesSubjectIds)
                ->where('is_active', true)
                ->with(['subject', 'classSeries.schoolClass.level'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $availableClassSeriesSubjects
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des matières disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}