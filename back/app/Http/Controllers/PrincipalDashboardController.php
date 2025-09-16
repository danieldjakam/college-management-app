<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\TeacherAssignment;
use App\Models\MainTeacher;
use App\Models\SeriesSubject;
use App\Models\SchoolYear;
use App\Models\Need;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrincipalDashboardController extends Controller
{
    /**
     * Dashboard principal pour le directeur/principal
     */
    public function index()
    {
        try {
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();

            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 404);
            }

            // Statistiques générales (similaires à admin mais orientées gestion pédagogique)
            $generalStats = [
                'total_students' => Student::count(),
                'total_teachers' => Teacher::count(),
                'total_subjects' => Subject::where('is_active', true)->count(),
                'total_classes' => SchoolClass::where('is_active', true)->count(),
                'active_school_year' => $currentSchoolYear->name
            ];

            // Statistiques étudiants par section et niveau
            $studentStats = [
                'by_section' => Student::join('school_classes', 'students.class_series_id', '=', 'school_classes.id')
                    ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                    ->join('sections', 'levels.section_id', '=', 'sections.id')
                    ->select('sections.name', DB::raw('count(*) as count'))
                    ->groupBy('sections.name')
                    ->pluck('count', 'name')
                    ->toArray(),

                'by_level' => Student::join('school_classes', 'students.class_series_id', '=', 'school_classes.id')
                    ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                    ->select('levels.name', DB::raw('count(*) as count'))
                    ->groupBy('levels.name')
                    ->pluck('count', 'name')
                    ->toArray(),

                'by_class' => Student::join('school_classes', 'students.class_series_id', '=', 'school_classes.id')
                    ->select('school_classes.name', DB::raw('count(*) as count'))
                    ->groupBy('school_classes.name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->pluck('count', 'name')
                    ->toArray(),

                'by_gender' => [
                    'male' => Student::where('gender', 'Masculin')->count(),
                    'female' => Student::where('gender', 'Féminin')->count()
                ]
            ];

            // Statistiques enseignants (focus sur affectations et professeurs principaux)
            $teacherStats = [
                'total_assignments' => TeacherAssignment::where('is_active', true)
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->count(),
                'teachers_with_assignments' => TeacherAssignment::where('is_active', true)
                    ->where('school_year_id', $currentSchoolYear->id)
                    ->distinct('teacher_id')
                    ->count(),
                'main_teachers' => MainTeacher::where('school_year_id', $currentSchoolYear->id)
                    ->where('is_active', true)
                    ->count(),
                'classes_without_main_teacher' => SchoolClass::where('is_active', true)
                    ->whereNotIn('id', MainTeacher::where('school_year_id', $currentSchoolYear->id)
                        ->where('is_active', true)
                        ->pluck('school_class_id'))
                    ->count()
            ];

            // Statistiques académiques (évaluations et notes)
            $currentSequence = Sequence::where('school_year_id', $currentSchoolYear->id)
                ->where('is_active', true)
                ->first();

            $currentTrimester = $currentSequence ?
                Trimester::where('school_year_id', $currentSchoolYear->id)
                    ->where('is_active', true)
                    ->whereHas('sequences', function($q) use ($currentSequence) {
                        $q->where('sequences.id', $currentSequence->id);
                    })
                    ->first() : null;

            $academicStats = [
                'current_sequence' => $currentSequence ? [
                    'name' => $currentSequence->name,
                    'progress' => $this->calculateSequenceProgress($currentSequence)
                ] : null,
                'current_trimester' => $currentTrimester ? [
                    'name' => $currentTrimester->name,
                    'progress' => $this->calculateTrimesterProgress($currentTrimester)
                ] : null,
                'total_evaluations' => Evaluation::where('school_year_id', $currentSchoolYear->id)->count(),
                'evaluations_this_sequence' => $currentSequence ?
                    Evaluation::where('sequence_id', $currentSequence->id)->count() : 0,
                'total_grades' => Grade::whereHas('evaluation', function($q) use ($currentSchoolYear) {
                    $q->where('school_year_id', $currentSchoolYear->id);
                })->count(),
                'evaluation_types_distribution' => Evaluation::where('school_year_id', $currentSchoolYear->id)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),

                // Calcul du taux de réussite global
                'success_rate' => $this->calculateGlobalSuccessRate($currentSchoolYear->id)
            ];

            // Besoins et demandes (spécifique au rôle de principal)
            $needsStats = [
                'total_needs' => Need::count(),
                'pending_needs' => Need::where('status', 'pending')->count(),
                'approved_needs' => Need::where('status', 'approved')->count(),
                'rejected_needs' => Need::where('status', 'rejected')->count(),
                'recent_needs' => Need::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'recent_pending_needs' => Need::where('status', 'pending')->where('created_at', '>=', Carbon::now()->subDays(3))->count()
            ];

            // Activité récente (focus pédagogique)
            $recentActivity = [
                'new_students' => Student::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'new_teachers' => Teacher::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'new_evaluations' => Evaluation::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'new_grades' => Grade::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'new_needs' => Need::where('created_at', '>=', Carbon::now()->subDays(7))->count()
            ];

            // Alertes spécifiques au principal
            $alerts = [];

            // Classes sans professeur principal
            if ($teacherStats['classes_without_main_teacher'] > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Classes sans professeur principal',
                    'message' => "{$teacherStats['classes_without_main_teacher']} classe(s) n'ont pas de professeur principal assigné"
                ];
            }

            // Besoins récents en attente
            if ($needsStats['recent_pending_needs'] > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Besoins récents en attente',
                    'message' => "{$needsStats['recent_pending_needs']} besoin(s) soumis récemment sont en attente de traitement"
                ];
            }

            // Vérification évaluations pour la séquence courante
            if ($currentSequence && $academicStats['evaluations_this_sequence'] === 0) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Évaluations manquantes',
                    'message' => "Aucune évaluation n'a été créée pour la séquence courante ({$currentSequence->name})"
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'generated_at' => now()->toISOString(),
                    'school_year' => $currentSchoolYear,
                    'general_stats' => $generalStats,
                    'student_stats' => $studentStats,
                    'teacher_stats' => $teacherStats,
                    'academic_stats' => $academicStats,
                    'needs_stats' => $needsStats,
                    'recent_activity' => $recentActivity,
                    'alerts' => $alerts
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le progrès d'une séquence (basé sur les dates)
     */
    private function calculateSequenceProgress($sequence)
    {
        if (!$sequence->start_date || !$sequence->end_date) {
            return 0;
        }

        $start = Carbon::parse($sequence->start_date);
        $end = Carbon::parse($sequence->end_date);
        $now = Carbon::now();

        if ($now <= $start) {
            return 0;
        } elseif ($now >= $end) {
            return 100;
        }

        $total = $end->diffInDays($start);
        $elapsed = $now->diffInDays($start);

        return $total > 0 ? round(($elapsed / $total) * 100, 1) : 0;
    }

    /**
     * Calculer le progrès d'un trimestre
     */
    private function calculateTrimesterProgress($trimester)
    {
        if (!$trimester->start_date || !$trimester->end_date) {
            return 0;
        }

        $start = Carbon::parse($trimester->start_date);
        $end = Carbon::parse($trimester->end_date);
        $now = Carbon::now();

        if ($now <= $start) {
            return 0;
        } elseif ($now >= $end) {
            return 100;
        }

        $total = $end->diffInDays($start);
        $elapsed = $now->diffInDays($start);

        return $total > 0 ? round(($elapsed / $total) * 100, 1) : 0;
    }

    /**
     * Calculer le taux de réussite global
     */
    private function calculateGlobalSuccessRate($schoolYearId)
    {
        try {
            // Récupérer toutes les notes de l'année scolaire
            $totalGrades = Grade::whereHas('evaluation', function($q) use ($schoolYearId) {
                $q->where('school_year_id', $schoolYearId);
            })->count();

            if ($totalGrades === 0) {
                return 0;
            }

            // Compter les notes >= 10 (réussite)
            $successfulGrades = Grade::whereHas('evaluation', function($q) use ($schoolYearId) {
                $q->where('school_year_id', $schoolYearId);
            })->where('grade', '>=', 10)->count();

            return round(($successfulGrades / $totalGrades) * 100, 1);

        } catch (\Exception $e) {
            \Log::error('Erreur calcul taux de réussite: ' . $e->getMessage());
            return 0;
        }
    }
}