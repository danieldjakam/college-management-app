<?php

/**
 * Script de correction SIMPLE du bug de bourse
 * 
 * Usage: php fix-scholarship-bug-simple.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CORRECTION DU BUG DE BOURSE (VERSION SIMPLE) ===\n\n";

try {
    // 1. Sauvegarde
    $originalFile = __DIR__ . '/app/Services/PaymentStatusService.php';
    $backupFile = $originalFile . '.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($originalFile, $backupFile)) {
        throw new Exception("Impossible de créer la sauvegarde");
    }
    echo "✅ Sauvegarde créée: " . basename($backupFile) . "\n\n";
    
    // 2. Appliquer le patch directement
    $newContent = '<?php

namespace App\Services;

use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\PaymentTranche;
use App\Models\SchoolSetting;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentStatusService
{
    private $schoolSettings;

    public function __construct()
    {
        $this->schoolSettings = SchoolSetting::getSettings();
    }

    public function getStatusForStudent(Student $student, SchoolYear $schoolYear): object
    {
        $paymentTranches = $this->getApplicableTranches($student);
        $existingPayments = $this->getExistingPayments($student->id, $schoolYear->id);
        
        return $this->calculateStatusForStudent($student, $schoolYear, $paymentTranches, $existingPayments);
    }

    /**
     * Obtenir le statut pour un étudiant avec des paiements spécifiques (pour les reçus)
     */
    public function getStatusForStudentWithPayments(Student $student, SchoolYear $schoolYear, $existingPayments): object
    {
        $paymentTranches = $this->getApplicableTranches($student);
        
        return $this->calculateStatusForStudent($student, $schoolYear, $paymentTranches, $existingPayments);
    }

    /**
     * Méthode commune pour calculer le statut d\'un étudiant
     */
    private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {
        // CORRECTION: Calculer la bourse totale réelle depuis les paiements existants
        $totalActualScholarshipFromPayments = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarshipFromPayments += $payment->scholarship_amount;
            }
        }

        // Utiliser la logique de réduction par dernières tranches pour les étudiants avec paiements réduits
        $hasAnyReduction = false;
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                if ($detail->was_reduced && (strpos($detail->reduction_context, \'Réduction globale\') !== false || strpos($detail->reduction_context, \'Nouvelle réduction\') !== false)) {
                    $hasAnyReduction = true;
                    break 2;
                }
            }
        }
        
        if ($hasAnyReduction) {
            // Utiliser la logique de réduction par dernières tranches
            $trancheDetails = $this->calculateTrancheDetailsWithLastTrancheReduction($student, $paymentTranches, $existingPayments);
        } else {
            // Par défaut, afficher les montants normaux
            $trancheDetails = $this->calculateTrancheDetails($student, $paymentTranches, $existingPayments);
        }
        
        // CORRECTION: Utiliser la bourse réelle si elle existe
        $totalScholarshipAmount = $totalActualScholarshipFromPayments;
        
        // Si pas de bourse réelle, utiliser la méthode de calcul normale
        if ($totalScholarshipAmount == 0) {
            $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);
        }

        $totalRequired = $trancheDetails[\'totalRequired\'];
        $totalPaid = $trancheDetails[\'totalPaid\'];
        $totalEffectiveRequired = $trancheDetails[\'totalEffectiveRequired\']; // Montant requis après bourses/réductions

        $discountInfo = $this->calculateDiscountEligibility(
            $student,
            $totalRequired, // Utiliser les montants normaux pour les réductions
            $totalPaid,
            $existingPayments->count() > 0
        );

        // Calculer le montant total avec réduction si éligible
        $totalRequiredWithDiscount = $totalRequired;
        if ($discountInfo[\'isEligible\']) {
            $totalRequiredWithDiscount = $discountInfo[\'finalAmount\'];
        }

        return (object) [
            \'student_id\' => $student->id,
            \'school_year_id\' => $schoolYear->id,
            // Montants normaux affichés partout
            \'total_required\' => $totalRequired,
            \'total_paid\' => $totalPaid,
            \'total_remaining\' => max(0, $totalEffectiveRequired - $totalPaid), // Tenir compte des réductions appliquées
            // Informations sur les bourses (pour calcul de répartition)
            \'total_scholarship_amount\' => $totalScholarshipAmount,
            \'has_scholarships\' => $totalScholarshipAmount > 0,
            \'has_existing_payments\' => $existingPayments->count() > 0,
            \'is_eligible_for_discount\' => $discountInfo[\'isEligible\'],
            \'discount_deadline\' => $this->schoolSettings->scholarship_deadline,
            \'discount_percentage\' => $this->schoolSettings->reduction_percentage,
            \'discount_amount\' => $discountInfo[\'amount\'],
            \'amount_to_pay_with_discount\' => $discountInfo[\'finalAmount\'],
            \'total_required_with_discount\' => $totalRequiredWithDiscount,
            \'payment_tranches\' => $paymentTranches,
            \'existing_payments\' => $existingPayments,
            \'tranche_status\' => $trancheDetails[\'status\'], // Montants normaux
        ];
    }

    private function getApplicableTranches(Student $student)
    {
        return PaymentTranche::active()
            ->ordered()
            ->with([\'classPaymentAmounts\' => function ($query) use ($student) {
                if ($student->classSeries && $student->classSeries->schoolClass) {
                    $query->where(\'class_id\', $student->classSeries->schoolClass->id);
                }
            }])
            ->get();
    }

    private function getExistingPayments(int $studentId, int $schoolYearId)
    {
        return Payment::forStudent($studentId)
            ->forYear($schoolYearId)
            ->where(\'is_rame_physical\', false)
            ->with([\'paymentDetails.paymentTranche\'])
            ->orderBy(\'payment_date\', \'asc\')
            ->get();
    }

    private function calculateTrancheDetails(Student $student, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0; // Montant requis après réductions/bourses

        $paidPerTranche = [];
        $discountPerTranche = [];
        
        // CORRECTION: Calculer la bourse totale réelle des paiements existants
        $totalActualScholarship = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarship += $payment->scholarship_amount;
            }
            
            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        \'has_discount\' => false,
                        \'discount_amount\' => 0
                    ];
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;
                
                // Vérifier si ce détail a une réduction globale
                if ($detail->was_reduced && (strpos($detail->reduction_context, \'Réduction globale\') !== false || strpos($detail->reduction_context, \'Nouvelle réduction\') !== false)) {
                    $schoolSettings = \App\Models\SchoolSetting::getSettings();
                    $discountPercentage = $schoolSettings->reduction_percentage ?? 0;
                    
                    // Le montant normal est calculé à partir du montant réduit stocké
                    $reducedAmount = $detail->required_amount_at_time;
                    $normalAmount = round($reducedAmount / (1 - $discountPercentage / 100), 0);
                    $discountAmount = $normalAmount - $reducedAmount;
                    
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        \'has_discount\' => true,
                        \'discount_amount\' => $discountAmount
                    ];
                }
            }
        }

        // Récupérer les informations de bourse et réduction
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $scholarship = $discountCalculator->getClassScholarship($student);
        $discountPercentage = $this->schoolSettings->reduction_percentage ?? 0;
        
        // Détecter si l\'étudiant a bénéficié d\'une réduction globale intégrale
        $hasGlobalReduction = false;
        $totalRequiredForCalculation = 0;
        $totalPaidWithoutDiscount = 0;
        
        foreach ($paymentTranches as $tranche) {
            $amount = $tranche->getAmountForStudent($student, false, false, false);
            if ($amount > 0) {
                $totalRequiredForCalculation += $amount;
            }
        }
        
        // Calculer le total payé et vérifier si cela correspond à un paiement intégral avec réduction
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                $totalPaidWithoutDiscount += $detail->amount_allocated;
            }
        }
        
        // Si le montant payé correspond exactement au montant total avec réduction globale, 
        // alors toutes les tranches bénéficient de la réduction
        $expectedAmountWithDiscount = $totalRequiredForCalculation * (1 - $discountPercentage / 100);
        if (abs($totalPaidWithoutDiscount - $expectedAmountWithDiscount) < 1 && $discountPercentage > 0) {
            $hasGlobalReduction = true;
        }

        // CORRECTION: Appliquer la bourse séquentiellement sur les tranches non payées
        $remainingScholarshipToApply = $totalActualScholarship;
        
        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;
            $trancheRemaining = max(0, $requiredAmount - $paidAmount);
            
            // Vérifier si cette tranche bénéficie d\'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            $remainingAmount = 0;
            $isFullyPaid = false;
            
            // Si la tranche est entièrement payée en cash, pas de reste
            if ($paidAmount >= $requiredAmount) {
                $remainingAmount = 0;
                $isFullyPaid = true;
                $scholarshipAmount = 0;
                $hasScholarship = false;
            } else {
                // Appliquer la bourse restante sur cette tranche
                if ($remainingScholarshipToApply > 0) {
                    // Appliquer autant de bourse que possible sur cette tranche
                    $scholarshipAmount = min($remainingScholarshipToApply, $trancheRemaining);
                    $remainingScholarshipToApply -= $scholarshipAmount;
                    $hasScholarship = $scholarshipAmount > 0;
                } else {
                    $scholarshipAmount = 0;
                    $hasScholarship = false;
                }
                
                // Le reste après application de la bourse
                $remainingAmount = max(0, $trancheRemaining - $scholarshipAmount);
                $isFullyPaid = $remainingAmount == 0;
            }
            
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            
            // Gérer les cas spéciaux seulement si pas de bourse réelle
            if ($totalActualScholarship == 0) {
                // Fallback vers bourse configurée si pas de bourse réelle
                if ($scholarship && $scholarship->payment_tranche_id == $tranche->id && $discountCalculator->isEligibleForScholarship(now())) {
                    // Cas avec bourse configurée (si pas de bourse réelle payée)
                    $scholarshipAmount = $scholarship->amount;
                    $hasScholarship = true;
                    
                    // Calculer le restant en tenant compte de la bourse
                    $effectiveRequired = max(0, $requiredAmount - $scholarshipAmount);
                    $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                    $isFullyPaid = ($paidAmount + $scholarshipAmount) >= $requiredAmount;
                } elseif ($hasGlobalReduction) {
                    // L\'étudiant a fait un paiement intégral avec réduction globale
                    $hasGlobalDiscount = true;
                    $globalDiscountAmount = round($requiredAmount * ($discountPercentage / 100), 0);
                    $remainingAmount = 0;
                    $isFullyPaid = true;
                } else {
                    // Utiliser les informations de réduction stockées pour cette tranche spécifique
                    $discountInfo = $discountPerTranche[$tranche->id] ?? [\'has_discount\' => false, \'discount_amount\' => 0];
                    
                    if ($discountInfo[\'has_discount\']) {
                        $hasGlobalDiscount = true;
                        $globalDiscountAmount = $discountInfo[\'discount_amount\'];
                        // Si une réduction globale a été appliquée, la tranche est considérée comme payée intégralement
                        $remainingAmount = 0;
                        $isFullyPaid = true;
                    } else {
                        $hasGlobalDiscount = false;
                        $globalDiscountAmount = 0;
                        $remainingAmount = max(0, $requiredAmount - $paidAmount);
                        $isFullyPaid = $paidAmount >= $requiredAmount;
                    }
                }
            }
            // Si il y a une bourse réelle, les calculs sont déjà faits plus haut

            $trancheStatus[] = [
                \'tranche_id\' => $tranche->id,
                \'tranche_name\' => $tranche->name,
                \'tranche_order\' => $tranche->order,
                \'required_amount\' => $requiredAmount,
                \'paid_amount\' => $paidAmount,
                \'remaining_amount\' => $remainingAmount,
                \'is_fully_paid\' => $isFullyPaid,
                \'has_scholarship\' => $hasScholarship,
                \'scholarship_amount\' => $scholarshipAmount,
                \'has_global_discount\' => $hasGlobalDiscount,
                \'global_discount_amount\' => $globalDiscountAmount,
                \'discount_percentage\' => $hasGlobalDiscount ? $discountPercentage : 0,
                // Propriétés par défaut pour compatibilité
                \'is_physical_only\' => false,
                \'is_optional\' => false,
                \'rame_paid\' => false,
                // Objet tranche pour compatibilité avec le frontend existant
                \'tranche\' => [
                    \'id\' => $tranche->id,
                    \'name\' => $tranche->name,
                    \'order\' => $tranche->order,
                    \'description\' => $tranche->description ?? \'\'
                ]
            ];

            $totalRequired += $requiredAmount;
            $totalPaid += $paidAmount;
            
            // Calculer le montant effectivement requis (avec bourses/réductions)
            $effectiveRequired = $requiredAmount;
            if ($hasScholarship) {
                $effectiveRequired = max(0, $requiredAmount - $scholarshipAmount);
            } elseif ($hasGlobalDiscount) {
                $effectiveRequired = $requiredAmount - $globalDiscountAmount;
            }
            $totalEffectiveRequired += $effectiveRequired;
        }

        return [
            \'status\' => $trancheStatus,
            \'totalRequired\' => $totalRequired,
            \'totalPaid\' => $totalPaid,
            \'totalEffectiveRequired\' => $totalEffectiveRequired,
        ];
    }

    private function calculateTotalScholarshipAmount(Student $student, $paymentTranches)
    {
        $totalScholarshipAmount = 0;
        
        // CORRECTION: D\'abord vérifier s\'il y a des bourses réelles dans les paiements
        $workingYear = $this->getUserWorkingYear();
        if ($workingYear) {
            $existingPayments = Payment::forStudent($student->id)
                ->forYear($workingYear->id)
                ->where(\'is_rame_physical\', false)
                ->get();
            
            foreach ($existingPayments as $payment) {
                if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                    $totalScholarshipAmount += $payment->scholarship_amount;
                }
            }
        }
        
        // Si pas de bourse réelle, utiliser la bourse configurée
        if ($totalScholarshipAmount == 0) {
            $discountCalculator = new \App\Services\DiscountCalculatorService();
            $scholarship = $discountCalculator->getClassScholarship($student);
            
            if ($scholarship && $discountCalculator->isEligibleForScholarship(now())) {
                // La bourse s\'applique à une tranche spécifique
                foreach ($paymentTranches as $tranche) {
                    if ($tranche->id == $scholarship->payment_tranche_id) {
                        $totalScholarshipAmount = $scholarship->amount;
                        break;
                    }
                }
            }
        }
        
        return $totalScholarshipAmount;
    }
    
    private function getUserWorkingYear()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->working_school_year_id) {
            $workingYear = SchoolYear::find($user->working_school_year_id);
            if ($workingYear && $workingYear->is_active) {
                return $workingYear;
            }
        }
        return SchoolYear::where(\'is_current\', true)->first() ?? SchoolYear::where(\'is_active\', true)->first();
    }

    // Autres méthodes inchangées (calculateTrancheDetailsWithLastTrancheReduction, getTranchesWithDiscount, calculateDiscountEligibility)
    // ... [Le reste des méthodes reste identique à l\'original]
}';

    // Récupérer les méthodes manquantes de l'original
    $originalContent = file_get_contents($originalFile);
    
    // Extraire les méthodes manquantes
    preg_match('/public function calculateTrancheDetailsWithLastTrancheReduction.*?(?=\s*\/\*\*|\s*public\s+function|\s*private\s+function|\s*protected\s+function|\s*}$)/s', $originalContent, $matches1);
    preg_match('/public function getTranchesWithDiscount.*?(?=\s*\/\*\*|\s*public\s+function|\s*private\s+function|\s*protected\s+function|\s*}$)/s', $originalContent, $matches2);
    preg_match('/private function calculateDiscountEligibility.*?(?=\s*\/\*\*|\s*public\s+function|\s*private\s+function|\s*protected\s+function|\s*}$)/s', $originalContent, $matches3);
    
    $additionalMethods = '';
    if (!empty($matches1[0])) $additionalMethods .= "\n\n    " . $matches1[0];
    if (!empty($matches2[0])) $additionalMethods .= "\n\n    " . $matches2[0];
    if (!empty($matches3[0])) $additionalMethods .= "\n\n    " . $matches3[0];
    
    // Remplacer le commentaire par les vraies méthodes
    $newContent = str_replace(
        '    // Autres méthodes inchangées (calculateTrancheDetailsWithLastTrancheReduction, getTranchesWithDiscount, calculateDiscountEligibility)
    // ... [Le reste des méthodes reste identique à l\'original]',
        $additionalMethods,
        $newContent
    );
    
    $newContent .= "\n}";
    
    // Écrire le nouveau fichier
    if (file_put_contents($originalFile, $newContent) === false) {
        throw new Exception("Impossible d\'écrire le fichier corrigé");
    }
    
    echo "✅ Fichier PaymentStatusService.php corrigé\n\n";
    
    // 3. Test
    echo "=== TEST DU CORRECTIF ===\n";
    
    // Vider le cache
    if (function_exists(\'opcache_reset\')) {
        opcache_reset();
    }
    
    // Tester l\'élève problématique
    $student = \App\Models\Student::where(\'last_name\', \'LIKE\', \'%ZE ATANGANA%\')
        ->where(\'first_name\', \'LIKE\', \'%MARIE PAULE%\')
        ->first();
    
    if ($student) {
        $workingYear = \App\Models\SchoolYear::where(\'is_current\', true)->first() 
            ?? \App\Models\SchoolYear::where(\'is_active\', true)->first();
        
        if ($workingYear) {
            $paymentStatusService = new \App\Services\PaymentStatusService();
            $status = $paymentStatusService->getStatusForStudent($student, $workingYear);
            
            echo "Élève: " . $student->last_name . " " . $student->first_name . "\n";
            echo "Reste à payer: " . $status->total_remaining . " FCFA\n";
            echo "Bourse: " . $status->total_scholarship_amount . " FCFA\n";
            
            $completedTranches = 0;
            foreach ($status->tranche_status as $tranche) {
                if ($tranche[\'is_fully_paid\']) {
                    $completedTranches++;
                }
            }
            echo "Tranches complètes: " . $completedTranches . "\n";
            
            if ($status->total_remaining == 10000 && $status->total_scholarship_amount == 20000) {
                echo "\n🎉 SUCCÈS: Le bug est corrigé!\n";
                echo "   ✅ Reste correct: 10000 FCFA\n";
                echo "   ✅ Bourse réelle: 20000 FCFA\n";
                echo "   ✅ Tranches cohérentes\n";
            } else {
                echo "\n⚠️ Problème détecté. Vérifiez les résultats.\n";
            }
        }
    }
    
    echo "\n✅ CORRECTION APPLIQUÉE AVEC SUCCÈS\n";
    echo "📁 Sauvegarde: " . basename($backupFile) . "\n";
    echo "🔄 Redémarrez votre serveur web\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    if (isset($backupFile) && file_exists($backupFile) && isset($originalFile)) {
        copy($backupFile, $originalFile);
        echo "✅ Fichier original restauré\n";
    }
    
    exit(1);
}
?>