<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Subject;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\TeacherAssignment;
use App\Models\MainTeacher;
use App\Models\ClassSeriesSubject;
use App\Models\SchoolYear;
use App\Models\Need;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard principal pour l'administrateur
     */
    public function index()
    {
        try {
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            $currentDate = Carbon::now();

            // 1. STATISTIQUES GÉNÉRALES
            $generalStats = [
                'total_students' => Student::where('is_active', true)->count(),
                'total_teachers' => Teacher::where('is_active', true)->count(),
                'total_subjects' => Subject::where('is_active', true)->count(),
                'total_classes' => SchoolClass::where('is_active', true)->count(),
                'total_sections' => Section::where('is_active', true)->count(),
                'total_levels' => Level::where('is_active', true)->count(),
            ];

            // 2. STATISTIQUES ÉTUDIANTS
            $genderStats = Student::where('is_active', true)
                ->select('gender', DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->pluck('count', 'gender');

            $studentStats = [
                'total_male' => $genderStats['M'] ?? 0,
                'total_female' => $genderStats['F'] ?? 0,
                'by_gender' => $genderStats,
                'by_section' => Student::where('students.is_active', true)
                    ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                    ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                    ->join('sections', 'levels.section_id', '=', 'sections.id')
                    ->select('sections.name', DB::raw('count(*) as count'))
                    ->groupBy('sections.name')
                    ->pluck('count', 'name'),
                'by_class' => Student::where('students.is_active', true)
                    ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                    ->select('school_classes.name', DB::raw('count(*) as count'))
                    ->groupBy('school_classes.name')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->pluck('count', 'name'),
                'new_students_this_year' => $currentSchoolYear ?
                    Student::where('is_active', true)
                        ->where('school_year_id', $currentSchoolYear->id)
                        ->count() : 0
            ];

            // 3. STATISTIQUES ENSEIGNANTS
            $teacherStats = [
                'total_assignments' => TeacherAssignment::where('is_active', true)->count(),
                'teachers_with_assignments' => TeacherAssignment::where('is_active', true)
                    ->distinct('teacher_id')->count(),
                'main_teachers' => MainTeacher::where('is_active', true)->count(),
                'by_qualification' => Teacher::where('is_active', true)
                    ->whereNotNull('qualification')
                    ->select('qualification', DB::raw('count(*) as count'))
                    ->groupBy('qualification')
                    ->pluck('count', 'qualification'),
                'classes_without_main_teacher' => ClassSeries::whereDoesntHave('mainTeacher', function($query) {
                    $query->where('is_active', true);
                })->where('is_active', true)->count()
            ];

            // 4. STATISTIQUES ACADÉMIQUES
            $currentSequence = Sequence::where('is_active', true)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            $currentTrimester = Trimester::where('is_active', true)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first();

            $academicStats = [
                'current_sequence' => $currentSequence ? [
                    'id' => $currentSequence->id,
                    'name' => $currentSequence->name,
                    'number' => $currentSequence->number,
                    'progress' => $this->calculateSequenceProgress($currentSequence)
                ] : null,
                'current_trimester' => $currentTrimester ? [
                    'id' => $currentTrimester->id,
                    'name' => $currentTrimester->name,
                    'number' => $currentTrimester->number,
                    'progress' => $this->calculateTrimesterProgress($currentTrimester)
                ] : null,
                'total_evaluations' => Evaluation::where('is_active', true)->count(),
                'evaluations_this_sequence' => $currentSequence ?
                    Evaluation::where('sequence_id', $currentSequence->id)
                        ->where('is_active', true)->count() : 0,
                'total_grades' => Grade::whereNotNull('score')->count(),
                'evaluation_types_distribution' => Evaluation::where('is_active', true)
                    ->select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
            ];

            // 5. STATISTIQUES DE CONFIGURATION
            $configStats = [
                'subjects_per_series' => ClassSeriesSubject::where('is_active', true)->count(),
                'active_subjects' => Subject::where('is_active', true)->count(),
                'configured_classes' => SchoolClass::whereHas('series', function($query) {
                    $query->whereHas('subjects');
                })->count(),
                'total_coefficients' => ClassSeriesSubject::where('is_active', true)
                    ->sum('coefficient')
            ];

            // 6. ACTIVITÉ RÉCENTE (7 derniers jours)
            $recentActivity = [
                'new_students' => Student::where('created_at', '>=', $currentDate->subDays(7))
                    ->count(),
                'new_teachers' => Teacher::where('created_at', '>=', $currentDate->subDays(7))
                    ->count(),
                'new_evaluations' => Evaluation::where('created_at', '>=', $currentDate->subDays(7))
                    ->count(),
                'new_grades' => Grade::where('created_at', '>=', $currentDate->subDays(7))
                    ->whereNotNull('score')->count()
            ];

            // 7. BESOINS/DEMANDES
            $needsStats = [
                'total_needs' => Need::count(),
                'pending_needs' => Need::where('status', 'pending')->count(),
                'approved_needs' => Need::where('status', 'approved')->count(),
                'rejected_needs' => Need::where('status', 'rejected')->count(),
                'recent_needs' => Need::where('created_at', '>=', $currentDate->subDays(7))
                    ->count()
            ];

            // 8. PRÉSENCES (si disponible)
            $attendanceStats = [];
            try {
                if (class_exists('App\Models\StudentAttendance')) {
                    // Vérifier que la table et les colonnes existent
                    $today = $currentDate->format('Y-m-d');
                    $attendanceStats = [
                        'students_present_today' => 0, // Désactivé temporairement
                        'students_absent_today' => 0,   // Désactivé temporairement
                    ];
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de présences pour le moment
                $attendanceStats = [];
            }

            // 9. ALERTES ET NOTIFICATIONS
            $alerts = $this->getSystemAlerts();

            return response()->json([
                'success' => true,
                'data' => [
                    'general_stats' => $generalStats,
                    'student_stats' => $studentStats,
                    'teacher_stats' => $teacherStats,
                    'academic_stats' => $academicStats,
                    'config_stats' => $configStats,
                    'recent_activity' => $recentActivity,
                    'needs_stats' => $needsStats,
                    'attendance_stats' => $attendanceStats,
                    'alerts' => $alerts,
                    'school_year' => $currentSchoolYear,
                    'generated_at' => $currentDate->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin Dashboard Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le progrès d'une séquence
     */
    private function calculateSequenceProgress($sequence)
    {
        $now = Carbon::now();
        $start = Carbon::parse($sequence->start_date);
        $end = Carbon::parse($sequence->end_date);

        if ($now->lt($start)) return 0;
        if ($now->gt($end)) return 100;

        $total = $end->diffInDays($start);
        $elapsed = $now->diffInDays($start);

        return round(($elapsed / $total) * 100, 1);
    }

    /**
     * Calculer le progrès d'un trimestre
     */
    private function calculateTrimesterProgress($trimester)
    {
        $now = Carbon::now();
        $start = Carbon::parse($trimester->start_date);
        $end = Carbon::parse($trimester->end_date);

        if ($now->lt($start)) return 0;
        if ($now->gt($end)) return 100;

        $total = $end->diffInDays($start);
        $elapsed = $now->diffInDays($start);

        return round(($elapsed / $total) * 100, 1);
    }

    /**
     * Générer les alertes système
     */
    private function getSystemAlerts()
    {
        $alerts = [];

        // Classes sans professeur principal
        $classesWithoutMainTeacher = ClassSeries::whereDoesntHave('mainTeacher', function($query) {
            $query->where('is_active', true);
        })->where('is_active', true)->count();

        if ($classesWithoutMainTeacher > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Classes sans professeur principal',
                'message' => "{$classesWithoutMainTeacher} classe(s) n'ont pas de professeur principal assigné",
                'count' => $classesWithoutMainTeacher
            ];
        }

        // Enseignants sans affectation
        $teachersWithoutAssignment = Teacher::where('is_active', true)
            ->whereDoesntHave('assignments', function($query) {
                $query->where('is_active', true);
            })->count();

        if ($teachersWithoutAssignment > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Enseignants sans affectation',
                'message' => "{$teachersWithoutAssignment} enseignant(s) n'ont pas de matière assignée",
                'count' => $teachersWithoutAssignment
            ];
        }

        // Besoins en attente
        $pendingNeeds = Need::where('status', 'pending')->count();
        if ($pendingNeeds > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Demandes en attente',
                'message' => "{$pendingNeeds} demande(s) en attente d'approbation",
                'count' => $pendingNeeds
            ];
        }

        // Séquence se terminant bientôt
        $currentSequence = Sequence::where('is_active', true)
            ->where('end_date', '>=', Carbon::now())
            ->where('end_date', '<=', Carbon::now()->addDays(7))
            ->first();

        if ($currentSequence) {
            $daysRemaining = Carbon::now()->diffInDays(Carbon::parse($currentSequence->end_date));
            $alerts[] = [
                'type' => 'info',
                'title' => 'Séquence se termine bientôt',
                'message' => "La {$currentSequence->name} se termine dans {$daysRemaining} jour(s)",
                'count' => $daysRemaining
            ];
        }

        return $alerts;
    }

    /**
     * Statistiques par période
     */
    public function getStatsByPeriod(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $type = $request->get('type', 'students'); // students, teachers, evaluations, grades

            $dates = $this->getDateRange($period);

            $stats = [];

            switch ($type) {
                case 'students':
                    $stats = Student::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetween('created_at', $dates)
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                    break;

                case 'teachers':
                    $stats = Teacher::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetween('created_at', $dates)
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                    break;

                case 'evaluations':
                    $stats = Evaluation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetween('created_at', $dates)
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                    break;

                case 'grades':
                    $stats = Grade::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereNotNull('score')
                        ->whereBetween('created_at', $dates)
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => $period,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par période',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les plages de dates selon la période
     */
    private function getDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'day':
                return [$now->subDays(7), $now];
            case 'week':
                return [$now->subWeeks(4), $now];
            case 'month':
                return [$now->subMonths(6), $now];
            case 'year':
                return [$now->subYears(2), $now];
            default:
                return [$now->subMonths(1), $now];
        }
    }
}