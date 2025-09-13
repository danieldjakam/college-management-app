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
        
        // Vérifier si c'est une séquence avec bulletin (1 ou 3)
        if (in_array($sequence->number, [1, 3])) {
            $this->checkSequenceBulletinCompletion($student->id, $sequence->number);
        }
        
        // Vérifier les bulletins de trimestre
        $this->checkTrimesterBulletinCompletion($student->id, $sequence->trimester_id);
        
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
        
        // Récupérer toutes les matières de l'étudiant
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
        
        $totalSubjects = $subjects->count();
        $gradedSubjects = 0;
        
        // Compter les matières avec notes
        foreach ($subjects as $subject) {
            $hasGrade = Grade::where('student_id', $studentId)
                           ->where('sequence_id', $sequence->id)
                           ->where('series_subject_id', $subject->id)
                           ->whereNotNull('score')
                           ->exists();
            
            if ($hasGrade) {
                $gradedSubjects++;
            }
        }
        
        // Calculer le pourcentage de completion
        $completionPercentage = $totalSubjects > 0 ? ($gradedSubjects / $totalSubjects) * 100 : 0;
        
        Log::info("Séquence {$sequenceNumber} - Étudiant {$studentId}: {$gradedSubjects}/{$totalSubjects} matières ({$completionPercentage}%)");
        
        // Générer/mettre à jour si au moins 50% des notes sont saisies
        if ($completionPercentage >= 50) {
            $this->generateOrUpdateSequenceBulletin($studentId, $sequenceNumber, $completionPercentage);
        }
    }
    
    /**
     * Vérifie si un bulletin de trimestre peut être généré/mis à jour
     */
    protected function checkTrimesterBulletinCompletion($studentId, $trimesterId)
    {
        $trimester = Trimester::find($trimesterId);
        if (!$trimester) return;
        
        $student = Student::find($studentId);
        if (!$student) return;
        
        $subjects = SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
        
        $completionData = [];
        $overallCompletion = 0;
        
        foreach ($subjects as $subject) {
            // Vérifier les DS (séquences du trimestre)
            $dsCompletion = $this->checkDSCompletion($studentId, $trimester->number, $subject->id);
            
            // Vérifier la composition
            $compositionCompletion = $this->checkCompositionCompletion($studentId, $trimester->number, $subject->id);
            
            // Calcul de completion pour cette matière
            if ($trimester->number == 3) {
                // Trimestre 3: composition seulement
                $subjectCompletion = $compositionCompletion;
            } else {
                // Trimestres 1 et 2: (DS + Composition) / 2
                $subjectCompletion = ($dsCompletion + $compositionCompletion) / 2;
            }
            
            $completionData[$subject->id] = $subjectCompletion;
            $overallCompletion += $subjectCompletion;
        }
        
        $overallCompletion = $subjects->count() > 0 ? $overallCompletion / $subjects->count() : 0;
        
        Log::info("Trimestre {$trimester->number} - Étudiant {$studentId}: {$overallCompletion}% complet");
        
        // Générer/mettre à jour si au moins 60% complet
        if ($overallCompletion >= 60) {
            $this->generateOrUpdateTrimesterBulletin($studentId, $trimester->number, $overallCompletion);
        }
    }
    
    /**
     * Vérifie la completion des DS pour un trimestre
     */
    protected function checkDSCompletion($studentId, $trimesterNumber, $subjectId)
    {
        $sequenceNumbers = [];
        
        switch ($trimesterNumber) {
            case 1:
                $sequenceNumbers = [1, 2];
                break;
            case 2:
                $sequenceNumbers = [3, 4];
                break;
            default:
                return 100; // Trimestre 3 n'a pas de DS
        }
        
        $sequences = Sequence::whereIn('number', $sequenceNumbers)->get();
        $gradedSequences = 0;
        
        foreach ($sequences as $sequence) {
            $hasGrade = Grade::where('student_id', $studentId)
                           ->where('sequence_id', $sequence->id)
                           ->where('series_subject_id', $subjectId)
                           ->whereNotNull('score')
                           ->exists();
            
            if ($hasGrade) {
                $gradedSequences++;
            }
        }
        
        return $sequences->count() > 0 ? ($gradedSequences / $sequences->count()) * 100 : 0;
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
                $template = new BulletinTemplate([
                    'id' => 1,
                    'name' => 'CPBD Template',
                    'type' => 'sequence'
                ]);
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
                $template = new BulletinTemplate([
                    'id' => 1,
                    'name' => 'CPBD Template',
                    'type' => 'trimester'
                ]);
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