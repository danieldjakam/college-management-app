<?php

namespace App\Http\Controllers;

use App\Models\StudentEvaluation;
use App\Models\Student;
use App\Models\ClassSeries;
use App\Models\Subject;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;

class MarkSheetController extends Controller
{
    /**
     * Générer une fiche de report de notes vierge pour une classe-série et une matière
     */
    public function generateBlankMarkSheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_series_id' => 'required|exists:class_series,id',
            'subject_id' => 'required|exists:subjects,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $classSeries = ClassSeries::with(['schoolClass'])->find($request->class_series_id);
            $subject = Subject::find($request->subject_id);
            $schoolYear = SchoolYear::find($request->school_year_id);

            // Récupérer tous les élèves de la classe-série, triés par ordre alphabétique
            $students = Student::where('class_series_id', $request->class_series_id)
                ->where('school_year_id', $request->school_year_id)
                ->where('is_active', true)
                ->orderBy('last_name', 'asc')
                ->orderBy('first_name', 'asc')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé dans cette classe'
                ], 404);
            }

            // Trouver l'enseignant de cette matière pour cette classe
            $teacher = $this->findTeacherForSubject($request->class_series_id, $request->subject_id);

            // Récupérer le coefficient de la matière
            $coefficient = $this->getSubjectCoefficient($subject, $classSeries);

            // Générer le PDF
            $html = $this->generateMarkSheetPdfHtml(
                $classSeries,
                $subject,
                $schoolYear,
                $students,
                $teacher,
                $coefficient,
                null // Pas de notes (fiche vierge)
            );

            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $filename = $this->generateFilename($classSeries, $subject, $schoolYear);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error generating blank mark sheet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la fiche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer toutes les fiches vierges pour une classe-série
     */
    public function generateAllBlankMarkSheetsForClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_series_id' => 'required|exists:class_series,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $classSeries = ClassSeries::with(['schoolClass'])->find($request->class_series_id);
            $schoolYear = SchoolYear::find($request->school_year_id);

            // Récupérer toutes les matières de cette classe
            $subjects = Subject::where('is_active', true)->get();

            if ($subjects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune matière trouvée'
                ], 404);
            }

            $generatedSheets = [];

            foreach ($subjects as $subject) {
                // Vérifier si cette matière s'applique à cette classe
                // (logique métier à adapter selon votre système)

                $students = Student::where('class_series_id', $request->class_series_id)
                    ->where('school_year_id', $request->school_year_id)
                    ->where('is_active', true)
                    ->orderBy('last_name', 'asc')
                    ->orderBy('first_name', 'asc')
                    ->get();

                if ($students->isEmpty()) {
                    continue;
                }

                $teacher = $this->findTeacherForSubject($request->class_series_id, $subject->id);
                $coefficient = $this->getSubjectCoefficient($subject, $classSeries);

                $generatedSheets[] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'teacher' => $teacher ? $teacher->name : 'Non assigné',
                    'coefficient' => $coefficient,
                    'students_count' => $students->count()
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Liste des fiches à générer',
                'data' => [
                    'class_series' => $classSeries->name,
                    'school_year' => $schoolYear->name,
                    'sheets' => $generatedSheets
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating all blank mark sheets', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des fiches',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Trouver l'enseignant d'une matière pour une classe
     */
    private function findTeacherForSubject($classSeriesId, $subjectId)
    {
        // Trouver le class_series_subject_id correspondant
        $classSeriesSubject = \App\Models\ClassSeriesSubject::where('class_series_id', $classSeriesId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();

        if (!$classSeriesSubject) {
            return null;
        }

        // Trouver l'affectation de l'enseignant pour cette matière dans cette classe
        $currentSchoolYear = SchoolYear::where('is_current', true)->first();

        $teacherAssignment = \App\Models\TeacherAssignment::where('class_series_subject_id', $classSeriesSubject->id)
            ->where('school_year_id', $currentSchoolYear ? $currentSchoolYear->id : null)
            ->where('is_active', true)
            ->with('teacher')
            ->first();

        return $teacherAssignment ? $teacherAssignment->teacher : null;
    }

    /**
     * Obtenir le coefficient d'une matière pour une classe-série
     */
    private function getSubjectCoefficient($subject, $classSeries)
    {
        // Chercher le coefficient dans class_series_subjects
        $classSeriesSubject = \App\Models\ClassSeriesSubject::where('class_series_id', $classSeries->id)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->first();

        // Si trouvé, utiliser le coefficient spécifique à la classe-série
        if ($classSeriesSubject && $classSeriesSubject->coefficient) {
            return $classSeriesSubject->coefficient;
        }

        // Sinon, utiliser le coefficient par défaut de la matière
        return $subject->coefficient ?? 1;
    }

    /**
     * Générer le nom de fichier PDF
     */
    private function generateFilename($classSeries, $subject, $schoolYear)
    {
        $className = str_replace(' ', '_', $classSeries->name);
        $subjectName = str_replace(' ', '_', $subject->name);
        $year = str_replace('/', '-', $schoolYear->name);

        return "Mark_Sheet_{$className}_{$subjectName}_{$year}.pdf";
    }

    /**
     * Générer le HTML du PDF (sera complété dans le prochain fichier)
     */
    private function generateMarkSheetPdfHtml($classSeries, $subject, $schoolYear, $students, $teacher, $coefficient, $evaluations)
    {
        $schoolSettings = SchoolSetting::first();

        return view('exports.mark-sheet', compact(
            'classSeries',
            'subject',
            'schoolYear',
            'students',
            'teacher',
            'coefficient',
            'evaluations',
            'schoolSettings'
        ))->render();
    }
}
