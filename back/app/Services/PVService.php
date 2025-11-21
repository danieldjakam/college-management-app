<?php

namespace App\Services;

use App\Models\ClassSeries;
use App\Models\Student;
use App\Models\Grade;
use App\Models\ClassSeriesSubject;
use App\Models\Evaluation;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\MainTeacher;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PVService
{
    /**
     * Générer le PV pour une série de classe et une période d'évaluation (séquence + trimestre)
     */
    public function generatePVByPeriod($classSeriesId, $sequenceId, $trimesterId)
    {
        try {
            Log::info("🎯 Génération PV pour class_series_id: {$classSeriesId}, sequence_id: {$sequenceId}, trimester_id: {$trimesterId}");

            // Récupérer les informations de base
            $classSeries = ClassSeries::with(['schoolClass.level'])->findOrFail($classSeriesId);
            $sequence = Sequence::findOrFail($sequenceId);
            $trimester = Trimester::findOrFail($trimesterId);
            $schoolYear = SchoolYear::where('is_current', true)->firstOrFail();

            // Créer un objet "evaluation" virtuel pour la compatibilité
            $evaluationData = (object) [
                'sequence' => $sequence,
                'trimester' => $trimester,
                'schoolYear' => $schoolYear,
                'school_year_id' => $schoolYear->id
            ];

            // Récupérer le professeur principal
            $mainTeacher = MainTeacher::where('class_series_id', $classSeriesId)
                ->where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->with('teacher')
                ->first();

            $mainTeacherName = $mainTeacher ? $mainTeacher->teacher->first_name . ' ' . $mainTeacher->teacher->last_name : 'Non désigné';

            // Récupérer les matières de cette série
            $seriesSubjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->with('subject')
                ->orderBy('subject_id')
                ->get();

            // Récupérer tous les élèves de cette série
            $students = Student::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->get();

            // Calculer les résultats de chaque élève
            $studentsResults = [];
            foreach ($students as $student) {
                $result = $this->calculateStudentResultsByPeriod($student, $seriesSubjects, $sequenceId, $trimesterId, $schoolYear->id);
                $studentsResults[] = $result;
            }

            // Trier par moyenne décroissante
            usort($studentsResults, function ($a, $b) {
                return $b['average'] <=> $a['average'];
            });

            // Attribuer les rangs
            foreach ($studentsResults as $index => &$result) {
                $result['rank'] = $index + 1;
            }

            // Calculer les statistiques
            $statistics = $this->calculateStatistics($studentsResults);

            // Générer le HTML
            $html = $this->generateHTML($classSeries, $evaluationData, $seriesSubjects, $studentsResults, $statistics, $mainTeacherName);

            // Générer le PDF
            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape'); // Mode paysage pour avoir plus d'espace

            Log::info("✅ PV généré avec succès");

            return $pdf;

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération PV: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Générer le HTML de prévisualisation pour une période
     */
    public function generateHTMLByPeriod($classSeriesId, $sequenceId, $trimesterId)
    {
        try {
            // Récupérer les informations de base
            $classSeries = ClassSeries::with(['schoolClass.level'])->findOrFail($classSeriesId);
            $sequence = Sequence::findOrFail($sequenceId);
            $trimester = Trimester::findOrFail($trimesterId);
            $schoolYear = SchoolYear::where('is_current', true)->firstOrFail();

            // Créer un objet "evaluation" virtuel
            $evaluationData = (object) [
                'sequence' => $sequence,
                'trimester' => $trimester,
                'schoolYear' => $schoolYear,
                'school_year_id' => $schoolYear->id
            ];

            // Récupérer le professeur principal
            $mainTeacher = MainTeacher::where('class_series_id', $classSeriesId)
                ->where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->with('teacher')
                ->first();

            $mainTeacherName = $mainTeacher ? $mainTeacher->teacher->first_name . ' ' . $mainTeacher->teacher->last_name : 'Non désigné';

            // Récupérer les matières
            $seriesSubjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->with('subject')
                ->orderBy('subject_id')
                ->get();

            // Récupérer les élèves
            $students = Student::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->get();

            // Calculer les résultats
            $studentsResults = [];
            foreach ($students as $student) {
                $result = $this->calculateStudentResultsByPeriod($student, $seriesSubjects, $sequenceId, $trimesterId, $schoolYear->id);
                $studentsResults[] = $result;
            }

            // Trier et attribuer rangs
            usort($studentsResults, function ($a, $b) {
                return $b['average'] <=> $a['average'];
            });

            foreach ($studentsResults as $index => &$result) {
                $result['rank'] = $index + 1;
            }

            // Statistiques
            $statistics = $this->calculateStatistics($studentsResults);

            // Générer et retourner le HTML
            return $this->generateHTML($classSeries, $evaluationData, $seriesSubjects, $studentsResults, $statistics, $mainTeacherName);

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération HTML: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ANCIENNE MÉTHODE - Garder pour compatibilité mais déprécier
     * @deprecated Use generatePVByPeriod() instead
     */
    public function generatePV($classSeriesId, $evaluationId)
    {
        try {
            Log::info("🎯 Génération PV pour class_series_id: {$classSeriesId}, evaluation_id: {$evaluationId}");

            // Récupérer les informations de base
            $classSeries = ClassSeries::with(['schoolClass.level'])->findOrFail($classSeriesId);
            $evaluation = Evaluation::with(['sequence', 'trimester', 'schoolYear'])->findOrFail($evaluationId);

            // Récupérer le professeur principal
            $mainTeacher = MainTeacher::where('class_series_id', $classSeriesId)
                ->where('school_year_id', $evaluation->school_year_id)
                ->where('is_active', true)
                ->with('teacher')
                ->first();

            $mainTeacherName = $mainTeacher ? $mainTeacher->teacher->first_name . ' ' . $mainTeacher->teacher->last_name : 'Non désigné';

            // Récupérer les matières de cette série
            $seriesSubjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->with('subject')
                ->orderBy('subject_id')
                ->get();

            // Récupérer tous les élèves de cette série
            $students = Student::where('class_series_id', $classSeriesId)
                ->where('is_active', true)
                ->get();

            // Calculer les résultats de chaque élève
            $studentsResults = [];
            foreach ($students as $student) {
                $result = $this->calculateStudentResults($student, $seriesSubjects, $evaluation);
                $studentsResults[] = $result;
            }

            // Trier par moyenne décroissante
            usort($studentsResults, function ($a, $b) {
                return $b['average'] <=> $a['average'];
            });

            // Attribuer les rangs
            foreach ($studentsResults as $index => &$result) {
                $result['rank'] = $index + 1;
            }

            // Calculer les statistiques
            $statistics = $this->calculateStatistics($studentsResults);

            // Générer le HTML
            $html = $this->generateHTML($classSeries, $evaluation, $seriesSubjects, $studentsResults, $statistics, $mainTeacherName);

            // Générer le PDF
            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape'); // Mode paysage pour avoir plus d'espace

            Log::info("✅ PV généré avec succès");

            return $pdf;

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération PV: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculer les résultats d'un élève pour une période donnée
     */
    private function calculateStudentResultsByPeriod($student, $seriesSubjects, $sequenceId, $trimesterId, $schoolYearId)
    {
        $totalPoints = 0;
        $totalCoef = 0;
        $grades = [];

        // 🔧 FIX: Calculer d'abord le total de TOUS les coefficients
        $allCoefficients = 0;
        foreach ($seriesSubjects as $seriesSubject) {
            $allCoefficients += (float)$seriesSubject->coefficient;
        }

        foreach ($seriesSubjects as $seriesSubject) {
            // Chercher la note pour cette matière dans cette période
            $grade = Grade::where('student_id', $student->id)
                ->where('sequence_id', $sequenceId)
                ->where('trimester_id', $trimesterId)
                ->where('school_year_id', $schoolYearId)
                ->where('class_series_subject_id', $seriesSubject->id)
                ->first();

            if ($grade && !$grade->is_absent) {
                $scoreOn20 = $grade->getScoreOn20();
                $grades[$seriesSubject->id] = $scoreOn20;
                $totalPoints += (float)$scoreOn20 * (float)$seriesSubject->coefficient;
                $totalCoef += (float)$seriesSubject->coefficient;
            } else {
                $grades[$seriesSubject->id] = null; // Absent ou pas de note (= 0)
                // 🔧 FIX: Les matières sans notes comptent comme 0 (0 * coef = 0)
            }
        }

        // 🔧 FIX: Compter TOUTES les matières (absence = 0)
        $average = $allCoefficients > 0 ? round($totalPoints / $allCoefficients, 2) : 0;

        return [
            'student' => $student,
            'grades' => $grades,
            'total_points' => round($totalPoints, 2),
            'average' => $average,
            'mention' => $this->getMention($average),
            'passed' => $average >= 10
        ];
    }

    /**
     * Calculer les résultats d'un élève (ANCIENNE MÉTHODE - pour compatibilité)
     * @deprecated Use calculateStudentResultsByPeriod() instead
     */
    private function calculateStudentResults($student, $seriesSubjects, $evaluation)
    {
        $totalPoints = 0;
        $totalCoef = 0;
        $grades = [];

        // 🔧 FIX: Calculer d'abord le total de TOUS les coefficients
        $allCoefficients = 0;
        foreach ($seriesSubjects as $seriesSubject) {
            $allCoefficients += (float)$seriesSubject->coefficient;
        }

        foreach ($seriesSubjects as $seriesSubject) {
            $grade = Grade::where('student_id', $student->id)
                ->where('evaluation_id', $evaluation->id)
                ->where('class_series_subject_id', $seriesSubject->id)
                ->first();

            if ($grade && !$grade->is_absent) {
                $scoreOn20 = $grade->getScoreOn20();
                $grades[$seriesSubject->id] = $scoreOn20;
                $totalPoints += (float)$scoreOn20 * (float)$seriesSubject->coefficient;
                $totalCoef += (float)$seriesSubject->coefficient;
            } else {
                $grades[$seriesSubject->id] = null; // Absent ou pas de note (= 0)
                // 🔧 FIX: Les matières sans notes comptent comme 0 (0 * coef = 0)
            }
        }

        // 🔧 FIX: Compter TOUTES les matières (absence = 0)
        $average = $allCoefficients > 0 ? round($totalPoints / $allCoefficients, 2) : 0;

        return [
            'student' => $student,
            'grades' => $grades,
            'total_points' => round($totalPoints, 2),
            'average' => $average,
            'mention' => $this->getMention($average),
            'passed' => $average >= 10
        ];
    }

    /**
     * Obtenir la mention selon la moyenne
     */
    private function getMention($average)
    {
        if ($average >= 16) return 'Excellent';
        if ($average >= 14) return 'Bien';
        if ($average >= 12) return 'Assez Bien';
        if ($average >= 10) return 'Passable';
        return 'Échec';
    }

    /**
     * Calculer les statistiques globales
     */
    private function calculateStatistics($studentsResults)
    {
        $total = count($studentsResults);
        $passed = array_filter($studentsResults, fn($r) => $r['passed']);
        $failed = array_filter($studentsResults, fn($r) => !$r['passed']);

        $averages = array_column($studentsResults, 'average');
        $classAverage = $total > 0 ? round(array_sum($averages) / $total, 2) : 0;

        // Répartition par mention
        $mentions = [
            'excellent' => 0,
            'bien' => 0,
            'assez_bien' => 0,
            'passable' => 0,
            'echec' => 0
        ];

        foreach ($studentsResults as $result) {
            switch ($result['mention']) {
                case 'Excellent': $mentions['excellent']++; break;
                case 'Bien': $mentions['bien']++; break;
                case 'Assez Bien': $mentions['assez_bien']++; break;
                case 'Passable': $mentions['passable']++; break;
                case 'Échec': $mentions['echec']++; break;
            }
        }

        return [
            'total_students' => $total,
            'passed_count' => count($passed),
            'failed_count' => count($failed),
            'success_rate' => $total > 0 ? round((count($passed) / $total) * 100, 2) : 0,
            'class_average' => $classAverage,
            'highest_average' => $total > 0 ? max($averages) : 0,
            'lowest_average' => $total > 0 ? min($averages) : 0,
            'mention_excellent' => $mentions['excellent'],
            'mention_bien' => $mentions['bien'],
            'mention_assez_bien' => $mentions['assez_bien'],
            'mention_passable' => $mentions['passable'],
            'mention_echec' => $mentions['echec']
        ];
    }

    /**
     * Générer le HTML du PV
     */
    private function generateHTML($classSeries, $evaluation, $seriesSubjects, $studentsResults, $statistics, $mainTeacherName)
    {
        // Déterminer le nombre de matières
        $subjectsCount = $seriesSubjects->count();

        // Déterminer la classe CSS adaptative
        $layoutClass = $this->getLayoutClass($subjectsCount);

        // Charger le template approprié
        if ($subjectsCount > 17) {
            // Pour les grandes classes (>17 matières), utiliser template 2 pages
            $template = file_get_contents(resource_path('views/pv/pv_template_2pages.html'));
        } else {
            // Template standard pour ≤17 matières
            $template = file_get_contents(resource_path('views/pv/pv_template.html'));
        }

        // Logo en base64 depuis les paramètres de l'école
        $logoBase64 = '';
        $schoolSettings = SchoolSetting::first();

        if ($schoolSettings && $schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:image/png;base64,' . $logoData;
            }
        }

        // Fallback sur le logo par défaut si pas de logo d'école
        if (empty($logoBase64)) {
            $defaultLogoPath = public_path('assets/logo.png');
            if (file_exists($defaultLogoPath)) {
                $logoData = base64_encode(file_get_contents($defaultLogoPath));
                $logoBase64 = 'data:image/png;base64,' . $logoData;
            }
        }

        // Type d'évaluation
        $evaluationType = '';
        if ($evaluation->sequence) {
            $evaluationType = "Séquence {$evaluation->sequence->number} - Trimestre {$evaluation->trimester->number}";
        } else {
            $evaluationType = "Composition - Trimestre {$evaluation->trimester->number}";
        }

        // Générer les en-têtes de matières
        $subjectsHeaders = '';
        $subjectsCoefHeaders = '';

        // Si mode compact (13-17 matières), utiliser en-têtes diagonaux
        $isCompact = ($subjectsCount > 12 && $subjectsCount <= 17);

        foreach ($seriesSubjects as $seriesSubject) {
            $subjectName = $seriesSubject->subject->name;
            $coef = $seriesSubject->coefficient;

            if ($isCompact) {
                // En-têtes diagonaux pour mode compact
                $subjectsHeaders .= "<th class=\"subject-header\" style=\"width: 25px;\"><div>{$subjectName} ({$coef})</div></th>";
            } else {
                // En-têtes normaux
                $subjectsHeaders .= "<th style=\"width: 35px;\">{$subjectName}<br/>({$coef})</th>";
            }
            $subjectsCoefHeaders .= "<th>{$coef}</th>";
        }

        // Générer les lignes d'élèves
        $studentsRows = '';
        foreach ($studentsResults as $result) {
            $student = $result['student'];
            $rank = $result['rank'];
            $average = number_format((float)$result['average'], 2);

            // Classes CSS pour le rang et le statut
            $rankClass = '';
            if ($rank == 1) $rankClass = 'rank-1';
            elseif ($rank == 2) $rankClass = 'rank-2';
            elseif ($rank == 3) $rankClass = 'rank-3';

            $passedClass = $result['passed'] ? 'passed' : 'failed';

            $mentionClass = 'mention-' . strtolower(str_replace(' ', '-', $result['mention']));

            $rankSuffix = $rank == 1 ? 'er' : 'ème';

            $studentsRows .= "<tr class=\"{$rankClass} {$passedClass}\">";
            $studentsRows .= "<td>{$rank}</td>";
            $studentsRows .= "<td class=\"student-name\">{$student->last_name} {$student->first_name}</td>";
            $studentsRows .= "<td>" . strtoupper(substr($student->gender, 0, 1)) . "</td>";

            // Notes par matière
            foreach ($seriesSubjects as $seriesSubject) {
                $grade = $result['grades'][$seriesSubject->id] ?? null;
                $displayGrade = $grade !== null ? number_format((float)$grade, 2) : '/';
                $studentsRows .= "<td>{$displayGrade}</td>";
            }

            $studentsRows .= "<td>{$result['total_points']}</td>";
            $studentsRows .= "<td class=\"{$mentionClass}\">{$average}</td>";
            $studentsRows .= "<td>{$rank}{$rankSuffix}</td>";
            $studentsRows .= "<td class=\"{$mentionClass}\">{$result['mention']}</td>";
            $studentsRows .= "</tr>";
        }

        // Observation automatique
        $observation = '';
        if ($statistics['success_rate'] >= 80) {
            $observation = "Excellent niveau général de la classe. Félicitations !";
        } elseif ($statistics['success_rate'] >= 60) {
            $observation = "Bon niveau général. Encourager les élèves en difficulté.";
        } else {
            $observation = "Taux de réussite insuffisant. Renforcement pédagogique nécessaire.";
        }

        // Remplacer les placeholders
        $replacements = [
            '{{logo_base64}}' => $logoBase64,
            '{{class_series_name}}' => $classSeries->name,
            '{{level_name}}' => $classSeries->schoolClass->level->name ?? '',
            '{{school_year}}' => $evaluation->schoolYear->name ?? '',
            '{{evaluation_type}}' => $evaluationType,
            '{{total_students}}' => $statistics['total_students'],
            '{{main_teacher}}' => $mainTeacherName,
            '{{current_date}}' => date('d/m/Y'),
            '{{subjects_headers}}' => $subjectsHeaders,
            '{{subjects_coef_headers}}' => $subjectsCoefHeaders,
            '{{students_rows}}' => $studentsRows,
            '{{passed_count}}' => $statistics['passed_count'],
            '{{failed_count}}' => $statistics['failed_count'],
            '{{success_rate}}' => $statistics['success_rate'],
            '{{class_average}}' => number_format((float)$statistics['class_average'], 2),
            '{{highest_average}}' => number_format((float)$statistics['highest_average'], 2),
            '{{lowest_average}}' => number_format((float)$statistics['lowest_average'], 2),
            '{{mention_excellent}}' => $statistics['mention_excellent'],
            '{{mention_bien}}' => $statistics['mention_bien'],
            '{{mention_assez_bien}}' => $statistics['mention_assez_bien'],
            '{{mention_passable}}' => $statistics['mention_passable'],
            '{{mention_echec}}' => $statistics['mention_echec'],
            '{{observation}}' => $observation,
            '{{layout_class}}' => $layoutClass,
            '{{subjects_count}}' => $subjectsCount
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Déterminer la classe CSS selon le nombre de matières
     */
    private function getLayoutClass($subjectsCount)
    {
        if ($subjectsCount <= 12) {
            return 'layout-normal'; // Format actuel
        } elseif ($subjectsCount <= 17) {
            return 'layout-compact'; // Police réduite + en-têtes diagonaux
        } else {
            return 'layout-2pages'; // 2 pages
        }
    }
}
