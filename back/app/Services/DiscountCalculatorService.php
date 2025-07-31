<?php

namespace App\Services;

use App\Models\Student;
use App\Models\SchoolSetting;
use App\Models\ClassScholarship;
use Carbon\Carbon;

class DiscountCalculatorService
{
    private $schoolSettings;

    public function __construct()
    {
        $this->schoolSettings = SchoolSetting::getSettings();
    }

    /**
     * Calculer les bourses et réductions applicables pour un étudiant
     * NOUVELLE LOGIQUE: 
     * - Bourse déduite de la tranche spécifiée lors de la création de la bourse
     * - Réduction s'applique directement aux montants par tranche
     */
    public function calculateDiscounts(Student $student, $totalAmount, $paymentDate = null, $isFullPayment = false, $totalSchoolFees = null, $hasExistingPayments = false)
    {
        $paymentDate = $paymentDate ? Carbon::parse($paymentDate) : now();
        
        $result = [
            'has_scholarship' => false,
            'scholarship_amount' => 0,
            'has_reduction' => false,
            'reduction_amount' => 0,
            'discount_reason' => null,
            'final_amount' => $totalAmount
        ];

        // 1. Vérifier les bourses de classe (déduite de la tranche spécifiée)
        $scholarship = $this->getClassScholarship($student);
        if ($scholarship && $this->isEligibleForScholarship($paymentDate)) {
            $result['has_scholarship'] = true;
            $result['scholarship_amount'] = $scholarship->amount;
            $trancheName = $scholarship->paymentTranche ? $scholarship->paymentTranche->name : 'inscription';
            $result['discount_reason'] = "Bourse: {$scholarship->name} (déduite de {$trancheName})";
        }

        // 2. Vérifier l'éligibilité à la réduction (SEULEMENT pour paiement total avant délai ET sans paiements antérieurs ET pas de bourse)
        if (!$result['has_scholarship'] && $this->isEligibleForFullPaymentReduction($student, $paymentDate, $isFullPayment, $totalSchoolFees, $totalAmount, $hasExistingPayments)) {
            $result['has_reduction'] = true;
            // La réduction sera appliquée directement aux montants par tranche via PaymentTranche::getAmountForStudent()
            // On ne calcule plus la réduction ici, c'est juste un indicateur d'éligibilité
            
            $reductionReason = [];
            if ($student->isOld()) {
                $reductionReason[] = "Ancien élève";
            }
            if ($this->isPaidBeforeDeadline($paymentDate)) {
                $reductionReason[] = "Paiement intégral avant délai";
            }
            
            $reductionText = "Réduction " . $this->schoolSettings->reduction_percentage . "% (montants par tranche réduits): " . implode(', ', $reductionReason);
            
            if ($result['discount_reason']) {
                $result['discount_reason'] .= " | " . $reductionText;
            } else {
                $result['discount_reason'] = $reductionText;
            }
        }

        // 3. Le montant final reste le montant original car :
        // - La bourse ne s'applique pas aux tranches (seulement à l'inscription)
        // - La réduction est appliquée aux montants requis par tranche
        $result['final_amount'] = $totalAmount;

        return $result;
    }

    /**
     * Obtenir la bourse applicable pour la classe de l'étudiant
     */
    public function getClassScholarship(Student $student)
    {
        if (!$student->classSeries || !$student->classSeries->schoolClass) {
            return null;
        }

        return ClassScholarship::active()
            ->with('paymentTranche')
            ->where('school_class_id', $student->classSeries->schoolClass->id)
            ->first();
    }

    /**
     * Vérifier si l'étudiant est éligible à une bourse
     */
    public function isEligibleForScholarship($paymentDate)
    {
        if (!$this->schoolSettings->scholarship_deadline) {
            return true; // Pas de délai défini
        }

        return $paymentDate->lte($this->schoolSettings->scholarship_deadline);
    }

    /**
     * Vérifier si l'étudiant est éligible à une réduction (ANCIENNE MÉTHODE - conservée pour compatibilité)
     */
    private function isEligibleForReduction(Student $student, $paymentDate)
    {
        // Réduction pour anciens élèves
        if ($student->isOld()) {
            return true;
        }

        // Réduction pour nouveaux élèves qui paient avant le délai
        if ($student->isNew() && $this->isPaidBeforeDeadline($paymentDate)) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'étudiant est éligible à une réduction pour paiement intégral
     * NOUVELLE LOGIQUE: Réduction seulement si paiement de TOUTE la scolarité en une fois ET aucun paiement antérieur
     */
    private function isEligibleForFullPaymentReduction(Student $student, $paymentDate, $isFullPayment, $totalSchoolFees, $paymentAmount, $hasExistingPayments)
    {
        // Conditions strictes pour la réduction
        
        // 1. Doit être un paiement complet de TOUTE la scolarité
        if (!$isFullPayment || !$totalSchoolFees || $paymentAmount < $totalSchoolFees) {
            return false;
        }
        
        // 2. CRITIQUE: Pas de paiements antérieurs autorisés
        if ($hasExistingPayments) {
            return false; // Déjà payé partiellement, plus éligible à la réduction
        }
        
        // 3. Réduction pour anciens élèves (peut payer n'importe quand)
        if ($student->isOld()) {
            return true;
        }

        // 4. Réduction pour nouveaux élèves SEULEMENT s'ils paient avant le délai
        if ($student->isNew() && $this->isPaidBeforeDeadline($paymentDate)) {
            return true;
        }

        return false;
    }


    /**
     * Vérifier si le paiement est effectué avant le délai
     */
    private function isPaidBeforeDeadline($paymentDate)
    {
        if (!$this->schoolSettings->scholarship_deadline) {
            return false;
        }

        return $paymentDate->lte($this->schoolSettings->scholarship_deadline);
    }

    /**
     * Obtenir les informations de réduction pour affichage
     */
    public function getDiscountInfo(Student $student)
    {
        $info = [
            'eligible_for_scholarship' => false,
            'scholarship_amount' => 0,
            'eligible_for_reduction' => false,
            'reduction_percentage' => $this->schoolSettings->reduction_percentage,
            'deadline' => $this->schoolSettings->scholarship_deadline,
            'reasons' => []
        ];

        // Vérifier la bourse
        $scholarship = $this->getClassScholarship($student);
        if ($scholarship) {
            $info['eligible_for_scholarship'] = true;
            $info['scholarship_amount'] = $scholarship->amount;
            if ($this->isEligibleForScholarship(now())) {
                $info['reasons'][] = "Bourse disponible: {$scholarship->name} ({$scholarship->amount} FCFA)";
            } else {
                $info['reasons'][] = "Bourse expirée: {$scholarship->name}";
            }
        }

        // Vérifier la réduction - NOUVELLE LOGIQUE avec paiements existants
        // D'abord vérifier s'il y a des paiements existants
        $currentYear = \App\Models\SchoolYear::where('is_current', true)->first();
        $hasExistingPayments = false;
        
        if ($currentYear) {
            $existingPayments = \App\Models\Payment::where('student_id', $student->id)
                ->where('school_year_id', $currentYear->id)
                ->count();
            $hasExistingPayments = ($existingPayments > 0);
        }

        // Réduction possible seulement s'il n'y a pas de paiements existants ET pas de bourse
        if (!$hasExistingPayments && !$info['eligible_for_scholarship']) {
            if ($student->isOld()) {
                $info['eligible_for_reduction'] = true;
                $info['reasons'][] = "Réduction {$this->schoolSettings->reduction_percentage}% - Ancien élève (paiement intégral requis)";
            }

            if ($student->isNew() && $this->isPaidBeforeDeadline(now())) {
                $info['eligible_for_reduction'] = true;
                $info['reasons'][] = "Réduction {$this->schoolSettings->reduction_percentage}% - Paiement intégral avant le {$this->schoolSettings->scholarship_deadline->format('d/m/Y')}";
            }
        } else if ($hasExistingPayments) {
            // Élève a déjà payé partiellement
            $info['reasons'][] = "Réduction non disponible - Paiements partiels déjà effectués";
        } else if ($info['eligible_for_scholarship']) {
            // Élève a une bourse - pas de cumul avec réduction
            $info['reasons'][] = "Réduction non disponible - Bourse déjà accordée (non cumulable)";
        }

        return $info;
    }
}