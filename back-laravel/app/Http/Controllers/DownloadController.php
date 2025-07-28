<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Note;
use App\Models\SchoolClass;
use App\Models\Sequence;
use App\Models\Trimester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DownloadController extends Controller
{
    /**
     * Download class list
     */
    public function downloadClassList($classId)
    {
        $class = SchoolClass::with(['section'])->find($classId);
        
        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        $students = Student::where('class_id', $classId)
                          ->orderBy('name')
                          ->get();

        $csvData = "Nom,Prénom,Sexe,Téléphone,Email,Statut\n";
        
        foreach ($students as $student) {
            $csvData .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $student->name,
                $student->subname,
                $student->sex === 'm' ? 'Masculin' : 'Féminin',
                $student->phone_number ?? '',
                $student->email ?? '',
                $student->is_new === 'yes' ? 'Nouveau' : 'Ancien'
            );
        }

        $filename = sprintf('liste_classe_%s_%s.csv', 
            $class->section->name ?? 'unknown', 
            $class->name
        );

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download student bulletin
     */
    public function downloadStudentBulletin($studentId, $sequenceId)
    {
        $student = Student::with(['schoolClass.section'])->find($studentId);
        $sequence = Sequence::find($sequenceId);
        
        if (!$student || !$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant ou séquence non trouvé'
            ], 404);
        }

        $notes = Note::where('student_id', $studentId)
                    ->where('sequence_id', $sequenceId)
                    ->with(['subject.domain'])
                    ->get();

        // Calculate averages
        $totalPoints = 0;
        $totalCoeff = 0;
        $notesByDomain = $notes->groupBy('subject.domain.name');

        $csvData = "BULLETIN DE NOTES\n";
        $csvData .= "Étudiant: {$student->name} {$student->subname}\n";
        // $csvData .= "Classe: {$student->schoolClass->section->name ?? ''} {$student->schoolClass->name}\n";
        $csvData .= "Séquence: {$sequence->name}\n\n";
        $csvData .= "Domaine,Matière,Note,Coefficient,Points\n";

        foreach ($notesByDomain as $domainName => $domainNotes) {
            foreach ($domainNotes as $note) {
                $points = $note->note * $note->coefficient;
                $totalPoints += $points;
                $totalCoeff += $note->coefficient;
                
                $csvData .= sprintf(
                    "\"%s\",\"%s\",\"%.2f\",\"%d\",\"%.2f\"\n",
                    $domainName,
                    $note->subject->name,
                    $note->note,
                    $note->coefficient,
                    $points
                );
            }
        }

        $average = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;
        $csvData .= "\nMoyenne générale:,,,\"%.2f\"\n" . $average;

        $filename = sprintf('bulletin_%s_%s_%s.csv', 
            $student->name, 
            $student->subname, 
            $sequence->name
        );

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download class bulletin
     */
    public function downloadClassBulletin($classId, $sequenceId)
    {
        $class = SchoolClass::with(['section'])->find($classId);
        $sequence = Sequence::find($sequenceId);
        
        if (!$class || !$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Classe ou séquence non trouvée'
            ], 404);
        }

        $students = Student::where('class_id', $classId)
                          ->with(['notes' => function($query) use ($sequenceId) {
                              $query->where('sequence_id', $sequenceId);
                          }])
                          ->orderBy('name')
                          ->get();

        $csvData = "BULLETIN DE CLASSE\n";
        // $csvData .= "Classe: {$class->section->name ?? ''} {$class->name}\n";
        $csvData .= "Séquence: {$sequence->name}\n\n";
        $csvData .= "Rang,Nom,Prénom,Moyenne,Mention\n";

        // Calculate averages for ranking
        $studentAverages = [];
        foreach ($students as $student) {
            $totalPoints = 0;
            $totalCoeff = 0;
            
            foreach ($student->notes as $note) {
                $totalPoints += $note->note * $note->coefficient;
                $totalCoeff += $note->coefficient;
            }
            
            $average = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;
            $studentAverages[] = [
                'student' => $student,
                'average' => $average
            ];
        }

        // Sort by average (descending)
        usort($studentAverages, function($a, $b) {
            return $b['average'] <=> $a['average'];
        });

        $rank = 1;
        foreach ($studentAverages as $data) {
            $student = $data['student'];
            $average = $data['average'];
            
            $mention = '';
            if ($average >= 16) $mention = 'Très Bien';
            elseif ($average >= 14) $mention = 'Bien';
            elseif ($average >= 12) $mention = 'Assez Bien';
            elseif ($average >= 10) $mention = 'Passable';
            else $mention = 'Échec';

            $csvData .= sprintf(
                "\"%d\",\"%s\",\"%s\",\"%.2f\",\"%s\"\n",
                $rank++,
                $student->name,
                $student->subname,
                $average,
                $mention
            );
        }

        $filename = sprintf('bulletin_classe_%s_%s_%s.csv', 
            $class->section->name ?? 'unknown', 
            $class->name,
            $sequence->name
        );

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download payment report
     */
    public function downloadPaymentReport($classId = null)
    {
        $query = Student::with(['paymentDetails', 'schoolClass.section']);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        $students = $query->orderBy('name')->get();

        $csvData = "RAPPORT DES PAIEMENTS\n";
        if ($classId) {
            $class = SchoolClass::with(['section'])->find($classId);
            // $csvData .= "Classe: {$class->section->name ?? ''} {$class->name}\n";
        }
        $csvData .= "Date: " . date('Y-m-d') . "\n\n";
        $csvData .= "Nom,Prénom,Classe,Total Attendu,Total Payé,Solde,Statut\n";

        foreach ($students as $student) {
            $totalExpected = $student->inscription + $student->first_tranch + 
                           $student->second_tranch + $student->third_tranch + 
                           $student->graduation + $student->assurance;
            
            $totalPaid = $student->paymentDetails->sum('amount');
            $balance = $totalExpected - $totalPaid;
            $status = $balance <= 0 ? 'Soldé' : 'En cours';

            $csvData .= sprintf(
                "\"%s\",\"%s\",\"%s %s\",\"%.0f\",\"%.0f\",\"%.0f\",\"%s\"\n",
                $student->name,
                $student->subname,
                $student->schoolClass->section->name ?? '',
                $student->schoolClass->name,
                $totalExpected,
                $totalPaid,
                $balance,
                $status
            );
        }

        $filename = 'rapport_paiements_' . date('Y-m-d') . '.csv';

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}