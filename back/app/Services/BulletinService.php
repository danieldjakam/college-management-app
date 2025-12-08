<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Evaluation;
use App\Models\SeriesSubject;
use App\Models\ClassSeriesSubject;
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
        // \Log::info("🔍 DEBUG DS: trimester={$trimester}, student={$studentId}, subject={$subjectId}");
        
        $sequences = $this->getSequencesForTrimester($trimester);
        // \Log::info("🔍 Sequences found: " . $sequences->pluck('number')->join(','));
        
        if ($sequences->count() != 2) {
            // \Log::info("🔍 PROBLÈME: Expected 2 sequences, found " . $sequences->count());
            return null;
        }
        
        $grades = collect();
        $foundSequenceNotes = 0;
        
        foreach ($sequences as $sequence) {
            // \Log::info("🔍 Checking sequence {$sequence->number} (id={$sequence->id})");
            $sequenceNote = null;
            
            // 🔧 FIX: Ajouter trimester dans la requête comme TrimesterController
            $grade = Grade::where('student_id', $studentId)
                         ->where('sequence_id', $sequence->id)
                         ->where('class_series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();
            
            // \Log::info("🔍 Direct grade found: " . ($grade ? "score={$grade->score}" : "none"));
            
            // Si pas de note directe, chercher via les évaluations de la séquence
            if (!$grade) {
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('class_series_subject_id', $subjectId)
                                        ->get();
                
                // \Log::info("🔍 Evaluations for sequence: " . $evaluations->count());
                
                foreach ($evaluations as $evaluation) {
                    $grade = Grade::where('student_id', $studentId)
                                 ->where('evaluation_id', $evaluation->id)
                                 ->where('trimester_id', $trimester)
                                 ->whereNotNull('score')
                                 ->first();
                    
                    if ($grade) {
                        // \Log::info("🔍 Grade found via evaluation {$evaluation->id}: score={$grade->score}");
                        break; // Prendre la première note trouvée pour cette séquence
                    }
                }
            }
            
            if ($grade) {
                $sequenceNote = $grade->getScoreOn20();
                $grades->push($sequenceNote);
                $foundSequenceNotes++;
                // \Log::info("🔍 Sequence {$sequence->number} note: {$sequenceNote}");
            } else {
                // \Log::info("🔍 No grade found for sequence {$sequence->number}");
            }
        }
        
        // \Log::info("🔍 Found {$foundSequenceNotes} sequence notes out of 2");

        // RÈGLE ACADÉMIQUE PROGRESSIVE avec 0.00 par défaut:
        // - Les séquences non saisies comptent comme 0.00
        // - DS = (Seq1 + Seq2) / 2 où Seq manquante = 0.00

        // Compléter avec des 0.00 pour les séquences manquantes
        while ($grades->count() < 2) {
            $grades->push(0.00);
        }

        $dsAverage = $grades->average();
        // \Log::info("🔍 DS CALCULATED: {$dsAverage} = ({$grades[0]} + {$grades[1]}) / 2");
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

        // RÈGLE ACADÉMIQUE PROGRESSIVE avec 0.00 par défaut:
        // - DS et Composition non saisies comptent comme 0.00
        // - M/20 = (DS + Composition) / 2 où valeur manquante = 0.00

        // Si DS existe (même si = 0 car séquences = 0)
        if ($dsAverage !== null) {
            // Composition = note saisie ou 0.00 si pas saisie
            $finalCompositionGrade = ($compositionGrade !== null && $compositionGrade !== 'ABS') ? $compositionGrade : 0.00;
            // \Log::info("🔍 M/20 = ({$dsAverage} + {$finalCompositionGrade}) / 2");
            return ($dsAverage + $finalCompositionGrade) / 2;
        }

        // Si seulement composition disponible (cas rare où DS impossible à calculer)
        if ($compositionGrade !== null && $compositionGrade !== 'ABS' && $dsAverage === null) {
            return $compositionGrade;
        }

        return null; // Vraiment aucune donnée (pas même une séquence)
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
                         ->where('class_series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();

            if (!$grade) {
                // Chercher via les évaluations de la séquence
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('class_series_subject_id', $subjectId)
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
                // Séquence manquante = 0.00
                $sequenceGrades[] = 0.00;
                \Log::info("🎓 No grade found for sequence {$sequence->number}, using 0.00");
            }
        }

        // Récupérer la composition
        $compositionGrade = $this->getCompositionGrade($trimester, $studentId, $subjectId);
        \Log::info("🎓 Composition grade: " . ($compositionGrade ?? 'null'));

        // OPTION B: Si AUCUNE note (ni Seq1, ni Seq2, ni Compo) → retourner null (annulation coefficient)
        // Si AU MOINS UNE note présente → les autres comptent comme 0.00 (pénalisation)

        // Compter combien de notes sont présentes
        $notesPresentes = 0;
        if ($sequenceGrades[0] !== null && $sequenceGrades[0] !== 'ABS') $notesPresentes++;
        if ($sequenceGrades[1] !== null && $sequenceGrades[1] !== 'ABS') $notesPresentes++;
        if ($compositionGrade !== null && $compositionGrade !== 'ABS') $notesPresentes++;

        // Si AUCUNE note du tout → retourner null
        if ($notesPresentes === 0) {
            \Log::info("🎓 DEUXIÈME CYCLE - Aucune note présente → moyenne = null (coefficient annulé)");
            return null;
        }

        // Sinon, calculer moyenne avec notes manquantes = 0.00
        $finalSeq1 = ($sequenceGrades[0] !== null && $sequenceGrades[0] !== 'ABS') ? $sequenceGrades[0] : 0.00;
        $finalSeq2 = ($sequenceGrades[1] !== null && $sequenceGrades[1] !== 'ABS') ? $sequenceGrades[1] : 0.00;
        $finalCompositionGrade = ($compositionGrade !== null && $compositionGrade !== 'ABS') ? $compositionGrade : 0.00;

        $average = ($finalSeq1 + $finalSeq2 + $finalCompositionGrade) / 3;
        \Log::info("🎓 DEUXIÈME CYCLE M/20 = ({$finalSeq1} + {$finalSeq2} + {$finalCompositionGrade}) / 3 = {$average}");

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
                         ->where('class_series_subject_id', $subjectId)
                         ->where('trimester_id', $trimester)
                         ->whereNotNull('score')
                         ->first();

            if (!$grade) {
                // Chercher via les évaluations de la séquence
                $evaluations = Evaluation::where('sequence_id', $sequence->id)
                                        ->where('class_series_subject_id', $subjectId)
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

            // Vérifier aussi si marqué absent (même sans note)
            if (!$grade) {
                $grade = Grade::where('student_id', $studentId)
                             ->where('sequence_id', $sequence->id)
                             ->where('class_series_subject_id', $subjectId)
                             ->where('trimester_id', $trimester)
                             ->where('is_absent', true)
                             ->first();
            }

            if ($grade && $index < 2) {
                if ($grade->is_absent) {
                    // Marqué absent: on met 'ABS' comme marqueur
                    $sequenceGrades[$index] = 'ABS';
                    \Log::info("🎓 Sequence {$sequence->number}: ABSENT");
                } else {
                    $sequenceGrades[$index] = $grade->getScoreOn20();
                    \Log::info("🎓 Sequence {$sequence->number} grade: {$sequenceGrades[$index]}");
                }
            }
        }

        // ⚡ Log réduit en DEBUG pour éviter pollution
        // \Log::debug("Sequence grades: [" . implode(', ', array_map(fn($g) => $g ?? 'null', $sequenceGrades)) . "]");
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
                               ->where('class_series_subject_id', $subjectId)
                               ->first();
        
        // RETOUR NULL SI AUCUNE COMPOSITION TROUVÉE
        if (!$evaluation) {
            // ⚡ Réduit en DEBUG pour éviter pollution des logs (450× par génération)
            // \Log::debug("Aucune composition trouvée pour trim:{$trimester}, student:{$studentId}, subject:{$subjectId}");
            return null;
        }
        
        $grade = Grade::where('student_id', $studentId)
                     ->where('evaluation_id', $evaluation->id)
                     ->where('trimester_id', $trimester)
                     ->whereNotNull('score')
                     ->first();

        // Vérifier aussi si marqué absent (même sans note)
        if (!$grade) {
            $grade = Grade::where('student_id', $studentId)
                         ->where('evaluation_id', $evaluation->id)
                         ->where('trimester_id', $trimester)
                         ->where('is_absent', true)
                         ->first();
        }

        if ($grade) {
            if ($grade->is_absent) {
                \Log::info("Composition: ABSENT (evaluation_id:{$evaluation->id})");
                return 'ABS';
            }
            $result = $grade->getScoreOn20();
            // ⚡ Log réduit en DEBUG (450× par génération = pollution)
            // \Log::debug("Composition trouvée: eval:{$evaluation->id}, grade:{$result}");
            return $result;
        }

        // ⚡ Log réduit en DEBUG pour éviter pollution
        // \Log::debug("Aucune note de composition (evaluation_id:{$evaluation->id})");
        return null;
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

        $subjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();
        $trimesterAverages = [];
        
        for ($trimester = 1; $trimester <= 3; $trimester++) {
            $subjectGrades = [];
            
            foreach ($subjects as $subject) {
                $grade = $this->calculateTrimesterGrade($trimester, $studentId, $subject->id);
                if ($grade !== null) {
                    $subjectGrades[] = (float)$grade * (float)$subject->coefficient;
                }
            }

            if (count($subjectGrades) > 0) {
                $totalCoefficient = (float)$subjects->sum('coefficient');
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
        // 🚀 OPTIMISATION: Eager load toutes les relations nécessaires en une seule requête
        $student = Student::with([
            'schoolClass', // Charger toute la relation pour éviter l'ambiguïté SQL
            'classSeries',
            'classSeries.students:id,class_series_id' // Pour compter les élèves
        ])->find($studentId);

        if (!$student) return null;

        $sequence = Sequence::where('number', $sequenceNumber)->first();
        if (!$sequence) return null;

        // Detect section type for sequence bulletins
        $sectionType = $this->determineSectionType($student);

        // 🚀 OPTIMISATION: Cache l'année scolaire pour éviter la requête répétée
        $currentSchoolYear = \Cache::remember('current_school_year', 3600, function() {
            return \App\Models\SchoolYear::where('is_active', true)->first();
        });
        $schoolYearId = $currentSchoolYear ? $currentSchoolYear->id : null;

        // 🚀 OPTIMISATION: Eager load subject + teachers + grades en une seule requête
        $subjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)
                                 ->with([
                                     'subject', // Charger toute la relation pour éviter l'ambiguïté SQL
                                     'teachers' => function($query) use ($schoolYearId) {
                                         $query->wherePivot('is_active', true);
                                         if ($schoolYearId) {
                                             $query->wherePivot('school_year_id', $schoolYearId);
                                         }
                                     }
                                 ])
                                 ->get();

        // 🚀 OPTIMISATION: Charger TOUTES les notes en une seule requête
        $gradesCollection = Grade::where('student_id', $studentId)
                                 ->where('sequence_id', $sequence->id)
                                 ->whereIn('class_series_subject_id', $subjects->pluck('id'))
                                 ->get()
                                 ->keyBy('class_series_subject_id');
        
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
            'class_name' => $student->classSeries->name ?? $student->schoolClass->name ?? 'N/A',
            'class_size' => $student->classSeries ? $student->classSeries->students()->count() : 57,
            'sequence_number' => $sequenceNumber,
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'subjects_rows' => ''
        ];
        
        $totalPoints = 0;
        $totalCoefficient = 0;

        foreach ($subjects as $seriesSubject) {
            // 🚀 OPTIMISATION: Utiliser les grades préchargées au lieu de faire une requête
            $grade = $gradesCollection->get($seriesSubject->id);

            // Vérifier si absent
            $scoreOn20 = null;
            if ($grade) {
                if ($grade->is_absent) {
                    $scoreOn20 = 'ABS'; // Marqueur pour absent
                } else {
                    $scoreOn20 = $grade->getScoreOn20();
                }
            }

            // OPTION B: Si pas de note (null), on n'ajoute NI le coefficient NI les points
            // Si note présente (même 0.5), on compte tout normalement
            $weightedScore = ($scoreOn20 !== null && $scoreOn20 !== 'ABS') ? (float)$scoreOn20 * (float)$seriesSubject->coefficient : null;

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
                'section_type' => $sectionType,
                'class_size' => $bulletinData['class_size'] ?? 57 // Pour le rang par défaut
            ];

            // OPTION B: Compter UNIQUEMENT les matières avec notes (annulation du coefficient si pas de note)
            if ($scoreOn20 !== null && $scoreOn20 !== 'ABS' && $weightedScore !== null) {
                $totalPoints += $weightedScore;
                $totalCoefficient += (float)$seriesSubject->coefficient;
            }
        }

        // OPTION B: Moyenne = Total Points / SEULEMENT les coefficients des matières composées
        $bulletinData['average'] = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_points'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient; // Uniquement les coefficients des matières avec notes
        $bulletinData['rank'] = $this->getStudentRank($sequence->id, $studentId);
        $bulletinData['mention'] = $this->getMentionBySection($bulletinData['average'], $sectionType);

        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'sequence');

        // Calculate real class statistics using class_series_id
        $classStats = $this->calculateClassStatistics($sequence->id, $student->class_series_id, 'sequence');
        $bulletinData['first_average'] = $classStats['first_average'];
        $bulletinData['last_average'] = $classStats['last_average'];
        $bulletinData['class_average'] = $classStats['class_average'];
        $bulletinData['class_size'] = $classStats['class_size'];

        $bulletinData['appreciation'] = $this->getAppreciationBySection($bulletinData['average'], $sectionType);

        // Récupérer les données de discipline pour cette séquence
        $discipline = \App\Models\StudentDiscipline::where('student_id', $studentId)
            ->where('sequence_id', $sequence->id)
            ->first();

        $bulletinData['discipline'] = [
            'delays_justified' => $discipline->delays_justified ?? 0,
            'delays_unjustified' => $discipline->delays_unjustified ?? 0,
            'absences_justified' => $discipline->absences_justified ?? 0,
            'absences_unjustified' => $discipline->absences_unjustified ?? 0,
            'blame_conduct' => $discipline->blame_conduct ?? 0,
            'blame_work' => $discipline->blame_work ?? 0,
            'warning_conduct' => $discipline->warning_conduct ?? 0,
            'warning_work' => $discipline->warning_work ?? 0,
            'detention_hours' => $discipline->detention_hours ?? 0,
            'exclusion_days' => $discipline->exclusion_days ?? 0,
            'observations' => $discipline->observations ?? ''
        ];

        return $bulletinData;
    }
    
    /**
     * Generate trimester bulletin data for a student
     */
    public function generateTrimesterBulletinData($trimesterNumber, $studentId, $cycleType = 'premier')
    {
        // 🚀 OPTIMISATION: Eager load toutes les relations nécessaires en une seule requête
        $student = Student::with([
            'schoolClass', // Charger toute la relation pour éviter l'ambiguïté SQL
            'classSeries',
            'classSeries.students:id,class_series_id' // Pour compter les élèves
        ])->find($studentId);

        if (!$student) return null;

        $trimester = Trimester::where('number', $trimesterNumber)->first();
        if (!$trimester) return null;

        // Detect section type (Anglophone and Technique use DEUXIÈME CYCLE logic)
        $sectionType = $this->determineSectionType($student);
        if ($sectionType === 'anglophone' || $sectionType === 'technique') {
            $cycleType = 'deuxieme'; // Force Anglophone and Technique to use DEUXIÈME CYCLE logic
        }

        // 🚀 OPTIMISATION: Cache l'année scolaire pour éviter la requête répétée
        $currentSchoolYear = \Cache::remember('current_school_year', 3600, function() {
            return \App\Models\SchoolYear::where('is_active', true)->first();
        });
        $schoolYearId = $currentSchoolYear ? $currentSchoolYear->id : null;

        // 🚀 OPTIMISATION: Eager load subject + teachers
        $subjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)
                                 ->with([
                                     'subject', // Charger toute la relation pour éviter l'ambiguïté SQL
                                     'teachers' => function($query) use ($schoolYearId) {
                                         $query->wherePivot('is_active', true);
                                         if ($schoolYearId) {
                                             $query->wherePivot('school_year_id', $schoolYearId);
                                         }
                                     }
                                 ])
                                 ->get();

        // 🚀 OPTIMISATION: Précharger TOUTES les notes du trimestre en une seule requête
        // Récupérer les séquences de ce trimestre pour charger toutes les notes
        $sequences = $this->getSequencesForTrimester($trimesterNumber);
        $allGrades = Grade::where('student_id', $studentId)
                          ->where('trimester_id', $trimesterNumber)
                          ->whereIn('class_series_subject_id', $subjects->pluck('id'))
                          ->whereIn('sequence_id', $sequences->pluck('id'))
                          ->get()
                          ->groupBy('class_series_subject_id');
        
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
            'class_name' => $student->classSeries->name ?? $student->schoolClass->name ?? 'N/A',
            'class_size' => $student->classSeries ? $student->classSeries->students()->count() : 57,
            'trimester_number' => $trimesterNumber,
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'subjects_rows' => ''
        ];
        
        $totalPoints = 0;
        $totalCoefficient = 0;

        foreach ($subjects as $seriesSubject) {
            // \Log::info("🔍 Processing subject: {$seriesSubject->subject->name} (id={$seriesSubject->id}) for student {$studentId}, trimester={$trimesterNumber}, cycle={$cycleType}");

            if ($cycleType === 'deuxieme') {
                // DEUXIÈME CYCLE: Récupérer les notes individuelles
                $sequenceGrades = $this->getIndividualSequenceGrades($trimesterNumber, $studentId, $seriesSubject->id);
                $compositionGrade = $this->getCompositionGrade($trimesterNumber, $studentId, $seriesSubject->id);
                $trimesterGrade = $this->calculateTrimesterGrade($trimesterNumber, $studentId, $seriesSubject->id, 'deuxieme');

                \Log::info("🎓 DEUXIÈME CYCLE - {$seriesSubject->subject->name}: Seq1={$sequenceGrades[0]}, Seq2={$sequenceGrades[1]}, Compo={$compositionGrade}, Avg={$trimesterGrade}");

                // OPTION B: Si pas de note, on n'ajoute NI le coefficient NI les points
                $weightedScore = $trimesterGrade !== null ? (float)$trimesterGrade * (float)$seriesSubject->coefficient : null;

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
                    'nxc' => $weightedScore, // NXC = Moy × COEF (ou null si absent)
                    'rank' => $this->getTrimesterSubjectRank($trimesterNumber, $studentId, $seriesSubject->id),
                    'grade' => $this->getMentionBySection($trimesterGrade, $sectionType),
                    'competence' => $this->getCompetence($trimesterGrade, 'deuxieme', $sectionType), // Compétences avec section
                    'teacher' => $teacherName,
                    'min_max' => $this->getTrimesterSubjectMinMax($trimesterNumber, $seriesSubject->id),
                    'appreciation' => $this->getAppreciationBySection($trimesterGrade, $sectionType),
                    'cycle_type' => 'deuxieme',
                    'section_type' => $sectionType,
                    'class_size' => $bulletinData['class_size'] ?? 57 // Pour le rang par défaut
                ];

            } else {
                // PREMIER CYCLE: Logique existante
                $dsAverage = $this->calculateDSAverage($trimesterNumber, $studentId, $seriesSubject->id);
                // \Log::info("🔍 DS Average for {$seriesSubject->subject->name}: " . ($dsAverage ?? 'null'));
                $compositionGrade = $this->getCompositionGrade($trimesterNumber, $studentId, $seriesSubject->id);
                // \Log::info("🔍 Composition Grade for {$seriesSubject->subject->name}: " . ($compositionGrade ?? 'null'));
                $trimesterGrade = $this->calculateTrimesterGrade($trimesterNumber, $studentId, $seriesSubject->id, 'premier');
                // \Log::info("🔍 Final Trimester Grade for {$seriesSubject->subject->name}: " . ($trimesterGrade ?? 'null'));

                // OPTION B: Si pas de note, on n'ajoute NI le coefficient NI les points
                $weightedScore = $trimesterGrade !== null ? (float)$trimesterGrade * (float)$seriesSubject->coefficient : null;

                // Get the first teacher assigned to this subject
                $teacherName = 'N/A';
                if ($seriesSubject->teachers && $seriesSubject->teachers->isNotEmpty()) {
                    $teacherName = $seriesSubject->teachers->first()->full_name;
                }

                $bulletinData['subjects'][] = [
                    'name' => $seriesSubject->subject->name,
                    'subject_id' => $seriesSubject->id, // ID du ClassSeriesSubject pour les calculs Min-Max
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
                    'cycle_type' => 'premier',
                    'class_size' => $bulletinData['class_size'] ?? 57 // Pour le rang par défaut
                ];
            }

            // OPTION B: Compter UNIQUEMENT les matières avec notes (annulation du coefficient si pas de note)
            if ($trimesterGrade !== null && $weightedScore !== null) {
                $totalPoints += $weightedScore;
                $totalCoefficient += (float)$seriesSubject->coefficient;
            }
        }

        // OPTION B: Moyenne = Total Points / SEULEMENT les coefficients des matières composées
        $bulletinData['average'] = $totalCoefficient > 0 ? $totalPoints / $totalCoefficient : 0;
        $bulletinData['total_points'] = $totalPoints;
        $bulletinData['total_coefficient'] = $totalCoefficient; // Uniquement les coefficients des matières avec notes
        $bulletinData['rank'] = $this->getTrimesterRank($trimesterNumber, $studentId);
        $bulletinData['mention'] = $this->getMentionBySection($bulletinData['average'], $sectionType);
        $bulletinData['section_type'] = $sectionType;

        // Construire les lignes HTML pour le template
        $bulletinData['subjects_rows'] = $this->buildSubjectRowsHTML($bulletinData['subjects'], 'trimester');

        // Calculer les statistiques de classe pour le trimestre
        $classStats = $this->calculateClassStatistics($trimester->id, $student->class_series_id, 'trimester');
        $bulletinData['first_average'] = $classStats['first_average'];
        $bulletinData['last_average'] = $classStats['last_average'];
        $bulletinData['class_average'] = $classStats['class_average'];
        $bulletinData['class_size'] = $classStats['class_size'];

        $bulletinData['appreciation'] = $this->getAppreciationBySection($bulletinData['average'], $sectionType);

        // Récupérer les données de discipline pour ce trimestre
        $discipline = \App\Models\StudentDiscipline::where('student_id', $studentId)
            ->where('trimester_id', $trimester->id)
            ->first();

        $bulletinData['discipline'] = [
            'delays_justified' => $discipline->delays_justified ?? 0,
            'delays_unjustified' => $discipline->delays_unjustified ?? 0,
            'absences_justified' => $discipline->absences_justified ?? 0,
            'absences_unjustified' => $discipline->absences_unjustified ?? 0,
            'blame_conduct' => $discipline->blame_conduct ?? 0,
            'blame_work' => $discipline->blame_work ?? 0,
            'warning_conduct' => $discipline->warning_conduct ?? 0,
            'warning_work' => $discipline->warning_work ?? 0,
            'detention_hours' => $discipline->detention_hours ?? 0,
            'exclusion_days' => $discipline->exclusion_days ?? 0,
            'observations' => $discipline->observations ?? ''
        ];

        return $bulletinData;
    }

    /**
     * Get subject min/max scores for ranking
     */
    protected function getSubjectMinMax($sequenceId, $seriesSubjectId)
    {
        $grades = Grade::where('sequence_id', $sequenceId)
                      ->where('class_series_subject_id', $seriesSubjectId)
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
     * 🔧 FIX: Compter TOUTES les matières (absences = 0) pour cohérence avec le calcul de moyenne
     */
    protected function getStudentRank($sequenceId, $studentId)
    {
        // Get student's class_series_id
        $student = Student::find($studentId);
        if (!$student || !$student->class_series_id) {
            return 1;
        }

        // Get all students in the same class series
        $students = Student::where('class_series_id', $student->class_series_id)->get();

        // Get ALL subjects for this class series
        $allSubjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();

        // Calculate average for each student
        $averages = [];

        foreach ($students as $classmate) {
            $totalPoints = 0;
            $totalCoef = 0; // OPTION B: Compter seulement les coefficients des matières avec notes

            // Pour chaque matière, chercher la note
            foreach ($allSubjects as $subject) {
                $grade = Grade::where('student_id', $classmate->id)
                    ->where('sequence_id', $sequenceId)
                    ->where('class_series_subject_id', $subject->id)
                    ->whereNotNull('score')
                    ->where('is_absent', false)
                    ->first();

                if ($grade) {
                    $totalPoints += (float)$grade->score * (float)$subject->coefficient;
                    $totalCoef += (float)$subject->coefficient; // OPTION B: Ajouter coefficient seulement si note présente
                }
            }

            // OPTION B: Utiliser SEULEMENT les coefficients des matières composées
            if ($totalCoef > 0) {
                $averages[$classmate->id] = $totalPoints / $totalCoef;
            }
        }

        if (empty($averages) || !isset($averages[$studentId])) {
            return 1;
        }

        // Sort by average descending
        arsort($averages);

        // Find student's position
        $rank = 1;
        foreach ($averages as $sid => $avg) {
            if ($sid == $studentId) {
                return $rank;
            }
            $rank++;
        }

        return 1;
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
     * 🔧 FIX: Retourne [0.00 - 0.00] au lieu de [N/A] si aucune note
     */
    protected function getTrimesterSubjectMinMax($trimesterNumber, $seriesSubjectId)
    {
        // Get trimester ID
        $trimester = Trimester::where('number', $trimesterNumber)->first();
        if (!$trimester) {
            return '[0.00 - 0.00]';
        }

        // Get all students in the same class series
        $seriesSubject = ClassSeriesSubject::find($seriesSubjectId);
        if (!$seriesSubject) {
            return '[0.00 - 0.00]';
        }

        $students = Student::where('class_series_id', $seriesSubject->class_series_id)->pluck('id');

        if ($students->isEmpty()) {
            return '[0.00 - 0.00]';
        }

        $subjectAverages = [];

        foreach ($students as $studentId) {
            // Calculate M/20 (Moyenne finale du trimestre) for each student
            // This uses the same logic as the bulletin: (DS + Composition) / 2
            // If composition not entered, it uses 0: (DS + 0) / 2 = DS / 2
            $moyenne = $this->calculateTrimesterGrade($trimester->id, $studentId, $seriesSubjectId, 'premier');

            // Only include numeric values (exclude null and 'ABS')
            if ($moyenne !== null && is_numeric($moyenne)) {
                $subjectAverages[] = (float)$moyenne;
            }
        }

        // 🔧 FIX: Si aucune note trouvée, retourner [0.00 - 0.00] au lieu de [N/A]
        if (empty($subjectAverages)) {
            return '[0.00 - 0.00]';
        }

        $min = min($subjectAverages);
        $max = max($subjectAverages);

        return '[' . number_format((float)$min, 2) . ' - ' . number_format((float)$max, 2) . ']';
    }
    
    /**
     * Generate PDF from HTML template
     */
    public function generatePDF($htmlContent, $filename)
    {
        // 🚀 OPTIMISATION: Configuration DomPDF optimisée pour la vitesse
        $options = new Options();
        $options->set('defaultFont', 'Times-Roman');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // ⚡ Désactiver pour accélérer
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', false); // ⚡ Désactiver pour accélérer
        $options->set('dpi', 96); // ⚡ Réduire de 120 à 96 (plus rapide, qualité acceptable)
        $options->set('debugKeepTemp', false);
        $options->set('debugPng', false);
        $options->set('debugLayout', false);
        $options->set('debugLayoutLines', false);
        $options->set('debugLayoutBlocks', false);
        $options->set('debugLayoutInline', false);
        $options->set('debugLayoutPaddingBox', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 🚀 OPTIMISATION: Désactiver temporairement le watermark pour accélérer la génération
        // Le watermark ajoute 2-3 secondes par PDF. Peut être réactivé si nécessaire.
        // DÉSACTIVER LE WATERMARK POUR PLUS DE VITESSE
        /*
        $canvas = $dompdf->getCanvas();
        $logoPath = $this->getLogoPath();

        if ($logoPath && file_exists($logoPath)) {
            $pageWidth = $canvas->get_width();
            $pageHeight = $canvas->get_height();
            $logoWidth = 300; // Réduit de 400 à 300 pour accélérer
            $logoHeight = 300;
            $x = ($pageWidth - $logoWidth) / 2;
            $y = ($pageHeight - $logoHeight) / 2;
            $canvas->set_opacity(0.10); // Réduit l'opacité pour être plus discret
            $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
            $canvas->set_opacity(1.0);
        }
        */

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
     * Get logo file path for watermark
     */
    protected function getLogoPath()
    {
        // Try database logo first
        $schoolSettings = \App\Models\SchoolSetting::first();
        if ($schoolSettings && $schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                return $logoPath;
            }
        }

        // Fallback to public logo
        $publicLogoPath = public_path('assets/logo.png');
        if (file_exists($publicLogoPath)) {
            return $publicLogoPath;
        }

        return null;
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
        // Detect section type and cycle type
        $sectionType = $this->determineSectionType($data['student']);
        $cycleType = $this->determineCycleType($data['student']);

        // Use APC template for 1er cycle francophone trimester bulletins
        if ($cycleType === 'premier' && $sectionType === 'francophone' && $templateType === 'trimester') {
            $templateFile = 'bulletin_apc_structure_finale.html';
        } else {
            // Default templates
            $templateFile = $forPdf ? 'cpbd_bulletin_pdf.html' : 'cpbd_bulletin.html';
        }

        $templatePath = resource_path('views/bulletins/' . $templateFile);

        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: {$templatePath}");
        }

        $html = file_get_contents($templatePath);

        // Detect section type for language adaptation
        $sectionType = $this->determineSectionType($data['student']);
        $data['section_type'] = $sectionType;

        // Use APC data preparation for APC template
        if ($templateFile === 'bulletin_apc_structure_finale.html') {
            return $this->prepareAPCBulletinHTML($html, $data);
        }

        // Prepare the template data
        $templateData = $this->prepareTemplateData($data, $templateType);

        // Debug: Log logo_base64 info
        if (isset($templateData['logo_base64'])) {
            \Log::info("✓ logo_base64 présent dans templateData", [
                'size' => strlen($templateData['logo_base64']),
                'starts_with' => substr($templateData['logo_base64'], 0, 50)
            ]);
        } else {
            \Log::error("✗ logo_base64 ABSENT de templateData !");
        }

        // Replace simple placeholders
        foreach ($templateData as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $html = str_replace("{{" . $key . "}}", $value, $html);
            }
        }

        // Handle subject groups (more complex replacement)
        $html = $this->replaceBulletinSubjectGroups($html, $data, $forPdf);

        // Debug: Vérifier si logo_base64 est bien remplacé
        if (strpos($html, '{{logo_base64}}') !== false) {
            \Log::error("⚠️ Le placeholder {{logo_base64}} n'a PAS été remplacé dans le bulletin CPBD !");
        } else {
            \Log::info("✓ Le placeholder {{logo_base64}} a été remplacé avec succès dans le bulletin CPBD");
        }

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
        // \Log::info("DEBUG prepareTemplateData: templateType=$templateType, sectionType=$sectionType, trimester=" . ($trimester ? $trimester->number : 'null') . ", sequence=" . ($sequence ? $sequence->number : 'null'));
        
        // Get school settings and logo
        $schoolSettings = \App\Models\SchoolSetting::first();
        $logoBase64 = '';

        if ($schoolSettings && $schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);

            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoBase64 = "data:image/{$logoMime};base64,{$logoData}";
                \Log::info("✓ Logo converti en base64 avec succès", ['path' => $logoPath, 'size' => strlen($logoBase64)]);
            } else {
                \Log::warning("⚠️ Logo non trouvé au chemin: {$logoPath}");
            }
        } else {
            \Log::warning("⚠️ SchoolSettings ou school_logo non configuré");
        }

        // Fallback sur le logo public si pas de logo configuré
        if (empty($logoBase64)) {
            $publicLogoPath = public_path('assets/logo.png');
            if (file_exists($publicLogoPath)) {
                $logoData = base64_encode(file_get_contents($publicLogoPath));
                $logoMime = pathinfo($publicLogoPath, PATHINFO_EXTENSION);
                $logoBase64 = "data:image/{$logoMime};base64,{$logoData}";
                \Log::info("✓ Logo public utilisé en fallback", ['path' => $publicLogoPath]);
            } else {
                \Log::error("✗ Aucun logo disponible (ni BDD ni public)");
            }
        }
        
        // Language-specific labels
        $labels = $this->getLanguageLabels($sectionType);

        return [
            'student_name' => strtoupper($student->last_name . ' ' . $student->first_name),
            'birth_date' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
            'birth_place' => $student->place_of_birth ?? 'EMANA',
            'class_name' => $student->classSeries->name ?? $student->schoolClass->name ?? ($sectionType === 'anglophone' ? 'FORM 2A' : 'SIXIÈME A'),
            'main_teacher' => $sectionType === 'anglophone' ? 'MR. TCHAMENI MATHIEU' : 'TCHAMENI MATHIEU', // TODO: Get from database
            'class_size' => $this->getClassSize($student),
            'student_number' => $student->student_number ?? '24A856',
            'bulletin_type_label' => $bulletinTypeLabel,
            'bulletin_period' => $bulletinPeriod,
            // Maintenir la compatibilité avec les anciens templates
            'evaluation_number' => $sequence ? $sequence->number : ($trimester ? $trimester->number : '1'),
            'school_year' => date('Y') . '/' . (date('Y') + 1),
            'total_general' => number_format((float)($data['total_points'] ?? 0), 2),
            'total_coef' => number_format((float)($data['total_coefficient'] ?? 0), 2),
            'evaluation_average' => number_format((float)($data['average'] ?? 0), 2),
            'average_class' => $this->getAverageClass((float)($data['average'] ?? 0)),
            'student_rank' => ($data['rank'] ?? 1) . ($sectionType === 'anglophone' ? '' : 'e'),
            'class_average' => number_format((float)($data['class_average'] ?? 0), 2, ',', ''),
            'first_average' => number_format((float)($data['first_average'] ?? 0), 2, ',', ''),
            'last_average' => number_format((float)($data['last_average'] ?? 0), 2, ',', ''),
            'general_appreciation' => $this->getGeneralAppreciationBySection((float)($data['average'] ?? 0), $sectionType),
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
            'legend_items' => $labels['legend_items'],
            // Données de discipline
            'discipline_delays_justified' => $data['discipline']['delays_justified'] ?? 0,
            'discipline_delays_unjustified' => $data['discipline']['delays_unjustified'] ?? 0,
            'discipline_absences_justified' => $data['discipline']['absences_justified'] ?? 0,
            'discipline_absences_unjustified' => $data['discipline']['absences_unjustified'] ?? 0,
            'discipline_blame_conduct' => $data['discipline']['blame_conduct'] ?? 0,
            'discipline_blame_work' => $data['discipline']['blame_work'] ?? 0,
            'discipline_warning_conduct' => $data['discipline']['warning_conduct'] ?? 0,
            'discipline_warning_work' => $data['discipline']['warning_work'] ?? 0,
            'discipline_detention_hours' => $data['discipline']['detention_hours'] ?? 0,
            'discipline_exclusion_days' => $data['discipline']['exclusion_days'] ?? 0
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
        // Récupérer tous les groupes actifs de la base de données
        $subjectGroups = \App\Models\SubjectGroup::where('is_active', true)
                                                  ->orderBy('order')
                                                  ->get();

        // Initialiser un tableau pour chaque groupe
        $groups = [];
        $groupCodeToKey = [];

        foreach ($subjectGroups as $subjectGroup) {
            $groupKey = "GROUPE {$subjectGroup->code} : " . strtoupper($subjectGroup->name);
            $groups[$groupKey] = [];
            $groupCodeToKey[$subjectGroup->code] = $groupKey;
        }

        // Groupe par défaut pour les matières non catégorisées
        $defaultGroupKey = count($groups) > 0 ? array_key_first($groups) : 'GROUPE A : MATIÈRES LITTÉRAIRES';

        foreach ($subjects as $subject) {
            // Récupérer le groupe depuis la base de données via le subject_id
            $subjectModel = \App\Models\Subject::find($subject['subject_id'] ?? null);

            if ($subjectModel && $subjectModel->group && isset($groupCodeToKey[$subjectModel->group])) {
                // Utiliser le groupe de la base de données
                $groupKey = $groupCodeToKey[$subjectModel->group];
                $groups[$groupKey][] = $subject;
            } else {
                // Si pas de groupe défini, utiliser le système de fallback (ancien système)
                $subjectName = strtolower($subject['name']);
                $assigned = false;

                // Essayer de trouver un groupe correspondant au nom de la matière
                if (in_array($subjectName, ['anglais', 'français', 'histoire', 'géographie', 'expression écrite', 'expression orale', 'étude de texte', 'orthographe', 'langues et cultures nationales', 'langue et culture nationale'])) {
                    // Chercher le groupe A s'il existe
                    if (isset($groupCodeToKey['A'])) {
                        $groups[$groupCodeToKey['A']][] = $subject;
                        $assigned = true;
                    }
                } elseif (in_array($subjectName, ['mathématiques', 'sciences physiques', 'svt', 'sciences naturelles', 'sciences', 'physique'])) {
                    // Chercher le groupe B s'il existe
                    if (isset($groupCodeToKey['B'])) {
                        $groups[$groupCodeToKey['B']][] = $subject;
                        $assigned = true;
                    }
                } elseif (in_array($subjectName, ['eps', 'informatique', 'travail manuel', 'arts plastiques', 'education physique et sportive', 'éducation artistique'])) {
                    // Chercher le groupe C s'il existe
                    if (isset($groupCodeToKey['C'])) {
                        $groups[$groupCodeToKey['C']][] = $subject;
                        $assigned = true;
                    }
                }

                // Si pas assigné, mettre dans le dernier groupe ou groupe D
                if (!$assigned) {
                    if (isset($groupCodeToKey['D'])) {
                        $groups[$groupCodeToKey['D']][] = $subject;
                    } else {
                        // Mettre dans le dernier groupe disponible
                        $lastGroupKey = array_key_last($groups);
                        if ($lastGroupKey) {
                            $groups[$lastGroupKey][] = $subject;
                        }
                    }
                }
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

        // Traduire le nom du groupe si Anglophone (depuis la base de données)
        $translatedGroupName = $groupName;
        if ($sectionType === 'anglophone') {
            // Extraire le code du groupe depuis le nom (ex: "GROUPE A : MATIÈRES LITTÉRAIRES" -> "A")
            if (preg_match('/GROUPE\s+([A-Z0-9]+)\s*:/i', $groupName, $matches)) {
                $groupCode = $matches[1];
                $subjectGroup = \App\Models\SubjectGroup::where('code', $groupCode)->first();

                if ($subjectGroup && $subjectGroup->header_en && $subjectGroup->name_en) {
                    $translatedGroupName = strtoupper($subjectGroup->header_en) . ': ' . strtoupper($subjectGroup->name_en);
                }
            }
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
        $totalCoefWithGrades = 0; // Coef pour moyenne (seulement matières avec notes)
        $totalPoints = 0;
        $groupAverage = 0;

        // Récupérer l'effectif de la classe pour le rang par défaut
        $classSize = count($subjects) > 0 && isset($subjects[0]['class_size']) ? $subjects[0]['class_size'] : 57;

        foreach ($subjects as $subject) {
            // Pour DEUXIÈME CYCLE, utiliser 'average', sinon 'score'
            $grade = ($cycleType === 'deuxieme') ? ($subject['average'] ?? null) : ($subject['score'] ?? null);
            $coef = $subject['coefficient'] ?? 1;

            // 🔧 FIX BUG #2: TOUJOURS ajouter le coefficient au total, même sans note
            $totalCoef += (float)$coef;

            // Calcul weighted grade seulement si note présente
            $weightedGrade = ($grade !== null && $grade !== 'ABS') ? (float)$grade * (float)$coef : null;
            $gradeClass = $this->getGradeClass($grade);
            $competence = $subject['competence'] ?? $this->getCompetence($grade, $cycleType);

            // Compter points et coef UNIQUEMENT pour matières avec notes (pour calcul moyenne)
            if ($grade !== null && $grade !== 'ABS' && $weightedGrade !== null) {
                $totalCoefWithGrades += (float)$coef;
                $totalPoints += (float)$weightedGrade;
            }

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
                    // CORRECTION: Afficher "/" pour les absents (ABS) au lieu de "-"
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($seq1 === 'ABS' ? '/' : (is_numeric($seq1) && $seq1 > 0 ? number_format((float)$seq1, 2) : '-')) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($seq2 === 'ABS' ? '/' : (is_numeric($seq2) && $seq2 > 0 ? number_format((float)$seq2, 2) : '-')) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($compo1 === 'ABS' ? '/' : (is_numeric($compo1) && $compo1 > 0 ? number_format((float)$compo1, 2) : '-')) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . (is_numeric($average) && $average > 0 ? number_format((float)$average, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$coef, 2) . '</td>';
                    // CORRECTION: Afficher "-" si NXC est null (élève absent)
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . ($nxc !== null ? number_format((float)$nxc, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . ($nxc !== null ? number_format((float)$nxc, 2) : '-') . '</td>'; // TOTAL = NXC
                    // 🔧 FIX BUG #1: Si pas de rang, afficher dernier rang au lieu de 1er
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? $classSize) . 'e</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10pt;">' . $competence . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10pt;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
                } else {
                    // 📚 PREMIER CYCLE: 9 colonnes avec DS1 (moyenne cachée)
                    $ds1 = $subject['ds'] ?? null;
                    $compo1 = $subject['composition'] ?? null;
                    $average = $subject['average'] ?? null;

                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>';
                    // CORRECTION: Afficher "/" pour les absents (ABS) au lieu de "-"
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($ds1 === 'ABS' ? '/' : (is_numeric($ds1) && $ds1 > 0 ? number_format((float)$ds1, 2) : '-')) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($compo1 === 'ABS' ? '/' : (is_numeric($compo1) && $compo1 > 0 ? number_format((float)$compo1, 2) : '-')) . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . (is_numeric($average) && $average > 0 ? number_format((float)$average, 2) : '-') . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$coef, 2) . '</td>';
                    // CORRECTION: Afficher "-" si weightedGrade est null (élève absent)
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . ($weightedGrade !== null ? number_format((float)$weightedGrade, 2) : '-') . '</td>';
                    // 🔧 FIX BUG #1: Si pas de rang, afficher dernier rang au lieu de 1er
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? $classSize) . 'e</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $competence . '</td>';
                    $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
                }
            } else {
                // Pour bulletin séquence avec alignement
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>';
                // CORRECTION: Afficher "/" pour absent (ABS), "-" si null, ou la note
                $displayGrade = ($grade === 'ABS') ? '/' : ((is_numeric($grade) && $grade > 0) ? number_format((float)$grade, 2) : '-');
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $displayGrade . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$coef, 2) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;" class="' . $gradeClass . '">' . ($weightedGrade !== null ? number_format((float)$weightedGrade, 2) : '-') . '</td>';
                // 🔧 FIX BUG #1: Si pas de rang, afficher dernier rang au lieu de 1er
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . ($subject['rank'] ?? $classSize) . 'e</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $competence . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . strtoupper($subject['teacher'] ?? 'N/A') . '</td>';
            }

            $html .= '</tr>';
        }

        // Calcul moyenne du groupe basé sur coefficient TOTAL (incluant matières sans notes)
        // CORRECTION BUG: Utiliser $totalCoef au lieu de $totalCoefWithGrades
        // Moyenne = Total Points / Total Coef (avec matières non saisies = 0 points)
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
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$groupAverage, 2) . '</td>'; // Moy./20
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalCoef, 2) . '</td>'; // COEF
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalPoints, 2) . '</td>'; // (NXC)
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalPoints, 2) . '</td>'; // TOTAL
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // RANG
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10pt;">' . strtoupper(explode(' :', $groupName)[0]) . '</td>'; // COMPÉTENCES
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10pt;">Moy Gpe: ' . number_format((float)$groupAverage, 2) . '</td>'; // PROFESSEURS
            } else {
                // 📚 PREMIER CYCLE: 9 colonnes - ligne de total
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // DS1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // Compo1
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$groupAverage, 2) . '</td>'; // Moy
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalCoef, 2) . '</td>'; // COEF
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalPoints, 2) . '</td>'; // (NXC)
                $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>'; // RANG
                $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format((float)$groupAverage, 2) . ' ' . strtoupper(explode(' :', $groupName)[0]) . '</td>'; // COMPÉTENCES + PROFESSEURS
            }
        } else {
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalPoints, 2) . '</td>';
            $html .= '<td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format((float)$totalCoef, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy gpe: ' . number_format((float)$groupAverage, 2) . '</td>';
            $html .= '<td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Moy Gen Gpe: ' . number_format((float)$groupAverage, 2) . '</td>';
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
            if ($average >= 16) return 'A+ : Expert';
            if ($average >= 14) return 'A : Acquired';
            if ($average >= 10) return 'ECA : In Progress';
            return 'NA : Not Acquired';
        }

        // Francophone - Système de compétences
        if ($average >= 16) return 'A+ : Expert';
        if ($average >= 14) return 'A : Acquise';
        if ($average >= 10) return 'ECA : En Cours d\'Acquisition';
        return 'NA : Non Acquise';
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
        if ($average >= 16) return 'A+ : Expert';
        if ($average >= 14) return 'A : Acquise';
        if ($average >= 10) return 'ECA : En Cours d\'Acquisition';
        return 'NA : Non Acquise';
    }
    
    /**
     * Get class size
     */
    protected function getClassSize($student)
    {
        // Use class_series_id (specific section like "6ème A") instead of school_class_id (all "6ème")
        if ($student->class_series_id) {
            return Student::where('class_series_id', $student->class_series_id)->count();
        }
        if ($student->schoolClass) {
            return $student->schoolClass->students()->count();
        }
        return 72; // Default value
    }

    /**
     * Get competences for a subject in a given trimester
     * Returns array with competence_1 and competence_2
     *
     * @param int $classSeriesSubjectId - L'ID du ClassSeriesSubject (pas l'ID du Subject)
     * @param int $trimesterId - L'ID du trimestre
     */
    protected function getSubjectCompetences($classSeriesSubjectId, $trimesterId)
    {
        // Get the competences from the database using class_series_subject_id directly
        $competence = \App\Models\SubjectCompetence::where('class_series_subject_id', $classSeriesSubjectId)
            ->where('trimester_id', $trimesterId)
            ->first();

        if (!$competence) {
            return [
                'competence_1' => '',
                'competence_2' => ''
            ];
        }

        return [
            'competence_1' => $competence->competence_1 ?? '',
            'competence_2' => $competence->competence_2 ?? ''
        ];
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

    /**
     * Prepare APC Bulletin HTML with dynamic data
     */
    protected function prepareAPCBulletinHTML($html, $data)
    {
        $student = $data['student'];
        $trimester = $data['trimester'];

        // Get class level and section
        $classLevel = '6'; // Default
        $classSection = 'A'; // Default
        if ($student->schoolClass) {
            // Extract level from class name (e.g., "6ème A" -> "6")
            preg_match('/(\d+)/', $student->schoolClass->name, $matches);
            if (!empty($matches[1])) {
                $classLevel = $matches[1];
            }
            // Extract section (last letter of class name)
            preg_match('/([A-Z])$/', $student->schoolClass->name, $sectionMatches);
            if (!empty($sectionMatches[1])) {
                $classSection = $sectionMatches[1];
            }
        }

        // Get main teacher - Find a teacher teaching this class
        $mainTeacher = 'N/A';
        if ($student->classSeries) {
            $classSeriesSubject = \App\Models\ClassSeriesSubject::where('class_series_id', $student->class_series_id)
                ->with('teachers')
                ->first();
            if ($classSeriesSubject && $classSeriesSubject->teachers && $classSeriesSubject->teachers->isNotEmpty()) {
                $mainTeacher = $classSeriesSubject->teachers->first()->full_name;
            }
        }

        // Get parent info
        $parentInfo = 'N/A';
        if ($student->parent_name) {
            $parentInfo = $student->parent_name;
            if ($student->parent_phone) {
                $parentInfo .= ' - Tél: ' . $student->parent_phone;
            }
        }

        // Student information
        $replacements = [
            'student_name' => strtoupper($student->last_name . ' ' . $student->first_name),
            'birth_date' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : ($student->birthday ?? 'N/A'),
            'birth_place' => $student->place_of_birth ?? ($student->birthday_place ?? 'N/A'),
            'gender' => $student->gender === 'M' ? 'Masculin' : 'Féminin',
            'unique_id' => $student->matricule ?? $student->student_number ?? 'N/A',
            'class_level' => $classLevel,
            'class_section' => $classSection,
            'class_size' => $student->classSeries ? \App\Models\Student::where('class_series_id', $student->class_series_id)->count() : 0,
            'main_teacher' => $mainTeacher,
            'parent_info' => $parentInfo,
            'school_year' => date('Y') . '/' . (date('Y') + 1),
        ];

        // Generate subjects HTML
        $subjectsHTML = '';
        $totalGeneral = 0;
        $totalCoef = 0;

        foreach ($data['subjects'] as $subject) {
            $coef = (float)($subject['coefficient'] ?? 1);
            $subjectId = $subject['subject_id'] ?? null;

            // Calculate DS = (Seq1 + Seq2) / 2
            // Use $subject['ds'] which contains the DS average (not $subject['score'] which is the final trimester grade)
            $dsNote = is_numeric($subject['ds'] ?? 0) ? (float)$subject['ds'] : 0; // This is (Seq1 + Seq2) / 2

            // Get Composition grade
            $compositionNote = is_numeric($subject['composition'] ?? 0) ? (float)$subject['composition'] : 0;

            // Calculate M/20 = (DS + Composition) / 2
            $moyenne = 0;
            if ($dsNote !== null && $compositionNote !== null) {
                $moyenne = ($dsNote + $compositionNote) / 2;
            } elseif ($dsNote !== null) {
                $moyenne = $dsNote;
            } elseif ($compositionNote !== null) {
                $moyenne = $compositionNote;
            }

            // Calculate total = M/20 × Coef
            $total = $moyenne * $coef;
            $totalGeneral += $total;
            $totalCoef += $coef;

            // Get cote based on M/20
            $cote = $this->getCote($moyenne);
            $coteClass = $this->getCoteClass($cote);

            // Calculate [Min - Max] for this subject
            $minMax = $this->getAPCSubjectMinMax($trimester->id, $student->class_series_id, $subjectId);

            // Get competences for this subject (using class_series_subject_id)
            $competences = $this->getSubjectCompetences($subjectId, $trimester->id);

            // Generate HTML for this subject (2 rows: competence_1 + competence_2)
            // Row 1: DS note + Competence 1
            $subjectsHTML .= '<tr>';
            $subjectsHTML .= '<td class="subject-col" rowspan="2"><b>' . htmlspecialchars($subject['name']) . '</b><br><span class="teacher-name">' . htmlspecialchars($subject['teacher'] ?? 'N/A') . '</span></td>';
            $subjectsHTML .= '<td class="competence-col">' . htmlspecialchars($competences['competence_1'] ?: 'Compétence 1') . '</td>';
            $subjectsHTML .= '<td class="note-col">' . number_format((float)$dsNote, 2) . '</td>';
            $subjectsHTML .= '<td class="avg-col" rowspan="2">' . number_format((float)$moyenne, 2) . '</td>';
            $subjectsHTML .= '<td class="coef-col" rowspan="2">' . number_format((float)$coef, 2) . '</td>';
            $subjectsHTML .= '<td class="total-col" rowspan="2">' . number_format((float)$total, 2) . '</td>';
            $subjectsHTML .= '<td class="cote-col ' . $coteClass . '" rowspan="2"><b>' . $cote . '</b></td>';
            $subjectsHTML .= '<td class="minmax-col" rowspan="2">' . $minMax . '</td>';

            // 🔧 FIX: Appréciation selon la moyenne M/20 (règle APC correcte)
            // 0-9.99: Non Acquise, 10-11.99: En Cours d'Acquisition, 12-13.99: Acquise, 14-20: Expert
            $appreciation = '';
            if ($moyenne >= 14) {
                $appreciation = 'Expert';
            } elseif ($moyenne >= 12) {
                $appreciation = 'Acquise';
            } elseif ($moyenne >= 10) {
                $appreciation = 'En Cours d\'Acquisition';
            } else {
                $appreciation = 'Non Acquise';
            }
            $subjectsHTML .= '<td class="app-col" rowspan="2">' . $appreciation . '</td>';
            $subjectsHTML .= '</tr>';

            // Row 2: Composition note + Competence 2
            $subjectsHTML .= '<tr>';
            $subjectsHTML .= '<td class="competence-col">' . htmlspecialchars($competences['competence_2'] ?: 'Compétence 2') . '</td>';
            $subjectsHTML .= '<td class="note-col">' . number_format((float)$compositionNote, 2) . '</td>';
            $subjectsHTML .= '</tr>';
        }

        $replacements['subjects_html'] = $subjectsHTML;

        // Calculate general statistics
        $generalAverage = $totalCoef > 0 ? $totalGeneral / $totalCoef : 0;
        $replacements['total_general'] = number_format((float)$totalGeneral, 2);
        $replacements['total_coef'] = $totalCoef;
        $replacements['general_average'] = number_format((float)$generalAverage, 2);
        $replacements['student_cote'] = $this->getCote($generalAverage);

        // Additional data
        $replacements['abs_non_just'] = '0';
        $replacements['abs_just'] = '0';
        $replacements['delays'] = '0';
        $replacements['class_min_max'] = $this->getClassMinMax($trimester->id, $student->class_series_id);
        $replacements['nb_averages'] = $this->getNumberOfAverages($trimester->id, $student->class_series_id);
        $replacements['success_rate'] = $this->getSuccessRate($trimester->id, $student->class_series_id);
        $replacements['detailed_appreciation'] = $this->getDetailedAppreciation($generalAverage);

        // Replace all placeholders
        foreach ($replacements as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        // Debug: Vérifier si logo_base64 est bien remplacé
        if (strpos($html, '{{logo_base64}}') !== false) {
            \Log::error("⚠️ Le placeholder {{logo_base64}} n'a PAS été remplacé !");
        } else {
            \Log::info("✓ Le placeholder {{logo_base64}} a été remplacé avec succès");
        }

        // Replace Blade public_path() directive with base64 encoded image for DomPDF
        $html = preg_replace_callback('/src=["\']?\{\{\s*public_path\([\'"](.+?)[\'"]\)\s*\}\}["\']?/', function($matches) {
            $imagePath = public_path($matches[1]);
            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                return 'src="data:image/' . $imageType . ';base64,' . $imageData . '"';
            }
            return 'src=""';
        }, $html);

        // Replace background-image with base64 encoded image for DomPDF
        $html = preg_replace_callback('/background-image:\s*\{\{\s*public_path\([\'"](.+?)[\'"]\)\s*\}\}/', function($matches) {
            $imagePath = public_path($matches[1]);
            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                return 'background-image: url(data:image/' . $imageType . ';base64,' . $imageData . ')';
            }
            return 'background-image: none';
        }, $html);

        // Remove any remaining placeholders
        $html = preg_replace('/\{\{.*?\}\}/', '', $html);

        return $html;
    }

    /**
     * Get cote (grade letter) based on average - APC Grading System
     */
    private function getCote($avg)
    {
        // Niveau 4
        if ($avg >= 18 && $avg <= 20) return 'A+'; // 18 ≤ m ≤ 20
        if ($avg >= 16 && $avg < 18) return 'A';   // 16 ≤ m < 18

        // Niveau 3
        if ($avg >= 15 && $avg < 16) return 'B+';  // 15 ≤ m < 16
        if ($avg >= 14 && $avg < 15) return 'B';   // 14 ≤ m < 15

        // Niveau 2
        if ($avg >= 12 && $avg < 14) return 'C+';  // 12 ≤ m < 14
        if ($avg >= 10 && $avg < 12) return 'C';   // 10 ≤ m < 12

        // Niveau 1
        return 'D'; // m < 10
    }

    /**
     * Get appreciation based on cote - APC System
     */
    private function getAppreciationByCote($cote)
    {
        $appreciations = [
            'A+' => 'Compétences très bien acquises (CTBA)',
            'A'  => 'Compétences très bien acquises (CTBA)',
            'B+' => 'Compétences bien acquises (CBA)',
            'B'  => 'Compétences bien acquises (CBA)',
            'C+' => 'Compétences acquises (CA)',
            'C'  => 'Compétences moyennement acquises (CMA)',
            'D'  => 'Compétences non acquises (CNA)'
        ];

        return $appreciations[$cote] ?? 'N/A';
    }

    /**
     * Get CSS class for cote color
     */
    private function getCoteClass($cote)
    {
        return 'cote-' . strtolower(str_replace('+', '-plus', $cote));
    }

    /**
     * Get class min-max average range for PROFIL CLASSE
     * Returns [minimum general average, maximum general average] of the class
     * 🔧 FIX: Retourne [0.00 - 0.00] au lieu de [N/A] si aucune note
     */
    private function getClassMinMax($trimesterId, $classSeriesId)
    {
        // Get all students in the same class series
        $students = Student::where('class_series_id', $classSeriesId)->pluck('id');

        if ($students->isEmpty()) {
            return '[0.00 - 0.00]';
        }

        $averages = [];

        foreach ($students as $studentId) {
            // Calculate trimester average for each student
            $subjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)->get();

            $totalPoints = 0;
            $totalCoef = 0;

            foreach ($subjects as $subject) {
                // Calculate M/20 (Moyenne finale du trimestre) for this subject
                // This uses: (DS + Composition) / 2, or (DS + 0) / 2 if composition not entered
                $moyenne = $this->calculateTrimesterGrade($trimesterId, $studentId, $subject->id, 'premier');

                // Only include numeric values (exclude null and 'ABS')
                if ($moyenne !== null && is_numeric($moyenne)) {
                    $totalPoints += (float)$moyenne * (float)$subject->coefficient;
                    $totalCoef += (float)$subject->coefficient;
                }
            }

            if ($totalCoef > 0) {
                $averages[] = $totalPoints / $totalCoef;
            }
        }

        // 🔧 FIX: Si aucune note trouvée, retourner [0.00 - 0.00] au lieu de [N/A]
        if (empty($averages)) {
            return '[0.00 - 0.00]';
        }

        $min = min($averages);
        $max = max($averages);

        return '[' . number_format((float)$min, 2) . ' - ' . number_format((float)$max, 2) . ']';
    }

    /**
     * Get subject min-max for a specific subject
     * Returns [minimum average, maximum average] for this subject across all students in the class
     * 🔧 FIX: Retourne [0.00 - 0.00] au lieu de [N/A] si aucune note
     */
    private function getAPCSubjectMinMax($trimesterId, $classSeriesId, $subjectId)
    {
        if (!$subjectId) {
            return '[0.00 - 0.00]';
        }

        // Get all students in the same class series
        $students = Student::where('class_series_id', $classSeriesId)->pluck('id');

        if ($students->isEmpty()) {
            return '[0.00 - 0.00]';
        }

        $subjectAverages = [];

        foreach ($students as $studentId) {
            // Calculate M/20 (Moyenne finale du trimestre) for this subject
            // This uses: (DS + Composition) / 2, or (DS + 0) / 2 if composition not entered
            $moyenne = $this->calculateTrimesterGrade($trimesterId, $studentId, $subjectId, 'premier');

            // Only include numeric values (exclude null and 'ABS')
            if ($moyenne !== null && is_numeric($moyenne)) {
                $subjectAverages[] = (float)$moyenne;
            }
        }

        // 🔧 FIX: Si aucune note trouvée, retourner [0.00 - 0.00] au lieu de [N/A]
        if (empty($subjectAverages)) {
            return '[0.00 - 0.00]';
        }

        $min = min($subjectAverages);
        $max = max($subjectAverages);

        return '[' . number_format((float)$min, 2) . ' - ' . number_format((float)$max, 2) . ']';
    }

    /**
     * Get number of students with average >= 10
     */
    private function getNumberOfAverages($trimesterId, $classSeriesId)
    {
        // Get all students in the same class series
        $students = Student::where('class_series_id', $classSeriesId)->pluck('id');

        if ($students->isEmpty()) {
            return '0';
        }

        $passCount = 0;

        foreach ($students as $studentId) {
            // Calculate trimester average for each student
            $subjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)->get();

            $totalPoints = 0;
            $totalCoef = 0;

            foreach ($subjects as $subject) {
                // Use calculateTrimesterGrade to get M/20 (handles composition = 0 correctly)
                $moyenne = $this->calculateTrimesterGrade($trimesterId, $studentId, $subject->id, 'premier');

                // Only include numeric values (exclude null and 'ABS')
                if ($moyenne !== null && is_numeric($moyenne)) {
                    $totalPoints += (float)$moyenne * (float)$subject->coefficient;
                    $totalCoef += (float)$subject->coefficient;
                }
            }

            if ($totalCoef > 0) {
                $generalAverage = $totalPoints / $totalCoef;
                if ($generalAverage >= 10) {
                    $passCount++;
                }
            }
        }

        return (string)$passCount;
    }

    /**
     * Get success rate (percentage of students with average >= 10)
     */
    private function getSuccessRate($trimesterId, $classSeriesId)
    {
        // Get all students in the same class series
        $students = Student::where('class_series_id', $classSeriesId)->pluck('id');

        if ($students->isEmpty()) {
            return '0,0';
        }

        $totalStudents = 0;
        $passCount = 0;

        foreach ($students as $studentId) {
            // Calculate trimester average for each student
            $subjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)->get();

            $totalPoints = 0;
            $totalCoef = 0;

            foreach ($subjects as $subject) {
                // Use calculateTrimesterGrade to get M/20 (handles composition = 0 correctly)
                $moyenne = $this->calculateTrimesterGrade($trimesterId, $studentId, $subject->id, 'premier');

                // Only include numeric values (exclude null and 'ABS')
                if ($moyenne !== null && is_numeric($moyenne)) {
                    $totalPoints += (float)$moyenne * (float)$subject->coefficient;
                    $totalCoef += (float)$subject->coefficient;
                }
            }

            if ($totalCoef > 0) {
                $totalStudents++;
                $generalAverage = $totalPoints / $totalCoef;
                if ($generalAverage >= 10) {
                    $passCount++;
                }
            }
        }

        if ($totalStudents === 0) {
            return '0,0';
        }

        $successRate = ($passCount / $totalStudents) * 100;
        return number_format((float)$successRate, 1, ',', '');
    }

    /**
     * Get detailed appreciation based on average
     */
    private function getDetailedAppreciation($avg)
    {
        if ($avg >= 16) {
            return 'Points forts: Excellente maîtrise des matières. Points à améliorer: Maintenir cet excellent niveau.';
        } elseif ($avg >= 14) {
            return 'Points forts: Très bonne maîtrise des matières. Points à améliorer: Viser l\'excellence.';
        } elseif ($avg >= 12) {
            return 'Points forts: Bonne compréhension générale. Points à améliorer: Renforcer les acquis.';
        } elseif ($avg >= 10) {
            return 'Points forts: Compréhension satisfaisante. Points à améliorer: Travailler plus régulièrement.';
        } else {
            return 'Points forts: Des efforts remarqués. Points à améliorer: Redoubler d\'efforts et demander de l\'aide.';
        }
    }

    /**
     * Determine cycle type based on student's class
     */
    protected function determineCycleType($student)
    {
        if (!$student || !$student->schoolClass) {
            return 'premier'; // Par défaut
        }

        $className = strtolower($student->schoolClass->name);

        // 🎓 DEUXIÈME CYCLE: Classes du lycée
        $deuxiemeCycleClasses = [
            'seconde', '2nde', 'première', '1ère', '1ere', 'terminale', 'tle',
            'seconde a', 'seconde c', 'seconde d',
            'première a', 'première c', 'première d', 'première a4',
            '1ère a', '1ère c', '1ère d', '1ere a', '1ere c', '1ere d',
            'terminale a', 'terminale c', 'terminale d'
        ];

        foreach ($deuxiemeCycleClasses as $cycleClass) {
            if (strpos($className, $cycleClass) !== false) {
                return 'deuxieme';
            }
        }

        // 📚 PREMIER CYCLE: Classes du collège (par défaut)
        return 'premier';
    }

    /**
     * Calculate class statistics (first, last, average) for a sequence or trimester
     * Uses class_series_id to get students in the specific section (e.g., "6ème A")
     * 🔧 FIX: Compter TOUTES les matières (absences = 0) pour cohérence
     */
    protected function calculateClassStatistics($evaluationId, $classSeriesId, $type = 'sequence')
    {
        // Get all students in the same class series (specific section)
        $students = Student::where('class_series_id', $classSeriesId)->get();

        if ($students->isEmpty()) {
            return [
                'first_average' => 0,
                'last_average' => 0,
                'class_average' => 0,
                'class_size' => 0
            ];
        }

        // Get ALL subjects for this class series
        $allSubjects = ClassSeriesSubject::where('class_series_id', $classSeriesId)->get();

        // Calculate average for each student
        $averages = [];

        foreach ($students as $student) {
            $totalPoints = 0;
            $totalCoef = 0; // OPTION B: Compter seulement les coefficients des matières avec notes

            if ($type === 'sequence') {
                // Pour chaque matière, chercher la note
                foreach ($allSubjects as $subject) {
                    $grade = Grade::where('student_id', $student->id)
                        ->where('sequence_id', $evaluationId)
                        ->where('class_series_subject_id', $subject->id)
                        ->whereNotNull('score')
                        ->where('is_absent', false)
                        ->first();

                    if ($grade) {
                        $totalPoints += (float)$grade->score * (float)$subject->coefficient;
                        $totalCoef += (float)$subject->coefficient; // OPTION B: Ajouter coefficient seulement si note présente
                    }
                }
            } else {
                // For trimester: calculate trimester average (M/20 par matière)
                foreach ($allSubjects as $subject) {
                    // Calculer la moyenne du trimestre pour cette matière
                    $trimesterGrade = $this->calculateTrimesterGrade($evaluationId, $student->id, $subject->id, 'premier');

                    if ($trimesterGrade !== null && $trimesterGrade > 0) {
                        $totalPoints += (float)$trimesterGrade * (float)$subject->coefficient;
                        $totalCoef += (float)$subject->coefficient;
                    }
                }
            }

            // OPTION B: Utiliser SEULEMENT les coefficients des matières composées
            if ($totalCoef > 0) {
                $averages[] = $totalPoints / $totalCoef;
            }
        }

        if (empty($averages)) {
            return [
                'first_average' => 0,
                'last_average' => 0,
                'class_average' => 0,
                'class_size' => $students->count()
            ];
        }

        // Sort averages to get first and last
        sort($averages);

        return [
            'first_average' => end($averages), // Highest average
            'last_average' => reset($averages), // Lowest average
            'class_average' => array_sum($averages) / count($averages),
            'class_size' => $students->count()
        ];
    }
}
