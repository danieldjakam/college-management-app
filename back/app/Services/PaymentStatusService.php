<?php

namespace App\Services;

use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\PaymentTranche;
use App\Models\SchoolSetting;
use App\Models\Payment;
use Carbon\Carbon;
use App\Services\NewcomerSchoolFeeDiscountService;
use App\Services\ManualDiscountService;

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
                if ($detail->was_reduced && (strpos($detail->reduction_context, 'Réduction globale') !== false || strpos($detail->reduction_context, 'Nouvelle réduction') !== false)) {
                    $hasAnyReduction = true;
                    break 2;
                }
            }
        }
        
        if ($hasAnyReduction) {
            // Utiliser la logique de réduction par dernières tranches
            $trancheDetails = $this->calculateTrancheDetailsWithLastTrancheReduction($student, $schoolYear, $paymentTranches, $existingPayments);
        } else {
            // Par défaut, afficher les montants normaux
            $trancheDetails = $this->calculateTrancheDetails($student, $schoolYear, $paymentTranches, $existingPayments);
        }
        
        // CORRECTION: Calculer la bourse totale réelle depuis les paiements existants
        $totalScholarshipAmount = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalScholarshipAmount += $payment->scholarship_amount;
            }
        }
        
        // Si pas de bourse dans les paiements, utiliser la méthode de calcul normale
        if ($totalScholarshipAmount == 0) {
            $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);
        }

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

    private function calculateTrancheDetails(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0; // Montant requis après réductions/bourses

        $paidPerTranche = [];
        $discountPerTranche = [];

        // Réduction spécifique "nouveau élève" (2e/3e trimestre) : LECTURE depuis les paiements existants
        // La réduction est maintenant saisie MANUELLEMENT, donc on la lit depuis les payment_details
        $newcomerDiscountPerTranche = []; // tranche_id => montant total de réduction newcomer
        $totalNewcomerDiscountApplied = 0;

        // Réduction manuelle (montant fixe) répartie sur les dernières tranches (hors inscription)
        $manualDiscountService = new ManualDiscountService();
        $manualDiscountAmount = $manualDiscountService->getManualDiscountAmount($student, $schoolYear);
        \Log::info("Manual discount amount for student {$student->id}: {$manualDiscountAmount}");
        $manualDiscountMap = [];
        if ($manualDiscountAmount > 0) {
            $rows = [];
            foreach ($paymentTranches as $t) {
                $req = (float) $t->getAmountForStudent($student, false, false, false);
                \Log::info("Tranche {$t->name} (order {$t->order}): required = {$req}");
                if ($req <= 0) continue;
                $rows[] = ['tranche' => $t, 'required' => $req];
            }
            $manualDiscountMap = $manualDiscountService->distributeAcrossTranches($manualDiscountAmount, $rows);
            \Log::info("Manual discount map: " . json_encode($manualDiscountMap));
        }
        
        // CORRECTION: Calculer la bourse totale réelle des paiements existants + réductions newcomer
        $totalActualScholarship = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarship += $payment->scholarship_amount;
            }

            // Détecter si ce paiement a une réduction newcomer
            $isNewcomerReduction = $payment->has_reduction && $payment->reduction_amount > 0 &&
                (strpos($payment->discount_reason, 'Nouveau élève') !== false ||
                 strpos($payment->discount_reason, 'nouvel élève') !== false ||
                 strpos($payment->discount_reason, 'trimestre') !== false);

            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        'has_discount' => false,
                        'discount_amount' => 0
                    ];
                    $newcomerDiscountPerTranche[$detail->payment_tranche_id] = 0;
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;

                // Si c'est une réduction newcomer, calculer la réduction appliquée sur cette tranche
                if ($isNewcomerReduction) {
                    // Récupérer la tranche pour calculer le montant normal
                    $tranche = $detail->paymentTranche;
                    if ($tranche) {
                        $normalAmount = (float) $tranche->getAmountForStudent($student, false, false, false);
                        $reducedAmount = (float) $detail->required_amount_at_time;
                        $discountForThisTranche = max(0, $normalAmount - $reducedAmount);

                        $newcomerDiscountPerTranche[$detail->payment_tranche_id] += $discountForThisTranche;
                        $totalNewcomerDiscountApplied += $discountForThisTranche;
                    }
                }

                // Vérifier si ce détail a une réduction globale (date limite)
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

        // CORRECTION: Appliquer la bourse séquentiellement sur les tranches non payées
        $remainingScholarshipToApply = $totalActualScholarship;
        
        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            // Appliquer la réduction "nouveau" depuis les paiements existants
            // Cette réduction a été saisie manuellement lors du paiement et distribuée sur les tranches
            $newcomerDiscountAmount = (float) ($newcomerDiscountPerTranche[$tranche->id] ?? 0);

            $manualDiscountOnTranche = (float) ($manualDiscountMap[$tranche->id] ?? 0);
            \Log::info("Tranche {$tranche->name}: manualDiscountOnTranche = {$manualDiscountOnTranche}");

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;
            $trancheRemaining = max(0, $requiredAmount - $paidAmount);
            
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
            
            // Réduction globale (ancienne logique) OU réduction "nouveau" (nouvelle logique)
            $globalDiscountAmount = $newcomerDiscountAmount + $manualDiscountOnTranche;
            $hasGlobalDiscount = $globalDiscountAmount > 0;
            
            // Gérer les cas spéciaux seulement si pas de bourse réelle
            \Log::info("Tranche {$tranche->name}: totalActualScholarship={$totalActualScholarship}, hasGlobalReduction=" . ($hasGlobalReduction ? 'true' : 'false'));
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
                    \Log::info("Tranche {$tranche->name}: PATH = scholarship");
                } elseif ($hasGlobalReduction) {
                    // L'étudiant a fait un paiement intégral avec réduction globale
                    \Log::info("Tranche {$tranche->name}: PATH = hasGlobalReduction, setting remainingAmount=0");
                    $hasGlobalDiscount = true;
                    $globalDiscountAmount = round($requiredAmount * ($discountPercentage / 100), 0);
                    $remainingAmount = 0;
                    $isFullyPaid = true;
                } else {
                    // Utiliser les informations de réduction stockées pour cette tranche spécifique
                    $discountInfo = $discountPerTranche[$tranche->id] ?? ['has_discount' => false, 'discount_amount' => 0];
                    \Log::info("Tranche {$tranche->name}: discountInfo=" . json_encode($discountInfo));

                    if ($discountInfo['has_discount']) {
                        // CORRECTION: Ne pas considérer la tranche comme entièrement payée automatiquement
                        // Il faut calculer le montant effectif requis après TOUTES les réductions
                        $globalDiscountAmount = $discountInfo['discount_amount'] + $newcomerDiscountAmount + $manualDiscountOnTranche;
                        $hasGlobalDiscount = true;

                        // Calculer le montant effectif requis après toutes les réductions
                        $effectiveRequired = max(0, $requiredAmount - $globalDiscountAmount);
                        $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                        $isFullyPaid = ($paidAmount + $globalDiscountAmount) >= $requiredAmount;

                        \Log::info("Tranche {$tranche->name}: requiredAmount={$requiredAmount}, globalDiscountAmount={$globalDiscountAmount}, effectiveRequired={$effectiveRequired}, paidAmount={$paidAmount}, remainingAmount={$remainingAmount}");
                    } else {
                        // Si pas de réduction globale enregistrée, on garde éventuellement la réduction "nouveau" déjà calculée
                        // et on recalcule le reste à payer en tenant compte de cette réduction.
                        \Log::info("Tranche {$tranche->name}: PATH = else/if hasGlobalDiscount");
                        if ($hasGlobalDiscount && $globalDiscountAmount > 0) {
                            $effectiveRequired = max(0, $requiredAmount - $globalDiscountAmount);
                            $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                            $isFullyPaid = ($paidAmount + $globalDiscountAmount) >= $requiredAmount;
                            \Log::info("Tranche {$tranche->name}: requiredAmount={$requiredAmount}, globalDiscountAmount={$globalDiscountAmount}, effectiveRequired={$effectiveRequired}, paidAmount={$paidAmount}, remainingAmount={$remainingAmount}");
                        } else {
                            $hasGlobalDiscount = false;
                            $globalDiscountAmount = 0;
                            $remainingAmount = max(0, $requiredAmount - $paidAmount);
                            $isFullyPaid = $paidAmount >= $requiredAmount;
                        }
                    }
                }
            }
            // Si il y a une bourse réelle, les calculs sont déjà faits plus haut

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
                // Pour compatibilité, on met un % uniquement pour l'ancienne réduction globale.
                // La réduction "nouveau" est maintenant manuelle et sera visible via global_discount_amount.
                'discount_percentage' => ($hasGlobalDiscount && $newcomerDiscountAmount == 0) ? $discountPercentage : 0,
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
                $effectiveRequired = max(0, $requiredAmount - $globalDiscountAmount);
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
        
        // CORRECTION: D'abord vérifier s'il y a des bourses réelles dans les paiements
        $workingYear = $this->getUserWorkingYear();
        if ($workingYear) {
            $existingPayments = Payment::forStudent($student->id)
                ->forYear($workingYear->id)
                ->where('is_rame_physical', false)
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
                // La bourse s'applique à une tranche spécifique
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
            if ($workingYear) {
                return $workingYear;
            }
        }
        return SchoolYear::where('is_current', true)->first() ?? SchoolYear::where('is_active', true)->first();
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
    private function calculateTrancheDetailsWithLastTrancheReduction(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0;

        // Calculer les montants avec la nouvelle logique de réduction
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $reductionResult = $discountCalculator->calculateAmountsWithLastTrancheReduction($student, $paymentTranches);

        // Réduction "nouveau élève" : LECTURE depuis les paiements existants
        $newcomerDiscountPerTranche = []; // tranche_id => montant total de réduction newcomer
        $totalNewcomerDiscountApplied = 0;

        // Réduction manuelle (montant fixe) répartie sur les dernières tranches (hors inscription)
        $manualDiscountService = new ManualDiscountService();
        $manualDiscountAmount = $manualDiscountService->getManualDiscountAmount($student, $schoolYear);
        \Log::info("Manual discount amount for student {$student->id}: {$manualDiscountAmount}");
        $manualDiscountMap = [];
        if ($manualDiscountAmount > 0) {
            $rows = [];
            foreach ($paymentTranches as $t) {
                $req = (float) $t->getAmountForStudent($student, false, false, false);
                \Log::info("Tranche {$t->name} (order {$t->order}): required = {$req}");
                if ($req <= 0) continue;
                $rows[] = ['tranche' => $t, 'required' => $req];
            }
            $manualDiscountMap = $manualDiscountService->distributeAcrossTranches($manualDiscountAmount, $rows);
            \Log::info("Manual discount map: " . json_encode($manualDiscountMap));
        }

        // Calculer ce qui a été payé par tranche + les réductions newcomer appliquées
        $paidPerTranche = [];
        foreach ($existingPayments as $payment) {
            // Détecter si ce paiement a une réduction newcomer
            $isNewcomerReduction = $payment->has_reduction && $payment->reduction_amount > 0 &&
                (strpos($payment->discount_reason, 'Nouveau élève') !== false ||
                 strpos($payment->discount_reason, 'nouvel élève') !== false ||
                 strpos($payment->discount_reason, 'trimestre') !== false);

            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                    $newcomerDiscountPerTranche[$detail->payment_tranche_id] = 0;
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;

                // Si c'est une réduction newcomer, calculer la réduction appliquée sur cette tranche
                if ($isNewcomerReduction) {
                    // Récupérer la tranche pour calculer le montant normal
                    $tranche = $detail->paymentTranche;
                    if ($tranche) {
                        $normalAmount = (float) $tranche->getAmountForStudent($student, false, false, false);
                        $reducedAmount = (float) $detail->required_amount_at_time;
                        $discountForThisTranche = max(0, $normalAmount - $reducedAmount);

                        $newcomerDiscountPerTranche[$detail->payment_tranche_id] += $discountForThisTranche;
                        $totalNewcomerDiscountApplied += $discountForThisTranche;
                    }
                }
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

            // Appliquer la réduction "nouveau" depuis les paiements existants
            $newcomerDiscountAmount = (float) ($newcomerDiscountPerTranche[$tranche->id] ?? 0);

            $manualDiscountOnTranche = (float) ($manualDiscountMap[$tranche->id] ?? 0);

            $effectiveRequiredAmount = max(0, $reducedAmount - $newcomerDiscountAmount - $manualDiscountOnTranche);

            // Utiliser le montant effectif pour calculer ce qui reste à payer
            $remainingAmount = max(0, $effectiveRequiredAmount - $paidAmount);

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
                'effective_required_amount' => $effectiveRequiredAmount, // Montant effectif à payer
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'is_fully_paid' => $remainingAmount <= 0,
                'reduction_applied' => ($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche),
                'has_reduction' => ($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche) > 0,
                'reduction_percentage' => $normalAmount > 0 ? round((($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche) / $normalAmount) * 100, 2) : 0,
                // Propriétés pour compatibilité frontend
                'has_global_discount' => ($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche) > 0,
                'global_discount_amount' => ($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche),
                'discount_percentage' => $normalAmount > 0 ? round((($reductionApplied + $newcomerDiscountAmount + $manualDiscountOnTranche) / $normalAmount) * 100, 2) : 0,
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
            $totalEffectiveRequired += $effectiveRequiredAmount;
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