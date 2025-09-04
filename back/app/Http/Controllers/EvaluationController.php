<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\SchoolYear;
use App\Models\SeriesSubject;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    /**
     * Lister toutes les évaluations
     */
    public function index(Request $request)
    {
        try {
            $query = Evaluation::with([
                'sequence',
                'trimester',
                'schoolYear',
                'seriesSubject.subject',
                'seriesSubject.schoolClass',
                'teacher'
            ]);

            // Filtrer par séquence
            if ($request->has('sequence_id')) {
                $query->where('sequence_id', $request->sequence_id);
            }

            // Filtrer par trimestre
            if ($request->has('trimester_id')) {
                $query->where('trimester_id', $request->trimester_id);
            }

            // Filtrer par matière
            if ($request->has('series_subject_id')) {
                $query->where('series_subject_id', $request->series_subject_id);
            }

            // Filtrer par type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filtrer par enseignant
            if ($request->has('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }

            $evaluations = $query->orderBy('date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $evaluations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des évaluations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle évaluation
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|in:interrogation,devoir,composition,tp,controle',
                'sequence_id' => 'required|exists:sequences,id',
                'series_subject_id' => 'required|exists:series_subjects,id',
                'date' => 'required|date',
                'max_score' => 'required|numeric|min:0|max:100',
                'coefficient' => 'required|numeric|min:0.1|max:10',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Récupérer les infos de la séquence pour compléter
            $sequence = Sequence::with('trimester')->find($request->sequence_id);

            $evaluation = Evaluation::create([
                'name' => $request->name,
                'type' => $request->type,
                'sequence_id' => $request->sequence_id,
                'trimester_id' => $sequence->trimester_id,
                'school_year_id' => $sequence->school_year_id,
                'series_subject_id' => $request->series_subject_id,
                'teacher_id' => $request->teacher_id,
                'date' => $request->date,
                'max_score' => $request->max_score,
                'coefficient' => $request->coefficient,
                'description' => $request->description,
                'is_active' => true
            ]);

            $evaluation->load([
                'sequence',
                'trimester',
                'schoolYear',
                'seriesSubject.subject',
                'seriesSubject.schoolClass',
                'teacher'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Évaluation créée avec succès',
                'data' => $evaluation
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'évaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les types d'évaluations disponibles
     */
    public function getTypes()
    {
        return response()->json([
            'success' => true,
            'data' => Evaluation::getTypes()
        ]);
    }

    /**
     * Obtenir les statistiques d'une évaluation
     */
    public function getStats(Evaluation $evaluation)
    {
        try {
            $stats = [
                'total_grades' => $evaluation->grades()->count(),
                'graded_count' => $evaluation->grades()->whereNotNull('score')->count(),
                'absent_count' => $evaluation->grades()->where('is_absent', true)->count(),
                'class_average' => $evaluation->getClassAverage(),
                'success_rate' => $evaluation->getSuccessRate(),
                'type_label' => $evaluation->getTypeLabel()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le tableau de bord des évaluations
     */
    public function dashboard()
    {
        try {
            $currentSequence = Sequence::getCurrentSequence();
            $currentTrimester = Trimester::getCurrentTrimester();

            $stats = [
                'current_sequence' => $currentSequence,
                'current_trimester' => $currentTrimester,
                'evaluations_this_sequence' => $currentSequence ? 
                    Evaluation::where('sequence_id', $currentSequence->id)->count() : 0,
                'evaluations_this_trimester' => $currentTrimester ? 
                    Evaluation::where('trimester_id', $currentTrimester->id)->count() : 0,
                'total_evaluations' => Evaluation::count(),
                'evaluation_types_count' => Evaluation::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}