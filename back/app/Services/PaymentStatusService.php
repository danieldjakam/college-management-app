<?php

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
     * Méthode commune pour calculer le statut d'un étudiant
     */
    private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {

        // Utiliser la logique de réduction par dernières tranches pour les étudiants avec paiements réduits
        $hasAnyReduction = false;
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                if ($detail->was_reduced && (strpos($detail->reduction_context, 'Réduction globale') !== false || strpos($detail->reduction_context, 'Nouvelle réduction') !== false)) {
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
        
        // Calculer le montant total des bourses disponibles (pour information)
        $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);

        $totalRequired = $trancheDetails['totalRequired'];
        $totalPaid = $trancheDetails['totalPaid'];
        $totalEffectiveRequired = $trancheDetails['totalEffectiveRequired']; // Montant requis après bourses/réductions

        $discountInfo = $this->calculateDiscountEligibility(
            $student,
            $totalRequired, // Utiliser les montants normaux pour les réductions
            $totalPaid,
            $existingPayments->count() > 0
        );

        // Calculer le montant total avec réduction si éligible
        $totalRequiredWithDiscount = $totalRequired;
        if ($discountInfo['isEligible']) {
            $totalRequiredWithDiscount = $discountInfo['finalAmount'];
        }

        return (object) [
            'student_id' => $student->id,
            'school_year_id' => $schoolYear->id,
            // Montants normaux affichés partout
            'total_required' => $totalRequired,
            'total_paid' => $totalPaid,
            'total_remaining' => max(0, $totalEffectiveRequired - $totalPaid), // Tenir compte des réductions appliquées
            // Informations sur les bourses (pour calcul de répartition)
            'total_scholarship_amount' => $totalScholarshipAmount,
            'has_scholarships' => $totalScholarshipAmount > 0,
            'has_existing_payments' => $existingPayments->count() > 0,
            'is_eligible_for_discount' => $discountInfo['isEligible'],
            'discount_deadline' => $this->schoolSettings->scholarship_deadline,
            'discount_percentage' => $this->schoolSettings->reduction_percentage,
            'discount_amount' => $discountInfo['amount'],
            'amount_to_pay_with_discount' => $discountInfo['finalAmount'],
            'total_required_with_discount' => $totalRequiredWithDiscount,
            'payment_tranches' => $paymentTranches,
            'existing_payments' => $existingPayments,
            'tranche_status' => $trancheDetails['status'], // Montants normaux
        ];
    }

    private function getApplicableTranches(Student $student)
    {
        return PaymentTranche::active()
            ->ordered()
            ->with(['classPaymentAmounts' => function ($query) use ($student) {
                if ($student->classSeries && $student->classSeries->schoolClass) {
                    $query->where('class_id', $student->classSeries->schoolClass->id);
                }
            }])
            ->get();
    }

    private function getExistingPayments(int $studentId, int $schoolYearId)
    {
        return Payment::forStudent($studentId)
            ->forYear($schoolYearId)
            ->where('is_rame_physical', false)
            ->with(['paymentDetails.paymentTranche'])
            ->orderBy('payment_date', 'asc')
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
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        'has_discount' => false,
                        'discount_amount' => 0
                    ];
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;
                
                // Vérifier si ce détail a une réduction globale
                if ($detail->was_reduced && (strpos($detail->reduction_context, 'Réduction globale') !== false || strpos($detail->reduction_context, 'Nouvelle réduction') !== false)) {
                    $schoolSettings = \App\Models\SchoolSetting::getSettings();
                    $discountPercentage = $schoolSettings->reduction_percentage ?? 0;
                    
                    // Le montant normal est calculé à partir du montant réduit stocké
                    $reducedAmount = $detail->required_amount_at_time;
                    $normalAmount = round($reducedAmount / (1 - $discountPercentage / 100), 0);
                    $discountAmount = $normalAmount - $reducedAmount;
                    
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        'has_discount' => true,
                        'discount_amount' => $discountAmount
                    ];
                }
            }
        }

        // Récupérer les informations de bourse et réduction
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $scholarship = $discountCalculator->getClassScholarship($student);
        $discountPercentage = $this->schoolSettings->reduction_percentage ?? 0;
        
        // Détecter si l'étudiant a bénéficié d'une réduction globale intégrale
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

        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;

            // Vérifier si cette tranche bénéficie d'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            $remainingAmount = 0;
            $isFullyPaid = false;
            
            if ($scholarship && $scholarship->payment_tranche_id == $tranche->id && $discountCalculator->isEligibleForScholarship(now())) {
                // Cas avec bourse
                $scholarshipAmount = $scholarship->amount;
                $hasScholarship = true;
                
                // Calculer le restant en tenant compte de la bourse
                $effectiveRequired = max(0, $requiredAmount - $scholarshipAmount);
                $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                $isFullyPaid = ($paidAmount + $scholarshipAmount) >= $requiredAmount;
            } else {
                // Cas normal - vérifier d'abord si réduction globale intégrale
                if ($hasGlobalReduction) {
                    // L'étudiant a fait un paiement intégral avec réduction globale
                    $hasGlobalDiscount = true;
                    $globalDiscountAmount = round($requiredAmount * ($discountPercentage / 100), 0);
                    $remainingAmount = 0;
                    $isFullyPaid = true;
                } else {
                    // Utiliser les informations de réduction stockées pour cette tranche spécifique
                    $discountInfo = $discountPerTranche[$tranche->id] ?? ['has_discount' => false, 'discount_amount' => 0];
                    
                    if ($discountInfo['has_discount']) {
                        $hasGlobalDiscount = true;
                        $globalDiscountAmount = $discountInfo['discount_amount'];
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

            $trancheStatus[] = [
                'tranche_id' => $tranche->id,
                'tranche_name' => $tranche->name,
                'tranche_order' => $tranche->order,
                'required_amount' => $requiredAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'is_fully_paid' => $isFullyPaid,
                'has_scholarship' => $hasScholarship,
                'scholarship_amount' => $scholarshipAmount,
                'has_global_discount' => $hasGlobalDiscount,
                'global_discount_amount' => $globalDiscountAmount,
                'discount_percentage' => $hasGlobalDiscount ? $discountPercentage : 0,
                // Propriétés par défaut pour compatibilité
                'is_physical_only' => false,
                'is_optional' => false,
                'rame_paid' => false,
                // Objet tranche pour compatibilité avec le frontend existant
                'tranche' => [
                    'id' => $tranche->id,
                    'name' => $tranche->name,
                    'order' => $tranche->order,
                    'description' => $tranche->description ?? ''
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
            'status' => $trancheStatus,
            'totalRequired' => $totalRequired,
            'totalPaid' => $totalPaid,
            'totalEffectiveRequired' => $totalEffectiveRequired,
        ];
    }

    private function calculateTotalScholarshipAmount(Student $student, $paymentTranches)
    {
        $totalScholarshipAmount = 0;
        
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $scholarship = $discountCalculator->getClassScholarship($student);
        
        if ($scholarship && $discountCalculator->isEligibleForScholarship(now())) {
            // La bourse s'applique à une tranche spécifique
            foreach ($paymentTranches as $tranche) {
                if ($tranche->id == $scholarship->payment_tranche_id) {
                    $totalScholarshipAmount = $scholarship->amount;
                    break;
                }
            }
        }
        
        return $totalScholarshipAmount;
    }

    /**
     * Obtenir les détails des tranches avec réduction appliquée depuis les dernières tranches
     */
    public function getTranchesWithDiscount(Student $student, SchoolYear $schoolYear): array
    {
        $paymentTranches = $this->getApplicableTranches($student);
        $existingPayments = $this->getExistingPayments($student->id, $schoolYear->id);
        $discountPercentage = $this->schoolSettings->reduction_percentage ?? 0;
        
        // Utiliser la nouvelle logique de réduction depuis les dernières tranches
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $reductionResult = $discountCalculator->calculateAmountsWithLastTrancheReduction($student, $paymentTranches);
        
        $trancheDetails = [];
        $totalRequired = 0;
        $totalRequiredWithDiscount = 0;
        
        foreach ($reductionResult['tranches'] as $trancheData) {
            $tranche = $trancheData['tranche'];
            $normalAmount = $trancheData['normal_amount'];
            $reducedAmount = $trancheData['reduced_amount'];
            $reductionApplied = $trancheData['reduction_applied'];
            
            if ($normalAmount <= 0) continue;
            
            $trancheDetails[] = [
                'tranche' => $tranche,
                'normal_amount' => $normalAmount,
                'discount_amount' => $reductionApplied,
                'reduced_amount' => $reducedAmount,
                'discount_percentage' => $normalAmount > 0 ? round(($reductionApplied / $normalAmount) * 100, 2) : 0
            ];
            
            $totalRequired += $normalAmount;
            $totalRequiredWithDiscount += $reducedAmount;
        }
        
        return [
            'tranches' => $trancheDetails,
            'total_normal' => $totalRequired,
            'total_with_discount' => $totalRequiredWithDiscount,
            'total_discount_amount' => $reductionResult['total_reduction'],
            'discount_percentage' => $discountPercentage
        ];
    }

    /**
     * Nouvelle méthode qui calcule les détails des tranches avec la logique de réduction par dernières tranches
     */
    private function calculateTrancheDetailsWithLastTrancheReduction(Student $student, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0;

        // Calculer les montants avec la nouvelle logique de réduction
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $reductionResult = $discountCalculator->calculateAmountsWithLastTrancheReduction($student, $paymentTranches);

        // Calculer ce qui a été payé par tranche
        $paidPerTranche = [];
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;
            }
        }

        // Traiter chaque tranche avec les nouveaux montants
        foreach ($reductionResult['tranches'] as $trancheData) {
            $tranche = $trancheData['tranche'];
            $normalAmount = $trancheData['normal_amount'];
            $reducedAmount = $trancheData['reduced_amount'];
            $reductionApplied = $trancheData['reduction_applied'];

            if ($normalAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;
            
            // Utiliser le montant réduit pour calculer ce qui reste à payer
            $remainingAmount = max(0, $reducedAmount - $paidAmount);

            // Calculer les informations de bourse pour cette tranche
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $discountCalculator = new \App\Services\DiscountCalculatorService();
            $scholarship = $discountCalculator->getClassScholarship($student);
            if ($scholarship && $scholarship->payment_tranche_id == $tranche->id && $discountCalculator->isEligibleForScholarship(now())) {
                $scholarshipAmount = min($scholarship->amount, $normalAmount);
                $hasScholarship = true;
            }

            $trancheStatus[] = [
                'tranche_id' => $tranche->id,
                'tranche_name' => $tranche->name,
                'tranche_order' => $tranche->order,
                'required_amount' => $normalAmount, // Montant normal pour affichage
                'effective_required_amount' => $reducedAmount, // Montant effectif à payer
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'is_fully_paid' => $remainingAmount <= 0,
                'reduction_applied' => $reductionApplied,
                'has_reduction' => $reductionApplied > 0,
                'reduction_percentage' => $normalAmount > 0 ? round(($reductionApplied / $normalAmount) * 100, 2) : 0,
                // Propriétés pour compatibilité frontend
                'has_global_discount' => $reductionApplied > 0,
                'global_discount_amount' => $reductionApplied,
                'discount_percentage' => $normalAmount > 0 ? round(($reductionApplied / $normalAmount) * 100, 2) : 0,
                // Propriétés de bourse
                'has_scholarship' => $hasScholarship,
                'scholarship_amount' => $scholarshipAmount,
                // Propriétés par défaut pour compatibilité
                'is_physical_only' => false, // Cette tranche n'est pas physique seulement
                'is_optional' => false, // Par défaut, les tranches ne sont pas optionnelles
                'rame_paid' => false, // Pour la RAME, géré séparément
                // Objet tranche pour compatibilité avec le frontend existant
                'tranche' => [
                    'id' => $tranche->id,
                    'name' => $tranche->name,
                    'order' => $tranche->order,
                    'description' => $tranche->description ?? ''
                ]
            ];

            $totalRequired += $normalAmount;
            $totalPaid += $paidAmount;
            $totalEffectiveRequired += $reducedAmount;
        }

        return [
            'status' => $trancheStatus,
            'totalRequired' => $totalRequired,
            'totalPaid' => $totalPaid,
            'totalEffectiveRequired' => $totalEffectiveRequired,
            'totalReduction' => $reductionResult['total_reduction']
        ];
    }

    private function calculateDiscountEligibility(Student $student, float $totalRequired, float $totalPaid, bool $hasExistingPayments)
    {
        $isEligible = false;
        $discountAmount = 0;
        $finalAmount = $totalRequired - $totalPaid;

        $deadline = $this->schoolSettings->scholarship_deadline;
        $percentage = $this->schoolSettings->reduction_percentage;

        // Vérifier que l'étudiant n'a pas de bourse (exclusion mutuelle)
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $hasScholarship = $discountCalculator->getClassScholarship($student) !== null;

        if ($deadline && $percentage > 0 && !$hasExistingPayments && $totalPaid == 0 && !$hasScholarship) {
            $isEligible = true;
            $discountAmount = $totalRequired * ($percentage / 100);
            $finalAmount = $totalRequired - $discountAmount;
        }

        return [
            'isEligible' => $isEligible,
            'amount' => $discountAmount,
            'finalAmount' => $finalAmount,
        ];
    }
}