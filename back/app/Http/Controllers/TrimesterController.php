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
     * Lister les trimestres pour un professeur (filtrés par ses classes)
     */
    public function getTeacherTrimesters(Request $request)
    {
        try {
            $user = $request->user();
            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non associé à un profil enseignant'
                ], 403);
            }

            // Obtenir l'année scolaire courante
            $currentYear = SchoolYear::where('is_current', true)->first();
            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 404);
            }

            // Récupérer les classes où enseigne le professeur via ses assignations
            $teacherClassIds = $teacher->assignments()
                ->where('school_year_id', $currentYear->id)
                ->where('is_active', true)
                ->with('seriesSubject.schoolClass.level')
                ->get()
                ->pluck('seriesSubject.school_class_id')
                ->unique();

            if ($teacherClassIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucune classe assignée pour cette année scolaire'
                ]);
            }

            // Récupérer les niveaux des classes du professeur
            $teacherLevels = $teacher->assignments()
                ->where('school_year_id', $currentYear->id)
                ->where('is_active', true)
                ->with('seriesSubject.schoolClass.level')
                ->get()
                ->pluck('seriesSubject.schoolClass.level.id')
                ->unique();

            // Récupérer les configs d'évaluation pour ces niveaux
            $evaluationConfigs = collect();
            if ($teacherLevels->isNotEmpty()) {
                $evaluationConfigs = \App\Models\EvaluationConfig::where('school_year_id', $currentYear->id)
                    ->whereIn('level_id', $teacherLevels)
                    ->where('is_active', true)
                    ->get();
            }

            // Déterminer le nombre max de séquences basé sur les configs
            $maxSequencesPerTrimester = $evaluationConfigs->max(function($config) {
                return $config->evaluation_mode === '2ds_1comp' ? 2 : 1;
            }) ?: 2;

            // Récupérer les trimestres avec séquences et statistiques
            $trimesters = Trimester::where('school_year_id', $currentYear->id)
                ->with([
                    'sequences' => function($query) use ($maxSequencesPerTrimester) {
                        $query->orderBy('number')
                            ->where(function($q) use ($maxSequencesPerTrimester) {
                                // Filtrer selon le nombre de séquences autorisées
                                if ($maxSequencesPerTrimester === 1) {
                                    $q->whereRaw('(number - 1) % 2 = 0'); // Séquences 1, 3, 5 (impaires)
                                }
                                // Si maxSequences = 2, on garde toutes les séquences
                            });
                    },
                    'sequences.evaluations' => function($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    },
                    'schoolYear'
                ])
                ->orderBy('number')
                ->get();

            // Enrichir les données avec les statistiques du professeur
            $trimesters = $trimesters->map(function($trimester) use ($teacher, $teacherClassIds) {
                $trimester->teacher_classes_count = $teacherClassIds->count();
                $trimester->can_manage = true;
                
                $trimester->sequences = $trimester->sequences->map(function($sequence) use ($teacher) {
                    $sequence->teacher_evaluations_count = $sequence->evaluations->count();
                    $sequence->can_add_evaluations = $sequence->canAcceptEvaluations();
                    return $sequence;
                });

                return $trimester;
            });

            return response()->json([
                'success' => true,
                'data' => $trimesters,
                'teacher_info' => [
                    'name' => $teacher->user->name,
                    'classes_count' => $teacherClassIds->count(),
                    'current_year' => $currentYear->name,
                    'max_sequences_per_trimester' => $maxSequencesPerTrimester,
                    'evaluation_configs' => $evaluationConfigs->map(function($config) {
                        return [
                            'level_name' => $config->level->name,
                            'evaluation_mode' => $config->evaluation_mode,
                            'mode_label' => $config->getEvaluationModeLabel()
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des trimestres du professeur',
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
     * Créer un nouveau trimestre
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'number' => 'required|integer|min:1|max:3',
                'school_year_id' => 'required|exists:school_years,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_active' => 'boolean'
            ]);

            // Vérifier qu'il n'y a pas déjà un trimestre avec ce numéro
            $existingTrimester = Trimester::where('school_year_id', $validated['school_year_id'])
                ->where('number', $validated['number'])
                ->first();

            if ($existingTrimester) {
                return response()->json([
                    'success' => false,
                    'message' => "Un trimestre #{$validated['number']} existe déjà pour cette année scolaire"
                ], 422);
            }

            $trimester = Trimester::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Trimestre créé avec succès',
                'data' => $trimester->load(['sequences', 'schoolYear'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un trimestre
     */
    public function update(Request $request, Trimester $trimester)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'number' => 'sometimes|required|integer|min:1|max:3',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after:start_date',
                'is_active' => 'sometimes|boolean'
            ]);

            // Vérifier qu'il n'y a pas déjà un trimestre avec ce numéro (si le numéro change)
            if (isset($validated['number']) && $validated['number'] !== $trimester->number) {
                $existingTrimester = Trimester::where('school_year_id', $trimester->school_year_id)
                    ->where('number', $validated['number'])
                    ->where('id', '!=', $trimester->id)
                    ->first();

                if ($existingTrimester) {
                    return response()->json([
                        'success' => false,
                        'message' => "Un trimestre #{$validated['number']} existe déjà pour cette année scolaire"
                    ], 422);
                }
            }

            $trimester->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Trimestre mis à jour avec succès',
                'data' => $trimester->load(['sequences', 'schoolYear'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un trimestre
     */
    public function destroy(Request $request, Trimester $trimester)
    {
        try {
            // Vérifier si le trimestre est actuellement actif
            if ($trimester->is_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le trimestre actuel. Activez d\'abord un autre trimestre.'
                ], 422);
            }

            // Vérifier s'il y a des séquences ou évaluations liées
            $sequencesCount = $trimester->sequences()->count();
            $evaluationsCount = $trimester->evaluations()->count();
            $forceDelete = $request->input('force', false);

            if (($sequencesCount > 0 || $evaluationsCount > 0) && !$forceDelete) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce trimestre contient {$sequencesCount} séquence(s) et {$evaluationsCount} évaluation(s).",
                    'data' => [
                        'sequences_count' => $sequencesCount,
                        'evaluations_count' => $evaluationsCount,
                        'can_force_delete' => true
                    ]
                ], 422);
            }

            $trimesterName = $trimester->name;
            
            // Suppression en cascade si forcée
            if ($forceDelete && ($sequencesCount > 0 || $evaluationsCount > 0)) {
                // Supprimer d'abord toutes les évaluations liées aux séquences
                $trimester->sequences()->each(function($sequence) {
                    $sequence->evaluations()->each(function($evaluation) {
                        // Supprimer les notes liées à l'évaluation
                        $evaluation->grades()->delete();
                        $evaluation->delete();
                    });
                    $sequence->delete();
                });
                
                $message = "Le trimestre \"{$trimesterName}\" et tous ses éléments ({$sequencesCount} séquence(s), {$evaluationsCount} évaluation(s)) ont été supprimés avec succès";
            } else {
                $message = "Le trimestre \"{$trimesterName}\" a été supprimé avec succès";
            }

            $trimester->delete();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du trimestre',
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