<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ExamClass;
use App\Models\LivretScolaireGrade;
use App\Models\ClassSeries;
use App\Models\ClassSeriesSubject;
use App\Models\SchoolYear;
use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Services\BulletinService;

class LivretScolaireController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Liste des classes d'examen pour l'annee en cours
     */
    public function getExamClasses()
    {
        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        $examClassIds = ExamClass::where('school_year_id', $schoolYear->id)
            ->pluck('class_series_id');

        // Toutes les series avec indication si classe d'examen
        $allSeries = ClassSeries::with(['schoolClass:id,name', 'students' => function ($q) {
            $q->where('is_active', true)->select('id', 'class_series_id');
        }])->get()->map(function ($series) use ($examClassIds) {
            return [
                'id' => $series->id,
                'name' => $series->name,
                'class_name' => $series->schoolClass->name ?? '',
                'student_count' => $series->students->count(),
                'is_exam_class' => $examClassIds->contains($series->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $allSeries,
            'school_year' => $schoolYear->name,
        ]);
    }

    /**
     * Definir les classes d'examen
     */
    public function setExamClasses(Request $request)
    {
        $request->validate([
            'class_series_ids' => 'required|array',
            'class_series_ids.*' => 'exists:class_series,id',
        ]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        // Supprimer les anciennes selections et inserer les nouvelles
        ExamClass::where('school_year_id', $schoolYear->id)->delete();

        foreach ($request->class_series_ids as $seriesId) {
            ExamClass::create([
                'class_series_id' => $seriesId,
                'school_year_id' => $schoolYear->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->class_series_ids) . ' classes d\'examen definies',
        ]);
    }

    /**
     * Charger les donnees du livret pour une classe (notes reelles + notes ajustees)
     */
    public function getClassLivretData($seriesId)
    {
        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        $series = ClassSeries::with('schoolClass')->find($seriesId);
        if (!$series) {
            return response()->json(['success' => false, 'error' => 'Classe introuvable'], 404);
        }

        $students = Student::where('class_series_id', $seriesId)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('subname')
            ->get(['id', 'first_name', 'last_name', 'name', 'subname', 'student_number', 'class_series_id']);

        $subjects = ClassSeriesSubject::where('class_series_id', $seriesId)
            ->with('subject:id,name')
            ->get();

        // Charger les notes ajustees existantes
        $adjustedGrades = LivretScolaireGrade::where('school_year_id', $schoolYear->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('class_series_subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy(function ($g) {
                return $g->student_id . '_' . $g->class_series_subject_id;
            });

        // Determiner le type de cycle
        $firstStudent = $students->first();
        $cycleType = $firstStudent ? $this->bulletinService->determineCycleType($firstStudent) : 'premier';
        $sectionType = $firstStudent ? $this->bulletinService->determineSectionType($firstStudent) : 'francophone';
        if ($sectionType === 'anglophone' || $sectionType === 'technique') {
            $cycleType = 'deuxieme';
        }

        // Precharger toutes les notes en masse (meme approche optimisee)
        $allGrades = \App\Models\Grade::whereIn('student_id', $students->pluck('id'))
            ->whereIn('trimester_id', [1, 2, 3])
            ->get();

        $gradeIndex = [];
        foreach ($allGrades as $g) {
            $gradeIndex[$g->student_id][$g->sequence_id][$g->class_series_subject_id][] = $g;
        }

        // Precharger sequences et compositions
        $seqMap = [];
        $compMap = [];
        for ($t = 1; $t <= 2; $t++) {
            $seqs = $this->bulletinService->getSequencesForTrimester($t);
            $seqMap[$t] = $seqs->pluck('id')->toArray();
        }
        $seqMap[3] = [];
        for ($t = 1; $t <= 3; $t++) {
            $comp = \App\Models\Sequence::where('is_composition', true)
                ->where('trimester_id', $t)->first();
            $compMap[$t] = $comp ? $comp->id : null;
        }

        $subjectIdMap = [];
        foreach ($subjects as $ss) {
            $subjectIdMap[$ss->subject_id][] = $ss->id;
        }

        $scoreOn20 = function ($grade) {
            if ($grade->score === null || $grade->max_score === null) return null;
            return round(((float)$grade->score / (float)$grade->max_score) * 20, 2);
        };

        $getGradeFromIndex = function ($sid, $seqId, $cssId) use (&$gradeIndex, $scoreOn20) {
            if (!isset($gradeIndex[$sid][$seqId][$cssId])) return null;
            foreach ($gradeIndex[$sid][$seqId][$cssId] as $g) {
                if ($g->score !== null) return $scoreOn20($g);
            }
            return null;
        };

        $getCompGrade = function ($sid, $trimester, $cssId) use (&$gradeIndex, &$compMap, &$subjects, &$subjectIdMap, $scoreOn20) {
            $compSeqId = $compMap[$trimester] ?? null;
            if (!$compSeqId) return null;
            if (isset($gradeIndex[$sid][$compSeqId][$cssId])) {
                foreach ($gradeIndex[$sid][$compSeqId][$cssId] as $g) {
                    if ($g->score !== null) return $scoreOn20($g);
                }
            }
            $css = $subjects->firstWhere('id', $cssId);
            if ($css && isset($subjectIdMap[$css->subject_id])) {
                foreach ($subjectIdMap[$css->subject_id] as $altCssId) {
                    if ($altCssId == $cssId) continue;
                    if (isset($gradeIndex[$sid][$compSeqId][$altCssId])) {
                        foreach ($gradeIndex[$sid][$compSeqId][$altCssId] as $g) {
                            if ($g->score !== null) return $scoreOn20($g);
                        }
                    }
                }
            }
            return null;
        };

        $calcTrimGrade = function ($sid, $trimester, $cssId) use (&$seqMap, $getGradeFromIndex, $getCompGrade, $cycleType) {
            if ($trimester == 3) {
                $comp = $getCompGrade($sid, 3, $cssId);
                return ($comp !== null && $comp !== 'ABS') ? (float)$comp : null;
            }
            $seqIds = $seqMap[$trimester] ?? [];
            $s1 = isset($seqIds[0]) ? $getGradeFromIndex($sid, $seqIds[0], $cssId) : null;
            $s2 = isset($seqIds[1]) ? $getGradeFromIndex($sid, $seqIds[1], $cssId) : null;
            $cg = $getCompGrade($sid, $trimester, $cssId);
            
            $notesPresentes = ($s1 !== null ? 1 : 0) + ($s2 !== null ? 1 : 0) + (($cg !== null && $cg !== 'ABS') ? 1 : 0);
            if ($notesPresentes === 0) return null;

            $finalS1 = $s1 ?? 0.0;
            $finalS2 = $s2 ?? 0.0;
            $finalComp = ($cg !== null && $cg !== 'ABS') ? (float)$cg : 0.0;

            // DS = (Seq1 + Seq2) / 2, M/20 = (DS + Compo) / 2
            $ds = ($finalS1 + $finalS2) / 2;
            return ($ds + $finalComp) / 2;
        };

        // Construire les donnees par eleve
        $studentsData = [];
        foreach ($students as $student) {
            $studentSubjects = [];
            foreach ($subjects as $ss) {
                $key = $student->id . '_' . $ss->id;
                $adjusted = $adjustedGrades->get($key);

                $realT1 = $calcTrimGrade($student->id, 1, $ss->id);
                $realT2 = $calcTrimGrade($student->id, 2, $ss->id);
                $realT3 = $calcTrimGrade($student->id, 3, $ss->id);

                // Notes de sequences individuelles
                $seqIds1 = $seqMap[1] ?? [];
                $seqIds2 = $seqMap[2] ?? [];
                $realEv1 = isset($seqIds1[0]) ? $getGradeFromIndex($student->id, $seqIds1[0], $ss->id) : null;
                $realEv2 = isset($seqIds1[1]) ? $getGradeFromIndex($student->id, $seqIds1[1], $ss->id) : null;
                $realComp1 = $getCompGrade($student->id, 1, $ss->id);
                $realEv3 = isset($seqIds2[0]) ? $getGradeFromIndex($student->id, $seqIds2[0], $ss->id) : null;
                $realEv4 = isset($seqIds2[1]) ? $getGradeFromIndex($student->id, $seqIds2[1], $ss->id) : null;
                $realComp2 = $getCompGrade($student->id, 2, $ss->id);
                $realComp3 = $getCompGrade($student->id, 3, $ss->id);

                $studentSubjects[] = [
                    'class_series_subject_id' => $ss->id,
                    'subject_name' => $ss->subject->name ?? 'N/A',
                    'coefficient' => (float)$ss->coefficient,
                    'real_ev1' => $realEv1 !== null ? round((float)$realEv1, 2) : null,
                    'real_ev2' => $realEv2 !== null ? round((float)$realEv2, 2) : null,
                    'real_comp1' => ($realComp1 !== null && $realComp1 !== 'ABS') ? round((float)$realComp1, 2) : null,
                    'real_ev3' => $realEv3 !== null ? round((float)$realEv3, 2) : null,
                    'real_ev4' => $realEv4 !== null ? round((float)$realEv4, 2) : null,
                    'real_comp2' => ($realComp2 !== null && $realComp2 !== 'ABS') ? round((float)$realComp2, 2) : null,
                    'real_comp3' => ($realComp3 !== null && $realComp3 !== 'ABS') ? round((float)$realComp3, 2) : null,
                    'real_trim1' => $realT1 !== null ? round($realT1, 2) : null,
                    'real_trim2' => $realT2 !== null ? round($realT2, 2) : null,
                    'real_trim3' => $realT3 !== null ? round($realT3, 2) : null,
                    'adjusted_ev1' => $adjusted ? (float)$adjusted->ev1 : null,
                    'adjusted_ev2' => $adjusted ? (float)$adjusted->ev2 : null,
                    'adjusted_comp1' => $adjusted ? (float)$adjusted->comp1 : null,
                    'adjusted_ev3' => $adjusted ? (float)$adjusted->ev3 : null,
                    'adjusted_ev4' => $adjusted ? (float)$adjusted->ev4 : null,
                    'adjusted_comp2' => $adjusted ? (float)$adjusted->comp2 : null,
                    'adjusted_comp3' => $adjusted ? (float)$adjusted->comp3 : null,
                    'adjusted_trim1' => $adjusted ? (float)$adjusted->trim1 : null,
                    'adjusted_trim2' => $adjusted ? (float)$adjusted->trim2 : null,
                    'adjusted_trim3' => $adjusted ? (float)$adjusted->trim3 : null,
                ];
            }

            $studentsData[] = [
                'id' => $student->id,
                'name' => trim(($student->name ?? '') . ' ' . ($student->subname ?? '')),
                'matricule' => $student->student_number ?? 'N/A',
                'subjects' => $studentSubjects,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class_name' => $series->name,
                'school_year' => $schoolYear->name,
                'students' => $studentsData,
                'subjects' => $subjects->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->subject->name ?? 'N/A',
                    'coefficient' => (float)$s->coefficient,
                ]),
            ],
        ]);
    }

    /**
     * Sauvegarder les notes ajustees du livret
     */
    public function saveAdjustedGrades(Request $request)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.class_series_subject_id' => 'required|exists:class_series_subjects,id',
            'grades.*.ev1' => 'nullable|numeric|min:0|max:20',
            'grades.*.ev2' => 'nullable|numeric|min:0|max:20',
            'grades.*.comp1' => 'nullable|numeric|min:0|max:20',
            'grades.*.ev3' => 'nullable|numeric|min:0|max:20',
            'grades.*.ev4' => 'nullable|numeric|min:0|max:20',
            'grades.*.comp2' => 'nullable|numeric|min:0|max:20',
            'grades.*.comp3' => 'nullable|numeric|min:0|max:20',
            'grades.*.trim1' => 'nullable|numeric|min:0|max:20',
            'grades.*.trim2' => 'nullable|numeric|min:0|max:20',
            'grades.*.trim3' => 'nullable|numeric|min:0|max:20',
        ]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        $userId = auth()->id();
        $count = 0;

        $seqFields = ['ev1', 'ev2', 'comp1', 'ev3', 'ev4', 'comp2', 'comp3'];
        $trimFields = ['trim1', 'trim2', 'trim3'];
        $allFields = array_merge($seqFields, $trimFields);

        foreach ($request->grades as $grade) {
            // Verifier si au moins une note est fournie
            $hasAnyValue = false;
            foreach ($allFields as $f) {
                if (isset($grade[$f]) && $grade[$f] !== null) {
                    $hasAnyValue = true;
                    break;
                }
            }

            if (!$hasAnyValue) {
                LivretScolaireGrade::where('student_id', $grade['student_id'])
                    ->where('class_series_subject_id', $grade['class_series_subject_id'])
                    ->where('school_year_id', $schoolYear->id)
                    ->delete();
                continue;
            }

            $updateData = ['modified_by' => $userId];
            foreach ($allFields as $f) {
                $updateData[$f] = $grade[$f] ?? null;
            }

            LivretScolaireGrade::updateOrCreate(
                [
                    'student_id' => $grade['student_id'],
                    'class_series_subject_id' => $grade['class_series_subject_id'],
                    'school_year_id' => $schoolYear->id,
                ],
                $updateData
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} notes du livret sauvegardees",
        ]);
    }

    /**
     * Generer le PDF du livret scolaire pour un eleve
     * Reutilise le template du bulletin annuel avec les notes ajustees
     */
    public function generateLivret(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studentId = $request->student_id;
        $student = Student::with(['schoolClass', 'classSeries'])->findOrFail($studentId);

        $schoolYear = SchoolYear::where('is_active', true)->first();

        // Generer les donnees du bulletin annuel
        if ($this->bulletinService->isApcClass($student)) {
            $bulletinData = $this->bulletinService->generateAnnualBulletinData($studentId);
        } else {
            $bulletinData = $this->bulletinService->generateAnnualBulletinDataNonApc($studentId);
        }

        if (!$bulletinData) {
            return response()->json(['success' => false, 'error' => 'Impossible de generer les donnees'], 500);
        }

        // Charger les notes ajustees et les appliquer
        $adjustedGrades = LivretScolaireGrade::where('student_id', $studentId)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->keyBy('class_series_subject_id');

        if ($adjustedGrades->isNotEmpty()) {
            $bulletinData = $this->applyAdjustedGrades($bulletinData, $adjustedGrades);
        }

        // Marquer comme livret pour changer le titre
        $bulletinData['is_livret'] = true;

        // Generer le HTML puis le PDF
        $htmlContent = $this->bulletinService->renderBulletinTemplate('annual', $bulletinData, true);

        $filename = "livret_scolaire_{$studentId}_" . now()->format('Y-m-d') . ".pdf";
        $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);

        return response()->json([
            'success' => true,
            'file_path' => $filePath,
            'download_url' => url("storage/{$filePath}"),
        ]);
    }

    /**
     * Generer les livrets pour un petit batch d'eleves (par IDs)
     */
    public function generateSmallBatch(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120');

        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        $students = Student::whereIn('id', $request->student_ids)
            ->where('is_active', true)
            ->get();

        $allAdjusted = LivretScolaireGrade::where('school_year_id', $schoolYear->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $generated = [];
        $errors = [];

        foreach ($students as $student) {
            $studentName = trim(($student->name ?? '') . ' ' . ($student->subname ?? ''));
            try {
                if ($this->bulletinService->isApcClass($student)) {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinData($student->id);
                } else {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinDataNonApc($student->id);
                }

                if (!$bulletinData) {
                    throw new \Exception('Donnees non generees');
                }

                $studentAdjusted = $allAdjusted->get($student->id);
                if ($studentAdjusted && $studentAdjusted->isNotEmpty()) {
                    $adjustedBySubject = $studentAdjusted->keyBy('class_series_subject_id');
                    $bulletinData = $this->applyAdjustedGrades($bulletinData, $adjustedBySubject);
                }

                $bulletinData['is_livret'] = true;

                $htmlContent = $this->bulletinService->renderBulletinTemplate('annual', $bulletinData, true);

                $filename = "livret_scolaire_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);

                $generated[] = [
                    'id' => $student->id,
                    'name' => $studentName,
                    'file_path' => $filePath,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $student->id,
                    'student' => $studentName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'generated' => $generated,
            'errors' => $errors,
        ]);
    }

    /**
     * Generer les livrets en lot pour toute une classe
     */
    public function batchGenerateLivrets(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'series_id' => 'required|exists:class_series,id',
        ]);

        $schoolYear = SchoolYear::where('is_active', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'error' => 'Aucune annee scolaire active'], 404);
        }

        $students = Student::where('class_series_id', $request->series_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('subname')
            ->get();

        $generated = 0;
        $errors = [];
        $generatedStudents = [];

        // Charger toutes les notes ajustees pour cette classe
        $allAdjusted = LivretScolaireGrade::where('school_year_id', $schoolYear->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $studentName = trim(($student->name ?? '') . ' ' . ($student->subname ?? ''));
            try {
                if ($this->bulletinService->isApcClass($student)) {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinData($student->id);
                } else {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinDataNonApc($student->id);
                }

                if (!$bulletinData) {
                    throw new \Exception('Donnees non generees');
                }

                // Appliquer les notes ajustees
                $studentAdjusted = $allAdjusted->get($student->id);
                if ($studentAdjusted && $studentAdjusted->isNotEmpty()) {
                    $adjustedBySubject = $studentAdjusted->keyBy('class_series_subject_id');
                    $bulletinData = $this->applyAdjustedGrades($bulletinData, $adjustedBySubject);
                }

                $bulletinData['is_livret'] = true;

                $htmlContent = $this->bulletinService->renderBulletinTemplate('annual', $bulletinData, true);

                $filename = "livret_scolaire_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
                $generated++;

                $generatedStudents[] = [
                    'id' => $student->id,
                    'name' => $studentName,
                    'file_path' => $filePath,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'student' => $studentName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'generated' => $generated,
            'errors' => $errors,
            'total' => $students->count(),
            'students' => $generatedStudents,
        ]);
    }

    /**
     * Appliquer les notes ajustees sur les donnees du bulletin
     * Moteur de calcul ultra-robuste pour éviter les erreurs de moyennes
     */
    protected function applyAdjustedGrades($bulletinData, $adjustedGrades)
    {
        $totalPoints = 0;
        $totalCoefficient = 0;
        
        // Accumulateurs pour le résumé annuel en bas de page
        $summary = [
            'ev1' => [0, 0], 'ev2' => [0, 0], 'comp1' => [0, 0], 'trim1' => [0, 0],
            'ev3' => [0, 0], 'ev4' => [0, 0], 'comp2' => [0, 0], 'trim2' => [0, 0],
            'ev5' => [0, 0], 'ev6' => [0, 0], 'comp3' => [0, 0], 'trim3' => [0, 0],
        ];

        foreach ($bulletinData['subjects'] as &$subject) {
            $cssId = $subject['class_series_subject_id'] ?? null;
            if (!$cssId) continue;

            $adj = $adjustedGrades->get($cssId);
            $coef = (float)($subject['coefficient'] ?? 1);

            // 1. Récupération sécurisée des notes
            $getN = function($field, $key) use ($subject, $adj) {
                if ($adj && $adj->$field !== null) return (float)$adj->$field;
                $v = $subject[$key] ?? null;
                if ($v === '-' || $v === 'ABS' || $v === '' || $v === null) return null;
                return (is_numeric($v)) ? (float)$v : null;
            };

            $n = [
                'ev1' => $getN('ev1', 'ev1'), 'ev2' => $getN('ev2', 'ev2'), 'c1' => $getN('comp1', 'comp1'),
                'ev3' => $getN('ev3', 'ev3'), 'ev4' => $getN('ev4', 'ev4'), 'c2' => $getN('comp2', 'comp2'),
                'ev5' => $getN('ev5', 'ev5'), 'ev6' => $getN('ev6', 'ev6'), 'c3' => $getN('comp3', 'comp3'),
            ];

            // 2. Mise à jour de l'affichage texte dans le tableau
            foreach ($n as $k => $v) {
                $subject[$k == 'c1' ? 'comp1' : ($k == 'c2' ? 'comp2' : ($k == 'c3' ? 'comp3' : $k))] = ($v !== null) ? number_format($v, 2) : '-';
            }

            // 3. Calcul intelligent des trimestres
            $calcTrim = function($e1, $e2, $comp, $isT3 = false) {
                if ($e1 === null && $e2 === null && $comp === null) return null;
                
                // Règle T3 : Souvent uniquement la composition
                if ($isT3 && $e1 === null && $e2 === null) return $comp;

                // Calcul du DS (Moyenne des séquences présentes)
                $ds = null;
                if ($e1 !== null && $e2 !== null) $ds = ($e1 + $e2) / 2;
                elseif ($e1 !== null) $ds = $e1; 
                elseif ($e2 !== null) $ds = $e2;
                elseif (!$isT3) $ds = 0; // Pénalité si séquences vides en T1/T2

                // Moyenne Trimestre = (DS + Compo) / 2
                if ($ds !== null && $comp !== null) return ($ds + $comp) / 2;
                return $ds ?? $comp;
            };

            // Toujours recalculer depuis les sequences (originales ou ajustees)
            // Le trim stocke en base peut etre obsolete - il ne sert que de fallback
            // si aucune donnee de sequence n'est disponible pour ce trimestre
            $calcT1 = $calcTrim($n['ev1'], $n['ev2'], $n['c1']);
            $calcT2 = $calcTrim($n['ev3'], $n['ev4'], $n['c2']);
            $calcT3 = $calcTrim($n['ev5'], $n['ev6'], $n['c3'], true);
            $t1 = ($calcT1 !== null) ? $calcT1 : (($adj && $adj->trim1 !== null) ? (float)$adj->trim1 : null);
            $t2 = ($calcT2 !== null) ? $calcT2 : (($adj && $adj->trim2 !== null) ? (float)$adj->trim2 : null);
            $t3 = ($calcT3 !== null) ? $calcT3 : (($adj && $adj->trim3 !== null) ? (float)$adj->trim3 : null);

            $subject['trim1'] = ($t1 !== null) ? number_format($t1, 2) : '-';
            $subject['trim2'] = ($t2 !== null) ? number_format($t2, 2) : '-';
            $subject['trim3'] = ($t3 !== null) ? number_format($t3, 2) : '-';

            // 4. Accumulation pour le résumé annuel
            $map = [
                'ev1' => $n['ev1'], 'ev2' => $n['ev2'], 'comp1' => $n['c1'], 'trim1' => $t1,
                'ev3' => $n['ev3'], 'ev4' => $n['ev4'], 'comp2' => $n['c2'], 'trim2' => $t2,
                'ev5' => $n['ev5'], 'ev6' => $n['ev6'], 'comp3' => $n['c3'], 'trim3' => $t3,
            ];
            foreach ($map as $k => $v) {
                if ($v !== null) {
                    $summary[$k][0] += $v * $coef;
                    $summary[$k][1] += $coef;
                }
            }

            // 5. Moyenne Annuelle de la matière (Division par le nombre de trimestres réels)
            $trims = array_filter([$t1, $t2, $t3], fn($v) => $v !== null);
            $annualAvg = (count($trims) > 0) ? array_sum($trims) / count($trims) : 0;
            
            $subject['annual_average'] = $annualAvg;
            $subject['total'] = $annualAvg * $coef;

            $totalPoints += $subject['total'];
            $totalCoefficient += $coef;
            
            if ($annualAvg >= 16) $subject['competence'] = 'A+ (Expert)';
            elseif ($annualAvg >= 14) $subject['competence'] = 'A (Acquise)';
            elseif ($annualAvg >= 10) $subject['competence'] = 'ECA (En Cours)';
            else $subject['competence'] = 'NA (Non Acquise)';
        }

        // 6. Moyenne Générale du Bulletin
        $generalAvg = ($totalCoefficient > 0) ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['average'] = $generalAvg;
        $bulletinData['total_general'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient;

        // 7. Synchronisation du résumé "TRAVAIL ANNUEL"
        if (isset($bulletinData['travail_annuel']) && is_array($bulletinData['travail_annuel'])) {
            foreach ($bulletinData['travail_annuel'] as &$row) {
                $lbl = strtolower($row['label']);
                $key = null;
                if (strpos($lbl, 'ev1') !== false) $key = 'ev1';
                elseif (strpos($lbl, 'ev2') !== false) $key = 'ev2';
                elseif (strpos($lbl, 'ev3') !== false) $key = 'ev3';
                elseif (strpos($lbl, 'ev4') !== false) $key = 'ev4';
                elseif (strpos($lbl, 'ev5') !== false) $key = 'ev5';
                elseif (strpos($lbl, 'ev6') !== false) $key = 'ev6';
                elseif (strpos($lbl, 'compo trim1') !== false) $key = 'comp1';
                elseif (strpos($lbl, 'compo trim2') !== false) $key = 'comp2';
                elseif (strpos($lbl, 'compo trim3') !== false) $key = 'comp3';
                elseif (preg_match('/trimestre\s*1/', $lbl)) $key = 'trim1';
                elseif (preg_match('/trimestre\s*2/', $lbl)) $key = 'trim2';
                elseif (preg_match('/trimestre\s*3/', $lbl)) $key = 'trim3';

                if ($key && $summary[$key][1] > 0) {
                    $row['avg'] = number_format($summary[$key][0] / $summary[$key][1], 2);
                }
                if (strpos($lbl, 'annuelle') !== false) $row['avg'] = number_format($generalAvg, 2);
                if (strpos($lbl, "honneur") !== false) $row['avg'] = ($generalAvg >= 12) ? 'Oui' : 'Non';
            }
        }

        return $bulletinData;
    }

    /**
     * Telecharger le livret PDF d'un eleve
     */
    public function downloadLivret($studentId)
    {
        $filename = "livret_scolaire_{$studentId}_" . now()->format('Y-m-d') . ".pdf";
        $path = storage_path("app/public/bulletins/{$filename}");

        if (!file_exists($path)) {
            return response()->json(['success' => false, 'error' => 'Livret non genere'], 404);
        }

        return response()->download($path, $filename);
    }

    /**
     * Fusionner tous les livrets d'une classe en un seul PDF
     */
    public function downloadAll(Request $request)
    {
        $request->validate([
            'series_id' => 'required|exists:class_series,id',
        ]);

        $seriesId = $request->series_id;
        $series = ClassSeries::with('schoolClass')->find($seriesId);

        $students = Student::where('class_series_id', $seriesId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $pdfFiles = [];
        $today = now()->format('Y-m-d');
        foreach ($students as $student) {
            $path = storage_path("app/public/bulletins/livret_scolaire_{$student->id}_{$today}.pdf");
            if (file_exists($path)) {
                $pdfFiles[] = $path;
            }
        }

        if (empty($pdfFiles)) {
            return response()->json(['success' => false, 'error' => 'Aucun livret genere pour cette classe'], 404);
        }

        $className = str_replace(' ', '_', $series->name ?? 'classe');
        $outputFilename = "livrets_scolaires_{$className}_{$today}.pdf";
        $outputPath = storage_path("app/public/merged_bulletins/{$outputFilename}");

        $directory = dirname($outputPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi();

            foreach ($pdfFiles as $file) {
                $pageCount = $pdf->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $templateId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $pdf->Output('F', $outputPath);
        } catch (\Exception $e) {
            \Log::error('Erreur fusion livrets: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Echec de la fusion: ' . $e->getMessage()], 500);
        }

        if (!file_exists($outputPath)) {
            return response()->json(['success' => false, 'error' => 'Echec de la fusion du PDF'], 500);
        }

        return response()->json([
            'success' => true,
            'download_url' => "merged_bulletins/{$outputFilename}",
            'count' => count($pdfFiles),
        ]);
    }
}
