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
     * PREMIER CYCLE: Trimestre 1/2: (DS + COMPOSITION) / 2, Trimestre 3: COMPOSITION uniquement
     * DEUXIÈME CYCLE: (Sequence 1 + Sequence 2 + COMPOSITION) / 3
     */
    public function calculateTrimesterGrade($trimester, $studentId, $subjectId, $cycleType = 'premier')
    {
        if ($cycleType === 'deuxieme') {
            return $this->calculateTrimesterGradeDeuxiemeCycle($trimester, $studentId, $subjectId);
        }

        // PREMIER CYCLE (logique existante)
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
     * Calculate trimester grade for DEUXIÈME CYCLE
     * Formule: (Sequence 1 + Sequence 2 + Composition) / 3
     */
    public function calculateTrimesterGradeDeuxiemeCycle($trimester, $studentId, $subjectId)
    {
        \Log::info("🎓 DEUXIÈME CYCLE: calculating trimester grade for trimester={$trimester}, student={$studentId}, subject={$subjectId}");

        // Récupérer les 2 séquences du trimestre
        $sequences = $this->getSequencesForTrimester($trimester);
        \Log::info("🎓 Sequences found: " . $sequences->pluck('number')->join(','));

        if ($sequences->count() != 2) {
            \Log::info("🎓 PROBLÈME: Expected 2 sequences, found " . $sequences->count());
            return null;
        }

        $sequenceGrades = [];
        $foundSequenceNotes = 0;

        // Récupérer les notes des 2 séquences
        foreach ($sequences as $sequence) {
            $grade = Grade::where('student_id', $studentId)
                         ->where('sequence_id', $sequence->id)
                         ->where('series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();

            if (!$grade) {
                // Chercher via les évaluations de la séquence
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('series_subject_id', $subjectId)
                                        ->get();

                foreach ($evaluations as $evaluation) {
                    $grade = Grade::where('student_id', $studentId)
                                 ->where('evaluation_id', $evaluation->id)
                                 ->where('trimester_id', $trimester)
                                 ->whereNotNull('score')
                                 ->first();

                    if ($grade) {
                        break;
                    }
                }
            }

            if ($grade) {
                $sequenceNote = $grade->getScoreOn20();
                $sequenceGrades[] = $sequenceNote;
                $foundSequenceNotes++;
                \Log::info("🎓 Sequence {$sequence->number} note: {$sequenceNote}");
            } else {
                \Log::info("🎓 No grade found for sequence {$sequence->number}");
            }
        }

        // Récupérer la composition
        $compositionGrade = $this->getCompositionGrade($trimester, $studentId, $subjectId);
        \Log::info("🎓 Composition grade: " . ($compositionGrade ?? 'null'));

        // RÈGLE DEUXIÈME CYCLE: Il faut au moins 2 notes pour calculer la moyenne
        $totalNotes = $foundSequenceNotes + ($compositionGrade !== null ? 1 : 0);

        if ($totalNotes < 2) {
            \Log::info("🎓 Insufficient data: only {$totalNotes} notes found, need at least 2");
            return null;
        }

        // Calcul de la moyenne: (Seq1 + Seq2 + Compo) / 3
        $allGrades = $sequenceGrades;
        if ($compositionGrade !== null) {
            $allGrades[] = $compositionGrade;
        }

        $average = array_sum($allGrades) / count($allGrades);
        \Log::info("🎓 DEUXIÈME CYCLE average calculated: {$average} from " . count($allGrades) . " notes");

        return $average;
    }

    /**
     * Get individual sequence grades for DEUXIÈME CYCLE display
     * Retourne [sequence1_grade, sequence2_grade] pour affichage
     */
    public function getIndividualSequenceGrades($trimester, $studentId, $subjectId)
    {
        \Log::info("🎓 Getting individual sequence grades for trimester={$trimester}, student={$studentId}, subject={$subjectId}");

        $sequences = $this->getSequencesForTrimester($trimester);
        $sequenceGrades = [null, null]; // [seq1, seq2]

        foreach ($sequences as $index => $sequence) {
            $grade = Grade::where('student_id', $studentId)
                         ->where('sequence_id', $sequence->id)
                         ->where('series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();

            if (!$grade) {
                // Chercher via les évaluations de la séquence
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('series_subject_id', $subjectId)
                                        ->get();

                foreach ($evaluations as $evaluation) {
                    $grade = Grade::where('student_id', $studentId)
                                 ->where('evaluation_id', $evaluation->id)
                                 ->where('trimester_id', $trimester)
                                 ->whereNotNull('score')
                                 ->first();

                    if ($grade) {
                        break;
                    }
                }
            }

            if ($grade && $index < 2) {
                $sequenceGrades[$index] = $grade->getScoreOn20();
                \Log::info("🎓 Sequence {$sequence->number} grade: {$sequenceGrades[$index]}");
            }
        }

        \Log::info("🎓 Individual sequence grades: [" . implode(', ', array_map(fn($g) => $g ?? 'null', $sequenceGrades)) . "]");
        return $sequenceGrades;
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

        // Detect section type for sequence bulletins
        $sectionType = $this->determineSectionType($student);
        
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
            'section_type' => $sectionType,
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
                'grade' => $this->getMentionBySection($scoreOn20, $sectionType),
                'competence' => $this->getCompetence($scoreOn20, 'premier', $sectionType),
                'min_max' => $this->getSubjectMinMax($sequence->id, $seriesSubject->id),
                'appreciation' => $this->getAppreciationBySection($scoreOn20, $sectionType),
                'section_type' => $sectionType
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
        $bulletinData['mention'] = $this->getMentionBySection($bulletinData['average'], $sectionType);

        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'sequence');
        $bulletinData['first_average'] = 20; // TODO: Calculer vraiment
        $bulletinData['last_average'] = 5;   // TODO: Calculer vraiment
        $bulletinData['appreciation'] = $this->getAppreciationBySection($bulletinData['average'], $sectionType);
        
        return $bulletinData;
    }
    
    /**
     * Generate trimester bulletin data for a student
     */
    public function generateTrimesterBulletinData($trimesterNumber, $studentId, $cycleType = 'premier')
    {
        $student = Student::with(['schoolClass', 'classSeries'])->find($studentId);
        if (!$student) return null;

        $trimester = Trimester::where('number', $trimesterNumber)->first();
        if (!$trimester) return null;

        // Detect section type (Anglophone and Technique use DEUXIÈME CYCLE logic)
        $sectionType = $this->determineSectionType($student);
        if ($sectionType === 'anglophone' || $sectionType === 'technique') {
            $cycleType = 'deuxieme'; // Force Anglophone and Technique to use DEUXIÈME CYCLE logic
        }
        
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
            \Log::info("🔍 Processing subject: {$seriesSubject->subject->name} (id={$seriesSubject->id}) for student {$studentId}, trimester={$trimesterNumber}, cycle={$cycleType}");

            if ($cycleType === 'deuxieme') {
                // DEUXIÈME CYCLE: Récupérer les notes individuelles
                $sequenceGrades = $this->getIndividualSequenceGrades($trimesterNumber, $studentId, $seriesSubject->id);
                $compositionGrade = $this->getCompositionGrade($trimesterNumber, $studentId, $seriesSubject->id);
                $trimesterGrade = $this->calculateTrimesterGrade($trimesterNumber, $studentId, $seriesSubject->id, 'deuxieme');

                \Log::info("🎓 DEUXIÈME CYCLE - {$seriesSubject->subject->name}: Seq1={$sequenceGrades[0]}, Seq2={$sequenceGrades[1]}, Compo={$compositionGrade}, Avg={$trimesterGrade}");

                $weightedScore = $trimesterGrade ? $trimesterGrade * $seriesSubject->coefficient : 0;

                // Get the first teacher assigned to this subject
                $teacherName = 'N/A';
                if ($seriesSubject->teachers && $seriesSubject->teachers->isNotEmpty()) {
                    $teacherName = $seriesSubject->teachers->first()->full_name;
                }

                $bulletinData['subjects'][] = [
                    'name' => $seriesSubject->subject->name,
                    'sequence1' => $sequenceGrades[0], // Nouvelle structure pour Deuxième Cycle
                    'sequence2' => $sequenceGrades[1], // Nouvelle structure pour Deuxième Cycle
                    'composition' => $compositionGrade,
                    'score' => $trimesterGrade, // Pour compatibilité avec le template
                    'average' => $trimesterGrade,
                    'coefficient' => $seriesSubject->coefficient,
                    'total' => $weightedScore,
                    'nxc' => $weightedScore, // NXC = Moy × COEF
                    'rank' => $this->getTrimesterSubjectRank($trimesterNumber, $studentId, $seriesSubject->id),
                    'grade' => $this->getMentionBySection($trimesterGrade, $sectionType),
                    'competence' => $this->getCompetence($trimesterGrade, 'deuxieme', $sectionType), // Compétences avec section
                    'teacher' => $teacherName,
                    'min_max' => $this->getTrimesterSubjectMinMax($trimesterNumber, $seriesSubject->id),
                    'appreciation' => $this->getAppreciationBySection($trimesterGrade, $sectionType),
                    'cycle_type' => 'deuxieme',
                    'section_type' => $sectionType
                ];

            } else {
                // PREMIER CYCLE: Logique existante
                $dsAverage = $this->calculateDSAverage($trimesterNumber, $studentId, $seriesSubject->id);
                \Log::info("🔍 DS Average for {$seriesSubject->subject->name}: " . ($dsAverage ?? 'null'));
                $compositionGrade = $this->getCompositionGrade($trimesterNumber, $studentId, $seriesSubject->id);
                \Log::info("🔍 Composition Grade for {$seriesSubject->subject->name}: " . ($compositionGrade ?? 'null'));
                $trimesterGrade = $this->calculateTrimesterGrade($trimesterNumber, $studentId, $seriesSubject->id, 'premier');
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
                    'appreciation' => $this->getAppreciation($trimesterGrade),
                    'cycle_type' => 'premier'
                ];
            }

            if ($trimesterGrade) {
                $totalPoints += $weightedScore;
                $totalCoefficient += $seriesSubject->coefficient;
            }
        }
        
        $bulletinData['average'] = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_points'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient;
        $bulletinData['rank'] = $this->getTrimesterRank($trimesterNumber, $studentId);
        $bulletinData['mention'] = $this->getMentionBySection($bulletinData['average'], $sectionType);
        $bulletinData['section_type'] = $sectionType;

        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'trimester');
        $bulletinData['first_average'] = 20; // TODO: Calculer vraiment
        $bulletinData['last_average'] = 5;   // TODO: Calculer vraiment
        $bulletinData['appreciation'] = $this->getAppreciationBySection($bulletinData['average'], $sectionType);
        
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
     * Detect section type (Francophone/Anglophone/Technique) from student
     */
    protected function determineSectionType($student)
    {
        $sectionName = $student->schoolClass->level->section->name ?? '';

        if (stripos($sectionName, 'anglophone') !== false ||
            stripos($sectionName, 'english') !== false ||
            stripos($sectionName, 'anglo') !== false) {
            return 'anglophone';
        }

        if (stripos($sectionName, 'technique') !== false ||
            stripos($sectionName, 'technical') !== false ||
            stripos($sectionName, 'enseignement technique') !== false) {
            return 'technique';
        }

        return 'francophone';
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

        // Detect section type for language adaptation
        $sectionType = $this->determineSectionType($data['student']);
        $data['section_type'] = $sectionType;

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
        $sectionType = $data['section_type'] ?? 'francophone';

        // Déterminer le type de bulletin et les labels appropriés selon la section
        $bulletinTypeLabel = 'Évaluation';
        $bulletinPeriod = 'N°1';

        if ($sectionType === 'anglophone') {
            // Anglophone translations
            $bulletinTypeLabel = $templateType === 'trimester' ? 'Semester' : 'Assessment';
            if ($templateType === 'trimester' && $trimester) {
                $bulletinPeriod = 'N°' . $trimester->number;
            } elseif ($templateType === 'sequence' && $sequence) {
                $bulletinPeriod = 'N°' . $sequence->number;
            }
        } else {
            // Francophone (original)
            if ($templateType === 'trimester' && $trimester) {
                $bulletinTypeLabel = 'Trimestre';
                $bulletinPeriod = 'N°' . $trimester->number;
            } elseif ($templateType === 'sequence' && $sequence) {
                $bulletinTypeLabel = 'Évaluation';
                $bulletinPeriod = 'N°' . $sequence->number;
            }
        }

        // DEBUG: Log pour tracer le problème
        \Log::info("DEBUG prepareTemplateData: templateType=$templateType, sectionType=$sectionType, trimester=" . ($trimester ? $trimester->number : 'null') . ", sequence=" . ($sequence ? $sequence->number : 'null'));
        
        // Get school settings and logo
        $schoolSettings = \App\Models\SchoolSetting::first();
        $logoBase64 = '';
        if ($schoolSettings && $schoolSettings->school_logo && file_exists(storage_path('app/public/' . $schoolSettings->school_logo))) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoMime = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = "data:image/{$logoMime};base64,{$logoData}";
        }
        
        // Language-specific labels
        $labels = $this->getLanguageLabels($sectionType);

        return [
            'student_name' => strtoupper($student->last_name . ' ' . $student->first_name),
            'birth_date' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
            'birth_place' => $student->place_of_birth ?? 'EMANA',
            'class_name' => $student->schoolClass->name ?? ($sectionType === 'anglophone' ? 'FORM 2A' : 'SIXIÈME A'),
            'main_teacher' => $sectionType === 'anglophone' ? 'MR. TCHAMENI MATHIEU' : 'TCHAMENI MATHIEU', // TODO: Get from database
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
            'student_rank' => ($data['rank'] ?? 1) . ($sectionType === 'anglophone' ? '' : 'e'),
            'class_average' => '11,77', // TODO: Calculate real class average
            'first_average' => '15,36', // TODO: Calculate real first average
            'last_average' => '0,57', // TODO: Calculate real last average
            'general_appreciation' => $this->getGeneralAppreciationBySection($data['average'] ?? 0, $sectionType),
            'logo_base64' => $logoBase64,
            'current_date' => now()->format('d/m/Y'),
            'section_type' => $sectionType,
            // Dynamic labels based on section
            'bulletin_title' => $labels['bulletin_title'],
            'name_label' => $labels['name_label'],
            'birth_date_label' => $labels['birth_date_label'],
            'birth_place_label' => $labels['birth_place_label'],
            'class_label' => $labels['class_label'],
            'class_size_label' => $labels['class_size_label'],
            'main_teacher_label' => $labels['main_teacher_label'],
            'student_number_label' => $labels['student_number_label'],
            'evaluation_label' => $labels['evaluation_label'],
            'school_year_label' => $labels['school_year_label'],
            'discipline_header' => $labels['discipline_header'],
            'general_results_header' => $labels['general_results_header'],
            'total_general_label' => $labels['total_general_label'],
            'total_coef_label' => $labels['total_coef_label'],
            'evaluation_average_label' => $labels['evaluation_average_label'],
            'rank_label' => $labels['rank_label'],
            'class_average_label' => $labels['class_average_label'],
            'first_average_label' => $labels['first_average_label'],
            'last_average_label' => $labels['last_average_label'],
            'appreciation_header' => $labels['appreciation_header'],
            'visa_parent_label' => $labels['visa_parent_label'],
            'made_in_douala' => $labels['made_in_douala'],
            'principal_title' => $labels['principal_title'],
            'legend_title' => $labels['legend_title'],
            'legend_items' => $labels['legend_items']
        ];
    }
    
    /**
     * Get language-specific labels based on section type
     */
    protected function getLanguageLabels($sectionType = 'francophone')
    {
        if ($sectionType === 'technique') {
            // ENSEIGNEMENT TECHNIQUE: French labels with technical terminology
            return [
                'bulletin_title' => 'BULLETIN DE NOTES - ENSEIGNEMENT TECHNIQUE',
                'name_label' => 'Nom et Prénoms:',
                'birth_date_label' => 'Date de naissance:',
                'birth_place_label' => 'Lieu de naissance:',
                'class_label' => 'Classe:',
                'class_size_label' => 'Effectif:',
                'main_teacher_label' => 'Enseignant titulaire:',
                'student_number_label' => 'Matricule:',
                'evaluation_label' => 'Évaluation:',
                'school_year_label' => 'ANNÉE SCOLAIRE:',
                'discipline_header' => 'DISCIPLINE TECHNIQUE',
                'general_results_header' => 'RÉSULTATS TECHNIQUES',
                'total_general_label' => 'TOTAL GÉNÉRAL:',
                'total_coef_label' => 'TOTAL COEF:',
                'evaluation_average_label' => 'MOY. TECHNIQUE:',
                'rank_label' => 'RANG:',
                'class_average_label' => 'MOY. GEN. CLASSE:',
                'first_average_label' => 'MOY. PREMIER:',
                'last_average_label' => 'MOY. DERNIER:',
                'appreciation_header' => 'APPRÉCIATIONS TECHNIQUES ET OBSERVATIONS',
                'visa_parent_label' => 'VISA & NOMS DU PARENT',
                'made_in_douala' => 'Fait à Douala, le',
                'principal_title' => 'Le Principal',
                'legend_title' => 'Légende:',
                'legend_items' => [
                    'Maîtrisé (Excellent)' => 'Expert Technique',
                    'Maîtrisé (Très Bien)' => 'Compétent',
                    'En cours de maîtrise' => 'En Apprentissage',
                    'Non maîtrisé' => 'À Renforcer'
                ]
            ];
        }

        if ($sectionType === 'anglophone') {
            return [
                'bulletin_title' => 'STUDENT REPORT CARD',
                'name_label' => 'Name and Surname:',
                'birth_date_label' => 'Date of birth:',
                'birth_place_label' => 'Place of birth:',
                'class_label' => 'Class:',
                'class_size_label' => 'Class size:',
                'main_teacher_label' => 'Main teacher:',
                'student_number_label' => 'Student number:',
                'evaluation_label' => 'Assessment:',
                'school_year_label' => 'SCHOOL YEAR:',
                'discipline_header' => 'DISCIPLINE',
                'general_results_header' => 'GENERAL RESULTS',
                'total_general_label' => 'GENERAL TOTAL:',
                'total_coef_label' => 'TOTAL COEF:',
                'evaluation_average_label' => 'ASSESSMENT AVG:',
                'rank_label' => 'RANK:',
                'class_average_label' => 'CLASS AVERAGE:',
                'first_average_label' => 'FIRST AVERAGE:',
                'last_average_label' => 'LAST AVERAGE:',
                'appreciation_header' => 'WORK ASSESSMENT AND OBSERVATIONS',
                'visa_parent_label' => 'PARENT VISA & NAME',
                'made_in_douala' => 'Done in Douala, on',
                'principal_title' => 'The Principal',
                'legend_title' => 'Legend:',
                'legend_items' => [
                    'Mastered (Excellent)' => 'Expert',
                    'Mastered (Very Good)' => 'Mastered',
                    'Developing' => 'Developing',
                    'Beginning' => 'Beginning'
                ]
            ];
        }

        // Francophone (French) labels
        return [
            'bulletin_title' => 'BULLETIN DE NOTES',
            'name_label' => 'Nom et Prénoms:',
            'birth_date_label' => 'Date de naissance:',
            'birth_place_label' => 'Lieu de naissance:',
            'class_label' => 'Classe:',
            'class_size_label' => 'Effectif:',
            'main_teacher_label' => 'Enseignant titulaire:',
            'student_number_label' => 'Matricule:',
            'evaluation_label' => 'Évaluation:',
            'school_year_label' => 'ANNÉE SCOLAIRE:',
            'discipline_header' => 'DISCIPLINE',
            'general_results_header' => 'RÉSULTATS GÉNÉRAUX',
            'total_general_label' => 'TOTAL GÉNÉRAL:',
            'total_coef_label' => 'TOTAL COEF:',
            'evaluation_average_label' => 'MOY. ÉVALUATION:',
            'rank_label' => 'RANG:',
            'class_average_label' => 'MOY. GEN. CLASSE:',
            'first_average_label' => 'MOY. PREMIER:',
            'last_average_label' => 'MOY. DERNIER:',
            'appreciation_header' => 'APPRÉCIATIONS DU TRAVAIL ET OBSERVATIONS',
            'visa_parent_label' => 'VISA & NOMS DU PARENT',
            'made_in_douala' => 'Fait à Douala, le',
            'principal_title' => 'Le Principal',
            'legend_title' => 'Légende:',
            'legend_items' => [
                'A+' => 'Expert',
                'A' => 'Acquise',
                'ECA' => 'En Cours d\'Acquisition',
                'NA' => 'Non Acquise'
            ]
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
        // Déterminer le type de cycle et de section à partir des matières
        $cycleType = 'premier'; // Par défaut
        $sectionType = 'francophone'; // Par défaut
        if (!empty($subjects)) {
            $cycleType = $subjects[0]['cycle_type'] ?? 'premier';
            $sectionType = $subjects[0]['section_type'] ?? 'francophone';
        }

        // Traduire le nom du groupe si Anglophone
        $translatedGroupName = $groupName;
        if ($sectionType === 'anglophone') {
            $translations = [
                'GROUPE A : MATIÈRES LITTÉRAIRES' => 'GROUP A: LITERARY SUBJECTS',
                'GROUPE B : MATIÈRES SCIENTIFIQUES' => 'GROUP B: SCIENTIFIC SUBJECTS',
                'GROUPE C : MATIÈRES PRATIQUES' => 'GROUP C: PRACTICAL SUBJECTS',
                'GROUPE D : AUTRES MATIÈRES' => 'GROUP D: OTHER SUBJECTS'
            ];
            $translatedGroupName = $translations[$groupName] ?? $groupName;
        }

        $html = '<div class="grades-section" style="margin-bottom: 20px;">';
        $html .= '<div class="group-header" style="background: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000;">' . $translatedGroupName . '</div>';
        $html .= '<table class="subjects-table" style="width: 100%; border-collapse: collapse; border: 1px solid #000;">';

        // Header row avec largeurs fixes pour alignement
        $html .= '<tr style="background: #f8f8f8;">';

        if ($bulletinType === 'trimester') {
            if ($cycleType === 'deuxieme') {
                // 🎓 DEUXIÈME CYCLE: 11 colonnes avec Sequence 1 et Sequence 2 séparées
                if ($sectionType === 'anglophone') {
                    // English headers for Anglophone section
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: left; width: 15%;">SUBJECT</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Term 1</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Term 2</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Final Exam</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Avg./20</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">COEF.</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">(NXC)</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">TOTAL</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">RANK</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">COMPETENCY</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 13%;">TEACHER NAMES</th>';
                } else {
                    // French headers for Francophone section
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: left; width: 15%;">DISCIPLINE</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Sequence 1</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Sequence 2</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Compo1</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Moy./20</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">COEF.</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">(NXC)</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">TOTAL</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">RANG</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">COMPÉTENCES</th>';
                    $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 13%;">NOMS DES PROFESSEURS</th>';
                }
            } else {
                // 📚 PREMIER CYCLE: 9 colonnes avec DS1 (moyenne cachée)
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: left; width: 20%;">DISCIPLINE</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">DS1</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Compo1</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Moy</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">COEF.</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">(NXC)</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">RANG</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">COMPÉTENCES</th>';
                $html .= '<th style="border: 1px solid #000; padding: 5px; text-align: center; width: 20%;">NOMS DES PROFESSEURS</th>';
            }
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
            // Pour DEUXIÈME CYCLE, utiliser 'average', sinon 'score'
            $grade = ($cycleType === 'deuxieme') ? ($subject['average'] ?? 0) : ($subject['score'] ?? 0);
            $coef = $subject['coefficient'] ?? 1;
            $weightedGrade = $grade * $coef;
            $gradeClass = $this->getGradeClass($grade);
            $competence = $subject['competence'] ?? $this->getCompetence($grade, $cycleType);

            $totalCoef += $coef;
            $totalPoints += $weightedGrade;

            $html .= '<tr>';

            if ($bulletinType === 'trimester') {
                if ($cycleType === 'deuxieme') {
                    // 🎓 DEUXIÈME CYCLE: 11 colonnes avec séquences individuelles
                    $seq1 = $subject['sequence1'] ?? null;
                    $seq2 = $subject['sequence2'] ?? null;
                    $compo1 = $subject['composition'] ?? null;
                    $average = $subject['average'] ?? null;
                    $nxc = $subject['nxc'] ?? $weightedGrade;

                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($seq1 !== null && $seq1 > 0 ? number_format($seq1, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($seq2 !== null && $seq2 > 0 ? number_format($seq2, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($compo1 !== null && $compo1 > 0 ? number_format($compo1, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($average !== null && $average > 0 ? number_format($average, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($coef, 2) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . number_format($nxc, 2) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . number_format($nxc, 2) . '</td>'; // TOTAL = NXC
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? '1') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 11px;">' . $competence . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 11px;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
                } else {
                    // 📚 PREMIER CYCLE: 9 colonnes avec DS1 (moyenne cachée)
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
                }
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
            if ($cycleType === 'deuxieme') {
                // 🎓 DEUXIÈME CYCLE: 11 colonnes - ligne de total
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Sequence 1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Sequence 2
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Compo1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($groupAverage, 2) . '</td>'; // Moy./20
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>'; // COEF
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>'; // (NXC)
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>'; // TOTAL
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // RANG
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px;">' . strtoupper(explode(' :', $groupName)[0]) . '</td>'; // COMPÉTENCES
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px;">Moy Gpe: ' . number_format($groupAverage, 2) . '</td>'; // PROFESSEURS
            } else {
                // 📚 PREMIER CYCLE: 9 colonnes - ligne de total
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // DS1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Compo1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($groupAverage, 2) . '</td>'; // Moy
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>'; // COEF
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>'; // (NXC)
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // RANG
                $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format($groupAverage, 2) . ' ' . strtoupper(explode(' :', $groupName)[0]) . '</td>'; // COMPÉTENCES + PROFESSEURS
            }
        } else {
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format($groupAverage, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy Gen Gpe: ' . number_format($groupAverage, 2) . '</td>';
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
    protected function getCompetence($grade, $cycleType = 'premier', $sectionType = 'francophone')
    {
        if ($sectionType === 'technique') {
            // ENSEIGNEMENT TECHNIQUE: Compétences techniques spécialisées
            if ($grade === null) return 'Non évaluée';
            if ($grade >= 16) return 'Maîtrisé (Excellent)';
            if ($grade >= 14) return 'Maîtrisé (Très Bien)';
            if ($grade >= 12) return 'Maîtrisé (Bien)';
            if ($grade >= 10) return 'En cours de maîtrise';
            return 'Non maîtrisé';
        }

        if ($sectionType === 'anglophone') {
            // ANGLOPHONE: Use DEUXIÈME CYCLE logic with English translations
            if ($grade === null) return 'Not Assessed';
            if ($grade >= 16) return 'Mastered (Excellent)';
            if ($grade >= 14) return 'Mastered (Very Good)';
            if ($grade >= 12) return 'Mastered (Good)';
            if ($grade >= 10) return 'Developing';
            return 'Beginning';
        }

        if ($cycleType === 'deuxieme') {
            // DEUXIÈME CYCLE FRANCOPHONE: Compétences détaillées
            if ($grade === null) return 'Non évaluée';
            if ($grade >= 16) return 'Acquise (Excellent)';
            if ($grade >= 14) return 'Acquise (Très Bien)';
            if ($grade >= 12) return 'Acquise (Bien)';
            if ($grade >= 10) return 'En cours d\'acquisition';
            return 'Non acquise';
        }

        // PREMIER CYCLE FRANCOPHONE: Compétences simples
        if ($grade >= 16) return 'A+';
        if ($grade >= 14) return 'A';
        if ($grade >= 10) return 'ECA';
        return 'NA';
    }

    /**
     * Get appreciation based on score
     */
    protected function getAppreciationBySection($score, $sectionType = 'francophone')
    {
        if ($sectionType === 'anglophone') {
            if ($score === null) return 'Absent';
            if ($score >= 16) return 'Excellent';
            if ($score >= 14) return 'Very Good';
            if ($score >= 12) return 'Good';
            if ($score >= 10) return 'Average';
            return 'Below Average';
        }

        // Francophone (original)
        if ($score === null) return 'Absent';
        if ($score >= 16) return 'Excellent';
        if ($score >= 14) return 'Très Bien';
        if ($score >= 12) return 'Bien';
        if ($score >= 10) return 'Assez Bien';
        return 'Insuffisant';
    }

    /**
     * Get mention based on average with section support
     */
    protected function getMentionBySection($average, $sectionType = 'francophone')
    {
        if ($sectionType === 'anglophone') {
            if ($average === null) return 'N/A';
            if ($average >= 16) return 'Very Good';
            if ($average >= 14) return 'Good';
            if ($average >= 12) return 'Average';
            if ($average >= 10) return 'Pass';
            return 'Fail';
        }

        // Francophone (original)
        if ($average === null) return 'N/A';
        if ($average >= 16) return 'Très Bien';
        if ($average >= 14) return 'Bien';
        if ($average >= 12) return 'Assez Bien';
        if ($average >= 10) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Get general appreciation with section support
     */
    protected function getGeneralAppreciationBySection($average, $sectionType = 'francophone')
    {
        if ($sectionType === 'anglophone') {
            if ($average >= 16) return 'Excellent (Very Good)';
            if ($average >= 14) return 'Very Good';
            if ($average >= 12) return 'Good';
            if ($average >= 10) return 'Average (Pass)';
            return 'Beginning (Fail)';
        }

        // Francophone (original)
        if ($average >= 16) return 'Excellent (Très Bien)';
        if ($average >= 14) return 'Très Bien';
        if ($average >= 12) return 'Bien';
        if ($average >= 10) return 'Assez Bien (Passable)';
        return 'Non Acquise (NA)';
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