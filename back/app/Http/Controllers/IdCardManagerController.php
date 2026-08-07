<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Student;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IdCardManagerController extends Controller
{
    /**
     * Obtenir l'année scolaire de travail de l'utilisateur connecté
     */
    private function getUserWorkingYear()
    {
        $user = Auth::user();

        // Si l'utilisateur a une année de travail définie, l'utiliser
        if ($user && $user->working_school_year_id) {
            $workingYear = SchoolYear::find($user->working_school_year_id);
            if ($workingYear) {
                return $workingYear;
            }
        }

        // Sinon, utiliser l'année courante par défaut
        $workingYear = SchoolYear::where('is_current', true)->first();

        if (!$workingYear) {
            // Si aucune année courante, prendre la première année active
            $workingYear = SchoolYear::where('is_active', true)->first();
        }

        return $workingYear;
    }

    /**
     * Dashboard pour le gestionnaire de cartes d'identité
     */
    public function dashboard()
    {
        try {
            $workingYear = $this->getUserWorkingYear();

            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            // Statistiques globales
            $totalStudents = Student::where('is_active', true)
                ->where('school_year_id', $workingYear->id)
                ->count();

            $studentsWithPhoto = Student::where('is_active', true)
                ->where('school_year_id', $workingYear->id)
                ->whereNotNull('photo')
                ->where('photo', '!=', '')
                ->count();

            $studentsWithoutPhoto = $totalStudents - $studentsWithPhoto;

            $totalClasses = SchoolClass::where('is_active', true)->count();

            // Statistiques par classe
            $classesStat = SchoolClass::with(['series' => function($query) use ($workingYear) {
                $query->withCount(['students' => function($q) use ($workingYear) {
                    $q->where('is_active', true)
                      ->where('school_year_id', $workingYear->id);
                }]);
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function($class) use ($workingYear) {
                $totalStudents = $class->series->sum('students_count');
                $studentsWithPhoto = Student::whereIn('class_series_id', $class->series->pluck('id'))
                    ->where('is_active', true)
                    ->where('school_year_id', $workingYear->id)
                    ->whereNotNull('photo')
                    ->where('photo', '!=', '')
                    ->count();

                return [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'total_students' => $totalStudents,
                    'students_with_photo' => $studentsWithPhoto,
                    'students_without_photo' => $totalStudents - $studentsWithPhoto,
                    'percentage_with_photo' => $totalStudents > 0
                        ? round(($studentsWithPhoto / $totalStudents) * 100, 2)
                        : 0
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'global_stats' => [
                        'total_students' => $totalStudents,
                        'students_with_photo' => $studentsWithPhoto,
                        'students_without_photo' => $studentsWithoutPhoto,
                        'percentage_with_photo' => $totalStudents > 0
                            ? round(($studentsWithPhoto / $totalStudents) * 100, 2)
                            : 0,
                        'total_classes' => $totalClasses
                    ],
                    'classes_stats' => $classesStat,
                    'working_year' => $workingYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard gestionnaire cartes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }

    /**
     * Liste toutes les classes (lecture seule)
     */
    public function getClasses()
    {
        try {
            $workingYear = $this->getUserWorkingYear();

            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $classes = SchoolClass::with([
                'level.section',
                'series' => function ($query) use ($workingYear) {
                    $query->orderBy('name')
                          ->withCount(['students' => function ($q) use ($workingYear) {
                              $q->where('is_active', true)
                                ->where('school_year_id', $workingYear->id);
                          }]);
                }
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->withCount('series')
            ->get();

            // Calculer le nombre total d'élèves par classe
            foreach ($classes as $class) {
                $class->total_students = $class->series->sum('students_count');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'classes' => $classes,
                    'working_year' => $workingYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération classes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes'
            ], 500);
        }
    }

    /**
     * Récupérer tous les élèves d'une classe
     */
    public function getClassStudents($classId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();

            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $class = SchoolClass::with(['level', 'series'])->findOrFail($classId);

            // Récupérer tous les élèves de toutes les séries de cette classe
            $students = Student::with(['classSeries'])
                ->whereIn('class_series_id', $class->series->pluck('id'))
                ->where('is_active', true)
                ->where('school_year_id', $workingYear->id)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'matricule' => $student->matricule,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'full_name' => $student->first_name . ' ' . $student->last_name,
                        'date_of_birth' => $student->date_of_birth,
                        'gender' => $student->gender,
                        'photo' => $student->photo,
                        'has_photo' => !empty($student->photo),
                        'class_series' => $student->classSeries ? $student->classSeries->name : null,
                        'class_series_id' => $student->class_series_id
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => $class,
                    'students' => $students,
                    'total' => $students->count(),
                    'with_photo' => $students->where('has_photo', true)->count(),
                    'without_photo' => $students->where('has_photo', false)->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération élèves classe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves'
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'un élève
     */
    public function getStudent($studentId)
    {
        try {
            $student = Student::with(['classSeries.schoolClass', 'classSeries'])
                ->findOrFail($studentId);

            return response()->json([
                'success' => true,
                'data' => $student
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'élève'
            ], 500);
        }
    }

    /**
     * Mettre à jour UNIQUEMENT la photo d'un élève
     * Le gestionnaire de cartes ne peut modifier que la photo, rien d'autre
     */
    public function updateStudentPhoto(Request $request, $studentId)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $student = Student::findOrFail($studentId);

            // Supprimer l'ancienne photo si elle existe
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            // Sauvegarder la nouvelle photo
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = 'student_' . $student->matricule . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('students/photos', $filename, 'public');

                // Mettre à jour UNIQUEMENT le champ photo
                $student->photo = $path;
                $student->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'data' => [
                    'photo' => $student->photo
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour photo élève: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo'
            ], 500);
        }
    }
}
