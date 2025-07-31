<?php

namespace App\Http\Controllers;

use App\Models\SupervisorClassAssignment;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SupervisorController extends Controller
{
    public function __construct()
    {
        // Middleware JWT sera appliqué dans les routes
    }

    public function assignSupervisorToClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur est bien un surveillant général
        $supervisor = User::find($request->supervisor_id);
        if ($supervisor->role !== 'surveillant_general') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un surveillant général'
            ], 403);
        }

        try {
            $assignment = SupervisorClassAssignment::create([
                'supervisor_id' => $request->supervisor_id,
                'school_class_id' => $request->school_class_id,
                'school_year_id' => $request->school_year_id,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surveillant affecté à la classe avec succès',
                'data' => $assignment->load(['supervisor', 'schoolClass', 'schoolYear'])
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() == '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce surveillant est déjà affecté à cette classe pour cette année scolaire'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'affectation'
            ], 500);
        }
    }

    public function getSupervisorAssignments($supervisorId)
    {
        $assignments = SupervisorClassAssignment::with(['schoolClass', 'schoolYear'])
            ->forSupervisor($supervisorId)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function scanStudentQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_qr_code' => 'required|string',
            'supervisor_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Décoder le QR code pour obtenir l'ID de l'étudiant
        $studentId = $this->decodeStudentQR($request->student_qr_code);
        
        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Code QR invalide'
            ], 422);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable'
            ], 404);
        }

        // Obtenir l'année scolaire active
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        if (!$currentSchoolYear) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année scolaire active trouvée'
            ], 422);
        }

        // Vérifier si le surveillant est affecté à la classe de l'étudiant
        $assignment = SupervisorClassAssignment::where('supervisor_id', $request->supervisor_id)
            ->where('school_class_id', $student->class_series_id)
            ->where('school_year_id', $currentSchoolYear->id)
            ->active()
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à marquer la présence pour cette classe',
                'student_name' => $student->full_name,
                'class_name' => $student->classSeries->name ?? 'Classe inconnue'
            ], 403);
        }

        $today = Carbon::today();

        // Vérifier si l'élève est déjà marqué présent aujourd'hui
        $existingAttendance = Attendance::where('student_id', $studentId)
            ->forDate($today)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève est déjà marqué présent aujourd\'hui',
                'student_name' => $student->full_name,
                'marked_at' => $existingAttendance->scanned_at->format('H:i')
            ], 422);
        }

        try {
            $attendance = Attendance::create([
                'student_id' => $studentId,
                'supervisor_id' => $request->supervisor_id,
                'school_class_id' => $student->class_series_id,
                'school_year_id' => $currentSchoolYear->id,
                'attendance_date' => $today,
                'scanned_at' => Carbon::now(),
                'is_present' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Présence marquée avec succès',
                'data' => [
                    'student_name' => $student->full_name,
                    'class_name' => $student->classSeries->name ?? 'Classe inconnue',
                    'marked_at' => $attendance->scanned_at->format('H:i'),
                    'date' => $attendance->attendance_date->format('d/m/Y')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la présence'
            ], 500);
        }
    }

    public function getDailyAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
            'date' => 'nullable|date',
            'class_id' => 'nullable|exists:school_classes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $supervisorId = $request->supervisor_id;
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        // Vérifier si le superviseur connecté correspond au superviseur demandé
        if (auth()->user()->id != $supervisorId && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez voir que vos propres présences'
            ], 403);
        }

        // Obtenir les classes assignées au superviseur
        $assignedClasses = SupervisorClassAssignment::where('supervisor_id', $supervisorId)
            ->where('school_year_id', $currentSchoolYear->id)
            ->active()
            ->pluck('school_class_id')
            ->toArray();

        if (empty($assignedClasses)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune classe assignée',
                'data' => [
                    'date' => $date->format('d/m/Y'),
                    'total_present' => 0,
                    'attendances' => []
                ]
            ]);
        }

        // Filtrer les présences pour les classes assignées seulement
        $query = Attendance::with(['student', 'schoolClass'])
            ->whereIn('school_class_id', $assignedClasses)
            ->forDate($date)
            ->forSchoolYear($currentSchoolYear->id);

        if ($request->class_id && in_array($request->class_id, $assignedClasses)) {
            $query->forClass($request->class_id);
        }

        $attendances = $query->orderBy('scanned_at')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('d/m/Y'),
                'total_present' => $attendances->count(),
                'attendances' => $attendances
            ]
        ]);
    }

    public function getAttendanceRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:school_classes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $supervisorId = $request->supervisor_id;
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        // Vérifier si le superviseur connecté correspond au superviseur demandé
        if (auth()->user()->id != $supervisorId && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez voir que vos propres présences'
            ], 403);
        }

        // Obtenir les classes assignées au superviseur
        $assignedClasses = SupervisorClassAssignment::where('supervisor_id', $supervisorId)
            ->where('school_year_id', $currentSchoolYear->id)
            ->active()
            ->pluck('school_class_id')
            ->toArray();

        if (empty($assignedClasses)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune classe assignée',
                'data' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'total_records' => 0,
                    'attendances' => []
                ]
            ]);
        }

        // Filtrer les présences pour les classes assignées seulement
        $query = Attendance::with(['student', 'schoolClass', 'supervisor'])
            ->whereIn('school_class_id', $assignedClasses)
            ->forDateRange($request->start_date, $request->end_date)
            ->forSchoolYear($currentSchoolYear->id);

        if ($request->class_id && in_array($request->class_id, $assignedClasses)) {
            $query->forClass($request->class_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('scanned_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_records' => $attendances->count(),
                'attendances' => $attendances
            ]
        ]);
    }

    public function getAllAssignments()
    {
        $assignments = SupervisorClassAssignment::with(['supervisor', 'schoolClass', 'schoolYear'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function deleteAssignment($assignmentId)
    {
        try {
            $assignment = SupervisorClassAssignment::findOrFail($assignmentId);
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Affectation supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    public function generateStudentQR($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable'
            ], 404);
        }

        // Generate QR code content
        $qrContent = "STUDENT_ID_" . $student->id;
        
        return response()->json([
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'qr_content' => $qrContent,
                'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrContent)
            ]
        ]);
    }

    public function generateAllStudentQRs()
    {
        $students = Student::where('is_active', true)->get();
        $qrCodes = [];

        foreach ($students as $student) {
            $qrContent = "STUDENT_ID_" . $student->id;
            $qrCodes[] = [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'student_number' => $student->student_number,
                'class_name' => $student->classSeries->name ?? 'N/A',
                'qr_content' => $qrContent,
                'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrContent)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => count($qrCodes),
                'qr_codes' => $qrCodes
            ]
        ]);
    }

    private function decodeStudentQR($qrCode)
    {
        // Format attendu du QR code: "STUDENT_ID_123" ou simplement "123"
        if (str_starts_with($qrCode, 'STUDENT_ID_')) {
            return (int) str_replace('STUDENT_ID_', '', $qrCode);
        }
        
        // Si c'est juste un nombre
        if (is_numeric($qrCode)) {
            return (int) $qrCode;
        }
        
        return null;
    }
}