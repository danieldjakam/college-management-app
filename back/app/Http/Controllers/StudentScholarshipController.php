<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentScholarship;
use App\Models\ClassScholarship;
use App\Models\PaymentTranche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentScholarshipController extends Controller
{
    /**
     * Lister les bourses d'un étudiant
     */
    public function getStudentScholarships($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            
            $scholarships = StudentScholarship::where('student_id', $studentId)
                ->with(['classScholarship', 'paymentTranche'])
                ->orderBy('payment_tranche_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'scholarships' => $scholarships
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des bourses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner une bourse à un étudiant
     */
    public function assignScholarship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'class_scholarship_id' => 'required|exists:class_scholarships,id',
            'payment_tranche_id' => 'required|exists:payment_tranches,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Vérifier que l'étudiant n'a pas déjà une bourse pour cette tranche
            $existingScholarship = StudentScholarship::where('student_id', $request->student_id)
                ->where('payment_tranche_id', $request->payment_tranche_id)
                ->first();

            if ($existingScholarship) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'étudiant a déjà une bourse assignée pour cette tranche'
                ], 422);
            }

            // Vérifier que la bourse de classe correspond à la classe de l'étudiant
            $student = Student::with('classSeries.schoolClass')->findOrFail($request->student_id);
            $classScholarship = ClassScholarship::findOrFail($request->class_scholarship_id);

            if (!$student->classSeries || !$student->classSeries->schoolClass || 
                $student->classSeries->schoolClass->id !== $classScholarship->school_class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette bourse n\'est pas disponible pour la classe de cet étudiant'
                ], 422);
            }

            // Créer l'assignation de bourse
            $studentScholarship = StudentScholarship::create([
                'student_id' => $request->student_id,
                'class_scholarship_id' => $request->class_scholarship_id,
                'payment_tranche_id' => $request->payment_tranche_id,
                'notes' => $request->notes
            ]);

            $studentScholarship->load(['classScholarship', 'paymentTranche']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $studentScholarship,
                'message' => 'Bourse assignée avec succès'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation de la bourse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer une bourse d'un étudiant (seulement si non utilisée)
     */
    public function removeScholarship($scholarshipId)
    {
        try {
            $scholarship = StudentScholarship::findOrFail($scholarshipId);

            if ($scholarship->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de retirer une bourse déjà utilisée'
                ], 422);
            }

            $scholarship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bourse retirée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la bourse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les bourses disponibles pour une classe
     */
    public function getAvailableScholarshipsForClass($classId)
    {
        try {
            $scholarships = ClassScholarship::active()
                ->where('school_class_id', $classId)
                ->with('paymentTranche')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $scholarships
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des bourses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les étudiants éligibles pour une bourse (même classe)
     */
    public function getEligibleStudents($classScholarshipId)
    {
        try {
            $classScholarship = ClassScholarship::with('schoolClass')->findOrFail($classScholarshipId);
            
            // Récupérer les étudiants de cette classe qui n'ont pas encore cette bourse
            $students = Student::whereHas('classSeries.schoolClass', function($query) use ($classScholarship) {
                $query->where('id', $classScholarship->school_class_id);
            })
            ->whereDoesntHave('scholarships', function($query) use ($classScholarship) {
                $query->where('class_scholarship_id', $classScholarship->id)
                      ->where('payment_tranche_id', $classScholarship->payment_tranche_id);
            })
            ->with('classSeries.schoolClass')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'scholarship' => $classScholarship,
                    'eligible_students' => $students
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants éligibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner une bourse à plusieurs étudiants en lot
     */
    public function bulkAssignScholarship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'class_scholarship_id' => 'required|exists:class_scholarships,id',
            'payment_tranche_id' => 'required|exists:payment_tranches,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $classScholarship = ClassScholarship::findOrFail($request->class_scholarship_id);
            $assignedCount = 0;
            $errors = [];

            foreach ($request->student_ids as $studentId) {
                try {
                    // Vérifier que l'étudiant n'a pas déjà une bourse pour cette tranche
                    $existingScholarship = StudentScholarship::where('student_id', $studentId)
                        ->where('payment_tranche_id', $request->payment_tranche_id)
                        ->first();

                    if ($existingScholarship) {
                        $student = Student::find($studentId);
                        $errors[] = "L'étudiant {$student->full_name} a déjà une bourse pour cette tranche";
                        continue;
                    }

                    // Créer l'assignation
                    StudentScholarship::create([
                        'student_id' => $studentId,
                        'class_scholarship_id' => $request->class_scholarship_id,
                        'payment_tranche_id' => $request->payment_tranche_id,
                        'notes' => $request->notes
                    ]);

                    $assignedCount++;
                } catch (\Exception $e) {
                    $student = Student::find($studentId);
                    $errors[] = "Erreur pour {$student->full_name}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$assignedCount} bourse(s) assignée(s) avec succès",
                'data' => [
                    'assigned_count' => $assignedCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation en lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}