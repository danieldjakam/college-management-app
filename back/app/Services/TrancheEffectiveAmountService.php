<?php

namespace App\Services;

use App\Models\PaymentTranche;
use App\Models\Student;

class TrancheEffectiveAmountService
{
    public function __construct(
        private readonly NewcomerSchoolFeeDiscountService $newcomerDiscountService = new NewcomerSchoolFeeDiscountService(),
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
    public function getEffectiveRequired(PaymentTranche $tranche, Student $student): float
    {
        $normal = $this->getNormalRequired($tranche, $student);

        if ($normal <= 0) {
            return 0.0;
        }

        $ratio = $this->newcomerDiscountService->getReductionRatio($student);
        if ($ratio <= 0) {
            return $normal;
        }

        if ((int) $tranche->order === 1) {
            return $normal; // jamais de réduction sur inscription
        }

        $discountAmount = round($normal * $ratio, 0);

        return max(0.0, $normal - $discountAmount);
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
