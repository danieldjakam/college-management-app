<?php

namespace App\Http\Controllers;

use App\Models\ClassSeries;
use App\Models\SchoolYear;
use App\Traits\ResolvesSchoolYear;
use Illuminate\Http\Request;

class ClassSeriesController extends Controller
{
    use ResolvesSchoolYear;
    /**
     * Lister toutes les séries de classes avec filtrage optionnel
     */
    public function index(Request $request)
    {
        try {
            $query = ClassSeries::with(['schoolClass', 'mainTeacher', 'schoolYear']);

            // Filtrer par classe (school_class)
            if ($request->has('class_id')) {
                $query->where('class_id', $request->class_id);
            }

            // Filtrer par année scolaire (utilise l'année de travail de l'utilisateur par défaut)
            $schoolYear = $this->resolveSchoolYear($request->input('school_year_id'));
            if ($schoolYear) {
                $query->where(function($q) use ($schoolYear) {
                    $q->where('school_year_id', $schoolYear->id)
                      ->orWhereNull('school_year_id');
                });
            }

            // Filtrer par statut actif
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $classSeries = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $classSeries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séries de classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir une série de classe spécifique
     */
    public function show($id)
    {
        try {
            $classSeries = ClassSeries::with([
                'schoolClass',
                'mainTeacher',
                'schoolYear',
                'students'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $classSeries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Série de classe non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer une nouvelle série de classe
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'class_id' => 'required|exists:school_classes,id',
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
                'capacity' => 'nullable|integer|min:1',
                'is_active' => 'boolean',
                'main_teacher_id' => 'nullable|exists:teachers,id',
                'school_year_id' => 'nullable|exists:school_years,id'
            ]);

            $classSeries = ClassSeries::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Série de classe créée avec succès',
                'data' => $classSeries->load(['schoolClass', 'mainTeacher', 'schoolYear'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la série de classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une série de classe
     */
    public function update(Request $request, $id)
    {
        try {
            $classSeries = ClassSeries::findOrFail($id);

            $validated = $request->validate([
                'class_id' => 'sometimes|required|exists:school_classes,id',
                'name' => 'sometimes|required|string|max:255',
                'code' => 'nullable|string|max:50',
                'capacity' => 'nullable|integer|min:1',
                'is_active' => 'boolean',
                'main_teacher_id' => 'nullable|exists:teachers,id',
                'school_year_id' => 'nullable|exists:school_years,id'
            ]);

            $classSeries->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Série de classe mise à jour avec succès',
                'data' => $classSeries->fresh(['schoolClass', 'mainTeacher', 'schoolYear'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la série de classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une série de classe
     */
    public function destroy($id)
    {
        try {
            $classSeries = ClassSeries::findOrFail($id);

            // Vérifier s'il y a des étudiants assignés
            $studentsCount = $classSeries->students()->count();
            if ($studentsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer : {$studentsCount} élève(s) sont assignés à cette série"
                ], 422);
            }

            $classSeries->delete();

            return response()->json([
                'success' => true,
                'message' => 'Série de classe supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la série de classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
