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

        $calcTrimGrade = function ($sid, $trimester, $cssId) use (&$seqMap, $getGradeFromIndex, $getCompGrade) {
            if ($trimester == 3) {
                $comp = $getCompGrade($sid, 3, $cssId);
                return ($comp !== null && $comp !== 'ABS') ? (float)$comp : null;
            }
            $seqIds = $seqMap[$trimester] ?? [];
            $s1 = isset($seqIds[0]) ? $getGradeFromIndex($sid, $seqIds[0], $cssId) : null;
            $s2 = isset($seqIds[1]) ? $getGradeFromIndex($sid, $seqIds[1], $cssId) : null;
            $cg = $getCompGrade($sid, $trimester, $cssId);
            $notesPresentes = ($s1 !== null ? 1 : 0) + ($s2 !== null ? 1 : 0) + ($cg !== null ? 1 : 0);
            if ($notesPresentes === 0) return null;
            $ds = (($s1 ?? 0) + ($s2 ?? 0)) / 2;
            return ($ds + ($cg ?? 0)) / 2;
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

        // Remplacer le titre "BULLETIN ANNUEL" par "LIVRET SCOLAIRE"
        $htmlContent = str_replace('BULLETIN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
        $htmlContent = str_replace('BULLETIN BILAN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
        $htmlContent = str_replace('bulletin annuel', 'livret scolaire', $htmlContent);
        $htmlContent = str_replace('Bulletin Annuel', 'Livret Scolaire', $htmlContent);

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
                $htmlContent = str_replace('BULLETIN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
                $htmlContent = str_replace('BULLETIN BILAN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
                $htmlContent = str_replace('bulletin annuel', 'livret scolaire', $htmlContent);
                $htmlContent = str_replace('Bulletin Annuel', 'Livret Scolaire', $htmlContent);

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
                $htmlContent = str_replace('BULLETIN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
                $htmlContent = str_replace('BULLETIN BILAN ANNUEL', 'LIVRET SCOLAIRE', $htmlContent);
                $htmlContent = str_replace('bulletin annuel', 'livret scolaire', $htmlContent);
                $htmlContent = str_replace('Bulletin Annuel', 'Livret Scolaire', $htmlContent);

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
     */
    protected function applyAdjustedGrades($bulletinData, $adjustedGrades)
    {
        $totalPoints = 0;
        $totalCoefficient = 0;
        $isNonApc = !empty($bulletinData['is_non_apc_annual']);

        foreach ($bulletinData['subjects'] as &$subject) {
            $cssId = $subject['class_series_subject_id'] ?? null;
            if (!$cssId) continue;

            $adjusted = $adjustedGrades->get($cssId);
            $coef = (float)$subject['coefficient'];

            if (!$adjusted) {
                $totalPoints += ($subject['annual_average'] ?? 0) * $coef;
                $totalCoefficient += $coef;
                continue;
            }

            // Calculer la moyenne originale par trimestre a partir des ev/comp
            $origT1 = null;
            $origT2 = null;
            $origT3 = null;

            if ($isNonApc) {
                // Non-APC: calcul trimestre = (DS + Comp) / 2, DS = (ev1+ev2)/2
                $ev1 = is_numeric($subject['ev1'] ?? null) ? (float)$subject['ev1'] : null;
                $ev2 = is_numeric($subject['ev2'] ?? null) ? (float)$subject['ev2'] : null;
                $comp1 = is_numeric($subject['comp1'] ?? null) ? (float)$subject['comp1'] : null;
                $ev3 = is_numeric($subject['ev3'] ?? null) ? (float)$subject['ev3'] : null;
                $ev4 = is_numeric($subject['ev4'] ?? null) ? (float)$subject['ev4'] : null;
                $comp2 = is_numeric($subject['comp2'] ?? null) ? (float)$subject['comp2'] : null;
                $comp3 = is_numeric($subject['comp3'] ?? null) ? (float)$subject['comp3'] : null;

                // Calculer les moyennes trimestrielles originales
                $ds1Parts = array_filter([$ev1, $ev2], fn($v) => $v !== null);
                $ds1 = count($ds1Parts) > 0 ? array_sum($ds1Parts) / max(count($ds1Parts), 2) : null;
                if ($ds1 !== null && $comp1 !== null) $origT1 = ($ds1 + $comp1) / 2;
                elseif ($ds1 !== null) $origT1 = $ds1 / 2;
                elseif ($comp1 !== null) $origT1 = $comp1 / 2;

                $ds2Parts = array_filter([$ev3, $ev4], fn($v) => $v !== null);
                $ds2 = count($ds2Parts) > 0 ? array_sum($ds2Parts) / max(count($ds2Parts), 2) : null;
                if ($ds2 !== null && $comp2 !== null) $origT2 = ($ds2 + $comp2) / 2;
                elseif ($ds2 !== null) $origT2 = $ds2 / 2;
                elseif ($comp2 !== null) $origT2 = $comp2 / 2;

                $origT3 = $comp3;
            }

            // Appliquer les notes de sequences ajustees si elles existent
            if ($isNonApc) {
                $adjEv1 = $adjusted->ev1 !== null ? (float)$adjusted->ev1 : null;
                $adjEv2 = $adjusted->ev2 !== null ? (float)$adjusted->ev2 : null;
                $adjComp1 = $adjusted->comp1 !== null ? (float)$adjusted->comp1 : null;
                $adjEv3 = $adjusted->ev3 !== null ? (float)$adjusted->ev3 : null;
                $adjEv4 = $adjusted->ev4 !== null ? (float)$adjusted->ev4 : null;
                $adjComp2 = $adjusted->comp2 !== null ? (float)$adjusted->comp2 : null;
                $adjComp3 = $adjusted->comp3 !== null ? (float)$adjusted->comp3 : null;

                // Remplacer les valeurs de sequences dans le subject si ajustees
                if ($adjEv1 !== null) $subject['ev1'] = number_format($adjEv1, 2);
                if ($adjEv2 !== null) $subject['ev2'] = number_format($adjEv2, 2);
                if ($adjComp1 !== null) $subject['comp1'] = number_format($adjComp1, 2);
                if ($adjEv3 !== null) $subject['ev3'] = number_format($adjEv3, 2);
                if ($adjEv4 !== null) $subject['ev4'] = number_format($adjEv4, 2);
                if ($adjComp2 !== null) $subject['comp2'] = number_format($adjComp2, 2);
                if ($adjComp3 !== null) $subject['comp3'] = number_format($adjComp3, 2);

                // Recalculer les trimestres a partir des sequences (ajustees ou originales)
                $finalEv1 = is_numeric($subject['ev1'] ?? null) ? (float)$subject['ev1'] : null;
                $finalEv2 = is_numeric($subject['ev2'] ?? null) ? (float)$subject['ev2'] : null;
                $finalComp1 = is_numeric($subject['comp1'] ?? null) ? (float)$subject['comp1'] : null;
                $finalEv3 = is_numeric($subject['ev3'] ?? null) ? (float)$subject['ev3'] : null;
                $finalEv4 = is_numeric($subject['ev4'] ?? null) ? (float)$subject['ev4'] : null;
                $finalComp2 = is_numeric($subject['comp2'] ?? null) ? (float)$subject['comp2'] : null;
                $finalComp3 = is_numeric($subject['comp3'] ?? null) ? (float)$subject['comp3'] : null;

                // Trim1: DS1 = (ev1+ev2)/2, M/20 = (DS1+Comp1)/2
                $ds1Parts = array_filter([$finalEv1, $finalEv2], fn($v) => $v !== null);
                $ds1 = count($ds1Parts) > 0 ? array_sum($ds1Parts) / max(count($ds1Parts), 2) : null;
                if ($ds1 !== null && $finalComp1 !== null) $origT1 = ($ds1 + $finalComp1) / 2;
                elseif ($ds1 !== null) $origT1 = $ds1 / 2;
                elseif ($finalComp1 !== null) $origT1 = $finalComp1 / 2;
                else $origT1 = null;

                // Trim2: DS2 = (ev3+ev4)/2, M/20 = (DS2+Comp2)/2
                $ds2Parts = array_filter([$finalEv3, $finalEv4], fn($v) => $v !== null);
                $ds2 = count($ds2Parts) > 0 ? array_sum($ds2Parts) / max(count($ds2Parts), 2) : null;
                if ($ds2 !== null && $finalComp2 !== null) $origT2 = ($ds2 + $finalComp2) / 2;
                elseif ($ds2 !== null) $origT2 = $ds2 / 2;
                elseif ($finalComp2 !== null) $origT2 = $finalComp2 / 2;
                else $origT2 = null;

                // Trim3: Comp3 only
                $origT3 = $finalComp3;
            }

            // Appliquer les notes trimestrielles ajustees (si null, utiliser le calcul des sequences)
            $t1 = $adjusted->trim1 !== null ? (float)$adjusted->trim1 : ($origT1 ?? 0);
            $t2 = $adjusted->trim2 !== null ? (float)$adjusted->trim2 : ($origT2 ?? 0);
            $t3 = $adjusted->trim3 !== null ? (float)$adjusted->trim3 : ($origT3 ?? 0);

            // Compter combien de trimestres ont des donnees
            $trimCount = 0;
            $trimSum = 0;
            if ($adjusted->trim1 !== null || $origT1 !== null) { $trimCount++; $trimSum += $t1; }
            if ($adjusted->trim2 !== null || $origT2 !== null) { $trimCount++; $trimSum += $t2; }
            if ($adjusted->trim3 !== null || $origT3 !== null) { $trimCount++; $trimSum += $t3; }

            $annualAvg = $trimCount > 0 ? $trimSum / $trimCount : 0;
            $total = $annualAvg * $coef;

            $subject['annual_average'] = $annualAvg;
            $subject['total'] = $total;

            // Mettre a jour competence si present
            if (isset($subject['competence'])) {
                $subject['competence'] = 'NA (Non Acquise)';
                if ($annualAvg >= 16) $subject['competence'] = 'A+ (Expert)';
                elseif ($annualAvg >= 14) $subject['competence'] = 'A (Acquise)';
                elseif ($annualAvg >= 10) $subject['competence'] = 'ECA (En Cours)';
            }

            $totalPoints += $total;
            $totalCoefficient += $coef;
        }

        // Recalculer la moyenne generale
        $generalAverage = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_general'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient;
        $bulletinData['general_average'] = $generalAverage;

        // Recalculer l'appreciation
        if ($generalAverage >= 16) $bulletinData['general_appreciation'] = 'Excellent';
        elseif ($generalAverage >= 14) $bulletinData['general_appreciation'] = 'Tres Bien';
        elseif ($generalAverage >= 12) $bulletinData['general_appreciation'] = 'Bien';
        elseif ($generalAverage >= 10) $bulletinData['general_appreciation'] = 'Assez Bien';
        elseif ($generalAverage >= 8) $bulletinData['general_appreciation'] = 'Passable';
        else $bulletinData['general_appreciation'] = 'Insuffisant';

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
