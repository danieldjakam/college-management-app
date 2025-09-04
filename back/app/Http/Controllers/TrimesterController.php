<?php

namespace App\Http\Controllers;

use App\Models\Trimester;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class TrimesterController extends Controller
{
    /**
     * Lister tous les trimestres
     */
    public function index(Request $request)
    {
        try {
            $query = Trimester::with(['sequences', 'schoolYear']);

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

            $trimesters = $query->orderBy('number')->get();

            return response()->json([
                'success' => true,
                'data' => $trimesters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des trimestres',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un trimestre spécifique
     */
    public function show(Trimester $trimester)
    {
        try {
            $trimester->load([
                'sequences.evaluations',
                'schoolYear'
            ]);

            return response()->json([
                'success' => true,
                'data' => $trimester
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le trimestre courant
     */
    public function getCurrentTrimester()
    {
        try {
            $currentTrimester = Trimester::where('is_current', true)
                ->where('is_active', true)
                ->with([
                    'sequences.evaluations',
                    'schoolYear'
                ])
                ->first();

            if (!$currentTrimester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun trimestre courant trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $currentTrimester
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du trimestre courant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer un trimestre (le définir comme courant)
     */
    public function activate(Trimester $trimester)
    {
        try {
            // Désactiver tous les autres trimestres
            Trimester::where('school_year_id', $trimester->school_year_id)
                ->update(['is_current' => false]);

            // Activer le trimestre sélectionné
            $trimester->update(['is_current' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Trimestre activé avec succès',
                'data' => $trimester->load(['sequences', 'schoolYear'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation du trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'un trimestre
     */
    public function getStats(Trimester $trimester)
    {
        try {
            $sequences = $trimester->sequences;
            $totalEvaluations = $sequences->sum(function($seq) {
                return $seq->evaluations->count();
            });

            $stats = [
                'total_sequences' => $sequences->count(),
                'total_evaluations' => $totalEvaluations,
                'progress_percentage' => $trimester->getProgressPercentage(),
                'is_in_progress' => $trimester->isInProgress(),
                'active_sequences' => $sequences->where('is_active', true)->count(),
                'current_sequence' => $sequences->where('is_current', true)->first()
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
}