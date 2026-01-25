<?php

namespace App\Services;

use App\Models\PaymentTranche;
use App\Models\Student;
use App\Models\SchoolYear;

class TrancheEffectiveAmountService
{
    public function __construct(
        private readonly NewcomerSchoolFeeDiscountService $newcomerDiscountService = new NewcomerSchoolFeeDiscountService(),
        private readonly ManualDiscountService $manualDiscountService = new ManualDiscountService(),
    ) {
    }

    /**
     * Montant requis normal (sans réduction) pour une tranche.
     */
    public function getNormalRequired(PaymentTranche $tranche, Student $student): float
    {
        return (float) $tranche->getAmountForStudent($student, false, false, false);
    }

    /**
     * Montant requis effectif à payer, en tenant compte de la réduction "nouveau".
     * IMPORTANT: ne réduit pas l'inscription (order=1).
     */
    /**
     * Montant effectif à payer pour une tranche (intègre: réduction "nouveau" + réduction manuelle).
     * NOTE: pour intégrer la réduction manuelle, il faut passer l'année + la liste des tranches pour faire la répartition.
     */
    public function getEffectiveRequired(PaymentTranche $tranche, Student $student, ?SchoolYear $schoolYear = null, $allTranches = null): float
    {
        $normal = $this->getNormalRequired($tranche, $student);

        if ($normal <= 0) {
            return 0.0;
        }

        // 1) Réduction nouveau (jamais sur inscription)
        $newcomerDiscount = 0.0;
        $ratio = $this->newcomerDiscountService->getReductionRatio($student);
        if ($ratio > 0 && (int) $tranche->order !== 1) {
            $newcomerDiscount = (float) round($normal * $ratio, 0);
        }

        $effective = max(0.0, $normal - $newcomerDiscount);

        // 2) Réduction manuelle (montant fixe réparti sur les dernières tranches)
        if ($schoolYear && $allTranches) {
            $manualMap = $this->getManualDiscountMap($student, $schoolYear, $allTranches);
            $manualOnThisTranche = (float) ($manualMap[$tranche->id] ?? 0);
            $effective = max(0.0, $effective - $manualOnThisTranche);
        }

        return $effective;
    }

    /**
     * Map tranche_id => montant de réduction manuelle (répartie sur les tranches, hors inscription).
     */
    public function getManualDiscountMap(Student $student, SchoolYear $schoolYear, $allTranches): array
    {
        $manualAmount = $this->manualDiscountService->getManualDiscountAmount($student, $schoolYear);
        if ($manualAmount <= 0) {
            return [];
        }

        $rows = [];
        foreach ($allTranches as $t) {
            $required = (float) $t->getAmountForStudent($student, false, false, false);
            if ($required <= 0) {
                continue;
            }
            $rows[] = ['tranche' => $t, 'required' => $required];
        }

        return $this->manualDiscountService->distributeAcrossTranches($manualAmount, $rows);
    }

    public function getNewcomerDiscountAmount(PaymentTranche $tranche, Student $student): float
    {
        $normal = $this->getNormalRequired($tranche, $student);
        if ($normal <= 0) {
            return 0.0;
        }

        $ratio = $this->newcomerDiscountService->getReductionRatio($student);
        if ($ratio <= 0) {
            return 0.0;
        }

        if ((int) $tranche->order === 1) {
            return 0.0;
        }

        return (float) round($normal * $ratio, 0);
    }
}
