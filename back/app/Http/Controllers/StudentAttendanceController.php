<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Section;
use App\Models\Level;
use App\Models\ClassSeries;
use App\Models\SchoolYear;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentAttendanceController extends Controller
{
    public function getStudentAttendance(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'section_id' => 'nullable|integer|exists:sections,id',
            'class_id' => 'nullable|integer|exists:levels,id',
            'series_id' => 'nullable|integer|exists:class_series,id',
        ]);

        $date = $request->input('date');
        $sectionId = $request->input('section_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        // Récupérer l'année scolaire active
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        if (!$currentSchoolYear) {
            return response()->json(['error' => 'Aucune année scolaire active trouvée'], 400);
        }

        try {
            // Construction de la requête de base pour récupérer tous les étudiants selon les filtres
            $studentsQuery = Student::select([
                'students.id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.name', // champ de compatibilité
                'students.subname', // champ de compatibilité  
                'levels.name as class_name',
                'class_series.name as series_name',
                'sections.name as section_name'
            ])
            ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
            ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
            ->join('levels', 'school_classes.level_id', '=', 'levels.id')
            ->join('sections', 'levels.section_id', '=', 'sections.id')
            ->where('students.school_year_id', $currentSchoolYear->id)
            ->where('students.is_active', true); // Seulement les étudiants actifs

            // Appliquer les filtres
            if ($seriesId) {
                $studentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $studentsQuery->where('school_classes.level_id', $classId);
            } elseif ($sectionId) {
                $studentsQuery->where('levels.section_id', $sectionId);
            }

            $students = $studentsQuery->get();

            // Récupérer les présences pour la date donnée
            $attendances = Attendance::select([
                'student_id',
                'scanned_at',
                'is_present',
                'event_type'
            ])
            ->whereDate('attendance_date', $date)
            ->where('school_year_id', $currentSchoolYear->id)
            ->get()
            ->groupBy('student_id');

            // Combiner les données
            $result = $students->map(function ($student) use ($attendances) {
                $studentAttendance = $attendances->get($student->id);
                
                // Déterminer le statut de présence
                $isPresent = false;
                $arrivalTime = null;
                $exitTime = null;
                
                if ($studentAttendance) {
                    // Chercher la première entrée du jour
                    $entryRecord = $studentAttendance->where('event_type', 'entry')->first();
                    if ($entryRecord) {
                        $isPresent = true;
                        $arrivalTime = $entryRecord->scanned_at;
                    }
                    
                    // Chercher la première sortie du jour
                    $exitRecord = $studentAttendance->where('event_type', 'exit')->first();
                    if ($exitRecord) {
                        $exitTime = $exitRecord->scanned_at;
                    }
                }

                return [
                    'id' => $student->id,
                    'matricule' => $student->student_number,
                    'nom' => $student->last_name ?: $student->subname,
                    'prenom' => $student->first_name ?: $student->name,
                    'class_name' => $student->class_name,
                    'series_name' => $student->series_name,
                    'section_name' => $student->section_name,
                    'is_present' => $isPresent,
                    'arrival_time' => $arrivalTime,
                    'exit_time' => $exitTime
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Données de présence récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des données de présence',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceStats(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'section_id' => 'nullable|integer|exists:sections,id',
            'class_id' => 'nullable|integer|exists:levels,id',
            'series_id' => 'nullable|integer|exists:class_series,id',
        ]);

        $date = $request->input('date');
        $sectionId = $request->input('section_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        // Récupérer l'année scolaire active
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        if (!$currentSchoolYear) {
            return response()->json(['error' => 'Aucune année scolaire active trouvée'], 400);
        }

        try {
            // Compter le total d'étudiants selon les filtres
            $totalStudentsQuery = Student::join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->where('students.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            if ($seriesId) {
                $totalStudentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $totalStudentsQuery->where('school_classes.level_id', $classId);
            } elseif ($sectionId) {
                $totalStudentsQuery->where('levels.section_id', $sectionId);
            }

            $totalStudents = $totalStudentsQuery->count();

            // Compter les présents (étudiants qui ont scanné en entrée)
            $presentStudentsQuery = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
                ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
                ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                ->join('levels', 'school_classes.level_id', '=', 'levels.id')
                ->join('sections', 'levels.section_id', '=', 'sections.id')
                ->whereDate('attendances.attendance_date', $date)
                ->where('attendances.event_type', 'entry')
                ->where('attendances.school_year_id', $currentSchoolYear->id)
                ->where('students.is_active', true);

            if ($seriesId) {
                $presentStudentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $presentStudentsQuery->where('school_classes.level_id', $classId);
            } elseif ($sectionId) {
                $presentStudentsQuery->where('levels.section_id', $sectionId);
            }

            $presentStudents = $presentStudentsQuery->distinct('students.id')->count();
            $absentStudents = $totalStudents - $presentStudents;
            $attendanceRate = $totalStudents > 0 ? round(($presentStudents / $totalStudents) * 100, 1) : 0;

            return response()->json([
                'total' => $totalStudents,
                'present' => $presentStudents,
                'absent' => $absentStudents,
                'attendance_rate' => $attendanceRate,
                'date' => $date
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du calcul des statistiques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportStudentAttendancePDF(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'section_id' => 'nullable|integer|exists:sections,id',
            'class_id' => 'nullable|integer|exists:levels,id',
            'series_id' => 'nullable|integer|exists:class_series,id',
        ]);

        $date = $request->input('date');
        $sectionId = $request->input('section_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        // Récupérer l'année scolaire active
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        if (!$currentSchoolYear) {
            return response()->json(['error' => 'Aucune année scolaire active trouvée'], 400);
        }

        try {
            // Récupérer les données de présence (même logique que getStudentAttendance)
            $studentsQuery = Student::select([
                'students.id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.name',
                'students.subname',
                'levels.name as class_name',
                'class_series.name as series_name',
                'sections.name as section_name'
            ])
            ->join('class_series', 'students.class_series_id', '=', 'class_series.id')
            ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
            ->join('levels', 'school_classes.level_id', '=', 'levels.id')
            ->join('sections', 'levels.section_id', '=', 'sections.id')
            ->where('students.school_year_id', $currentSchoolYear->id)
            ->where('students.is_active', true);

            // Appliquer les filtres
            if ($seriesId) {
                $studentsQuery->where('students.class_series_id', $seriesId);
            } elseif ($classId) {
                $studentsQuery->where('school_classes.level_id', $classId);
            } elseif ($sectionId) {
                $studentsQuery->where('levels.section_id', $sectionId);
            }

            $students = $studentsQuery->orderBy('class_series.name')
                                   ->orderBy('students.last_name')
                                   ->orderBy('students.first_name')
                                   ->get();

            // Récupérer les présences pour la date donnée
            $attendances = Attendance::select([
                'student_id',
                'scanned_at',
                'is_present',
                'event_type'
            ])
            ->whereDate('attendance_date', $date)
            ->where('school_year_id', $currentSchoolYear->id)
            ->get()
            ->groupBy('student_id');

            // Combiner les données
            $attendanceData = $students->map(function ($student) use ($attendances) {
                $studentAttendance = $attendances->get($student->id);
                
                $isPresent = false;
                $arrivalTime = null;
                $exitTime = null;
                
                if ($studentAttendance) {
                    $entryRecord = $studentAttendance->where('event_type', 'entry')->first();
                    if ($entryRecord) {
                        $isPresent = true;
                        $arrivalTime = $entryRecord->scanned_at;
                    }
                    
                    $exitRecord = $studentAttendance->where('event_type', 'exit')->first();
                    if ($exitRecord) {
                        $exitTime = $exitRecord->scanned_at;
                    }
                }

                return [
                    'id' => $student->id,
                    'matricule' => $student->student_number,
                    'nom' => $student->last_name ?: $student->subname,
                    'prenom' => $student->first_name ?: $student->name,
                    'class_name' => $student->class_name,
                    'series_name' => $student->series_name,
                    'section_name' => $student->section_name,
                    'is_present' => $isPresent,
                    'arrival_time' => $arrivalTime,
                    'exit_time' => $exitTime
                ];
            });

            // Calculer les statistiques
            $total = $attendanceData->count();
            $present = $attendanceData->where('is_present', true)->count();
            $absent = $total - $present;
            $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            // Déterminer le titre du filtre et le type
            $filterTitle = 'Toutes les classes';
            $filterType = 'Filtre';
            
            if ($seriesId) {
                $series = ClassSeries::find($seriesId);
                $filterTitle = $series ? $series->name : 'Série inconnue';
                $filterType = 'Série';
            } elseif ($classId) {
                $class = Level::find($classId);
                $filterTitle = $class ? $class->name : 'Classe inconnue';
                $filterType = 'Classe';
            } elseif ($sectionId) {
                $section = Section::find($sectionId);
                $filterTitle = $section ? $section->name : 'Section inconnue';
                $filterType = 'Section';
            }

            // Préparer les données pour la vue PDF
            $data = [
                'attendanceData' => $attendanceData,
                'date' => Carbon::parse($date)->locale('fr')->isoFormat('dddd, D MMMM YYYY'),
                'filterTitle' => $filterTitle,
                'filterType' => $filterType,
                'stats' => [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'attendance_rate' => $attendanceRate
                ],
                'schoolYear' => $currentSchoolYear->name,
                'generatedAt' => Carbon::now()->locale('fr')->isoFormat('dddd, D MMMM YYYY [à] HH:mm')
            ];

            // Générer le HTML puis le PDF
            $html = view('reports.student-attendance', $data)->render();
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'presences_eleves_' . $date . '.pdf';
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}