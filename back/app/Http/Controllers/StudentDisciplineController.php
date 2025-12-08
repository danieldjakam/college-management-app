<?php

namespace App\Http\Controllers;

use App\Models\StudentDiscipline;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentDisciplineController extends Controller
{
    /**
     * Get discipline data for a student in a specific period
     */
    public function show($studentId, Request $request)
    {
        $request->validate([
            'sequence_id' => 'nullable|exists:sequences,id',
            'trimester_id' => 'nullable|exists:trimesters,id'
        ]);

        $query = StudentDiscipline::where('student_id', $studentId);

        if ($request->sequence_id) {
            $query->where('sequence_id', $request->sequence_id);
        }

        if ($request->trimester_id) {
            $query->where('trimester_id', $request->trimester_id);
        }

        $discipline = $query->first();

        return response()->json([
            'success' => true,
            'data' => $discipline
        ]);
    }

    /**
     * Get discipline data for all students in a class for a specific period
     */
    public function getByClass(Request $request)
    {
        $request->validate([
            'class_series_id' => 'required|exists:class_series,id',
            'sequence_id' => 'nullable|exists:sequences,id',
            'trimester_id' => 'nullable|exists:trimesters,id'
        ]);

        $students = Student::where('class_series_id', $request->class_series_id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $disciplineData = [];

        foreach ($students as $student) {
            $query = StudentDiscipline::where('student_id', $student->id);

            if ($request->sequence_id) {
                $query->where('sequence_id', $request->sequence_id);
            }

            if ($request->trimester_id) {
                $query->where('trimester_id', $request->trimester_id);
            }

            $discipline = $query->first();

            $disciplineData[] = [
                'student_id' => $student->id,
                'student_name' => $student->last_name . ' ' . $student->first_name,
                'matricule' => $student->matricule,
                'discipline' => $discipline
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $disciplineData
        ]);
    }

    /**
     * Store or update discipline data for a student
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'sequence_id' => 'nullable|exists:sequences,id',
            'trimester_id' => 'nullable|exists:trimesters,id',
            'delays_justified' => 'nullable|integer|min:0',
            'delays_unjustified' => 'nullable|integer|min:0',
            'absences_justified' => 'nullable|integer|min:0',
            'absences_unjustified' => 'nullable|integer|min:0',
            'blame_conduct' => 'nullable|integer|min:0',
            'blame_work' => 'nullable|integer|min:0',
            'warning_conduct' => 'nullable|integer|min:0',
            'warning_work' => 'nullable|integer|min:0',
            'detention_hours' => 'nullable|integer|min:0',
            'exclusion_days' => 'nullable|integer|min:0',
            'observations' => 'nullable|string'
        ]);

        // Find existing record or create new
        $discipline = StudentDiscipline::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'sequence_id' => $request->sequence_id,
                'trimester_id' => $request->trimester_id
            ],
            [
                'delays_justified' => $request->delays_justified ?? 0,
                'delays_unjustified' => $request->delays_unjustified ?? 0,
                'absences_justified' => $request->absences_justified ?? 0,
                'absences_unjustified' => $request->absences_unjustified ?? 0,
                'blame_conduct' => $request->blame_conduct ?? 0,
                'blame_work' => $request->blame_work ?? 0,
                'warning_conduct' => $request->warning_conduct ?? 0,
                'warning_work' => $request->warning_work ?? 0,
                'detention_hours' => $request->detention_hours ?? 0,
                'exclusion_days' => $request->exclusion_days ?? 0,
                'observations' => $request->observations
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Données de discipline enregistrées avec succès',
            'data' => $discipline
        ]);
    }

    /**
     * Bulk update discipline data for multiple students
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'sequence_id' => 'nullable|exists:sequences,id',
            'trimester_id' => 'nullable|exists:trimesters,id'
        ]);

        $updated = 0;

        foreach ($request->students as $studentData) {
            StudentDiscipline::updateOrCreate(
                [
                    'student_id' => $studentData['student_id'],
                    'sequence_id' => $request->sequence_id,
                    'trimester_id' => $request->trimester_id
                ],
                [
                    'delays_justified' => $studentData['delays_justified'] ?? 0,
                    'delays_unjustified' => $studentData['delays_unjustified'] ?? 0,
                    'absences_justified' => $studentData['absences_justified'] ?? 0,
                    'absences_unjustified' => $studentData['absences_unjustified'] ?? 0,
                    'blame_conduct' => $studentData['blame_conduct'] ?? 0,
                    'blame_work' => $studentData['blame_work'] ?? 0,
                    'warning_conduct' => $studentData['warning_conduct'] ?? 0,
                    'warning_work' => $studentData['warning_work'] ?? 0,
                    'detention_hours' => $studentData['detention_hours'] ?? 0,
                    'exclusion_days' => $studentData['exclusion_days'] ?? 0,
                    'observations' => $studentData['observations'] ?? null
                ]
            );

            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "$updated élève(s) mis à jour avec succès"
        ]);
    }

    /**
     * Delete discipline record
     */
    public function destroy($id)
    {
        $discipline = StudentDiscipline::findOrFail($id);
        $discipline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Données de discipline supprimées avec succès'
        ]);
    }
}
