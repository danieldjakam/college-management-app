<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    /**
     * Lister toutes les séquences
     */
    public function index(Request $request)
    {
        try {
            $query = Sequence::with(['trimester', 'schoolYear']);

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

            // Filtrer par trimestre
            if ($request->has('trimester_id')) {
                $query->where('trimester_id', $request->trimester_id);
            }

            $sequences = $query->orderBy('number')->get();

            return response()->json([
                'success' => true,
                'data' => $sequences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séquences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir une séquence spécifique
     */
    public function show(Sequence $sequence)
    {
        try {
            $sequence->load([
                'trimester',
                'schoolYear',
                'evaluations.seriesSubject.subject',
                'evaluations.seriesSubject.schoolClass'
            ]);

            return response()->json([
                'success' => true,
                'data' => $sequence
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la séquence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la séquence courante
     */
    public function getCurrentSequence()
    {
        try {
            $currentSequence = Sequence::where('is_current', true)
                ->where('is_active', true)
                ->with([
                    'trimester',
                    'schoolYear',
                    'evaluations.seriesSubject.subject',
                    'evaluations.seriesSubject.schoolClass'
                ])
                ->first();

            if (!$currentSequence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune séquence courante trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $currentSequence
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la séquence courante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer une séquence (la définir comme courante)
     */
    public function activate(Sequence $sequence)
    {
        try {
            // Désactiver toutes les autres séquences
            Sequence::where('school_year_id', $sequence->school_year_id)
                ->update(['is_current' => false]);

            // Activer la séquence sélectionnée (et la retirer du statut terminé si nécessaire)
            $sequence->update([
                'is_current' => true,
                'is_completed' => false  // Si elle était terminée, la réouvrir
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Séquence activée avec succès',
                'data' => $sequence->load(['trimester', 'schoolYear'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation de la séquence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une séquence comme terminée
     */
    public function markCompleted(Sequence $sequence)
    {
        try {
            $sequence->update([
                'is_completed' => true,
                'is_current' => false  // Une séquence terminée n'est plus en cours
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Séquence marquée comme terminée',
                'data' => $sequence->load(['trimester', 'schoolYear'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage de la séquence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une séquence comme non terminée
     */
    public function markIncomplete(Sequence $sequence)
    {
        try {
            $sequence->update([
                'is_completed' => false,
                'is_current' => false  // Retour au statut "Programmé"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Séquence marquée comme programmée',
                'data' => $sequence->load(['trimester', 'schoolYear'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle séquence
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'number' => 'required|integer|min:1|max:6',
                'trimester_id' => 'required|exists:trimesters,id',
                'school_year_id' => 'required|exists:school_years,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_active' => 'boolean',
                'is_current' => 'boolean'
            ]);

            // Vérifier qu'il n'y a pas déjà une séquence avec ce numéro
            $existingSequence = Sequence::where('school_year_id', $validated['school_year_id'])
                ->where('number', $validated['number'])
                ->first();

            if ($existingSequence) {
                return response()->json([
                    'success' => false,
                    'message' => "Une séquence #{$validated['number']} existe déjà pour cette année scolaire"
                ], 422);
            }

            $sequence = Sequence::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Séquence créée avec succès',
                'data' => $sequence->load(['trimester', 'schoolYear'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la séquence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'une séquence
     */
    public function getStats(Sequence $sequence)
    {
        try {
            $stats = [
                'total_evaluations' => $sequence->evaluations()->count(),
                'evaluations_by_type' => $sequence->evaluations()
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'progress_percentage' => $sequence->getProgressPercentage(),
                'is_in_progress' => $sequence->isInProgress(),
                'can_accept_evaluations' => $sequence->canAcceptEvaluations()
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