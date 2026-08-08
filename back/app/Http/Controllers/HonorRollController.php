<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Trimester;
use App\Models\Section;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\MergedHonorRollPDF;
use App\Jobs\MergeHonorRollPDFs;

class HonorRollController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Récupérer les élèves éligibles au tableau d'honneur
     * Critères: Moyenne >= 12/20 sur un trimestre ou sur l'année
     */
    public function getEligibleStudents(Request $request)
    {
        $trimesterId = $request->input('trimester_id');
        $periodType = $request->input('period_type', 'trimester');
        $sectionId = $request->input('section_id');
        $levelId = $request->input('level_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        if ($periodType !== 'annual' && !$trimesterId) {
            return response()->json(['success' => false, 'message' => 'Le trimestre est requis'], 400);
        }

        $trimester = null;
        if ($periodType !== 'annual') {
            $trimester = Trimester::find($trimesterId);
            if (!$trimester) {
                return response()->json(['success' => false, 'message' => 'Trimestre introuvable'], 404);
            }
        }

        if (!$seriesId && !$classId && !$levelId && !$sectionId) {
            return response()->json(['success' => false, 'message' => 'Veuillez sélectionner au moins un filtre'], 400);
        }

        // Construire la requête des étudiants avec filtres
        $query = Student::query();

        // Filtrer par année scolaire si on travaille sur une année archivée
        $user = auth()->user();
        $workingYearId = $user->working_school_year_id ?? null;
        if ($workingYearId) {
            $query->where('school_year_id', $workingYearId);
        } else {
            $query->where('is_active', true);
        }

        if ($seriesId) {
            $query->where('class_series_id', is_array($seriesId) ? $seriesId : [$seriesId]);
            if (is_array($seriesId)) {
                $query->whereIn('class_series_id', $seriesId);
            } else {
                $query->where('class_series_id', $seriesId);
            }
        } elseif ($classId) {
            $query->whereHas('classSeries', fn($q) => $q->where('class_id', $classId));
        } elseif ($levelId) {
            $query->whereHas('classSeries.schoolClass', fn($q) => $q->where('level_id', $levelId));
        } elseif ($sectionId) {
            $query->whereHas('classSeries.schoolClass.level', fn($q) => $q->where('section_id', $sectionId));
        }

        $students = $query->with(['classSeries.schoolClass.level.section'])->get();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => true,
                'students' => [],
                'grouped_by_mention' => ['Excellent' => [], 'Très bien' => [], 'Bien' => [], 'Assez bien' => []],
                'statistics' => ['total' => 0, 'excellent' => 0, 'tres_bien' => 0, 'bien' => 0, 'assez_bien' => 0],
            ]);
        }

        $studentIds = $students->pluck('id')->toArray();

        if ($periodType === 'annual') {
            $eligibleStudents = $this->calculateAnnualHonorRoll($students);
        } else {
            $eligibleStudents = $this->calculateTrimesterHonorRoll($students, $trimester);
        }

        // Trier par moyenne décroissante
        usort($eligibleStudents, fn($a, $b) => $b['average'] <=> $a['average']);

        // Calculer les rangs par classe
        $byClass = [];
        foreach ($eligibleStudents as &$s) {
            $byClass[$s['class_series_id']][] = &$s;
        }
        unset($s);
        // Les rangs sont déjà calculés dans les méthodes optimisées

        $groupedByMention = ['Excellent' => [], 'Très bien' => [], 'Bien' => [], 'Assez bien' => []];
        foreach ($eligibleStudents as $student) {
            $groupedByMention[$student['mention']][] = $student;
        }

        $periodLabel = $periodType === 'annual' ? 'Bilan Annuel' : 'Trimestre ' . ($trimester ? $trimester->number : '');

        return response()->json([
            'success' => true,
            'period' => ['type' => $periodType, 'label' => $periodLabel],
            'trimester' => $trimester ? [
                'id' => $trimester->id,
                'name' => 'Trimestre ' . $trimester->number,
                'trimester_number' => $trimester->number,
            ] : null,
            'students' => $eligibleStudents,
            'grouped_by_mention' => $groupedByMention,
            'statistics' => [
                'total' => count($eligibleStudents),
                'excellent' => count($groupedByMention['Excellent']),
                'tres_bien' => count($groupedByMention['Très bien']),
                'bien' => count($groupedByMention['Bien']),
                'assez_bien' => count($groupedByMention['Assez bien']),
            ]
        ]);
    }

    /**
     * 🚀 Calcul optimisé du tableau d'honneur pour un trimestre
     * Utilise des requêtes SQL directes au lieu de générer un bulletin complet par élève
     */
    private function calculateTrimesterHonorRoll($students, $trimester)
    {
        $studentIds = $students->pluck('id')->toArray();
        $trimesterId = $trimester->id;
        $trimesterNumber = $trimester->number;

        // 1. Récupérer les séquences de ce trimestre
        $sequences = \App\Models\Sequence::where('trimester_id', $trimesterId)
            ->where('is_composition', false)
            ->orderBy('number')
            ->get();

        // 2. Récupérer la composition de ce trimestre
        $composition = \App\Models\Sequence::where('trimester_id', $trimesterId)
            ->where('is_composition', true)
            ->first();

        // 3. Récupérer TOUTES les notes en une seule requête
        $allGrades = \App\Models\Grade::whereIn('student_id', $studentIds)
            ->where('trimester_id', $trimesterId)
            ->whereNotNull('score')
            ->where('is_absent', false)
            ->select('student_id', 'sequence_id', 'class_series_subject_id', 'score', 'max_score')
            ->get();

        // 4. Récupérer les coefficients des matières pour chaque série
        $seriesIds = $students->pluck('class_series_id')->unique()->toArray();
        $subjectCoefs = \App\Models\ClassSeriesSubject::whereIn('class_series_id', $seriesIds)
            ->select('id', 'class_series_id', 'coefficient')
            ->get()
            ->keyBy('id');

        // 5. Organiser les notes: [student_id][subject_id][sequence_id] = score/20
        $gradeIndex = [];
        foreach ($allGrades as $g) {
            $scoreOn20 = ($g->max_score > 0) ? ($g->score / $g->max_score) * 20 : $g->score;
            $gradeIndex[$g->student_id][$g->class_series_subject_id][$g->sequence_id] = $scoreOn20;
        }

        // 6. Calculer la moyenne trimestrielle pour chaque élève
        // Formule Premier Cycle: M/20 = (DS + Compo) / 2, DS = (Seq1 + Seq2) / 2
        // Trimestre 3: M/20 = Composition uniquement
        $studentAverages = [];

        foreach ($students as $student) {
            $studentGrades = $gradeIndex[$student->id] ?? [];
            if (empty($studentGrades)) continue;

            $totalPoints = 0;
            $totalCoef = 0;
            $gradedSubjects = 0;

            // Matières de la classe de l'élève
            $classSubjects = $subjectCoefs->where('class_series_id', $student->class_series_id);

            foreach ($classSubjects as $subject) {
                $subGrades = $studentGrades[$subject->id] ?? [];
                if (empty($subGrades)) continue;

                $coef = (float)$subject->coefficient;

                if ($trimesterNumber == 3) {
                    // Trimestre 3: Composition seule
                    if ($composition && isset($subGrades[$composition->id])) {
                        $totalPoints += $subGrades[$composition->id] * $coef;
                        $totalCoef += $coef;
                        $gradedSubjects++;
                    }
                } else {
                    // Trimestres 1 et 2: DS + Composition
                    $seqGrades = [];
                    foreach ($sequences as $seq) {
                        if (isset($subGrades[$seq->id])) {
                            $seqGrades[] = $subGrades[$seq->id];
                        }
                    }

                    $compGrade = ($composition && isset($subGrades[$composition->id])) ? $subGrades[$composition->id] : null;

                    // Si au moins une note existe pour cette matière
                    if (!empty($seqGrades) || $compGrade !== null) {
                        // DS = moyenne des séquences (séquences manquantes = 0)
                        $ds = null;
                        if (!empty($seqGrades)) {
                            // Compléter avec 0 pour les séquences manquantes
                            while (count($seqGrades) < $sequences->count()) {
                                $seqGrades[] = 0.0;
                            }
                            $ds = array_sum($seqGrades) / count($seqGrades);
                        }

                        if ($ds !== null) {
                            $finalComp = $compGrade ?? 0.0;
                            $subjectAvg = ($ds + $finalComp) / 2;
                        } else {
                            $subjectAvg = $compGrade;
                        }

                        $totalPoints += $subjectAvg * $coef;
                        $totalCoef += $coef;
                        $gradedSubjects++;
                    }
                }
            }

            if ($totalCoef > 0) {
                $average = $totalPoints / $totalCoef;
                $studentAverages[$student->id] = [
                    'average' => $average,
                    'graded_subjects' => $gradedSubjects,
                ];
            }
        }

        // 7. Calculer les rangs par série (classe)
        $ranksByClass = [];
        foreach ($students as $student) {
            if (isset($studentAverages[$student->id])) {
                $ranksByClass[$student->class_series_id][$student->id] = $studentAverages[$student->id]['average'];
            }
        }
        $classRanks = [];
        foreach ($ranksByClass as $seriesId => $avgs) {
            arsort($avgs);
            $rank = 0;
            $prevAvg = null;
            $assignedRank = null;
            foreach ($avgs as $sid => $avg) {
                $rank++;
                if ($avg !== $prevAvg) $assignedRank = $rank;
                $classRanks[$sid] = $assignedRank;
                $prevAvg = $avg;
            }
        }

        // 8. Construire le résultat (seulement les élèves >= 12)
        $eligibleStudents = [];
        foreach ($students as $student) {
            if (!isset($studentAverages[$student->id])) continue;
            $avg = round($studentAverages[$student->id]['average'], 2);
            if ($avg < 12.00) continue;

            $mention = $this->getMention($avg);
            $eligibleStudents[] = [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->first_name . ' ' . $student->last_name,
                'date_of_birth' => $student->date_of_birth,
                'class' => $student->classSeries->name ?? 'N/A',
                'class_series_id' => $student->class_series_id,
                'section' => $student->classSeries->schoolClass->level->section->name ?? 'N/A',
                'level' => $student->classSeries->schoolClass->level->name ?? 'N/A',
                'average' => $avg,
                'rank' => $classRanks[$student->id] ?? null,
                'mention' => $mention,
            ];
        }

        return $eligibleStudents;
    }

    /**
     * 🚀 Calcul optimisé du tableau d'honneur annuel
     */
    private function calculateAnnualHonorRoll($students)
    {
        $studentIds = $students->pluck('id')->toArray();

        // Récupérer l'année scolaire de travail
        $user = auth()->user();
        $workingYearId = $user->working_school_year_id ?? null;
        $schoolYear = $workingYearId
            ? \App\Models\SchoolYear::find($workingYearId)
            : \App\Models\SchoolYear::where('is_active', true)->first();

        if (!$schoolYear) return [];

        // Récupérer les 3 trimestres de cette année
        $trimesters = Trimester::where('school_year_id', $schoolYear->id)->orderBy('number')->get();
        if ($trimesters->isEmpty()) return [];

        $trimesterIds = $trimesters->pluck('id')->toArray();

        // Récupérer TOUTES les notes de l'année en une seule requête
        $allGrades = \App\Models\Grade::whereIn('student_id', $studentIds)
            ->whereIn('trimester_id', $trimesterIds)
            ->whereNotNull('score')
            ->where('is_absent', false)
            ->select('student_id', 'sequence_id', 'trimester_id', 'class_series_subject_id', 'score', 'max_score')
            ->get();

        // Récupérer les séquences et compositions par trimestre
        $seqsByTrim = [];
        $compByTrim = [];
        foreach ($trimesters as $trim) {
            $seqsByTrim[$trim->id] = \App\Models\Sequence::where('trimester_id', $trim->id)
                ->where('is_composition', false)->orderBy('number')->get();
            $compByTrim[$trim->id] = \App\Models\Sequence::where('trimester_id', $trim->id)
                ->where('is_composition', true)->first();
        }

        // Coefficients
        $seriesIds = $students->pluck('class_series_id')->unique()->toArray();
        $subjectCoefs = \App\Models\ClassSeriesSubject::whereIn('class_series_id', $seriesIds)
            ->select('id', 'class_series_id', 'coefficient')
            ->get()->keyBy('id');

        // Organiser les notes
        $gradeIndex = []; // [student][subject][trimester_id][sequence_id] = score/20
        foreach ($allGrades as $g) {
            $scoreOn20 = ($g->max_score > 0) ? ($g->score / $g->max_score) * 20 : $g->score;
            $gradeIndex[$g->student_id][$g->class_series_subject_id][$g->trimester_id][$g->sequence_id] = $scoreOn20;
        }

        // Calculer la moyenne annuelle pour chaque élève
        $studentAverages = [];

        foreach ($students as $student) {
            $studentGrades = $gradeIndex[$student->id] ?? [];
            if (empty($studentGrades)) continue;

            $totalPoints = 0;
            $totalCoef = 0;
            $classSubjects = $subjectCoefs->where('class_series_id', $student->class_series_id);

            foreach ($classSubjects as $subject) {
                $subGrades = $studentGrades[$subject->id] ?? [];
                if (empty($subGrades)) continue;

                $coef = (float)$subject->coefficient;
                $trimAvgs = [];
                $hasAnyGrade = false;

                foreach ($trimesters as $trim) {
                    $trimGrades = $subGrades[$trim->id] ?? [];
                    $sequences = $seqsByTrim[$trim->id];
                    $composition = $compByTrim[$trim->id];

                    if ($trim->number == 3) {
                        if ($composition && isset($trimGrades[$composition->id])) {
                            $trimAvgs[$trim->number] = $trimGrades[$composition->id];
                            $hasAnyGrade = true;
                        }
                    } else {
                        $seqGrades = [];
                        foreach ($sequences as $seq) {
                            if (isset($trimGrades[$seq->id])) $seqGrades[] = $trimGrades[$seq->id];
                        }
                        $compGrade = ($composition && isset($trimGrades[$composition->id])) ? $trimGrades[$composition->id] : null;

                        if (!empty($seqGrades) || $compGrade !== null) {
                            $hasAnyGrade = true;
                            $ds = null;
                            if (!empty($seqGrades)) {
                                while (count($seqGrades) < $sequences->count()) $seqGrades[] = 0.0;
                                $ds = array_sum($seqGrades) / count($seqGrades);
                            }
                            if ($ds !== null) {
                                $trimAvgs[$trim->number] = ($ds + ($compGrade ?? 0.0)) / 2;
                            } else {
                                $trimAvgs[$trim->number] = $compGrade;
                            }
                        }
                    }
                }

                if ($hasAnyGrade) {
                    $t1 = $trimAvgs[1] ?? 0;
                    $t2 = $trimAvgs[2] ?? 0;
                    $t3 = $trimAvgs[3] ?? 0;
                    $annualAvg = ($t1 + $t2 + $t3) / 3;
                    $totalPoints += $annualAvg * $coef;
                    $totalCoef += $coef;
                }
            }

            if ($totalCoef > 0) {
                $studentAverages[$student->id] = $totalPoints / $totalCoef;
            }
        }

        // Calculer les rangs par classe
        $ranksByClass = [];
        foreach ($students as $student) {
            if (isset($studentAverages[$student->id])) {
                $ranksByClass[$student->class_series_id][$student->id] = $studentAverages[$student->id];
            }
        }
        $classRanks = [];
        foreach ($ranksByClass as $seriesId => $avgs) {
            arsort($avgs);
            $rank = 0; $prevAvg = null; $assignedRank = null;
            foreach ($avgs as $sid => $avg) {
                $rank++;
                if ($avg !== $prevAvg) $assignedRank = $rank;
                $classRanks[$sid] = $assignedRank;
                $prevAvg = $avg;
            }
        }

        // Résultat
        $eligibleStudents = [];
        foreach ($students as $student) {
            if (!isset($studentAverages[$student->id])) continue;
            $avg = round($studentAverages[$student->id], 2);
            if ($avg < 12.00) continue;

            $eligibleStudents[] = [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->first_name . ' ' . $student->last_name,
                'date_of_birth' => $student->date_of_birth,
                'class' => $student->classSeries->name ?? 'N/A',
                'class_series_id' => $student->class_series_id,
                'section' => $student->classSeries->schoolClass->level->section->name ?? 'N/A',
                'level' => $student->classSeries->schoolClass->level->name ?? 'N/A',
                'average' => $avg,
                'rank' => $classRanks[$student->id] ?? null,
                'mention' => $this->getMention($avg),
            ];
        }

        return $eligibleStudents;
    }

    /**
     * Déterminer la mention selon la moyenne
     */
    private function getMention($average)
    {
        if ($average >= 18.00) {
            return 'Excellent';
        } elseif ($average >= 16.00) {
            return 'Très bien';
        } elseif ($average >= 14.00) {
            return 'Bien';
        } elseif ($average >= 12.00) {
            return 'Assez bien';
        } else {
            return 'Passable';
        }
    }

    /**
     * Générer le certificat de tableau d'honneur en PDF pour un élève
     */
    public function generateCertificate(Request $request)
    {
        $studentId = $request->input('student_id');
        $trimesterId = $request->input('trimester_id');
        $periodType = $request->input('period_type', 'trimester');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'ID étudiant requis'
            ], 400);
        }

        if ($periodType !== 'annual' && !$trimesterId) {
            return response()->json([
                'success' => false,
                'message' => 'Le trimestre est requis'
            ], 400);
        }

        $student = Student::with(['classSeries.schoolClass.level.section'])->find($studentId);
        $trimester = null;
        if ($periodType !== 'annual') {
            $trimester = Trimester::find($trimesterId);
            if (!$student || !$trimester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Étudiant ou trimestre introuvable'
                ], 404);
            }
        } elseif (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable'
            ], 404);
        }

        // Use pre-calculated average/rank from frontend if provided (avoids heavy bulletin recalculation)
        $average = $request->input('average');
        $rank = $request->input('rank');

        if ($average && $rank) {
            $average = round((float) $average, 2);
            if ($average < 12.00) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'est pas éligible au tableau d\'honneur (moyenne < 12/20)'
                ], 400);
            }
        } else {
            // Fallback: recalculate from bulletin data
            if ($periodType === 'annual') {
                $isApc = $this->bulletinService->isApcClass($student);
                $bulletinData = $isApc
                    ? $this->bulletinService->generateAnnualBulletinData($student->id)
                    : $this->bulletinService->generateAnnualBulletinDataNonApc($student->id);
                $average = $bulletinData['general_average'] ?? $bulletinData['average'] ?? null;
                $rank = $bulletinData['rank'] ?? null;
            } else {
                $bulletinData = $this->bulletinService->generateTrimesterBulletinData(
                    $trimester->number,
                    $student->id
                );
                $average = $bulletinData['average'] ?? null;
                $rank = $bulletinData['rank'] ?? null;
            }

            if (!$bulletinData || !$average || $average < 12.00) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'est pas éligible au tableau d\'honneur (moyenne < 12/20)'
                ], 400);
            }
            $average = round((float) $average, 2);
        }

        $mention = $this->getMention($average);

        // Préparer le logo en base64
        $logoPath = $this->getLogoPath();
        $logoBase64 = '';
        if ($logoPath && file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $mimeType = mime_content_type($logoPath);
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }

        // Récupérer l'année scolaire de travail (pas forcément l'active)
        $user = auth()->user();
        $workingYearId = $user->working_school_year_id ?? null;
        $currentSchoolYear = $workingYearId
            ? \App\Models\SchoolYear::find($workingYearId)
            : \App\Models\SchoolYear::where('is_active', true)->first();
        if ($currentSchoolYear && $currentSchoolYear->start_date && $currentSchoolYear->end_date) {
            $startYear = \Carbon\Carbon::parse($currentSchoolYear->start_date)->year;
            $endYear = \Carbon\Carbon::parse($currentSchoolYear->end_date)->year;
            $academicYear = $startYear . '/' . $endYear;
        } else {
            $academicYear = date('Y') . '/' . (date('Y') + 1);
        }

        // Préparer les données pour le template
        $data = [
            'student' => $student,
            'trimester' => $trimester,
            'period_type' => $periodType,
            'period_label' => $periodType === 'annual' ? 'Année Scolaire' : 'Trimestre ' . ($trimester ? $trimester->number : ''),
            'average' => $average,
            'rank' => $rank,
            'mention' => $mention,
            'total_points' => 0,
            'class_name' => $student->classSeries->name ?? 'N/A',
            'academic_year' => $academicYear,
            'generation_date' => now()->format('d/m/Y'),
            'logo_base64' => $logoBase64,
        ];

        // Générer le PDF
        $html = view('honor_roll.certificate', $data)->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Ajouter le watermark (logo en arrière-plan)
        $canvas = $dompdf->getCanvas();
        $logoPath = $this->getLogoPath();

        if ($logoPath && file_exists($logoPath)) {
            $pageWidth = $canvas->get_width();
            $pageHeight = $canvas->get_height();
            $logoWidth = 400;
            $logoHeight = 400;
            $x = ($pageWidth - $logoWidth) / 2;
            $y = ($pageHeight - $logoHeight) / 2;

            $canvas->page_script(function ($pageNumber) use ($canvas, $logoPath, $x, $y, $logoWidth, $logoHeight) {
                $canvas->set_opacity(0.08);
                $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
                $canvas->set_opacity(1.0);
            });
        }

        $pdfContent = $dompdf->output();

        // Sauvegarder le PDF
        $periodSuffix = $periodType === 'annual' ? 'annuel' : 'trim' . ($trimester ? $trimester->number : '');
        $filename = 'honor_roll_' . $student->id . '_' . $periodSuffix . '_' . date('Y-m-d') . '.pdf';
        $filePath = 'public/honor_rolls/' . $filename;
        $fullPath = storage_path('app/' . $filePath);

        // Créer le répertoire si nécessaire
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $pdfContent);

        return response()->json([
            'success' => true,
            'message' => 'Certificat généré avec succès',
            'file_path' => $filePath,
            'download_url' => '/api/honor-rolls/download/' . basename($filePath),
        ]);
    }

    /**
     * Télécharger un certificat
     */
    public function downloadCertificate(Request $request, $filename)
    {
        // Supporter le token en query string pour les téléchargements via window.open
        if ($request->has('token') && !$request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
            try {
                auth('api')->authenticate();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Token invalide'], 401);
            }
        }

        $filePath = storage_path('app/public/honor_rolls/' . basename($filename));

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable'
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Récupérer le chemin du logo
     */
    private function getLogoPath()
    {
        // Même logique que BulletinService
        $publicPath = public_path('assets/logo.png');
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    /**
     * Helper pour générer le label du filtre pour le nom de fichier fusionné
     */
    private function generateMergeFilterLabel($validated)
    {
        if (!empty($validated['series_id'])) return "serie_{$validated['series_id']}";
        if (!empty($validated['class_id'])) return "class_{$validated['class_id']}";
        if (!empty($validated['level_id'])) return "level_{$validated['level_id']}";
        if (!empty($validated['section_id'])) return "section_{$validated['section_id']}";
        return "all";
    }

    /**
     * Télécharger un fichier fusionné directement par nom
     */
    public function downloadMergedFile($filename)
    {
        $filePath = storage_path('app/public/merged_honor_rolls/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Fichier introuvable'], 404);
        }

        return response()->download($filePath, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Récupérer les filtres disponibles (sections, niveaux, classes, séries)
     */
    public function getFilters(Request $request)
    {
        $sections = Section::where('is_active', true)->get();
        $levels = Level::where('is_active', true)->with('section')->get();
        $classes = SchoolClass::where('is_active', true)->with('level.section')->get();
        $series = ClassSeries::where('is_active', true)->with('schoolClass.level.section')->get();

        // Filtrer les trimestres par année scolaire
        $schoolYearId = $request->input('school_year_id');
        if (!$schoolYearId) {
            $user = auth()->user();
            $schoolYearId = $user->working_school_year_id ?? null;
        }
        $trimesters = Trimester::when($schoolYearId, fn($q) => $q->where('school_year_id', $schoolYearId))
            ->orderBy('number')->get();

        return response()->json([
            'success' => true,
            'sections' => $sections,
            'levels' => $levels,
            'classes' => $classes,
            'series' => $series,
            'trimesters' => $trimesters,
        ]);
    }

    /**
     * Générer en masse tous les certificats pour les élèves éligibles
     */
    public function batchGenerateCertificates(Request $request)
    {
        set_time_limit(600); // 10 minutes

        $periodType = $request->input('period_type', 'trimester');

        $rules = [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ];
        if ($periodType !== 'annual') {
            $rules['trimester_id'] = 'required|exists:trimesters,id';
        }
        $request->validate($rules);

        $studentIds = $request->input('student_ids');
        $trimesterId = $request->input('trimester_id');
        $trimester = $periodType !== 'annual' ? Trimester::find($trimesterId) : null;

        // Build lookup for pre-calculated data from frontend
        $studentsDataMap = [];
        if ($request->has('students_data')) {
            foreach ($request->input('students_data') as $sd) {
                $studentsDataMap[$sd['id']] = $sd;
            }
        }

        $generated = [];
        $failed = [];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::with(['classSeries.schoolClass.level.section'])->find($studentId);

                if (!$student) {
                    $failed[] = ['id' => $studentId, 'reason' => 'Étudiant introuvable'];
                    continue;
                }

                // Use pre-calculated data if available
                if (isset($studentsDataMap[$studentId])) {
                    $average = round((float) $studentsDataMap[$studentId]['average'], 2);
                    $rank = $studentsDataMap[$studentId]['rank'];
                    if ($average < 12.00) {
                        $failed[] = ['id' => $studentId, 'reason' => 'Non éligible (moyenne < 12/20)'];
                        continue;
                    }
                } else {
                    // Fallback: recalculate
                    if ($periodType === 'annual') {
                        $isApc = $this->bulletinService->isApcClass($student);
                        $bulletinData = $isApc
                            ? $this->bulletinService->generateAnnualBulletinData($student->id)
                            : $this->bulletinService->generateAnnualBulletinDataNonApc($student->id);
                        $avg = $bulletinData['general_average'] ?? $bulletinData['average'] ?? null;
                    } else {
                        $bulletinData = $this->bulletinService->generateTrimesterBulletinData(
                            $trimester->number,
                            $student->id
                        );
                        $avg = $bulletinData['average'] ?? null;
                    }

                    if (!$bulletinData || !$avg || $avg < 12.00) {
                        $failed[] = ['id' => $studentId, 'reason' => 'Non éligible (moyenne < 12/20)'];
                        continue;
                    }
                    $average = round((float) $avg, 2);
                    $rank = $bulletinData['rank'] ?? null;
                }

                $mention = $this->getMention($average);

                // Préparer le logo en base64
                $logoPath = $this->getLogoPath();
                $logoBase64 = '';
                if ($logoPath && file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }

                // Récupérer l'année scolaire de travail
                $batchUser = auth()->user();
                $batchWorkingYearId = $batchUser->working_school_year_id ?? null;
                $currentSchoolYear = $batchWorkingYearId
                    ? \App\Models\SchoolYear::find($batchWorkingYearId)
                    : \App\Models\SchoolYear::where('is_active', true)->first();
                if ($currentSchoolYear && $currentSchoolYear->start_date && $currentSchoolYear->end_date) {
                    $startYear = \Carbon\Carbon::parse($currentSchoolYear->start_date)->year;
                    $endYear = \Carbon\Carbon::parse($currentSchoolYear->end_date)->year;
                    $academicYear = $startYear . '/' . $endYear;
                } else {
                    // Fallback: année courante
                    $academicYear = date('Y') . '/' . (date('Y') + 1);
                }

                // Préparer les données pour le template
                $data = [
                    'student' => $student,
                    'trimester' => $trimester,
                    'period_type' => $periodType,
                    'period_label' => $periodType === 'annual' ? 'Année Scolaire' : 'Trimestre ' . ($trimester ? $trimester->number : ''),
                    'average' => $average,
                    'rank' => $rank,
                    'mention' => $mention,
                    'total_points' => 0,
                    'class_name' => $student->classSeries->name ?? 'N/A',
                    'academic_year' => $academicYear,
                    'generation_date' => now()->format('d/m/Y'),
                    'logo_base64' => $logoBase64,
                ];

                // Générer le PDF
                $html = view('honor_roll.certificate', $data)->render();

                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true);

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();

                // Ajouter le watermark
                $canvas = $dompdf->getCanvas();
                if ($logoPath && file_exists($logoPath)) {
                    $pageWidth = $canvas->get_width();
                    $pageHeight = $canvas->get_height();
                    $logoWidth = 400;
                    $logoHeight = 400;
                    $x = ($pageWidth - $logoWidth) / 2;
                    $y = ($pageHeight - $logoHeight) / 2;

                    $canvas->page_script(function ($pageNumber) use ($canvas, $logoPath, $x, $y, $logoWidth, $logoHeight) {
                        $canvas->set_opacity(0.08);
                        $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
                        $canvas->set_opacity(1.0);
                    });
                }

                $pdfContent = $dompdf->output();

                // Sauvegarder le PDF
                $pSuffix = $periodType === 'annual' ? 'annuel' : 'trim' . ($trimester ? $trimester->number : '');
                $filename = 'honor_roll_' . $student->id . '_' . $pSuffix . '_' . date('Y-m-d') . '.pdf';
                $filePath = 'public/honor_rolls/' . $filename;
                $fullPath = storage_path('app/' . $filePath);

                $directory = dirname($fullPath);
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                file_put_contents($fullPath, $pdfContent);

                $generated[] = [
                    'student_id' => $studentId,
                    'file_path' => $filePath,
                    'filename' => $filename,
                ];

            } catch (\Exception $e) {
                \Log::error("Error generating certificate for student {$studentId}: " . $e->getMessage());
                $failed[] = ['id' => $studentId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($generated) . ' certificats générés avec succès',
            'generated' => $generated,
            'failed' => $failed,
            'total' => count($studentIds),
            'generated_count' => count($generated),
            'failed_count' => count($failed),
        ]);
    }

    /**
     * Fusionner les certificats d'honneur en un seul PDF
     */
    public function mergeCertificates(Request $request)
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $periodType = $request->input('period_type', 'trimester');

        $rules = [
            'section_id' => 'nullable|exists:sections,id',
            'level_id' => 'nullable|exists:levels,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'series_id' => 'nullable|exists:class_series,id',
        ];
        if ($periodType !== 'annual') {
            $rules['trimester_id'] = 'required|exists:trimesters,id';
        }
        $validated = $request->validate($rules);

        // Récupérer tous les fichiers de certificats selon les filtres
        $certificatePaths = [];
        $students = Student::where('is_active', true);

        if (!empty($validated['series_id'])) {
            $students->where('class_series_id', $validated['series_id']);
        } elseif (!empty($validated['class_id'])) {
            $students->whereHas('classSeries', function ($q) use ($validated) {
                $q->where('class_id', $validated['class_id']);
            });
        } elseif (!empty($validated['level_id'])) {
            $students->whereHas('classSeries.schoolClass', function ($q) use ($validated) {
                $q->where('level_id', $validated['level_id']);
            });
        } elseif (!empty($validated['section_id'])) {
            $students->whereHas('classSeries.schoolClass.level', function ($q) use ($validated) {
                $q->where('section_id', $validated['section_id']);
            });
        }

        $students = $students->get();

        // Déterminer le suffixe de période pour la recherche de fichiers
        if ($periodType === 'annual') {
            $fileSuffix = 'annuel';
        } else {
            $trimester = Trimester::find($validated['trimester_id']);
            $fileSuffix = 'trim' . ($trimester ? $trimester->number : $validated['trimester_id']);
        }

        // Chercher les certificats existants pour ces étudiants
        foreach ($students as $student) {
            $filename = 'honor_roll_' . $student->id . '_' . $fileSuffix . '_' . date('Y-m-d') . '.pdf';
            $filePath = 'public/honor_rolls/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (file_exists($fullPath)) {
                $certificatePaths[] = $filePath;
            } else {
                // Fallback: chercher avec n'importe quelle date
                $pattern = storage_path('app/public/honor_rolls/honor_roll_' . $student->id . '_' . $fileSuffix . '_*.pdf');
                $matches = glob($pattern);
                if (!empty($matches)) {
                    // Prendre le plus récent
                    usort($matches, fn($a, $b) => filemtime($b) - filemtime($a));
                    $certificatePaths[] = str_replace(storage_path('app/'), '', $matches[0]);
                }
            }
        }

        if (empty($certificatePaths)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun certificat trouvé pour les critères sélectionnés. Générez d\'abord les certificats individuels.'
            ], 404);
        }

        // Faire la fusion directement (sans job) pour éviter les problèmes de timeout/cache
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $processedCount = 0;

            foreach ($certificatePaths as $certificatePath) {
                try {
                    $filePath = storage_path('app/' . $certificatePath);
                    if (!file_exists($filePath)) continue;

                    $pageCount = $pdf->setSourceFile($filePath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $tplId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($tplId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($tplId);
                    }
                    $processedCount++;
                } catch (\Exception $e) {
                    \Log::warning("Erreur fusion certificat: " . $e->getMessage());
                }
            }

            if ($processedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun PDF n\'a pu être fusionné'
                ], 500);
            }

            // Générer le fichier
            $filterLabel = $this->generateMergeFilterLabel($validated);
            $filename = "honor_rolls_{$fileSuffix}_{$filterLabel}_" . now()->format('Y-m-d_His') . ".pdf";
            $relativePath = "merged_honor_rolls/{$filename}";
            $fullPath = storage_path('app/public/' . $relativePath);

            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $pdf->Output('F', $fullPath);

            // Sauvegarder en DB
            try {
                $mergedPdf = MergedHonorRollPDF::create([
                    'trimester_id' => $validated['trimester_id'] ?? null,
                    'section_id' => $validated['section_id'] ?? null,
                    'level_id' => $validated['level_id'] ?? null,
                    'class_id' => $validated['class_id'] ?? null,
                    'series_id' => $validated['series_id'] ?? null,
                    'file_path' => $relativePath,
                    'filename' => $filename,
                    'certificate_count' => $processedCount,
                    'file_size' => filesize($fullPath),
                    'status' => 'completed'
                ]);
                $fileId = $mergedPdf->id;
            } catch (\Exception $e) {
                \Log::warning("DB save failed for merge, but file exists: " . $e->getMessage());
                $fileId = null;
            }

            return response()->json([
                'success' => true,
                'message' => "{$processedCount} certificats fusionnés avec succès !",
                'completed' => true,
                'certificate_count' => $processedCount,
                'file_id' => $fileId,
                'filename' => $filename,
                'download_url' => $fileId ? "/api/honor-rolls/merged/{$fileId}/download" : null,
                'direct_download_url' => "/api/honor-rolls/download-merged-file/{$filename}",
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur fusion certificats: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fusion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la progression de la fusion
     */
    public function getMergeProgress($jobId)
    {
        $progress = Cache::get("merge_honor_progress_{$jobId}", [
            'status' => 'pending',
            'current' => 0,
            'total' => 0,
            'message' => 'En attente...'
        ]);

        // Si mode sync, vérifier directement en DB si fusion récente existe
        if (config('queue.default') === 'sync' && $progress['status'] !== 'completed') {
            $recentMerge = MergedHonorRollPDF::where('status', 'completed')
                ->where('created_at', '>=', now()->subSeconds(60))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentMerge) {
                $progress = [
                    'status' => 'completed',
                    'message' => "✅ {$recentMerge->certificate_count} certificats fusionnés avec succès !",
                    'percentage' => 100,
                    'current' => $recentMerge->certificate_count,
                    'total' => $recentMerge->certificate_count,
                    'file_id' => $recentMerge->id,
                    'filename' => $recentMerge->filename,
                    'download_url' => "/api/honor-rolls/merged/{$recentMerge->id}/download"
                ];
            }
        }

        return response()->json($progress);
    }

    /**
     * Télécharger un PDF fusionné
     */
    public function downloadMergedCertificate($mergedId)
    {
        $merged = MergedHonorRollPDF::findOrFail($mergedId);

        if (!Storage::disk('public')->exists($merged->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier PDF introuvable'
            ], 404);
        }

        return Storage::disk('public')->download(
            $merged->file_path,
            $merged->filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $merged->filename . '"'
            ]
        );
    }

    /**
     * Lister tous les PDFs fusionnés
     */
    public function listMergedCertificates(Request $request)
    {
        $query = MergedHonorRollPDF::with(['trimester', 'section', 'level', 'schoolClass', 'classSeries'])
            ->orderBy('created_at', 'desc');

        // Filtrage optionnel
        if ($request->has('trimester_id')) {
            $query->where('trimester_id', $request->trimester_id);
        }

        if ($request->has('series_id')) {
            $query->where('series_id', $request->series_id);
        }

        $mergedPdfs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $mergedPdfs
        ]);
    }
}
