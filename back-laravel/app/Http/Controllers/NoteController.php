<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteBySubject;
use App\Models\NoteByDomain;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Domain;
use App\Models\Sequence;
use App\Models\Trimester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * Get all notes for a student
     */
    public function getStudentNotes($studentId)
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        $notes = Note::where('student_id', $studentId)
                    ->with(['student', 'subject', 'sequence', 'trimester'])
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($notes);
    }

    /**
     * Get notes by subject for a class
     */
    public function getNotesBySubject($classId, $subjectId)
    {
        $notes = Note::whereHas('student', function($query) use ($classId) {
                        $query->where('class_id', $classId);
                    })
                    ->where('subject_id', $subjectId)
                    ->with(['student', 'subject', 'sequence', 'trimester'])
                    ->orderBy('student_id')
                    ->get();

        return response()->json($notes);
    }

    /**
     * Get notes by sequence for a class
     */
    public function getNotesBySequence($classId, $sequenceId)
    {
        $notes = Note::whereHas('student', function($query) use ($classId) {
                        $query->where('class_id', $classId);
                    })
                    ->where('sequence_id', $sequenceId)
                    ->with(['student', 'subject', 'sequence', 'trimester'])
                    ->orderBy('student_id')
                    ->get();

        return response()->json($notes);
    }

    /**
     * Add or update note
     */
    public function addOrUpdateNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
            'subject_id' => 'required|string|exists:subjects,id',
            'sequence_id' => 'sometimes|string|exists:sequences,id',
            'trimester_id' => 'sometimes|string|exists:trimesters,id',
            'note' => 'required|numeric|min:0|max:20',
            'note_type' => 'required|in:devoir,composition,exam',
            'coefficient' => 'sometimes|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Check if note already exists
        $existingNote = Note::where('student_id', $request->student_id)
                           ->where('subject_id', $request->subject_id)
                           ->where('sequence_id', $request->sequence_id)
                           ->where('note_type', $request->note_type)
                           ->first();

        if ($existingNote) {
            // Update existing note
            $existingNote->update([
                'note' => $request->note,
                'coefficient' => $request->coefficient ?? 1,
                'trimester_id' => $request->trimester_id,
            ]);

            $note = $existingNote;
            $message = 'Note mise à jour avec succès';
        } else {
            // Create new note
            $note = Note::create([
                'student_id' => $request->student_id,
                'subject_id' => $request->subject_id,
                'sequence_id' => $request->sequence_id,
                'trimester_id' => $request->trimester_id,
                'note' => $request->note,
                'note_type' => $request->note_type,
                'coefficient' => $request->coefficient ?? 1,
                'school_id' => $request->school_id ?? 'GSBPL_001',
            ]);

            $message = 'Note ajoutée avec succès';
        }

        // Recalculate averages
        $this->recalculateStudentAverages($request->student_id);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $note->load(['student', 'subject', 'sequence', 'trimester'])
        ]);
    }

    /**
     * Delete note
     */
    public function deleteNote($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note non trouvée'
            ], 404);
        }

        $studentId = $note->student_id;
        $note->delete();

        // Recalculate averages
        $this->recalculateStudentAverages($studentId);

        return response()->json([
            'success' => true,
            'message' => 'Note supprimée avec succès'
        ]);
    }

    /**
     * Get class bulletin for a sequence
     */
    public function getClassBulletin($classId, $sequenceId)
    {
        $students = Student::where('class_id', $classId)
                          ->with(['notes' => function($query) use ($sequenceId) {
                              $query->where('sequence_id', $sequenceId)
                                   ->with(['subject']);
                          }])
                          ->orderBy('name')
                          ->get();

        // Calculate averages for each student
        foreach ($students as $student) {
            $totalPoints = 0;
            $totalCoeff = 0;
            
            foreach ($student->notes as $note) {
                $totalPoints += $note->note * $note->coefficient;
                $totalCoeff += $note->coefficient;
            }
            
            $student->average = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;
        }

        // Sort by average (descending)
        $students = $students->sortByDesc('average')->values();

        return response()->json($students);
    }

    /**
     * Get student bulletin for a sequence
     */
    public function getStudentBulletin($studentId, $sequenceId)
    {
        $student = Student::with([
            'notes' => function($query) use ($sequenceId) {
                $query->where('sequence_id', $sequenceId)
                     ->with(['subject.domain']);
            },
            'schoolClass.section'
        ])->find($studentId);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant non trouvé'
            ], 404);
        }

        // Group notes by domain
        $notesByDomain = $student->notes->groupBy('subject.domain.name');
        
        // Calculate averages by domain
        $domainAverages = [];
        $totalPoints = 0;
        $totalCoeff = 0;

        foreach ($notesByDomain as $domainName => $notes) {
            $domainPoints = 0;
            $domainCoeff = 0;
            
            foreach ($notes as $note) {
                $domainPoints += $note->note * $note->coefficient;
                $domainCoeff += $note->coefficient;
                $totalPoints += $note->note * $note->coefficient;
                $totalCoeff += $note->coefficient;
            }
            
            $domainAverages[$domainName] = [
                'average' => $domainCoeff > 0 ? $domainPoints / $domainCoeff : 0,
                'notes' => $notes
            ];
        }

        $generalAverage = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;

        return response()->json([
            'student' => $student,
            'domain_averages' => $domainAverages,
            'general_average' => $generalAverage,
            'total_coefficient' => $totalCoeff
        ]);
    }

    /**
     * Recalculate student averages
     */
    private function recalculateStudentAverages($studentId)
    {
        // This would trigger the calculation of NoteBySubject and NoteByDomain
        // Implementation depends on business logic
        
        // Calculate by subject
        $subjectNotes = Note::where('student_id', $studentId)
                           ->select('subject_id', 
                                   DB::raw('AVG(note) as average'),
                                   DB::raw('COUNT(*) as count'))
                           ->groupBy('subject_id')
                           ->get();

        foreach ($subjectNotes as $subjectNote) {
            NoteBySubject::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $subjectNote->subject_id
                ],
                [
                    'average' => $subjectNote->average,
                    'note_count' => $subjectNote->count
                ]
            );
        }

        // Calculate by domain
        $domainNotes = Note::where('student_id', $studentId)
                          ->join('subjects', 'notes.subject_id', '=', 'subjects.id')
                          ->select('subjects.domain_id',
                                  DB::raw('AVG(notes.note) as average'),
                                  DB::raw('COUNT(*) as count'))
                          ->groupBy('subjects.domain_id')
                          ->get();

        foreach ($domainNotes as $domainNote) {
            NoteByDomain::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'domain_id' => $domainNote->domain_id
                ],
                [
                    'average' => $domainNote->average,
                    'note_count' => $domainNote->count
                ]
            );
        }
    }
}