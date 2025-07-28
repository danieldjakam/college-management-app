<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\PaymentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Get general stats
     */
    public function gAFC()
    {
        $totalStudents = Student::count();
        $newStudents = Student::where('is_new', 'yes')->count();
        $oldStudents = Student::where('is_new', 'no')->count();
        $maleStudents = Student::where('sex', 'm')->count();
        $femaleStudents = Student::where('sex', 'f')->count();

        return response()->json([
            'total_students' => $totalStudents,
            'new_students' => $newStudents,
            'old_students' => $oldStudents,
            'male_students' => $maleStudents,
            'female_students' => $femaleStudents
        ]);
    }

    /**
     * Get all students
     */
    public function getAllStudent()
    {
        $students = Student::with(['schoolClass.section'])
                          ->orderBy('name')
                          ->get();
        return response()->json($students);
    }

    /**
     * Get total students count
     */
    public function getTotal()
    {
        $total = Student::count();
        return response()->json(['total' => $total]);
    }

    /**
     * Get ordered students by class
     */
    public function getOrdonnedStudents($id)
    {
        $students = Student::where('class_id', $id)
                          ->with(['schoolClass.section'])
                          ->orderBy('name')
                          ->get();
        return response()->json($students);
    }

    /**
     * Get one student
     */
    public function getOneStudent($id)
    {
        $student = Student::with(['schoolClass.section', 'paymentDetails'])
                         ->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        return response()->json($student);
    }

    /**
     * Get student payments
     */
    public function getPayements($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        $payments = PaymentDetail::where('student_id', $id)
                                ->with(['operator'])
                                ->orderBy('created_at', 'desc')
                                ->get();

        $totalPaid = $payments->sum('amount');
        $totalExpected = $student->total_expected;
        $balance = $totalExpected - $totalPaid;

        return response()->json([
            'student' => $student,
            'payments' => $payments,
            'total_paid' => $totalPaid,
            'total_expected' => $totalExpected,
            'balance' => $balance
        ]);
    }

    /**
     * Get students by class
     */
    public function getSpecificStudents($id)
    {
        $students = Student::where('class_id', $id)
                          ->with(['schoolClass.section'])
                          ->orderBy('name')
                          ->get();
        return response()->json($students);
    }

    /**
     * Add new student
     */
    public function addStudent(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'subname' => 'required|string|min:3|max:255',
            'sex' => 'required|in:f,m',
            'fatherName' => 'sometimes|string|max:255',
            'profession' => 'sometimes|string|max:255',
            'birthday' => 'sometimes|date',
            'birthday_place' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:new,old',
            'is_new' => 'sometimes|in:yes,no',
            'inscription' => 'sometimes|numeric|min:0',
            'first_tranch' => 'sometimes|numeric|min:0',
            'second_tranch' => 'sometimes|numeric|min:0',
            'third_tranch' => 'sometimes|numeric|min:0',
            'graduation' => 'sometimes|numeric|min:0',
            'assurance' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Verify class exists
        $class = SchoolClass::find($id);
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        $student = Student::create([
            'name' => $request->name,
            'subname' => $request->subname,
            'class_id' => $id,
            'sex' => $request->sex,
            'fatherName' => $request->fatherName,
            'profession' => $request->profession,
            'birthday' => $request->birthday,
            'birthday_place' => $request->birthday_place,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'school_year' => $request->school_year ?? '2024-2025',
            'status' => $request->status ?? 'new',
            'is_new' => $request->is_new ?? 'yes',
            'school_id' => $request->school_id ?? 'GSBPL_001',
            'inscription' => $request->inscription ?? 0,
            'first_tranch' => $request->first_tranch ?? 0,
            'second_tranch' => $request->second_tranch ?? 0,
            'third_tranch' => $request->third_tranch ?? 0,
            'graduation' => $request->graduation ?? 0,
            'assurance' => $request->assurance ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Étudiant ajouté avec succès',
            'data' => $student->load(['schoolClass.section'])
        ], 201);
    }

    /**
     * Transfer student to another class
     */
    public function transfertToAotherClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
            'new_class_id' => 'required|string|exists:class,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $student = Student::find($request->student_id);
        $student->class_id = $request->new_class_id;
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Étudiant transféré avec succès',
            'data' => $student->load(['schoolClass.section'])
        ]);
    }

    /**
     * Update student
     */
    public function updateStudent(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:3|max:255',
            'subname' => 'sometimes|string|min:3|max:255',
            'sex' => 'sometimes|in:f,m',
            'fatherName' => 'sometimes|string|max:255',
            'profession' => 'sometimes|string|max:255',
            'birthday' => 'sometimes|date',
            'birthday_place' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:new,old',
            'is_new' => 'sometimes|in:yes,no',
            'class_id' => 'sometimes|string|exists:class,id',
            'inscription' => 'sometimes|numeric|min:0',
            'first_tranch' => 'sometimes|numeric|min:0',
            'second_tranch' => 'sometimes|numeric|min:0',
            'third_tranch' => 'sometimes|numeric|min:0',
            'graduation' => 'sometimes|numeric|min:0',
            'assurance' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'subname', 'sex', 'fatherName', 'profession',
            'birthday', 'birthday_place', 'email', 'phone_number',
            'status', 'is_new', 'class_id', 'inscription',
            'first_tranch', 'second_tranch', 'third_tranch',
            'graduation', 'assurance'
        ]);

        $student->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Étudiant mis à jour avec succès',
            'data' => $student->load(['schoolClass.section'])
        ]);
    }

    /**
     * Delete student
     */
    public function deleteStudent($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        // Check if student has payments
        if ($student->paymentDetails()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un étudiant qui a des paiements enregistrés'
            ], 400);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Étudiant supprimé avec succès'
        ]);
    }
}