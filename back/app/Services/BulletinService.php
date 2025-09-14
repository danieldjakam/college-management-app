<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Evaluation;
use App\Models\SeriesSubject;
use App\Models\BulletinTemplate;
use App\Models\BulletinGeneration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class BulletinService
{
    /**
     * Calculate DS (Devoir Surveill�) average for a student in a trimester
     * DS1 = (Sequence 1 + Sequence 2) / 2
     * DS2 = (Sequence 3 + Sequence 4) / 2
     */
    public function calculateDSAverage($trimester, $studentId, $subjectId)
    {
        \Log::info("🔍 DEBUG DS: trimester={$trimester}, student={$studentId}, subject={$subjectId}");
        
        $sequences = $this->getSequencesForTrimester($trimester);
        \Log::info("🔍 Sequences found: " . $sequences->pluck('number')->join(','));
        
        if ($sequences->count() != 2) {
            \Log::info("🔍 PROBLÈME: Expected 2 sequences, found " . $sequences->count());
            return null;
        }
        
        $grades = collect();
        $foundSequenceNotes = 0;
        
        foreach ($sequences as $sequence) {
            \Log::info("🔍 Checking sequence {$sequence->number} (id={$sequence->id})");
            $sequenceNote = null;
            
            // 🔧 FIX: Ajouter trimester dans la requête comme TrimesterController
            $grade = Grade::where('student_id', $studentId)
                         ->where('sequence_id', $sequence->id)
                         ->where('series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();
            
            \Log::info("🔍 Direct grade found: " . ($grade ? "score={$grade->score}" : "none"));
            
            // Si pas de note directe, chercher via les évaluations de la séquence
            if (!$grade) {
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('series_subject_id', $subjectId)
                                        ->get();
                
                \Log::info("🔍 Evaluations for sequence: " . $evaluations->count());
                
                foreach ($evaluations as $evaluation) {
                    $grade = Grade::where('student_id', $studentId)
                                 ->where('evaluation_id', $evaluation->id)
                                 ->where('trimester_id', $trimester)
                                 ->whereNotNull('score')
                                 ->first();
                    
                    if ($grade) {
                        \Log::info("🔍 Grade found via evaluation {$evaluation->id}: score={$grade->score}");
                        break; // Prendre la première note trouvée pour cette séquence
                    }
                }
            }
            
            if ($grade) {
                $sequenceNote = $grade->getScoreOn20();
                $grades->push($sequenceNote);
                $foundSequenceNotes++;
                \Log::info("🔍 Sequence {$sequence->number} note: {$sequenceNote}");
            } else {
                \Log::info("🔍 No grade found for sequence {$sequence->number}");
            }
        }
        
        \Log::info("🔍 Found {$foundSequenceNotes} sequence notes out of 2 required");
        
        // RÈGLE ACADÉMIQUE: DS n'existe que si les DEUX séquences ont des notes
        // Si seulement une séquence a une note, pas de DS calculé
        if ($foundSequenceNotes < 2) {
            \Log::info("🔍 DS NOT CALCULATED: Need 2 notes, found {$foundSequenceNotes}");
            return null;
        }
        
        $dsAverage = $grades->average();
        \Log::info("🔍 DS CALCULATED: {$dsAverage}");
        return $dsAverage;
    }
    
    /**
     * Calculate trimester grade for a student in a subject
     * Trimestre 1/2: (DS + COMPOSITION) / 2
     * Trimestre 3: COMPOSITION uniquement
     * RÈGLE STRICTE: Pas de note de trimestre si données incomplètes
     */
    public function calculateTrimesterGrade($trimester, $studentId, $subjectId)
    {
        if ($trimester == 3) {
            // Trimestre 3: Composition 3 uniquement
            $compositionGrade = $this->getCompositionGrade(3, $studentId, $subjectId);
            return $compositionGrade; // Peut être null si pas de composition
        }
        
        // Trimestres 1 et 2: (DS + COMPOSITION) / 2
        $dsAverage = $this->calculateDSAverage($trimester, $studentId, $subjectId);
        $compositionGrade = $this->getCompositionGrade($trimester, $studentId, $subjectId);
        
        // NOUVELLE RÈGLE : Si composition pas saisie, compter 0 dans le calcul
        // DS + Composition (ou 0 si pas saisie) / 2
        
        if ($dsAverage !== null) {
            // Composition = note saisie ou 0 si pas saisie
            $finalCompositionGrade = $compositionGrade !== null ? $compositionGrade : 0;
            return ($dsAverage + $finalCompositionGrade) / 2;
        }
        
        // Si seulement composition disponible (cas rare)
        if ($compositionGrade !== null && $dsAverage === null) {
            return $compositionGrade; // Utiliser composition seulement
        }
        
        return null; // Pas de données du tout
    }
    
    /**
     * Get composition grade for a student
     */
    public function getCompositionGrade($trimester, $studentId, $subjectId)
    {
        // RECHERCHE CORRECTE DES COMPOSITIONS
        // Chercher les évaluations avec type = 'composition' (pas evaluation_type)
        $evaluation = Evaluation::where('type', 'composition')
                               ->where('trimester_id', $trimester)
                               ->where('series_subject_id', $subjectId)
                               ->first();
        
        // RETOUR NULL SI AUCUNE COMPOSITION TROUVÉE
        if (!$evaluation) {
            \Log::info("Aucune composition trouvée (type=composition) pour trimester:{$trimester}, student:{$studentId}, subject:{$subjectId}");
            return null;
        }
        
        $grade = Grade::where('student_id', $studentId)
                     ->where('evaluation_id', $evaluation->id)
                     ->where('trimester_id', $trimester)
                     ->whereNotNull('score')
                     ->first();
        
        $result = $grade ? $grade->getScoreOn20() : null;
        \Log::info("Vraie composition trouvée: evaluation_id:{$evaluation->id}, grade:" . ($result ?? 'null'));
        
        return $result;
    }
    
    /**
     * Get sequences for a trimester
     */
    protected function getSequencesForTrimester($trimester)
    {
        $sequenceNumbers = [];
        
        switch ($trimester) {
            case 1:
                $sequenceNumbers = [1, 2];
                break;
            case 2:
                $sequenceNumbers = [3, 4];
                break;
            default:
                return collect();
        }
        
        // Prendre seulement une séquence par numéro pour éviter les doublons
        $sequences = collect();
        foreach ($sequenceNumbers as $number) {
            $seq = Sequence::where('number', $number)->first();
            if ($seq) {
                $sequences->push($seq);
            }
        }
        return $sequences;
    }
    
    /**
     * Check if student is eligible for honor roll
     * Moyenne annuelle >= 12/20
     */
    public function isEligibleForHonorRoll($studentId)
    {
        $annualAverage = $this->calculateAnnualAverage($studentId);
        return $annualAverage !== null && $annualAverage >= 12;
    }
    
    /**
     * Calculate annual average for a student
     * (Trimestre 1 + Trimestre 2 + Trimestre 3) / 3
     */
    public function calculateAnnualAverage($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) return null;
        
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
        $trimesterAverages = [];
        
        for ($trimester = 1; $trimester <= 3; $trimester++) {
            $subjectGrades = [];
            
            foreach ($subjects as $subject) {
                $grade = $this->calculateTrimesterGrade($trimester, $studentId, $subject->id);
                if ($grade !== null) {
                    $subjectGrades[] = $grade * $subject->coefficient;
                }
            }
            
            if (count($subjectGrades) > 0) {
                $totalCoefficient = $subjects->sum('coefficient');
                $trimesterAverages[] = array_sum($subjectGrades) / $totalCoefficient;
            }
        }
        
        return count($trimesterAverages) > 0 ? array_sum($trimesterAverages) / count($trimesterAverages) : null;
    }
    
    /**
     * Generate sequence bulletin data for a student
     */
    public function generateSequenceBulletinData($sequenceNumber, $studentId)
    {
        $student = Student::with(['schoolClass', 'classSeries'])->find($studentId);
        if (!$student) return null;
        
        $sequence = Sequence::where('number', $sequenceNumber)->first();
        if (!$sequence) return null;
        
        // Obtenir l'année scolaire courante
        $currentSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
        $schoolYearId = $currentSchoolYear ? $currentSchoolYear->id : null;
        
        // Utiliser school_class_id de la relation schoolClass
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)
                                 ->with(['subject', 'teachers' => function($query) use ($schoolYearId) {
                                     $query->wherePivot('is_active', true);
                                     if ($schoolYearId) {
                                         $query->wherePivot('school_year_id', $schoolYearId);
                                     }
                                 }])
                                 ->get();
        
        $bulletinData = [
            'student' => $student,
            'sequence' => $sequence,
            'subjects' => [],
            // Données pour le template
            'student_first_name' => $student->first_name,
            'student_last_name' => $student->last_name,
            'student_id' => $student->id,
            'student_birth_date' => $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A',
            'student_matricule' => $student->matricule ?? 'N/A',
            'class_name' => $student->schoolClass->name ?? 'N/A',
            'class_size' => $student->schoolClass->students()->count() ?? 0,
            'sequence_number' => $sequenceNumber,
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'subjects_rows' => ''
        ];
        
        $totalPoints = 0;
        $totalCoefficient = 0;
        
        foreach ($subjects as $seriesSubject) {
            $grade = Grade::where('student_id', $studentId)
                         ->where('sequence_id', $sequence->id)
                         ->where('series_subject_id', $seriesSubject->id)
                         ->first();
            
            $scoreOn20 = $grade ? $grade->getScoreOn20() : null;
            $weightedScore = $scoreOn20 ? $scoreOn20 * $seriesSubject->coefficient : 0;
            
            // Get the first teacher assigned to this subject
            $teacherName = 'N/A';
            if ($seriesSubject->teachers && $seriesSubject->teachers->isNotEmpty()) {
                $teacherName = $seriesSubject->teachers->first()->full_name;
            }
            
            $bulletinData['subjects'][] = [
                'name' => $seriesSubject->subject->name,
                'teacher' => $teacherName,
                'score' => $scoreOn20,
                'coefficient' => $seriesSubject->coefficient,
                'total' => $weightedScore,
                'rank' => $grade ? $grade->getRank() : null,
                'grade' => $grade ? $grade->getMention() : null,
                'min_max' => $this->getSubjectMinMax($sequence->id, $seriesSubject->id),
                'appreciation' => $this->getAppreciation($scoreOn20)
            ];
            
            if ($scoreOn20) {
                $totalPoints += $weightedScore;
                $totalCoefficient += $seriesSubject->coefficient;
            }
        }
        
        $bulletinData['average'] = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_points'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient;
        $bulletinData['rank'] = $this->getStudentRank($sequence->id, $studentId);
        $bulletinData['mention'] = $this->getMention($bulletinData['average']);
        
        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'sequence');
        $bulletinData['first_average'] = 20; // TODO: Calculer vraiment
        $bulletinData['last_average'] = 5;   // TODO: Calculer vraiment
        $bulletinData['appreciation'] = $this->getAppreciation($bulletinData['average']);
        
        return $bulletinData;
    }
    
    /**
     * Generate trimester bulletin data for a student
     */
    public function generateTrimesterBulletinData($trimesterNumber, $studentId)
    {
        $student = Student::with(['schoolClass', 'classSeries'])->find($studentId);
        if (!$student) return null;
        
        $trimester = Trimester::where('number', $trimesterNumber)->first();
        if (!$trimester) return null;
        
        // Obtenir l'année scolaire courante
        $currentSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
        $schoolYearId = $currentSchoolYear ? $currentSchoolYear->id : null;
        
        // Utiliser school_class_id de la relation schoolClass
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)
                                 ->with(['subject', 'teachers' => function($query) use ($schoolYearId) {
                                     $query->wherePivot('is_active', true);
                                     if ($schoolYearId) {
                                         $query->wherePivot('school_year_id', $schoolYearId);
                                     }
                                 }])
                                 ->get();
        
        $bulletinData = [
            'student' => $student,
            'trimester' => $trimester,
            'subjects' => [],
            // Données pour le template
            'student_first_name' => $student->first_name,
            'student_last_name' => $student->last_name,
            'student_id' => $student->id,
            'student_birth_date' => $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A',
            'student_matricule' => $student->matricule ?? 'N/A',
            'class_name' => $student->schoolClass->name ?? 'N/A',
            'class_size' => $student->schoolClass->students()->count() ?? 0,
            'trimester_number' => $trimesterNumber,
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'subjects_rows' => ''
        ];
        
        $totalPoints = 0;
        $totalCoefficient = 0;
        
        foreach ($subjects as $seriesSubject) {
            \Log::info("🔍 Processing subject: {$seriesSubject->subject->name} (id={$seriesSubject->id}) for student {$studentId}, trimester={$trimesterNumber}");
            $dsAverage = $this->calculateDSAverage($trimesterNumber, $studentId, $seriesSubject->id);
            \Log::info("🔍 DS Average for {$seriesSubject->subject->name}: " . ($dsAverage ?? 'null'));
            $compositionGrade = $this->getCompositionGrade($trimesterNumber, $studentId, $seriesSubject->id);
            \Log::info("🔍 Composition Grade for {$seriesSubject->subject->name}: " . ($compositionGrade ?? 'null'));
            $trimesterGrade = $this->calculateTrimesterGrade($trimesterNumber, $studentId, $seriesSubject->id);
            \Log::info("🔍 Final Trimester Grade for {$seriesSubject->subject->name}: " . ($trimesterGrade ?? 'null'));
            
            $weightedScore = $trimesterGrade ? $trimesterGrade * $seriesSubject->coefficient : 0;
            
            // Get the first teacher assigned to this subject
            $teacherName = 'N/A';
            if ($seriesSubject->teachers && $seriesSubject->teachers->isNotEmpty()) {
                $teacherName = $seriesSubject->teachers->first()->full_name;
            }
            
            $bulletinData['subjects'][] = [
                'name' => $seriesSubject->subject->name,
                'ds' => $dsAverage,
                'composition' => $compositionGrade,
                'score' => $trimesterGrade, // Pour compatibilité avec le template
                'average' => $trimesterGrade,
                'coefficient' => $seriesSubject->coefficient,
                'total' => $weightedScore,
                'rank' => $this->getTrimesterSubjectRank($trimesterNumber, $studentId, $seriesSubject->id),
                'grade' => $this->getMention($trimesterGrade),
                'teacher' => $teacherName,
                'min_max' => $this->getTrimesterSubjectMinMax($trimesterNumber, $seriesSubject->id),
                'appreciation' => $this->getAppreciation($trimesterGrade)
            ];
            
            if ($trimesterGrade) {
                $totalPoints += $weightedScore;
                $totalCoefficient += $seriesSubject->coefficient;
            }
        }
        
        $bulletinData['average'] = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_points'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient;
        $bulletinData['rank'] = $this->getTrimesterRank($trimesterNumber, $studentId);
        $bulletinData['mention'] = $this->getMention($bulletinData['average']);
        
        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'trimester');
        $bulletinData['first_average'] = 20; // TODO: Calculer vraiment
        $bulletinData['last_average'] = 5;   // TODO: Calculer vraiment
        $bulletinData['appreciation'] = $this->getAppreciation($bulletinData['average']);
        
        return $bulletinData;
    }
    
    /**
     * Get subject min/max scores for ranking
     */
    protected function getSubjectMinMax($sequenceId, $seriesSubjectId)
    {
        $grades = Grade::where('sequence_id', $sequenceId)
                      ->where('series_subject_id', $seriesSubjectId)
                      ->whereNotNull('score')
                      ->pluck('score')
                      ->map(function($score) {
                          return ($score / 20) * 20; // Convert to /20
                      });
        
        if ($grades->isEmpty()) {
            return 'N/A';
        }
        
        return round($grades->min(), 2) . '-' . round($grades->max(), 2);
    }
    
    /**
     * Get appreciation based on score
     */
    protected function getAppreciation($score)
    {
        if ($score === null) return 'Absent';
        if ($score >= 16) return 'Excellent';
        if ($score >= 14) return 'Tr�s Bien';
        if ($score >= 12) return 'Bien';
        if ($score >= 10) return 'Assez Bien';
        return 'Insuffisant';
    }
    
    /**
     * Get mention based on average
     */
    protected function getMention($average)
    {
        if ($average === null) return 'N/A';
        if ($average >= 16) return 'Tr�s Bien';
        if ($average >= 14) return 'Bien';
        if ($average >= 12) return 'Assez Bien';
        if ($average >= 10) return 'Passable';
        return 'Insuffisant';
    }
    
    /**
     * Get student rank in sequence
     */
    protected function getStudentRank($sequenceId, $studentId)
    {
        // TODO: Implement ranking logic based on overall sequence average
        return 1; // Placeholder
    }
    
    /**
     * Get student rank in trimester
     */
    protected function getTrimesterRank($trimesterNumber, $studentId)
    {
        // TODO: Implement ranking logic based on trimester average
        return 1; // Placeholder
    }
    
    /**
     * Get subject rank in trimester
     */
    protected function getTrimesterSubjectRank($trimesterNumber, $studentId, $subjectId)
    {
        // TODO: Implement subject ranking logic in trimester
        return 1; // Placeholder
    }
    
    /**
     * Get subject min/max scores for trimester ranking
     */
    protected function getTrimesterSubjectMinMax($trimesterNumber, $seriesSubjectId)
    {
        // Pour l'instant, on retourne un placeholder
        // TODO: Calculer le vrai min/max pour les trimestres
        return 'N/A';
    }
    
    /**
     * Generate PDF from HTML template
     */
    public function generatePDF($htmlContent, $filename)
    {        
        $options = new Options();
        $options->set('defaultFont', 'Times-Roman');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('dpi', 120);
        $options->set('debugKeepTemp', false);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdfContent = $dompdf->output();
        $filePath = 'public/bulletins/' . $filename;
        $fullPath = storage_path('app/' . $filePath);
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($fullPath, $pdfContent);
        
        return $filePath;
    }
    
    /**
     * Render bulletin template with data
     */
    public function renderBulletinTemplate($templateType, $data, $forPdf = false)
    {
        // RETOUR AU SYSTÈME ORIGINAL avec templates
        $templateFile = $forPdf ? 'cpbd_bulletin_pdf.html' : 'cpbd_bulletin.html';
        $templatePath = resource_path('views/bulletins/' . $templateFile);
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: {$templatePath}");
        }
        
        $html = file_get_contents($templatePath);
        
        // Prepare the template data
        $templateData = $this->prepareTemplateData($data, $templateType);
        
        // Replace simple placeholders
        foreach ($templateData as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace("{{" . $key . "}}", $value, $html);
            }
        }
        
        // Handle subject groups (more complex replacement)
        $html = $this->replaceBulletinSubjectGroups($html, $data, $forPdf);
        
        return $html;
    }
    
    
    /**
     * Prepare template data for the CPBD bulletin
     */
    protected function prepareTemplateData($data, $templateType = 'sequence')
    {
        $student = $data['student'];
        $sequence = $data['sequence'] ?? null;
        $trimester = $data['trimester'] ?? null;
        
        // Déterminer le type de bulletin et les labels appropriés
        $bulletinTypeLabel = 'Évaluation';
        $bulletinPeriod = 'N°1';
        
        // DEBUG: Log pour tracer le problème
        \Log::info("DEBUG prepareTemplateData: templateType=$templateType, trimester=" . ($trimester ? $trimester->number : 'null') . ", sequence=" . ($sequence ? $sequence->number : 'null'));
        
        if ($templateType === 'trimester' && $trimester) {
            $bulletinTypeLabel = 'Trimestre';
            $bulletinPeriod = 'N°' . $trimester->number;
            \Log::info("DEBUG: Set bulletinTypeLabel=Trimestre, bulletinPeriod=N°{$trimester->number}");
        } elseif ($templateType === 'sequence' && $sequence) {
            $bulletinTypeLabel = 'Évaluation';
            $bulletinPeriod = 'N°' . $sequence->number;
            \Log::info("DEBUG: Set bulletinTypeLabel=Évaluation, bulletinPeriod=N°{$sequence->number}");
        }
        
        // Get school settings and logo
        $schoolSettings = \App\Models\SchoolSetting::first();
        $logoBase64 = '';
        if ($schoolSettings && $schoolSettings->school_logo && file_exists(storage_path('app/public/' . $schoolSettings->school_logo))) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = "data:image/{$logoMime};base64,{$logoData}";
        }
        
        return [
            'student_name' => strtoupper($student->last_name . ' ' . $student->first_name),
            'birth_date' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
            'birth_place' => $student->place_of_birth ?? 'EMANA',
            'class_name' => $student->schoolClass->name ?? 'SIXIÈME A',
            'main_teacher' => 'TCHAMENI MATHIEU', // TODO: Get from database
            'class_size' => $this->getClassSize($student),
            'student_number' => $student->student_number ?? '24A856',
            'bulletin_type_label' => $bulletinTypeLabel,
            'bulletin_period' => $bulletinPeriod,
            // Maintenir la compatibilité avec les anciens templates
            'evaluation_number' => $sequence ? $sequence->number : ($trimester ? $trimester->number : '1'),
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'total_general' => number_format($data['total_points'] ?? 0, 2),
            'total_coef' => number_format($data['total_coefficient'] ?? 0, 2),
            'evaluation_average' => number_format($data['average'] ?? 0, 2),
            'average_class' => $this->getAverageClass($data['average'] ?? 0),
            'student_rank' => ($data['rank'] ?? 1) . 'e',
            'class_average' => '11,77', // TODO: Calculate real class average
            'first_average' => '15,36', // TODO: Calculate real first average
            'last_average' => '0,57', // TODO: Calculate real last average
            'general_appreciation' => $this->getGeneralAppreciation($data['average'] ?? 0),
            'logo_base64' => $logoBase64,
            'current_date' => now()->format('d/m/Y')
        ];
    }
    
    /**
     * Replace subject groups in the bulletin template
     */
    protected function replaceBulletinSubjectGroups($html, $data, $forPdf = false)
    {
        if (!isset($data['subjects']) || empty($data['subjects'])) {
            return str_replace('{{#each subject_groups}}{{/each}}', '', $html);
        }
        
        // Determine bulletin type based on data structure
        $bulletinType = isset($data['trimester']) ? 'trimester' : 'sequence';
        
        // Group subjects by type (simulate the groups from the example)
        $subjectGroups = $this->groupSubjectsByType($data['subjects']);
        
        $groupsHtml = '';
        foreach ($subjectGroups as $groupName => $subjects) {
            $groupsHtml .= $this->renderSubjectGroup($groupName, $subjects, $forPdf, $bulletinType);
        }
        
        // Replace the {{#each subject_groups}}...{{/each}} block
        $pattern = '/\{\{#each subject_groups\}\}(.*?)\{\{\/each\}\}/s';
        $html = preg_replace($pattern, $groupsHtml, $html);
        
        return $html;
    }
    
    /**
     * Group subjects by type for display
     */
    protected function groupSubjectsByType($subjects)
    {
        // For now, group all subjects into literary, scientific, and practical groups
        // This is a simplified version - you might want to add proper subject categorization
        $groups = [
            'GROUPE A : MATIÈRES LITTÉRAIRES' => [],
            'GROUPE B : MATIÈRES SCIENTIFIQUES' => [],
            'GROUPE C : MATIÈRES PRATIQUES' => [],
            'GROUPE D : AUTRES MATIÈRES' => []
        ];
        
        foreach ($subjects as $subject) {
            $subjectName = strtolower($subject['name']);
            
            if (in_array($subjectName, ['anglais', 'français', 'histoire', 'géographie', 'expression écrite', 'expression orale', 'étude de texte', 'orthographe', 'langues et cultures nationales'])) {
                $groups['GROUPE A : MATIÈRES LITTÉRAIRES'][] = $subject;
            } elseif (in_array($subjectName, ['mathématiques', 'sciences physiques', 'svt', 'sciences naturelles'])) {
                $groups['GROUPE B : MATIÈRES SCIENTIFIQUES'][] = $subject;
            } elseif (in_array($subjectName, ['eps', 'informatique', 'travail manuel', 'arts plastiques'])) {
                $groups['GROUPE C : MATIÈRES PRATIQUES'][] = $subject;
            } else {
                $groups['GROUPE D : AUTRES MATIÈRES'][] = $subject;
            }
        }
        
        // Remove empty groups
        return array_filter($groups, function($subjects) {
            return !empty($subjects);
        });
    }
    
    /**
     * Render a single subject group
     */
    protected function renderSubjectGroup($groupName, $subjects, $forPdf = false, $bulletinType = 'sequence')
    {
        $html = '<div class="grades-section" style="margin-bottom: 20px;">';
        $html .= '<div class="group-header" style="background: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000;">' . $groupName . '</div>';
        $html .= '<table class="subjects-table" style="width: 100%; border-collapse: collapse; border: 1px solid #000;">';
        
        // Header row avec largeurs fixes pour alignement
        $html .= '<tr style="background: #f8f8f8;">';
        
        if ($bulletinType === 'trimester') {
            // Pour bulletin trimestre : colonnes alignées avec largeurs fixes
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: left; width: 20%;">DISCIPLINE</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">DS1</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Compo1</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Moy</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">COEF.</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">(NXC)</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">RANG</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">COMPÉTENCES</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 20%;">NOMS DES PROFESSEURS</th>';
        } else {
            // Pour bulletin séquence : structure originale avec largeurs fixes
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: left; width: 25%;">DISCIPLINE</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">NOTES /20</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">COEF.</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">(NXC)</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">RANG</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 13%;">COMPÉTENCES</th>';
            $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 20%;">NOMS DES PROFESSEURS</th>';
        }
        
        $html .= '</tr>';
        
        $totalCoef = 0;
        $totalPoints = 0;
        $groupAverage = 0;
        
        foreach ($subjects as $subject) {
            $grade = $subject['score'] ?? 0;
            $coef = $subject['coefficient'] ?? 1;
            $weightedGrade = $grade * $coef;
            $gradeClass = $this->getGradeClass($grade);
            $competence = $this->getCompetence($grade);
            
            $totalCoef += $coef;
            $totalPoints += $weightedGrade;
            
            $html .= '<tr>';
            
            if ($bulletinType === 'trimester') {
                // Pour bulletin trimestre avec alignement
                $ds1 = $subject['ds'] ?? null;
                $compo1 = $subject['composition'] ?? null;
                $average = $subject['average'] ?? null;
                
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($ds1 !== null && $ds1 > 0 ? number_format($ds1, 2) : '-') . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($compo1 !== null && $compo1 > 0 ? number_format($compo1, 2) : '-') . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($average !== null && $average > 0 ? number_format($average, 2) : '-') . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($coef, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . number_format($weightedGrade, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? '1') . 'e</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $competence . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
            } else {
                // Pour bulletin séquence avec alignement
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($grade, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($coef, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . number_format($weightedGrade, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? '1') . 'e</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $competence . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $groupAverage = $totalCoef > 0 ? $totalPoints / $totalCoef : 0;
        
        // Total row avec alignement
        $html .= '<tr class="total-row" style="background: #f0f0f0; font-weight: bold;">';
        
        if ($bulletinType === 'trimester') {
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // DS1
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Compo1
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($groupAverage, 2) . '</td>'; // Moy
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>'; // COEF
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>'; // (NXC)
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">1e</td>'; // RANG
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format($groupAverage, 2) . ' ' . strtoupper(explode(' :', $groupName)[0]) . '</td>'; // COMPÉTENCES + NOMS DES PROFESSEURS
        } else {
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format($groupAverage, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Rang: 1e Moy Gen Gpe ' . number_format($groupAverage + 2, 2) . '</td>';
        }
        
        $html .= '</tr>';
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get CSS class for grade styling
     */
    protected function getGradeClass($grade)
    {
        if ($grade >= 16) return 'grade-excellent';
        if ($grade >= 14) return 'grade-good';
        if ($grade >= 10) return 'grade-average';
        return 'grade-poor';
    }
    
    /**
     * Get competence level based on grade
     */
    protected function getCompetence($grade)
    {
        if ($grade >= 16) return 'A+';
        if ($grade >= 14) return 'A';
        if ($grade >= 10) return 'ECA';
        return 'NA';
    }
    
    /**
     * Get average CSS class
     */
    protected function getAverageClass($average)
    {
        if ($average >= 16) return 'grade-excellent';
        if ($average >= 14) return 'grade-good';
        if ($average >= 10) return 'grade-average';
        return 'grade-poor';
    }
    
    /**
     * Get general appreciation
     */
    protected function getGeneralAppreciation($average)
    {
        if ($average >= 16) return 'Excellent (Très Bien)';
        if ($average >= 14) return 'Très Bien';
        if ($average >= 12) return 'Bien';
        if ($average >= 10) return 'Assez Bien (Passable)';
        return 'Non Acquise (NA)';
    }
    
    /**
     * Get class size
     */
    protected function getClassSize($student)
    {
        if ($student->schoolClass) {
            return $student->schoolClass->students()->count();
        }
        return 72; // Default value
    }
    
    /**
     * Build HTML rows for subjects in templates
     */
    protected function buildSubjectRowsHTML($subjects, $type = 'sequence')
    {
        $html = '';
        
        foreach ($subjects as $subject) {
            if ($type === 'sequence') {
                $html .= '<tr>';
                $html .= '<td style="text-align: left;">' . $subject['name'] . '<br><small>' . $subject['teacher'] . '</small></td>';
                $html .= '<td>' . ($subject['score'] ?? '-') . '</td>';
                $html .= '<td>' . $subject['coefficient'] . '</td>';
                $html .= '<td>' . ($subject['total'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['rank'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['grade'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['min_max'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['appreciation'] ?? '-') . '</td>';
                $html .= '</tr>';
            } elseif ($type === 'trimester') {
                $html .= '<tr>';
                $html .= '<td style="text-align: left;">' . $subject['name'] . '<br><small>' . ($subject['teacher'] ?? 'N/A') . '</small></td>';
                $html .= '<td>-</td><td>-</td><td>-</td>'; // C1, C2, C3 (à implémenter plus tard)
                $html .= '<td>' . ($subject['ds'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['composition'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['average'] ?? '-') . '</td>';
                $html .= '<td>' . $subject['coefficient'] . '</td>';
                $html .= '<td>' . ($subject['total'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['rank'] ?? '-') . '</td>';
                $html .= '<td>' . ($subject['grade'] ?? '-') . '</td>';
                $html .= '<td>-</td>'; // Min-Max (à calculer)
                $html .= '<td>-</td>'; // Appréciation
                $html .= '</tr>';
            }
        }
        
        return $html;
    }
}