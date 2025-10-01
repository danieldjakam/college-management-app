<?php

namespace App\Http\Controllers;

use App\Models\MainTeacher;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MainTeacherController extends Controller
{
    /**
     * Lister tous les professeurs principaux
     */
    public function index(Request $request)
    {
        try {
            $query = MainTeacher::with([
                'teacher',
                'classSeries.schoolClass.level',
                'schoolYear'
            ]);

            // Filtrer par année scolaire
            if ($request->has('school_year_id')) {
                $query->where('school_year_id', $request->school_year_id);
            } else {
                // Par défaut, année scolaire courante
                $currentYear = SchoolYear::where('is_current', true)->first();
                if ($currentYear) {
                    $query->where('school_year_id', $currentYear->id);
                }
            }

            // Filtrer par série de classe
            if ($request->has('class_series_id')) {
                $query->where('class_series_id', $request->class_series_id);
            }

            // Filtrer par enseignant
            if ($request->has('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            // Filtrer par statut actif
            if ($request->has('active')) {
                $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }

            $mainTeachers = $query->orderBy('class_series_id')
                                 ->get();

            return response()->json([
                'success' => true,
                'data' => $mainTeachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des professeurs principaux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Désigner un professeur principal pour une classe
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:teachers,id',
                'class_series_id' => 'required|exists:class_series,id',
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

            DB::beginTransaction();

            // Vérifier s'il y a déjà un professeur principal pour cette série cette année
            $existingMainTeacher = MainTeacher::where('class_series_id', $request->class_series_id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->first();

            if ($existingMainTeacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette série a déjà un professeur principal pour cette année scolaire'
                ], 422);
            }

            // Vérifier si cet enseignant est déjà professeur principal d'une autre classe
            $teacherHasClass = MainTeacher::where('teacher_id', $request->teacher_id)
                ->where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->exists();

            if ($teacherHasClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant est déjà professeur principal d\'une autre série'
                ], 422);
            }

            $mainTeacher = MainTeacher::create([
                'teacher_id' => $request->teacher_id,
                'class_series_id' => $request->class_series_id,
                'school_year_id' => $schoolYearId,
                'is_active' => true
            ]);

            DB::commit();

            $mainTeacher->load([
                'teacher',
                'classSeries.schoolClass.level',
                'schoolYear'
            ]);

            // Envoyer notification WhatsApp au professeur principal
            try {
                $whatsAppService = app(WhatsAppService::class);
                $whatsAppService->sendMainTeacherNotification($mainTeacher);
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas faire échouer la désignation
                \Log::error('Erreur envoi notification WhatsApp professeur principal', [
                    'main_teacher_id' => $mainTeacher->id,
                    'teacher_id' => $mainTeacher->teacher_id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Professeur principal désigné avec succès',
                'data' => $mainTeacher
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désignation du professeur principal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un professeur principal
     */
    public function update(Request $request, MainTeacher $mainTeacher)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:teachers,id',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Si on change d'enseignant, vérifier qu'il n'est pas déjà professeur principal ailleurs
            if ($request->teacher_id != $mainTeacher->teacher_id) {
                $teacherHasClass = MainTeacher::where('teacher_id', $request->teacher_id)
                    ->where('school_year_id', $mainTeacher->school_year_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $mainTeacher->id)
                    ->exists();

                if ($teacherHasClass) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet enseignant est déjà professeur principal d\'une autre série'
                    ], 422);
                }
            }

            $mainTeacher->update([
                'teacher_id' => $request->teacher_id,
                'is_active' => $request->is_active ?? $mainTeacher->is_active
            ]);

            DB::commit();

            $mainTeacher->load([
                'teacher',
                'classSeries.schoolClass.level',
                'schoolYear'
            ]);

            // Envoyer notification WhatsApp si changement d'enseignant
            if ($request->teacher_id != $mainTeacher->getOriginal('teacher_id')) {
                try {
                    $whatsAppService = app(WhatsAppService::class);
                    $whatsAppService->sendMainTeacherNotification($mainTeacher);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas faire échouer la mise à jour
                    \Log::error('Erreur envoi notification WhatsApp mise à jour professeur principal', [
                        'main_teacher_id' => $mainTeacher->id,
                        'teacher_id' => $mainTeacher->teacher_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Professeur principal mis à jour avec succès',
                'data' => $mainTeacher
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du professeur principal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un professeur principal
     */
    public function destroy(MainTeacher $mainTeacher)
    {
        try {
            $mainTeacher->delete();

            return response()->json([
                'success' => true,
                'message' => 'Professeur principal retiré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du professeur principal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver un professeur principal
     */
    public function toggleStatus(MainTeacher $mainTeacher)
    {
        try {
            $mainTeacher->update([
                'is_active' => !$mainTeacher->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut du professeur principal mis à jour avec succès',
                'data' => $mainTeacher
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
     * Obtenir les séries de classes sans professeur principal
     */
    public function getClassesWithoutMainTeacher(Request $request)
    {
        try {
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;

            // Obtenir les IDs des séries qui ont déjà un professeur principal
            $seriesWithMainTeacher = MainTeacher::where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->pluck('class_series_id');

            // Obtenir les séries sans professeur principal
            $seriesWithoutMainTeacher = \App\Models\ClassSeries::whereNotIn('id', $seriesWithMainTeacher)
                ->where('is_active', true)
                ->with(['schoolClass.level'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $seriesWithoutMainTeacher
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séries sans professeur principal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les enseignants disponibles pour être professeur principal
     */
    public function getAvailableTeachers(Request $request)
    {
        try {
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;

            // Obtenir les IDs des enseignants qui sont déjà professeurs principaux
            $teachersWithMainClass = MainTeacher::where('school_year_id', $schoolYearId)
                ->where('is_active', true)
                ->pluck('teacher_id');

            // Obtenir les enseignants disponibles
            $availableTeachers = Teacher::whereNotIn('id', $teachersWithMainClass)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $availableTeachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}