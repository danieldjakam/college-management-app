<?php

namespace App\Http\Controllers;

use App\Models\ClassSeriesSubject;
use App\Models\ClassSeries;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SeriesSubjectController extends Controller
{
    /**
     * Lister les configurations de matières par série
     */
    public function index(Request $request)
    {
        try {
            $query = ClassSeriesSubject::with(['classSeries.schoolClass.level', 'subject']);

            // Filtrer par série si spécifié
            if ($request->has('class_series_id')) {
                $query->where('class_series_id', $request->class_series_id);
            }

            // Filtrer par matière si spécifié
            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Filtrer par statut actif
            if ($request->has('active')) {
                $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }

            $configurations = $query->orderBy('class_series_id')
                                   ->orderBy('subject_id')
                                   ->get();

            return response()->json([
                'success' => true,
                'data' => $configurations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une configuration de matière par série spécifique
     */
    public function show($id)
    {
        try {
            $seriesSubject = ClassSeriesSubject::with(['classSeries.schoolClass.level', 'subject'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $seriesSubject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les matières configurées pour une série spécifique (ex: 6ème A)
     */
    public function getBySeries($seriesId)
    {
        try {
            $configurations = ClassSeriesSubject::where('class_series_id', $seriesId)
                ->where('is_active', true)
                ->with(['subject'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $configurations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des matières de la série',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter une matière à une série
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'class_series_id' => 'required|exists:class_series,id',
                'subject_id' => 'required|exists:subjects,id',
                'coefficient' => 'required|numeric|min:0.5|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier si la configuration existe déjà
            $existingConfig = ClassSeriesSubject::where('class_series_id', $request->class_series_id)
                ->where('subject_id', $request->subject_id)
                ->first();

            if ($existingConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette matière est déjà configurée pour cette série'
                ], 422);
            }

            $configuration = ClassSeriesSubject::create([
                'class_series_id' => $request->class_series_id,
                'subject_id' => $request->subject_id,
                'coefficient' => $request->coefficient,
                'is_active' => true
            ]);

            $configuration->load(['classSeries.schoolClass.level', 'subject']);

            return response()->json([
                'success' => true,
                'message' => 'Matière ajoutée à la série avec succès',
                'data' => $configuration
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la matière à la série',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une configuration
     */
    public function update(Request $request, $id)
    {
        try {
            $seriesSubject = ClassSeriesSubject::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'coefficient' => 'required|numeric|min:0.5|max:10',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $seriesSubject->update([
                'coefficient' => $request->coefficient,
                'is_active' => $request->is_active ?? $seriesSubject->is_active
            ]);

            $seriesSubject->load(['classSeries.schoolClass.level', 'subject']);

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès',
                'data' => $seriesSubject
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une matière d'une série
     */
    public function destroy($id)
    {
        try {
            $seriesSubject = ClassSeriesSubject::findOrFail($id);

            DB::beginTransaction();

            // Supprimer automatiquement les affectations d'enseignants pour cette configuration
            $deletedAssignments = DB::table('teacher_assignments')
                ->where('class_series_subject_id', $seriesSubject->id)
                ->delete();

            // Supprimer la configuration de la matière
            $seriesSubject->delete();

            DB::commit();

            $message = 'Matière retirée de la série avec succès';
            if ($deletedAssignments > 0) {
                $message .= " ({$deletedAssignments} affectation(s) d'enseignant(s) supprimée(s))";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_assignments' => $deletedAssignments
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/désactiver une configuration
     */
    public function toggleStatus($id)
    {
        try {
            $seriesSubject = ClassSeriesSubject::findOrFail($id);

            DB::beginTransaction();

            $newStatus = !$seriesSubject->is_active;
            $seriesSubject->update([
                'is_active' => $newStatus
            ]);

            // Mettre à jour également le statut des affectations d'enseignants
            $updatedAssignments = DB::table('teacher_assignments')
                ->where('class_series_subject_id', $seriesSubject->id)
                ->update(['is_active' => $newStatus]);

            DB::commit();

            $message = 'Statut mis à jour avec succès';
            if ($updatedAssignments > 0) {
                $statusText = $newStatus ? 'activée(s)' : 'désactivée(s)';
                $message .= " ({$updatedAssignments} affectation(s) d'enseignant(s) {$statusText})";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $seriesSubject,
                'updated_assignments' => $updatedAssignments
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configurer plusieurs matières pour une série en lot
     */
    public function bulkConfigure(Request $request, $seriesId)
    {
        try {
            $classSeries = ClassSeries::findOrFail($seriesId);

            $validator = Validator::make($request->all(), [
                'subjects' => 'required|array',
                'subjects.*.subject_id' => 'required|exists:subjects,id',
                'subjects.*.coefficient' => 'required|numeric|min:0.5|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $configurations = [];

            foreach ($request->subjects as $subjectData) {
                // Vérifier si la configuration existe déjà
                $existingConfig = ClassSeriesSubject::where('class_series_id', $classSeries->id)
                    ->where('subject_id', $subjectData['subject_id'])
                    ->first();

                if ($existingConfig) {
                    // Mettre à jour si elle existe
                    $existingConfig->update([
                        'coefficient' => $subjectData['coefficient'],
                        'is_active' => true
                    ]);
                    $configurations[] = $existingConfig;
                } else {
                    // Créer si elle n'existe pas
                    $configuration = ClassSeriesSubject::create([
                        'class_series_id' => $classSeries->id,
                        'subject_id' => $subjectData['subject_id'],
                        'coefficient' => $subjectData['coefficient'],
                        'is_active' => true
                    ]);
                    $configurations[] = $configuration;
                }
            }

            DB::commit();

            // Charger les relations
            foreach ($configurations as $config) {
                $config->load(['classSeries.schoolClass.level', 'subject']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configurations mises à jour avec succès',
                'data' => $configurations
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la configuration en lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}