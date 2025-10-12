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
                ->with('classSeriesSubject.classSeries.schoolClass.level')
                ->get()
                ->pluck('classSeriesSubject.classSeries.schoolClass.id')
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
                ->with('classSeriesSubject.classSeries.schoolClass.level')
                ->get()
                ->pluck('classSeriesSubject.classSeries.schoolClass.level.id')
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
                        $query->orderBy('number');
                        // TOUJOURS inclure les compositions + filtrer les séquences normales
                        // Simplification : on charge toutes les séquences, le filtrage se fera côté PHP si nécessaire
                    },
                    'sequences.evaluations' => function($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    },
                    'schoolYear'
                ])
                ->orderBy('number')
                ->get();

            // Enrichir les données avec les statistiques du professeur
            $trimesters = $trimesters->map(function($trimester) use ($teacher, $teacherClassIds, $maxSequencesPerTrimester) {
                $trimester->teacher_classes_count = $teacherClassIds->count();
                $trimester->can_manage = true;
                
                // Filtrer les séquences côté PHP pour éviter les erreurs SQL
                $filteredSequences = $trimester->sequences->filter(function($sequence) use ($maxSequencesPerTrimester) {
                    // TOUJOURS inclure les compositions
                    if ($sequence->is_composition) {
                        return true;
                    }
                    
                    // Pour les séquences normales, appliquer le filtrage selon maxSequencesPerTrimester
                    if ($maxSequencesPerTrimester === 1) {
                        // Mode 1DS: Garder seulement séquences 1, 3, 5 (impaires)
                        return ($sequence->number - 1) % 2 === 0;
                    }
                    
                    // Mode 2DS: Garder toutes les séquences normales
                    return true;
                });
                
                $trimester->sequences = $filteredSequences->map(function($sequence) use ($teacher) {
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in store trimester', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Exception in store trimester', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du trimestre',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
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

    /**
     * Obtenir les détails du calcul DS pour un trimestre et un teacher
     */
    public function getDSDetails(Request $request, Trimester $trimester)
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

            // Récupérer toutes les séquences normales (non compositions) du trimestre
            // Note: Le verrouillage a été désactivé, toutes les séquences sont accessibles
            $normalSequences = $trimester->sequences()
                ->where('is_composition', false)
                ->orderBy('number')
                ->get();

            if ($normalSequences->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune séquence disponible pour ce trimestre'
                ], 404);
            }

            // DEBUG: Afficher les infos du teacher
            \Log::info('DS Details Debug - Teacher info:', [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->user->name,
                'trimester_id' => $trimester->id,
                'school_year_id' => $trimester->school_year_id
            ]);

            // Récupérer les assignations du teacher pour cette année scolaire
            $teacherAssignments = $teacher->assignments()
                ->where('school_year_id', $trimester->school_year_id)
                ->where('is_active', true)
                ->with([
                    'seriesSubject.subject',
                    'seriesSubject.schoolClass'
                ])
                ->get();

            \Log::info('DS Details Debug - Teacher assignments:', [
                'assignments_count' => $teacherAssignments->count(),
                'assignments' => $teacherAssignments->map(function($assignment) {
                    return [
                        'id' => $assignment->id,
                        'subject' => $assignment->seriesSubject->subject->name,
                        'class' => $assignment->seriesSubject->schoolClass->name,
                        'class_id' => $assignment->seriesSubject->school_class_id
                    ];
                })
            ]);

            if ($teacherAssignments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune assignation trouvée pour ce professeur'
                ], 404);
            }

            // Récupérer tous les élèves des classes où enseigne le professeur
            $allStudents = collect();
            $subjectsByStudent = [];
            $classSeriesIds = [];

            foreach ($teacherAssignments as $assignment) {
                $seriesSubject = $assignment->seriesSubject;
                $subject = $seriesSubject->subject;
                $schoolClass = $seriesSubject->schoolClass;
                
                // Récupérer les séries de classes pour cette school_class
                $classSeries = \App\Models\ClassSeries::where('class_id', $schoolClass->id)
                    ->where(function($query) use ($trimester) {
                        $query->where('school_year_id', $trimester->school_year_id)
                              ->orWhereNull('school_year_id');
                    })
                    ->get();

                \Log::info('DS Details Debug - Class series for class:', [
                    'school_class_id' => $schoolClass->id,
                    'school_class_name' => $schoolClass->name,
                    'class_series_count' => $classSeries->count(),
                    'class_series' => $classSeries->map(function($cs) {
                        return ['id' => $cs->id, 'name' => $cs->name];
                    })
                ]);

                foreach ($classSeries as $cs) {
                    $classSeriesIds[] = $cs->id;
                    
                    // Récupérer les élèves de cette série de classe
                    $students = \App\Models\Student::where('class_series_id', $cs->id)->get();

                    \Log::info('DS Details Debug - Students for class series:', [
                        'class_series_id' => $cs->id,
                        'class_series_name' => $cs->name,
                        'students_count' => $students->count()
                    ]);

                    foreach ($students as $student) {
                        $allStudents->push($student);
                        
                        if (!isset($subjectsByStudent[$student->id])) {
                            $subjectsByStudent[$student->id] = [];
                        }
                        
                        $subjectsByStudent[$student->id][] = [
                            'subject' => $subject,
                            'series_subject_id' => $seriesSubject->id,
                            'coefficient' => $seriesSubject->coefficient,
                            'class_name' => $schoolClass->name
                        ];
                    }
                }
            }

            $allStudents = $allStudents->unique('id');

            \Log::info('DS Details Debug - Final students:', [
                'total_students' => $allStudents->count(),
                'students_sample' => $allStudents->take(3)->map(function($student) {
                    return ['id' => $student->id, 'name' => $student->first_name . ' ' . $student->last_name];
                })
            ]);

            if ($allStudents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé dans vos classes'
                ], 404);
            }

            // Pour chaque élève, calculer son DS
            $studentsDS = [];
            $sequenceIds = $normalSequences->pluck('id')->toArray();

            foreach ($allStudents as $student) {
                $studentSubjects = $subjectsByStudent[$student->id] ?? [];
                $subjectAverages = [];
                $totalWeightedScore = 0;
                $totalCoefficients = 0;

                foreach ($studentSubjects as $subjectInfo) {
                    $seriesSubjectId = $subjectInfo['series_subject_id'];
                    $coefficient = $subjectInfo['coefficient'];
                    $subject = $subjectInfo['subject'];

                    // Récupérer les notes de cet élève pour cette matière dans les séquences terminées
                    $grades = \App\Models\Grade::where('student_id', $student->id)
                        ->where('series_subject_id', $seriesSubjectId)
                        ->whereIn('sequence_id', $sequenceIds)
                        ->where('trimester_id', $trimester->id)
                        ->with('sequence')
                        ->get();

                    if ($grades->isNotEmpty()) {
                        // Grouper les notes par séquence
                        $gradesBySequence = [];
                        foreach ($grades as $grade) {
                            $seqId = $grade->sequence_id;
                            if (!isset($gradesBySequence[$seqId])) {
                                $gradesBySequence[$seqId] = [];
                            }
                            // Convertir la note sur 20
                            $score20 = ($grade->score / $grade->max_score) * 20;
                            $gradesBySequence[$seqId][] = $score20;
                        }

                        // Calculer la moyenne par séquence
                        $sequenceAverages = [];
                        foreach ($gradesBySequence as $seqId => $seqGrades) {
                            $sequenceAverages[] = array_sum($seqGrades) / count($seqGrades);
                        }

                        // Calculer la moyenne DS pour cette matière : (Seq1 + Seq2) / 2
                        if (count($sequenceAverages) >= 2) {
                            $subjectDSAverage = array_sum($sequenceAverages) / count($sequenceAverages);
                            $weightedScore = $subjectDSAverage * $coefficient;
                            
                            $subjectAverages[] = [
                                'subject' => $subject->name,
                                'sequence_averages' => $sequenceAverages,
                                'ds_average' => round($subjectDSAverage, 2),
                                'coefficient' => $coefficient,
                                'weighted_score' => round($weightedScore, 2)
                            ];

                            $totalWeightedScore += $weightedScore;
                            $totalCoefficients += $coefficient;
                        }
                    }
                }

                // Calculer la moyenne générale de l'élève
                $studentDSAverage = $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : null;

                if ($studentDSAverage !== null) {
                    $studentsDS[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'student_number' => $student->student_number,
                        'subjects' => $subjectAverages,
                        'ds_average' => round($studentDSAverage, 2),
                        'total_coefficients' => $totalCoefficients,
                        'total_weighted_score' => round($totalWeightedScore, 2)
                    ];
                }
            }

            // Trier les élèves par moyenne DS décroissante
            usort($studentsDS, function($a, $b) {
                return $b['ds_average'] <=> $a['ds_average'];
            });

            // Calculer quelques statistiques
            $dsAverages = array_column($studentsDS, 'ds_average');
            $classDS = [
                'count' => count($studentsDS),
                'average' => count($dsAverages) > 0 ? round(array_sum($dsAverages) / count($dsAverages), 2) : 0,
                'min' => count($dsAverages) > 0 ? min($dsAverages) : 0,
                'max' => count($dsAverages) > 0 ? max($dsAverages) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'trimester' => [
                        'name' => $trimester->name,
                        'number' => $trimester->number
                    ],
                    'sequences' => $normalSequences->map(function($seq) {
                        return [
                            'name' => $seq->name,
                            'number' => $seq->number,
                            'is_completed' => $seq->is_completed
                        ];
                    }),
                    'students_ds' => $studentsDS,
                    'class_statistics' => $classDS,
                    'calculation_details' => [
                        'formula' => 'DS = (Séquence 1 + Séquence 2) ÷ 2 pour chaque matière, puis moyenne pondérée par les coefficients'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur dans getDSDetails: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des détails DS',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}