<?php

namespace App\Http\Controllers;

use App\Models\SubjectCompetence;
use App\Models\Teacher;
use App\Models\ClassSeries;
use App\Models\ClassSeriesSubject;
use App\Models\TeacherAssignment;
use App\Models\Trimester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetenceController extends Controller
{
    /**
     * Get all competences for the authenticated teacher
     */
    public function index()
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            return response()->json(['error' => 'Enseignant non trouvé'], 404);
        }

        $competences = SubjectCompetence::where('teacher_id', $teacher->id)
            ->with(['classSeries', 'classSeriesSubject.subject', 'trimester'])
            ->get();

        return response()->json($competences);
    }

    /**
     * Get competences for a specific class, subject, and trimester
     */
    public function show($classSeriesId, $classSeriesSubjectId, $trimesterId)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            return response()->json(['error' => 'Enseignant non trouvé'], 404);
        }

        $competence = SubjectCompetence::where('teacher_id', $teacher->id)
            ->where('class_series_id', $classSeriesId)
            ->where('class_series_subject_id', $classSeriesSubjectId)
            ->where('trimester_id', $trimesterId)
            ->with(['classSeries', 'classSeriesSubject.subject', 'trimester'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $competence,
            'message' => $competence ? 'Compétences récupérées' : 'Aucune compétence définie pour ce trimestre'
        ]);
    }

    /**
     * Get all subjects taught by teacher in a specific class
     */
    public function getTeacherSubjectsInClass($classSeriesId)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            return response()->json(['error' => 'Enseignant non trouvé'], 404);
        }

        // Récupérer toutes les matières enseignées par ce prof dans cette classe via les affectations
        $assignments = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)
            ->whereHas('classSeriesSubject', function($query) use ($classSeriesId) {
                $query->where('class_series_id', $classSeriesId);
            })
            ->with(['classSeriesSubject.subject'])
            ->get();

        // Transformer pour retourner les class_series_subjects avec leurs sujets
        $subjects = $assignments->map(function($assignment) {
            return [
                'id' => $assignment->classSeriesSubject->id,
                'class_series_id' => $assignment->classSeriesSubject->class_series_id,
                'subject_id' => $assignment->classSeriesSubject->subject_id,
                'coefficient' => $assignment->classSeriesSubject->coefficient,
                'subject' => $assignment->classSeriesSubject->subject
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Store or update competences
     */
    public function store(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            return response()->json(['error' => 'Enseignant non trouvé'], 404);
        }

        $validated = $request->validate([
            'class_series_id' => 'required|exists:class_series,id',
            'class_series_subject_id' => 'required|exists:class_series_subjects,id',
            'trimester_id' => 'required|exists:trimesters,id',
            'competence_1' => 'nullable|string',
            'competence_2' => 'nullable|string',
        ]);

        // Vérifier que l'enseignant enseigne bien cette matière dans cette classe
        $hasAssignment = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_series_subject_id', $validated['class_series_subject_id'])
            ->where('is_active', true)
            ->exists();

        if (!$hasAssignment) {
            return response()->json([
                'error' => 'Vous n\'enseignez pas cette matière dans cette classe'
            ], 403);
        }

        // Update or create
        $competence = SubjectCompetence::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'class_series_id' => $validated['class_series_id'],
                'class_series_subject_id' => $validated['class_series_subject_id'],
                'trimester_id' => $validated['trimester_id'],
            ],
            [
                'competence_1' => $validated['competence_1'],
                'competence_2' => $validated['competence_2'],
            ]
        );

        return response()->json([
            'message' => 'Compétences enregistrées avec succès',
            'competence' => $competence->load(['classSeries', 'classSeriesSubject.subject', 'trimester'])
        ]);
    }

    /**
     * Delete competences
     */
    public function destroy($id)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            return response()->json(['error' => 'Enseignant non trouvé'], 404);
        }

        $competence = SubjectCompetence::where('id', $id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (!$competence) {
            return response()->json(['error' => 'Compétence non trouvée'], 404);
        }

        $competence->delete();

        return response()->json(['message' => 'Compétence supprimée avec succès']);
    }

    /**
     * Get competences for bulletin generation
     * This method can be called by the system to retrieve competences for a subject/trimester
     */
    public function getForBulletin($classSeriesSubjectId, $trimesterId)
    {
        $competence = SubjectCompetence::where('class_series_subject_id', $classSeriesSubjectId)
            ->where('trimester_id', $trimesterId)
            ->first();

        if (!$competence) {
            return response()->json([
                'competence_1' => null,
                'competence_2' => null
            ]);
        }

        return response()->json([
            'competence_1' => $competence->competence_1,
            'competence_2' => $competence->competence_2
        ]);
    }
}
