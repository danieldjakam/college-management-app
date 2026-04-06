<?php

namespace App\Http\Controllers;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class GradeTrackingController extends Controller
{
    /**
     * Get grade completion status for all teachers
     */
    public function index(Request $request)
    {
        try {
            // Resolve school year
            $schoolYear = SchoolYear::where('is_current', true)->first()
                ?? SchoolYear::where('is_active', true)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active'
                ], 404);
            }

            // Resolve sequence
            $sequence = null;
            if ($request->sequence_id) {
                $sequence = Sequence::find($request->sequence_id);
            } else {
                $sequence = Sequence::getCurrentSequence();
            }

            if (!$sequence) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune séquence active trouvée'
                ], 404);
            }

            $trimesterId = $sequence->trimester_id;

            // Get all sequences for filter dropdown
            $sequences = Sequence::orderBy('number')->get(['id', 'name', 'number', 'is_current', 'is_active', 'trimester_id']);

            // Fetch all active assignments for this school year
            $assignments = TeacherAssignment::where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->with([
                    'teacher:id,first_name,last_name,phone_number,email',
                    'classSeriesSubject.subject:id,name',
                    'classSeriesSubject.classSeries:id,name,class_id',
                ])
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'teachers' => [],
                        'sequences' => $sequences,
                        'current_sequence' => $sequence,
                        'stats' => ['total' => 0, 'complete' => 0, 'partial' => 0, 'not_started' => 0],
                    ]
                ]);
            }

            // Get unique class_series_ids to count students per class
            $classSeriesIds = $assignments->pluck('classSeriesSubject.class_series_id')->unique()->filter();
            $studentCounts = Student::where('school_year_id', $schoolYear->id)
                ->where('is_active', true)
                ->whereIn('class_series_id', $classSeriesIds)
                ->groupBy('class_series_id')
                ->select('class_series_id', DB::raw('count(*) as count'))
                ->pluck('count', 'class_series_id');

            // Get all grade counts in one batch query
            $cssIds = $assignments->pluck('class_series_subject_id')->unique();
            $gradeCounts = Grade::whereIn('class_series_subject_id', $cssIds)
                ->where('sequence_id', $sequence->id)
                ->where('trimester_id', $trimesterId)
                ->whereNotNull('score')
                ->groupBy('class_series_subject_id')
                ->select('class_series_subject_id', DB::raw('count(distinct student_id) as count'))
                ->pluck('count', 'class_series_subject_id');

            // Group by teacher
            $grouped = $assignments->groupBy('teacher_id');

            $teachers = [];
            $statsComplete = 0;
            $statsPartial = 0;
            $statsNotStarted = 0;

            foreach ($grouped as $teacherId => $teacherAssignments) {
                $teacher = $teacherAssignments->first()->teacher;
                if (!$teacher) continue;

                $assignmentsList = [];
                $teacherComplete = 0;
                $teacherPartial = 0;
                $teacherNotStarted = 0;

                foreach ($teacherAssignments as $assignment) {
                    $css = $assignment->classSeriesSubject;
                    if (!$css || !$css->subject) continue;

                    $classSeriesId = $css->class_series_id;
                    $totalStudents = $studentCounts[$classSeriesId] ?? 0;
                    $gradesEntered = $gradeCounts[$css->id] ?? 0;

                    if ($totalStudents > 0 && $gradesEntered >= $totalStudents) {
                        $status = 'complete';
                        $teacherComplete++;
                    } elseif ($gradesEntered > 0) {
                        $status = 'partial';
                        $teacherPartial++;
                    } else {
                        $status = 'not_started';
                        $teacherNotStarted++;
                    }

                    $assignmentsList[] = [
                        'class_name' => $css->classSeries->name ?? 'N/A',
                        'subject_name' => $css->subject->name ?? 'N/A',
                        'total_students' => $totalStudents,
                        'grades_entered' => $gradesEntered,
                        'status' => $status,
                    ];
                }

                // Sort assignments by class then subject
                usort($assignmentsList, function ($a, $b) {
                    $cmp = strcmp($a['class_name'], $b['class_name']);
                    return $cmp !== 0 ? $cmp : strcmp($a['subject_name'], $b['subject_name']);
                });

                $totalAssignments = count($assignmentsList);
                $teacherStatus = 'not_started';
                if ($teacherComplete === $totalAssignments && $totalAssignments > 0) {
                    $teacherStatus = 'complete';
                    $statsComplete++;
                } elseif ($teacherComplete > 0 || $teacherPartial > 0) {
                    $teacherStatus = 'partial';
                    $statsPartial++;
                } else {
                    $statsNotStarted++;
                }

                $teachers[] = [
                    'teacher_id' => $teacherId,
                    'full_name' => trim($teacher->last_name . ' ' . $teacher->first_name),
                    'phone_number' => $teacher->phone_number,
                    'email' => $teacher->email,
                    'assignments' => $assignmentsList,
                    'summary' => [
                        'total' => $totalAssignments,
                        'complete' => $teacherComplete,
                        'partial' => $teacherPartial,
                        'not_started' => $teacherNotStarted,
                    ],
                    'status' => $teacherStatus,
                ];
            }

            // Sort teachers by name
            usort($teachers, function ($a, $b) {
                return strcmp($a['full_name'], $b['full_name']);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'teachers' => $teachers,
                    'sequences' => $sequences,
                    'current_sequence' => $sequence,
                    'stats' => [
                        'total' => count($teachers),
                        'complete' => $statsComplete,
                        'partial' => $statsPartial,
                        'not_started' => $statsNotStarted,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur suivi saisie notes', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download PDF report of grade completion status
     */
    public function downloadPDF(Request $request)
    {
        try {
            // Reuse the same logic as index
            $response = $this->index($request);
            $responseData = json_decode($response->getContent(), true);

            if (!$responseData['success']) {
                return $response;
            }

            $data = $responseData['data'];
            $schoolSettings = DB::table('school_settings')->first();

            $pdfData = [
                'teachers' => $data['teachers'],
                'stats' => $data['stats'],
                'sequence_name' => $data['current_sequence']['name'] ?? 'N/A',
                'school_name' => $schoolSettings->school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA',
                'date_generation' => now()->format('d/m/Y H:i'),
                'filter_status' => $request->filter_status ?? 'all',
            ];

            // Filter by status if requested
            if ($request->filter_status && $request->filter_status !== 'all') {
                $pdfData['teachers'] = array_values(array_filter($pdfData['teachers'], function ($t) use ($request) {
                    return $t['status'] === $request->filter_status;
                }));
            }

            $pdf = Pdf::loadView('pdf.grade-tracking', $pdfData);
            $pdf->setPaper('A4', 'landscape');

            $fileName = 'Suivi_Saisie_Notes_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            \Log::error('Erreur génération PDF suivi notes', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
