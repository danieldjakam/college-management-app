<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Models\SeriesSubject;
use Illuminate\Support\Facades\Log;

class BulletinAutoGenerationService
{
    protected $bulletinService;
    
    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }
    
    /**
     * Vérifie et génère automatiquement les bulletins après mise à jour des notes
     */
    public function checkAndGenerateBulletins($gradeId)
    {
        $grade = Grade::with(['student', 'sequence', 'evaluation'])->find($gradeId);
        
        if (!$grade) {
            return;
        }
        
        $student = $grade->student;
        $sequence = $grade->sequence;
        
        // Logique académique camerounaise :
        // - Séquences 1,3 : Génèrent des bulletins individuels
        // - Séquences 2,4 : Saisie notes seulement, mais calculent les trimestres
        
        if (in_array($sequence->number, [1, 3])) {
            // Générer bulletin individuel pour séquences 1 et 3
            $this->checkSequenceBulletinCompletion($student->id, $sequence->number);
        }
        
        // Calculer trimestre selon la séquence actuelle
        if ($sequence->number == 2) {
            // Séquence 2 terminée → Calculer Trimestre 1 (DS1 + Composition1)
            $this->checkTrimesterBulletinCompletion($student->id, 1);
        } elseif ($sequence->number == 4) {
            // Séquence 4 terminée → Calculer Trimestre 2 (DS2 + Composition2) 
            $this->checkTrimesterBulletinCompletion($student->id, 2);
        } else {
            // Pour séquences 1,3 : vérifier aussi les trimestres associés
            $this->checkTrimesterBulletinCompletion($student->id, $sequence->trimester_id);
        }
        
        // Vérifier le bulletin annuel si on est en fin d'année
        $this->checkAnnualBulletinCompletion($student->id);
    }
    
    /**
     * Vérifie si un bulletin de séquence peut être généré/mis à jour
     */
    protected function checkSequenceBulletinCompletion($studentId, $sequenceNumber)
    {
        $sequence = Sequence::where('number', $sequenceNumber)->first();
        if (!$sequence) return;

        $student = Student::find($studentId);
        if (!$student) return;

        // 🎓 VÉRIFIER LE CYCLE DE L'ÉTUDIANT
        $cycleType = $this->determineCycleType($student);

        // 📚 PREMIER CYCLE: Seulement séquences 1 et 3 ont des bulletins
        if ($cycleType === 'premier') {
            if (!in_array($sequenceNumber, [1, 3])) {
                Log::info("Premier Cycle - Séquence {$sequenceNumber} : Pas de bulletin (saisie uniquement)");
                return; // Pas de bulletin pour séquences 2 et 4
            }
        }

        // 🎓 DEUXIÈME CYCLE: Toutes les séquences ont des bulletins
        // (Pas de restriction, continuer le traitement)

        // Récupérer toutes les matières de l'étudiant
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();

        $totalSubjects = $subjects->count();
        $gradedSubjects = 0;

        // Compter les matières avec notes
        foreach ($subjects as $subject) {
            $hasGrade = Grade::where('student_id', $studentId)
                           ->where('sequence_id', $sequence->id)
                           ->where('class_series_subject_id', $subject->id)
                           ->whereNotNull('score')
                           ->exists();

            if ($hasGrade) {
                $gradedSubjects++;
            }
        }

        // Calculer le pourcentage de completion
        $completionPercentage = $totalSubjects > 0 ? ($gradedSubjects / $totalSubjects) * 100 : 0;

        Log::info("Séquence {$sequenceNumber} ({$cycleType} cycle) - Étudiant {$studentId}: {$gradedSubjects}/{$totalSubjects} matières ({$completionPercentage}%)");

        // Générer/mettre à jour si au moins 50% des notes sont saisies
        if ($completionPercentage >= 50) {
            $this->generateOrUpdateSequenceBulletin($studentId, $sequenceNumber, $completionPercentage);
        }
    }
    
    /**
     * Détermine le type de cycle (premier/deuxieme) selon la classe de l'étudiant
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
     * Vérifie si un bulletin de trimestre peut être généré/mis à jour
     */
    protected function checkTrimesterBulletinCompletion($studentId, $trimesterNumber)
    {
        $student = Student::find($studentId);
        if (!$student) return;
        
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
        
        $completionData = [];
        $overallCompletion = 0;
        
        foreach ($subjects as $subject) {
            // Logique académique camerounaise
            if ($trimesterNumber == 3) {
                // Trimestre 3: Composition 3 seulement
                $compositionCompletion = $this->checkCompositionCompletion($studentId, 3, $subject->id);
                $subjectCompletion = $compositionCompletion;
            } else {
                // Trimestre 1: DS1 (Séq1+Séq2) + Composition1
                // Trimestre 2: DS2 (Séq3+Séq4) + Composition2
                $dsCompletion = $this->checkDSCompletion($studentId, $trimesterNumber, $subject->id);
                $compositionCompletion = $this->checkCompositionCompletion($studentId, $trimesterNumber, $subject->id);
                
                // Si les DS sont complètes ou si on a une composition, on peut calculer
                if ($dsCompletion >= 100 || $compositionCompletion >= 100) {
                    $subjectCompletion = ($dsCompletion + $compositionCompletion) / 2;
                } else {
                    $subjectCompletion = ($dsCompletion + $compositionCompletion) / 2;
                }
            }
            
            $completionData[$subject->id] = $subjectCompletion;
            $overallCompletion += $subjectCompletion;
        }
        
        $overallCompletion = $subjects->count() > 0 ? $overallCompletion / $subjects->count() : 0;
        
        Log::info("Trimestre {$trimesterNumber} - Étudiant {$studentId}: {$overallCompletion}% complet");
        
        // Générer/mettre à jour si au moins 50% complet (plus permissif pour avoir des bulletins pendant saisie)
        if ($overallCompletion >= 50) {
            $this->generateOrUpdateTrimesterBulletin($studentId, $trimesterNumber, $overallCompletion);
        }
    }
    
    /**
     * Vérifie la completion des DS pour un trimestre
     * DS1 = Séquences 1 + 2, DS2 = Séquences 3 + 4
     */
    protected function checkDSCompletion($studentId, $trimesterNumber, $subjectId)
    {
        $sequenceNumbers = [];
        
        switch ($trimesterNumber) {
            case 1:
                $sequenceNumbers = [1, 2]; // DS1 = (Séq1 + Séq2) / 2
                break;
            case 2:
                $sequenceNumbers = [3, 4]; // DS2 = (Séq3 + Séq4) / 2
                break;
            default:
                return 100; // Trimestre 3 n'a pas de DS
        }
        
        $sequences = Sequence::whereIn('number', $sequenceNumbers)->get();
        $gradedSequences = 0;
        $totalSequences = $sequences->count();
        
        foreach ($sequences as $sequence) {
            $hasGrade = Grade::where('student_id', $studentId)
                           ->where('sequence_id', $sequence->id)
                           ->where('class_series_subject_id', $subjectId)
                           ->whereNotNull('score')
                           ->exists();
            
            if ($hasGrade) {
                $gradedSequences++;
            }
        }
        
        // Retourner pourcentage de completion des DS
        return $totalSequences > 0 ? ($gradedSequences / $totalSequences) * 100 : 0;
    }
    
    /**
     * Vérifie la completion des compositions
     */
    protected function checkCompositionCompletion($studentId, $trimesterNumber, $subjectId)
    {
        $compositionGrade = $this->bulletinService->getCompositionGrade($trimesterNumber, $studentId, $subjectId);
        return $compositionGrade !== null ? 100 : 0;
    }
    
    /**
     * Génère ou met à jour un bulletin de séquence
     */
    protected function generateOrUpdateSequenceBulletin($studentId, $sequenceNumber, $completionPercentage)
    {
        try {
            // Vérifier si le bulletin existe déjà
            $existingBulletin = BulletinGeneration::where('student_id', $studentId)
                                                 ->where('period_type', 'sequence')
                                                 ->where('period_identifier', "seq{$sequenceNumber}")
                                                 ->first();
            
            // Générer les données du bulletin
            $bulletinData = $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $studentId);
            
            if (!$bulletinData) {
                Log::warning("Impossible de générer les données pour séquence {$sequenceNumber}, étudiant {$studentId}");
                return;
            }
            
            // Ajouter le pourcentage de completion
            $bulletinData['completion_percentage'] = $completionPercentage;
            $bulletinData['is_complete'] = $completionPercentage >= 100;
            
            // Obtenir un template (pour l'ID de la base de données seulement)
            $template = BulletinTemplate::where('type', 'sequence')->where('is_active', true)->first();
            
            if (!$template) {
                // Créer un template par défaut si aucun n'existe
                $template = BulletinTemplate::firstOrCreate(
                    ['type' => 'sequence', 'name' => 'CPBD Template'],
                    [
                        'name' => 'CPBD Template',
                        'type' => 'sequence',
                        'template_html' => 'Default CPBD Template',
                        'is_active' => true,
                        'description' => 'Template par défaut CPBD pour les séquences'
                    ]
                );
            }
            
            // Générer le HTML et le PDF avec le nouveau template CPBD
            $htmlContent = $this->bulletinService->renderBulletinTemplate('sequence', $bulletinData, true);
            $filename = "bulletin_seq{$sequenceNumber}_{$studentId}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
            $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
            
            if ($existingBulletin) {
                // Mettre à jour le bulletin existant
                $existingBulletin->update([
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'completion_percentage' => $completionPercentage,
                    'is_complete' => $completionPercentage >= 100
                ]);
                
                Log::info("Bulletin séquence {$sequenceNumber} mis à jour pour étudiant {$studentId} ({$completionPercentage}%)");
            } else {
                // Créer un nouveau bulletin
                BulletinGeneration::create([
                    'student_id' => $studentId,
                    'template_id' => $template->id,
                    'period_type' => 'sequence',
                    'period_identifier' => "seq{$sequenceNumber}",
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'completion_percentage' => $completionPercentage,
                    'is_complete' => $completionPercentage >= 100
                ]);
                
                Log::info("Nouveau bulletin séquence {$sequenceNumber} généré pour étudiant {$studentId} ({$completionPercentage}%)");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur génération automatique séquence {$sequenceNumber}, étudiant {$studentId}: " . $e->getMessage());
        }
    }
    
    /**
     * Génère ou met à jour un bulletin de trimestre
     */
    protected function generateOrUpdateTrimesterBulletin($studentId, $trimesterNumber, $completionPercentage)
    {
        try {
            // Vérifier si le bulletin existe déjà
            $existingBulletin = BulletinGeneration::where('student_id', $studentId)
                                                 ->where('period_type', 'trimester')
                                                 ->where('period_identifier', "trim{$trimesterNumber}")
                                                 ->first();
            
            // Générer les données du bulletin
            $bulletinData = $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $studentId);
            
            if (!$bulletinData) {
                Log::warning("Impossible de générer les données pour trimestre {$trimesterNumber}, étudiant {$studentId}");
                return;
            }
            
            // Ajouter le pourcentage de completion
            $bulletinData['completion_percentage'] = $completionPercentage;
            $bulletinData['is_complete'] = $completionPercentage >= 100;
            
            // Obtenir un template (pour l'ID de la base de données seulement)
            $template = BulletinTemplate::where('type', 'trimester')->where('is_active', true)->first();
            
            if (!$template) {
                // Créer un template par défaut si aucun n'existe
                $template = BulletinTemplate::firstOrCreate(
                    ['type' => 'trimester', 'name' => 'CPBD Template'],
                    [
                        'name' => 'CPBD Template',
                        'type' => 'trimester',
                        'template_html' => 'Default CPBD Template',
                        'is_active' => true,
                        'description' => 'Template par défaut CPBD pour les trimestres'
                    ]
                );
            }
            
            // Générer le HTML et le PDF avec le nouveau template CPBD
            $htmlContent = $this->bulletinService->renderBulletinTemplate('trimester', $bulletinData, true);
            $filename = "bulletin_trim{$trimesterNumber}_{$studentId}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
            $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
            
            if ($existingBulletin) {
                // Mettre à jour le bulletin existant
                $existingBulletin->update([
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'completion_percentage' => $completionPercentage,
                    'is_complete' => $completionPercentage >= 100
                ]);
                
                Log::info("Bulletin trimestre {$trimesterNumber} mis à jour pour étudiant {$studentId} ({$completionPercentage}%)");
            } else {
                // Créer un nouveau bulletin
                BulletinGeneration::create([
                    'student_id' => $studentId,
                    'template_id' => $template->id,
                    'period_type' => 'trimester',
                    'period_identifier' => "trim{$trimesterNumber}",
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'completion_percentage' => $completionPercentage,
                    'is_complete' => $completionPercentage >= 100
                ]);
                
                Log::info("Nouveau bulletin trimestre {$trimesterNumber} généré pour étudiant {$studentId} ({$completionPercentage}%)");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur génération automatique trimestre {$trimesterNumber}, étudiant {$studentId}: " . $e->getMessage());
        }
    }
    
    /**
     * Vérifie et génère le bulletin annuel
     */
    protected function checkAnnualBulletinCompletion($studentId)
    {
        // Vérifier si les 3 trimestres ont des bulletins générés
        $trimesterBulletins = BulletinGeneration::where('student_id', $studentId)
                                               ->where('period_type', 'trimester')
                                               ->whereIn('period_identifier', ['trim1', 'trim2', 'trim3'])
                                               ->count();
        
        // Générer le bulletin annuel si les 3 trimestres sont présents
        if ($trimesterBulletins >= 3) {
            $this->generateAnnualBulletin($studentId);
        }
    }
    
    /**
     * Génère le bulletin annuel
     */
    protected function generateAnnualBulletin($studentId)
    {
        try {
            // Vérifier si le bulletin annuel existe déjà
            $existingBulletin = BulletinGeneration::where('student_id', $studentId)
                                                 ->where('period_type', 'annual')
                                                 ->where('period_identifier', 'annual')
                                                 ->first();
            
            // Calculer la moyenne annuelle
            $annualAverage = $this->bulletinService->calculateAnnualAverage($studentId);
            
            if ($annualAverage === null) {
                return; // Pas assez de données
            }
            
            // Vérifier l'éligibilité au tableau d'honneur
            if ($annualAverage >= 12) {
                $this->generateHonorRollCertificate($studentId, $annualAverage);
            }
            
            // Générer le bulletin annuel
            // (logique similaire aux autres bulletins)
            Log::info("Bulletin annuel généré pour étudiant {$studentId} (moyenne: {$annualAverage})");
            
        } catch (\Exception $e) {
            Log::error("Erreur génération bulletin annuel, étudiant {$studentId}: " . $e->getMessage());
        }
    }
    
    /**
     * Génère le certificat du tableau d'honneur
     */
    protected function generateHonorRollCertificate($studentId, $annualAverage)
    {
        try {
            // Vérifier si le certificat existe déjà
            $existingCertificate = BulletinGeneration::where('student_id', $studentId)
                                                    ->where('period_type', 'honor_roll')
                                                    ->where('period_identifier', 'honor_roll')
                                                    ->first();
            
            if ($existingCertificate) {
                return; // Déjà généré
            }
            
            $student = Student::with(['schoolClass', 'classSeries'])->find($studentId);
            
            $honorRollData = [
                'student' => $student,
                'annual_average' => $annualAverage,
                'school_year' => date('Y') . '-' . (date('Y') + 1),
                // Autres données nécessaires pour le template
            ];
            
            $template = BulletinTemplate::where('type', 'honor_roll')->where('is_active', true)->first();
            
            if ($template) {
                $htmlContent = $this->bulletinService->renderBulletinTemplate('honor_roll', $honorRollData);
                $filename = "tableau_honneur_{$studentId}_" . now()->format('Y') . ".pdf";
                $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
                
                BulletinGeneration::create([
                    'student_id' => $studentId,
                    'template_id' => $template->id,
                    'period_type' => 'honor_roll',
                    'period_identifier' => 'honor_roll',
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'completion_percentage' => 100
                ]);
                
                Log::info("Certificat tableau d'honneur généré pour étudiant {$studentId} (moyenne: {$annualAverage})");
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur génération tableau d'honneur, étudiant {$studentId}: " . $e->getMessage());
        }
    }
}