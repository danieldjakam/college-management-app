<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Trimester;
use App\Models\Sequence;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\ClassSeries;
use App\Models\ClassSeriesSubject;
use App\Models\SchoolYear;
use App\Models\SchoolClass;
use App\Models\SubjectGroup;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BulletinDataController
 *
 * Endpoints qui fournissent les données calculées des bulletins
 * au service Next.js de génération PDF.
 * Ne génère PAS de PDF - renvoie uniquement du JSON.
 */
class BulletinDataController extends Controller
{
    protected BulletinService $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * POST /api/bulletins/class-data
     *
     * Retourne les données de bulletin pour TOUS les élèves d'une classe.
     * Optimisé: une seule passe sur la base de données.
     */
    public function classData(Request $request)
    {
        $request->validate([
            'class_id' => 'required|integer',
            'period_type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        try {
            $classSeriesId = $request->class_id;
            $periodType = $request->period_type;
            $periodIdentifier = $request->period_identifier;

            $classSeries = ClassSeries::with('schoolClass')->findOrFail($classSeriesId);

            // Déterminer le cycle et la section
            $cycleType = $this->determineCycleType($classSeries->schoolClass->name ?? '');
            $firstStudent = Student::where('class_series_id', $classSeriesId)->where('is_active', true)->first();
            $sectionType = $firstStudent ? $this->bulletinService->determineSectionType($firstStudent) : 'francophone';
            if ($sectionType === 'anglophone' || $sectionType === 'technique') {
                $cycleType = 'deuxieme';
            }

            // Récupérer les infos de l'école
            $schoolInfo = $this->getSchoolInfo();

            // Récupérer tous les élèves de la classe
            $students = Student::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé dans cette classe'
                ], 404);
            }

            // Déterminer le numéro de trimestre/séquence
            $periodNumber = $this->extractPeriodNumber($periodIdentifier);

            $studentsData = [];

            if ($periodType === 'trimester') {
                $studentsData = $this->generateTrimesterDataForClass(
                    $students, $classSeriesId, $periodNumber, $cycleType, $sectionType, $classSeries
                );
            } elseif ($periodType === 'sequence') {
                $studentsData = $this->generateSequenceDataForClass(
                    $students, $classSeriesId, $periodNumber, $cycleType, $sectionType, $classSeries
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'students_data' => $studentsData,
                    'school_info' => $schoolInfo,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('BulletinDataController::classData error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/bulletins/student-data
     *
     * Retourne les données de bulletin pour UN seul élève.
     */
    public function studentData(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'period_type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        try {
            $student = Student::with(['classSeries.schoolClass'])->findOrFail($request->student_id);
            $classSeriesId = $student->class_series_id;
            $classSeries = $student->classSeries;

            $cycleType = $this->determineCycleType($classSeries->schoolClass->name ?? '');
            $sectionType = $this->bulletinService->determineSectionType($student);
            if ($sectionType === 'anglophone' || $sectionType === 'technique') {
                $cycleType = 'deuxieme';
            }

            $periodNumber = $this->extractPeriodNumber($request->period_identifier);
            $schoolInfo = $this->getSchoolInfo();

            // Récupérer tous les élèves de la classe (pour rangs et stats)
            $classStudents = Student::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->get();

            $studentData = null;

            if ($request->period_type === 'trimester') {
                $allData = $this->generateTrimesterDataForClass(
                    $classStudents, $classSeriesId, $periodNumber, $cycleType, $sectionType, $classSeries
                );
                // Trouver les données de l'élève demandé
                foreach ($allData as $data) {
                    if ($data['student']['id'] === $student->id) {
                        $studentData = $data;
                        break;
                    }
                }
            } elseif ($request->period_type === 'sequence') {
                $allData = $this->generateSequenceDataForClass(
                    $classStudents, $classSeriesId, $periodNumber, $cycleType, $sectionType, $classSeries
                );
                foreach ($allData as $data) {
                    if ($data['student']['id'] === $student->id) {
                        $studentData = $data;
                        break;
                    }
                }
            }

            if (!$studentData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de bulletin non disponibles pour cet élève'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'student_data' => $studentData,
                    'school_info' => $schoolInfo,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('BulletinDataController::studentData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ================================================================
    // PRIVATE: Génération des données trimestrielles pour la classe
    // ================================================================

    private function generateTrimesterDataForClass($students, $classSeriesId, $trimesterNumber, $cycleType, $sectionType, $classSeries)
    {
        $trimester = Trimester::where('number', $trimesterNumber)->first();
        if (!$trimester) throw new \Exception("Trimestre {$trimesterNumber} non trouvé");

        $currentSchoolYear = Cache::remember('current_school_year', 3600, function () {
            return SchoolYear::where('is_active', true)->first();
        });
        $schoolYearId = $currentSchoolYear?->id;

        // Charger toutes les matières avec enseignants
        $subjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)
            ->with([
                'subject',
                'teachers' => function ($query) use ($schoolYearId) {
                    $query->wherePivot('is_active', true);
                    if ($schoolYearId) $query->wherePivot('school_year_id', $schoolYearId);
                }
            ])
            ->get();

        // Charger les groupes de matières
        $subjectGroups = SubjectGroup::where('is_active', true)->orderBy('order')->get();

        // Charger séquences et composition
        $sequences = $this->bulletinService->getSequencesForTrimester($trimesterNumber);
        $compositionSequence = $this->bulletinService->getCompositionForTrimester($trimesterNumber);

        $studentIds = $students->pluck('id');
        $subjectIds = $subjects->pluck('id');

        // Charger TOUTES les notes en une requête
        $allGrades = Grade::whereIn('student_id', $studentIds)
            ->where('trimester_id', $trimesterNumber)
            ->whereIn('class_series_subject_id', $subjectIds)
            ->with(['classSeriesSubject'])
            ->get()
            ->groupBy(function ($grade) {
                return $grade->student_id . '_' . $grade->class_series_subject_id . '_' . $grade->sequence_id;
            });

        // Charger discipline
        $disciplineData = DB::table('student_disciplines')
            ->whereIn('student_id', $studentIds)
            ->where('trimester_id', $trimester->id)
            ->get()
            ->keyBy('student_id');

        // Charger prof principal
        $mainTeacher = DB::table('main_teachers')
            ->join('teachers', 'main_teachers.teacher_id', '=', 'teachers.id')
            ->where('main_teachers.school_class_id', $classSeries->class_id)
            ->where('main_teachers.is_active', true)
            ->where('main_teachers.school_year_id', $schoolYearId)
            ->select('teachers.first_name', 'teachers.last_name')
            ->first();
        $mainTeacherName = $mainTeacher ? $mainTeacher->last_name . ' ' . $mainTeacher->first_name : null;

        $className = ($classSeries->schoolClass->name ?? '') . ' ' . ($classSeries->name ?? '');
        $schoolYear = $currentSchoolYear?->name ?? (date('Y') . '/' . (date('Y') + 1));

        // ============================================
        // PHASE 1: Calculer les moyennes de chaque élève par matière
        // ============================================
        $allStudentAverages = []; // [studentId => [subjectId => average]]
        $allStudentSubjectData = []; // [studentId => [subjectId => [...data]]]

        foreach ($students as $student) {
            $allStudentAverages[$student->id] = [];
            $allStudentSubjectData[$student->id] = [];

            foreach ($subjects as $subject) {
                $subjectData = $this->calculateSubjectGrade(
                    $student->id, $subject, $sequences, $compositionSequence,
                    $trimesterNumber, $cycleType, $allGrades
                );

                $allStudentAverages[$student->id][$subject->id] = $subjectData['average'];
                $allStudentSubjectData[$student->id][$subject->id] = $subjectData;
            }
        }

        // ============================================
        // PHASE 2: Calculer moyennes générales et rangs
        // ============================================
        $generalAverages = [];
        foreach ($students as $student) {
            $totalPoints = 0;
            $totalCoef = 0;
            foreach ($subjects as $subject) {
                $avg = $allStudentAverages[$student->id][$subject->id];
                if ($avg !== null) {
                    $totalPoints += $avg * $subject->coefficient;
                    $totalCoef += $subject->coefficient;
                }
            }
            $generalAverages[$student->id] = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
        }

        // Calculer les rangs
        $ranks = $this->calculateRanks($generalAverages);

        // Calculer stats de classe (min, max, moyenne, taux réussite)
        $validAverages = array_filter($generalAverages, fn($a) => $a !== null);
        $classAvg = count($validAverages) > 0 ? round(array_sum($validAverages) / count($validAverages), 2) : null;
        $classMin = count($validAverages) > 0 ? round(min($validAverages), 2) : null;
        $classMax = count($validAverages) > 0 ? round(max($validAverages), 2) : null;
        $passCount = count(array_filter($validAverages, fn($a) => $a >= 10));
        $passRate = count($validAverages) > 0 ? round(($passCount / count($validAverages)) * 100, 1) : null;

        // Calculer rangs et stats par matière
        $subjectRanks = [];
        $subjectStats = [];
        foreach ($subjects as $subject) {
            $subjectAverages = [];
            foreach ($students as $student) {
                $avg = $allStudentAverages[$student->id][$subject->id];
                if ($avg !== null) {
                    $subjectAverages[$student->id] = $avg;
                }
            }
            $subjectRanks[$subject->id] = $this->calculateRanks($subjectAverages);
            $vals = array_values($subjectAverages);
            $subjectStats[$subject->id] = [
                'min' => count($vals) > 0 ? round(min($vals), 2) : null,
                'max' => count($vals) > 0 ? round(max($vals), 2) : null,
                'avg' => count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null,
            ];
        }

        // ============================================
        // PHASE 3: Construire les données de chaque élève
        // ============================================
        $studentsResult = [];

        foreach ($students as $student) {
            $groups = [];

            foreach ($subjectGroups as $groupDef) {
                $groupSubjects = [];

                foreach ($subjects as $subject) {
                    $subjectGroup = $subject->subject->group ?? null;
                    if ($subjectGroup !== $groupDef->code) continue;

                    $data = $allStudentSubjectData[$student->id][$subject->id];
                    $avg = $data['average'];
                    $weighted = $avg !== null ? round($avg * $subject->coefficient, 2) : null;
                    $rank = $subjectRanks[$subject->id][$student->id] ?? null;
                    $stats = $subjectStats[$subject->id];

                    $teacherName = null;
                    if ($subject->teachers && $subject->teachers->isNotEmpty()) {
                        $t = $subject->teachers->first();
                        $teacherName = $t->last_name . ' ' . $t->first_name;
                    }

                    $groupSubjects[] = [
                        'subject_id' => $subject->subject->id,
                        'subject_name' => $subject->subject->name,
                        'subject_code' => $subject->subject->code ?? null,
                        'coefficient' => (float)$subject->coefficient,
                        'group' => $groupDef->code,
                        'seq1' => $data['seq1'] ?? null,
                        'seq2' => $data['seq2'] ?? null,
                        'ds_average' => $data['ds'] ?? null,
                        'composition' => $data['composition'],
                        'average_on_20' => $avg !== null ? round($avg, 2) : null,
                        'weighted_average' => $weighted,
                        'subject_rank' => $rank,
                        'appreciation' => $this->getAppreciation($avg),
                        'teacher_name' => $teacherName,
                        'is_absent' => $data['is_absent'] ?? false,
                        'class_min' => $stats['min'],
                        'class_max' => $stats['max'],
                        'class_avg' => $stats['avg'],
                    ];
                }

                // Calculer stats du groupe
                $grpCoef = 0;
                $grpWeighted = 0;
                foreach ($groupSubjects as $gs) {
                    if ($gs['average_on_20'] !== null) {
                        $grpCoef += $gs['coefficient'];
                        $grpWeighted += $gs['average_on_20'] * $gs['coefficient'];
                    }
                }

                $groups[] = [
                    'code' => $groupDef->code,
                    'name' => $groupDef->name,
                    'name_en' => $groupDef->name_en,
                    'header' => $groupDef->header,
                    'header_en' => $groupDef->header_en,
                    'subjects' => $groupSubjects,
                    'group_total_coef' => $grpCoef,
                    'group_total_weighted' => round($grpWeighted, 2),
                    'group_average' => $grpCoef > 0 ? round($grpWeighted / $grpCoef, 2) : null,
                ];
            }

            // Calculer total_coefficient et total_weighted
            $totalCoef = 0;
            $totalWeighted = 0;
            foreach ($subjects as $subject) {
                $avg = $allStudentAverages[$student->id][$subject->id];
                if ($avg !== null) {
                    $totalCoef += $subject->coefficient;
                    $totalWeighted += $avg * $subject->coefficient;
                }
            }

            $discipline = $disciplineData[$student->id] ?? null;

            $studentsResult[] = [
                'student' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'matricule' => $student->matricule ?? '',
                    'date_of_birth' => $student->date_of_birth ?? $student->birth_date ?? '',
                    'gender' => $student->gender ?? '',
                    'lieu_naissance' => $student->lieu_naissance ?? $student->place_of_birth ?? '',
                    'redoublant' => (bool)($student->is_repeater ?? $student->redoublant ?? false),
                ],
                'class_name' => trim($className),
                'cycle_type' => $cycleType,
                'period_type' => 'trimester',
                'period_label' => $trimesterNumber . ($trimesterNumber == 1 ? 'er' : 'ème') . ' Trimestre',
                'school_year' => $schoolYear,
                'trimester_number' => $trimesterNumber,
                'groups' => $groups,
                'total_coefficient' => $totalCoef,
                'total_weighted' => round($totalWeighted, 2),
                'general_average' => $generalAverages[$student->id],
                'rank' => $ranks[$student->id] ?? null,
                'total_students' => $students->count(),
                'class_average' => $classAvg,
                'class_min' => $classMin,
                'class_max' => $classMax,
                'pass_rate' => $passRate,
                'general_appreciation' => $this->getAppreciation($generalAverages[$student->id]),
                'main_teacher_name' => $mainTeacherName,
                'absences_total' => $discipline ? ($discipline->absences_justified ?? 0) + ($discipline->absences_unjustified ?? 0) : 0,
                'absences_justified' => $discipline->absences_justified ?? 0,
            ];
        }

        return $studentsResult;
    }

    // ================================================================
    // PRIVATE: Génération des données de séquence pour la classe
    // ================================================================

    private function generateSequenceDataForClass($students, $classSeriesId, $sequenceNumber, $cycleType, $sectionType, $classSeries)
    {
        $sequence = Sequence::where('number', $sequenceNumber)->first();
        if (!$sequence) throw new \Exception("Séquence {$sequenceNumber} non trouvée");

        $trimester = Trimester::find($sequence->trimester_id);
        $trimesterNumber = $trimester ? $trimester->number : 1;

        $currentSchoolYear = Cache::remember('current_school_year', 3600, function () {
            return SchoolYear::where('is_active', true)->first();
        });
        $schoolYearId = $currentSchoolYear?->id;

        $subjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)
            ->with([
                'subject',
                'teachers' => function ($query) use ($schoolYearId) {
                    $query->wherePivot('is_active', true);
                    if ($schoolYearId) $query->wherePivot('school_year_id', $schoolYearId);
                }
            ])
            ->get();

        $subjectGroups = SubjectGroup::where('is_active', true)->orderBy('order')->get();

        $studentIds = $students->pluck('id');
        $subjectIds = $subjects->pluck('id');

        // Charger toutes les notes de cette séquence
        $allGrades = Grade::whereIn('student_id', $studentIds)
            ->where('sequence_id', $sequence->id)
            ->where('trimester_id', $trimesterNumber)
            ->whereIn('class_series_subject_id', $subjectIds)
            ->get()
            ->groupBy(function ($g) {
                return $g->student_id . '_' . $g->class_series_subject_id;
            });

        $mainTeacher = DB::table('main_teachers')
            ->join('teachers', 'main_teachers.teacher_id', '=', 'teachers.id')
            ->where('main_teachers.school_class_id', $classSeries->class_id)
            ->where('main_teachers.is_active', true)
            ->where('main_teachers.school_year_id', $schoolYearId)
            ->select('teachers.first_name', 'teachers.last_name')
            ->first();
        $mainTeacherName = $mainTeacher ? $mainTeacher->last_name . ' ' . $mainTeacher->first_name : null;

        $className = ($classSeries->schoolClass->name ?? '') . ' ' . ($classSeries->name ?? '');
        $schoolYear = $currentSchoolYear?->name ?? (date('Y') . '/' . (date('Y') + 1));

        // Phase 1: Calculer notes par matière
        $allStudentAverages = [];
        foreach ($students as $student) {
            $allStudentAverages[$student->id] = [];
            foreach ($subjects as $subject) {
                $key = $student->id . '_' . $subject->id;
                $grades = $allGrades->get($key, collect());
                $grade = $grades->first();
                $avg = null;
                if ($grade && $grade->score !== null) {
                    $avg = $grade->getScoreOn20();
                }
                $allStudentAverages[$student->id][$subject->id] = $avg;
            }
        }

        // Phase 2: Moyennes générales et rangs
        $generalAverages = [];
        foreach ($students as $student) {
            $tp = 0; $tc = 0;
            foreach ($subjects as $subject) {
                $avg = $allStudentAverages[$student->id][$subject->id];
                if ($avg !== null) {
                    $tp += $avg * $subject->coefficient;
                    $tc += $subject->coefficient;
                }
            }
            $generalAverages[$student->id] = $tc > 0 ? round($tp / $tc, 2) : null;
        }

        $ranks = $this->calculateRanks($generalAverages);
        $validAverages = array_filter($generalAverages, fn($a) => $a !== null);
        $classAvg = count($validAverages) > 0 ? round(array_sum($validAverages) / count($validAverages), 2) : null;
        $classMin = count($validAverages) > 0 ? round(min($validAverages), 2) : null;
        $classMax = count($validAverages) > 0 ? round(max($validAverages), 2) : null;
        $passRate = count($validAverages) > 0 ? round((count(array_filter($validAverages, fn($a) => $a >= 10)) / count($validAverages)) * 100, 1) : null;

        // Stats par matière
        $subjectRanks = [];
        $subjectStats = [];
        foreach ($subjects as $subject) {
            $sa = [];
            foreach ($students as $st) {
                $a = $allStudentAverages[$st->id][$subject->id];
                if ($a !== null) $sa[$st->id] = $a;
            }
            $subjectRanks[$subject->id] = $this->calculateRanks($sa);
            $vals = array_values($sa);
            $subjectStats[$subject->id] = [
                'min' => count($vals) > 0 ? round(min($vals), 2) : null,
                'max' => count($vals) > 0 ? round(max($vals), 2) : null,
                'avg' => count($vals) > 0 ? round(array_sum($vals) / count($vals), 2) : null,
            ];
        }

        // Phase 3: Construire résultat
        $result = [];
        foreach ($students as $student) {
            $groups = [];
            foreach ($subjectGroups as $groupDef) {
                $groupSubjects = [];
                foreach ($subjects as $subject) {
                    if (($subject->subject->group ?? null) !== $groupDef->code) continue;
                    $avg = $allStudentAverages[$student->id][$subject->id];
                    $weighted = $avg !== null ? round($avg * $subject->coefficient, 2) : null;

                    $teacherName = null;
                    if ($subject->teachers && $subject->teachers->isNotEmpty()) {
                        $t = $subject->teachers->first();
                        $teacherName = $t->last_name . ' ' . $t->first_name;
                    }

                    $groupSubjects[] = [
                        'subject_id' => $subject->subject->id,
                        'subject_name' => $subject->subject->name,
                        'coefficient' => (float)$subject->coefficient,
                        'group' => $groupDef->code,
                        'average_on_20' => $avg !== null ? round($avg, 2) : null,
                        'weighted_average' => $weighted,
                        'subject_rank' => $subjectRanks[$subject->id][$student->id] ?? null,
                        'appreciation' => $this->getAppreciation($avg),
                        'teacher_name' => $teacherName,
                        'class_min' => $subjectStats[$subject->id]['min'],
                        'class_max' => $subjectStats[$subject->id]['max'],
                        'class_avg' => $subjectStats[$subject->id]['avg'],
                    ];
                }

                $gc = 0; $gw = 0;
                foreach ($groupSubjects as $gs) {
                    if ($gs['average_on_20'] !== null) {
                        $gc += $gs['coefficient'];
                        $gw += $gs['average_on_20'] * $gs['coefficient'];
                    }
                }

                $groups[] = [
                    'code' => $groupDef->code,
                    'name' => $groupDef->name,
                    'name_en' => $groupDef->name_en,
                    'header' => $groupDef->header,
                    'header_en' => $groupDef->header_en,
                    'subjects' => $groupSubjects,
                    'group_total_coef' => $gc,
                    'group_total_weighted' => round($gw, 2),
                    'group_average' => $gc > 0 ? round($gw / $gc, 2) : null,
                ];
            }

            $totalCoef = 0; $totalWeighted = 0;
            foreach ($subjects as $subject) {
                $avg = $allStudentAverages[$student->id][$subject->id];
                if ($avg !== null) {
                    $totalCoef += $subject->coefficient;
                    $totalWeighted += $avg * $subject->coefficient;
                }
            }

            $result[] = [
                'student' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'matricule' => $student->matricule ?? '',
                    'date_of_birth' => $student->date_of_birth ?? $student->birth_date ?? '',
                    'gender' => $student->gender ?? '',
                ],
                'class_name' => trim($className),
                'cycle_type' => $cycleType,
                'period_type' => 'sequence',
                'period_label' => 'Séquence ' . $sequenceNumber,
                'school_year' => $schoolYear,
                'sequence_number' => $sequenceNumber,
                'groups' => $groups,
                'total_coefficient' => $totalCoef,
                'total_weighted' => round($totalWeighted, 2),
                'general_average' => $generalAverages[$student->id],
                'rank' => $ranks[$student->id] ?? null,
                'total_students' => $students->count(),
                'class_average' => $classAvg,
                'class_min' => $classMin,
                'class_max' => $classMax,
                'pass_rate' => $passRate,
                'general_appreciation' => $this->getAppreciation($generalAverages[$student->id]),
                'main_teacher_name' => $mainTeacherName,
            ];
        }

        return $result;
    }

    // ================================================================
    // HELPERS
    // ================================================================

    /**
     * Calculer la note d'une matière pour un trimestre (depuis les données préchargées)
     */
    private function calculateSubjectGrade($studentId, $subject, $sequences, $compositionSequence, $trimesterNumber, $cycleType, $allGrades)
    {
        $result = [
            'average' => null,
            'ds' => null,
            'seq1' => null,
            'seq2' => null,
            'composition' => null,
            'is_absent' => false,
        ];

        // Trimestre 3: composition uniquement
        if ($trimesterNumber == 3) {
            if ($compositionSequence) {
                $key = $studentId . '_' . $subject->id . '_' . $compositionSequence->id;
                $grade = $allGrades->get($key, collect())->first();
                if ($grade && $grade->score !== null) {
                    $result['composition'] = $grade->getScoreOn20();
                    $result['average'] = $result['composition'];
                } elseif ($grade && $grade->is_absent) {
                    $result['is_absent'] = true;
                }
            }
            return $result;
        }

        // Séquences 1 et 2
        $seqGrades = [];
        $foundAny = false;
        foreach ($sequences as $index => $sequence) {
            $key = $studentId . '_' . $subject->id . '_' . $sequence->id;
            $grade = $allGrades->get($key, collect())->first();
            if ($grade && $grade->score !== null) {
                $seqGrades[$index] = $grade->getScoreOn20();
                $foundAny = true;
            } else {
                $seqGrades[$index] = null;
            }
        }

        $result['seq1'] = $seqGrades[0] ?? null;
        $result['seq2'] = $seqGrades[1] ?? null;

        // Composition
        if ($compositionSequence) {
            $key = $studentId . '_' . $subject->id . '_' . $compositionSequence->id;
            $grade = $allGrades->get($key, collect())->first();
            if ($grade && $grade->score !== null) {
                $result['composition'] = $grade->getScoreOn20();
                $foundAny = true;
            }
        }

        // Si aucune note → null (coefficient annulé)
        if (!$foundAny && $result['composition'] === null) {
            return $result;
        }

        // Calculer DS
        $s1 = $result['seq1'] ?? 0.00;
        $s2 = $result['seq2'] ?? 0.00;
        $ds = ($s1 + $s2) / 2;
        $result['ds'] = round($ds, 2);

        // M/20 = (DS + Composition) / 2
        $comp = $result['composition'] ?? 0.00;
        $result['average'] = round(($ds + $comp) / 2, 2);

        return $result;
    }

    /**
     * Calculer les rangs à partir d'un tableau [id => average]
     */
    private function calculateRanks(array $averages): array
    {
        $valid = array_filter($averages, fn($a) => $a !== null);
        arsort($valid);

        $ranks = [];
        $currentRank = 0;
        $lastAvg = null;
        $count = 0;

        foreach ($valid as $id => $avg) {
            $count++;
            if ($avg !== $lastAvg) {
                $currentRank = $count;
                $lastAvg = $avg;
            }
            $ranks[$id] = $currentRank;
        }

        return $ranks;
    }

    private function determineCycleType(string $className): string
    {
        $lower = strtolower($className);
        $keywords = ['seconde', '2nde', 'première', '1ère', '1ere', 'terminale', 'tle'];
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) return 'deuxieme';
        }
        return 'premier';
    }

    private function extractPeriodNumber(string $identifier): int
    {
        // "seq1" → 1, "trim2" → 2, "annual" → 0
        preg_match('/(\d+)/', $identifier, $matches);
        return (int)($matches[1] ?? 1);
    }

    private function getAppreciation(?float $average): string
    {
        if ($average === null) return '';
        if ($average >= 18) return 'Excellent';
        if ($average >= 16) return 'Très Bien';
        if ($average >= 14) return 'Bien';
        if ($average >= 12) return 'Assez Bien';
        if ($average >= 10) return 'Passable';
        if ($average >= 8) return 'Insuffisant';
        if ($average >= 6) return 'Médiocre';
        return 'Très Faible';
    }

    private function getSchoolInfo(): array
    {
        $settings = DB::table('school_settings')->first();

        $logoBase64 = null;
        if ($settings && !empty($settings->logo_path)) {
            $logoPath = storage_path('app/public/' . $settings->logo_path);
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $mimeType = mime_content_type($logoPath);
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
            }
        }

        return [
            'name' => $settings->school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA',
            'name_en' => $settings->school_name_en ?? 'BILINGUAL COMPREHENSIVE COLLEGE OF DOUALA',
            'address' => $settings->address ?? '',
            'phone' => $settings->phone ?? '',
            'email' => $settings->email ?? '',
            'bp' => $settings->bp ?? '',
            'logo_base64' => $logoBase64,
            'motto_fr' => 'Paix - Travail - Patrie',
            'motto_en' => 'Peace - Work - Fatherland',
        ];
    }
}
