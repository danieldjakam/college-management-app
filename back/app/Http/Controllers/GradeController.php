<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Evaluation;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    /**
     * Lister les notes d'une évaluation avec les élèves
     */
    public function getGradesByEvaluation(Evaluation $evaluation)
    {
        try {
            // Récupérer tous les étudiants de la classe/matière
            $seriesSubject = $evaluation->seriesSubject;
            
            // Récupérer tous les étudiants actifs de cette série
            $students = Student::whereHas('classSeries', function($query) use ($seriesSubject) {
                $query->where('class_id', $seriesSubject->school_class_id)
                      ->where('is_active', true);
            })
            ->with(['classSeries'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

            // Récupérer les notes existantes pour cette évaluation
            $existingGrades = Grade::where('evaluation_id', $evaluation->id)
                ->with(['student'])
                ->get()
                ->keyBy('student_id');

            // Préparer les données avec notes existantes ou vides
            $gradeData = $students->map(function($student) use ($existingGrades, $evaluation) {
                $existingGrade = $existingGrades->get($student->id);
                
                return [
                    'student_id' => $student->id,
                    'student' => [
                        'id' => $student->id,
                        'nom' => $student->last_name,
                        'prenom' => $student->first_name,
                        'matricule' => $student->student_number,
                        'class_name' => $student->classSeries->first()?->schoolClass?->name
                    ],
                    'grade' => $existingGrade ? [
                        'id' => $existingGrade->id,
                        'score' => $existingGrade->score,
                        'is_absent' => $existingGrade->is_absent,
                        'is_excused' => $existingGrade->is_excused,
                        'comment' => $existingGrade->comment,
                        'score_on_20' => $existingGrade->getScoreOn20(),
                        'mention' => $existingGrade->getMention(),
                        'weighted_score' => $existingGrade->weighted_score
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'evaluation' => $evaluation->load(['sequence', 'seriesSubject.subject', 'seriesSubject.schoolClass']),
                    'grades' => $gradeData,
                    'statistics' => [
                        'total_students' => $students->count(),
                        'graded_count' => $existingGrades->whereNotNull('score')->count(),
                        'absent_count' => $existingGrades->where('is_absent', true)->count(),
                        'present_count' => $existingGrades->where('is_absent', false)->whereNotNull('score')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder ou mettre à jour une note
     */
    public function saveGrade(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:students,id',
                'evaluation_id' => 'required|exists:evaluations,id',
                'score' => 'nullable|numeric|min:0',
                'is_absent' => 'boolean',
                'is_excused' => 'boolean',
                'comment' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $evaluation = Evaluation::findOrFail($request->evaluation_id);

            // Validation de la note selon la note maximale de l'évaluation
            if ($request->score !== null && $request->score > $evaluation->max_score) {
                return response()->json([
                    'success' => false,
                    'message' => "La note ne peut pas dépasser {$evaluation->max_score}",
                ], 422);
            }

            // Vérifier si une note existe déjà
            $grade = Grade::where('student_id', $request->student_id)
                         ->where('evaluation_id', $request->evaluation_id)
                         ->first();

            if (!$grade) {
                // Créer une nouvelle note
                $grade = new Grade();
                $grade->student_id = $request->student_id;
                $grade->evaluation_id = $request->evaluation_id;
                $grade->sequence_id = $evaluation->sequence_id;
                $grade->trimester_id = $evaluation->trimester_id;
                $grade->school_year_id = $evaluation->school_year_id;
                $grade->series_subject_id = $evaluation->series_subject_id;
                $grade->max_score = $evaluation->max_score;
                $grade->coefficient = $evaluation->coefficient;
            }

            // Mettre à jour les données
            $grade->score = $request->score;
            $grade->is_absent = $request->is_absent ?? false;
            $grade->is_excused = $request->is_excused ?? false;
            $grade->comment = $request->comment;

            // Si l'élève est absent, pas de note
            if ($grade->is_absent) {
                $grade->score = null;
            }

            $grade->save();

            // Recharger avec les relations
            $grade->load(['student', 'evaluation']);

            return response()->json([
                'success' => true,
                'message' => 'Note sauvegardée avec succès',
                'data' => [
                    'grade' => $grade,
                    'score_on_20' => $grade->getScoreOn20(),
                    'mention' => $grade->getMention()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde de la note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder plusieurs notes en lot
     */
    public function saveBulkGrades(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'evaluation_id' => 'required|exists:evaluations,id',
                'grades' => 'required|array',
                'grades.*.student_id' => 'required|exists:students,id',
                'grades.*.score' => 'nullable|numeric|min:0',
                'grades.*.is_absent' => 'boolean',
                'grades.*.is_excused' => 'boolean',
                'grades.*.comment' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $evaluation = Evaluation::findOrFail($request->evaluation_id);
            $savedGrades = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->grades as $gradeData) {
                try {
                    // Validation de la note selon la note maximale
                    if (isset($gradeData['score']) && $gradeData['score'] !== null && $gradeData['score'] > $evaluation->max_score) {
                        $errors[] = "Étudiant ID {$gradeData['student_id']}: note trop élevée (max: {$evaluation->max_score})";
                        continue;
                    }

                    $grade = Grade::updateOrCreate(
                        [
                            'student_id' => $gradeData['student_id'],
                            'evaluation_id' => $request->evaluation_id
                        ],
                        [
                            'sequence_id' => $evaluation->sequence_id,
                            'trimester_id' => $evaluation->trimester_id,
                            'school_year_id' => $evaluation->school_year_id,
                            'series_subject_id' => $evaluation->series_subject_id,
                            'max_score' => $evaluation->max_score,
                            'coefficient' => $evaluation->coefficient,
                            'score' => ($gradeData['is_absent'] ?? false) ? null : ($gradeData['score'] ?? null),
                            'is_absent' => $gradeData['is_absent'] ?? false,
                            'is_excused' => $gradeData['is_excused'] ?? false,
                            'comment' => $gradeData['comment'] ?? null
                        ]
                    );

                    $savedGrades[] = $grade;

                } catch (\Exception $e) {
                    $errors[] = "Erreur pour l'étudiant ID {$gradeData['student_id']}: {$e->getMessage()}";
                }
            }

            if (empty($errors)) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => count($savedGrades) . ' notes sauvegardées avec succès',
                    'data' => $savedGrades
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs lors de la sauvegarde',
                    'errors' => $errors
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde en lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une note
     */
    public function deleteGrade(Grade $grade)
    {
        try {
            $grade->delete();

            return response()->json([
                'success' => true,
                'message' => 'Note supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'une évaluation
     */
    public function getEvaluationStats(Evaluation $evaluation)
    {
        try {
            $grades = Grade::where('evaluation_id', $evaluation->id)
                          ->whereNotNull('score')
                          ->get();

            if ($grades->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_students' => 0,
                        'graded_count' => 0,
                        'average' => 0,
                        'min_score' => 0,
                        'max_score' => 0,
                        'success_rate' => 0,
                        'distribution' => []
                    ]
                ]);
            }

            $scores = $grades->pluck('score');
            $scoresOn20 = $grades->map(function($grade) {
                return $grade->getScoreOn20();
            })->filter();

            // Distribution des notes par tranche
            $distribution = [
                '0-5' => $scoresOn20->filter(fn($s) => $s < 5)->count(),
                '5-10' => $scoresOn20->filter(fn($s) => $s >= 5 && $s < 10)->count(),
                '10-12' => $scoresOn20->filter(fn($s) => $s >= 10 && $s < 12)->count(),
                '12-14' => $scoresOn20->filter(fn($s) => $s >= 12 && $s < 14)->count(),
                '14-16' => $scoresOn20->filter(fn($s) => $s >= 14 && $s < 16)->count(),
                '16-18' => $scoresOn20->filter(fn($s) => $s >= 16 && $s < 18)->count(),
                '18-20' => $scoresOn20->filter(fn($s) => $s >= 18)->count()
            ];

            $totalStudents = Student::whereHas('classSeries', function($query) use ($evaluation) {
                $query->where('class_id', $evaluation->seriesSubject->school_class_id);
            })->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'graded_count' => $grades->count(),
                    'absent_count' => Grade::where('evaluation_id', $evaluation->id)->where('is_absent', true)->count(),
                    'average' => round($scoresOn20->average(), 2),
                    'min_score' => $scoresOn20->min(),
                    'max_score' => $scoresOn20->max(),
                    'success_rate' => round(($scoresOn20->filter(fn($s) => $s >= 10)->count() / max(1, $scoresOn20->count())) * 100, 1),
                    'distribution' => $distribution
                ]
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