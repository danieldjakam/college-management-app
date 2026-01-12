<?php

namespace App\Services;

use App\Models\Student;

class NewcomerSchoolFeeDiscountService
{
    /**
     * Retourne le ratio de reduction (0, 1/3, 2/3) pour la scolarite selon le trimestre d'arrivee.
     * - Trim 2 => reduction 1/3
     * - Trim 3 => reduction 2/3
     */
    public function getReductionRatio(Student $student): float
    {
        if (!$student->isNew()) {
            return 0.0;
        }

        $trimester = (int) ($student->arrival_trimester ?? 0);

        return match ($trimester) {
            2 => 1 / 3,
            3 => 2 / 3,
            default => 0.0,
        };
    }

    public function hasNewcomerDiscount(Student $student): bool
    {
        return $this->getReductionRatio($student) > 0;
    }

    /**
     * Applique la reduction "nouveau" sur un montant (scolarite uniquement).
     * $amount est un montant de tranche scolarite.
     */
    public function applyToAmount(Student $student, float $amount): float
    {
        $ratio = $this->getReductionRatio($student);

        if ($ratio <= 0) {
            return $amount;
        }

        // montant a payer apres reduction
        return round($amount * (1 - $ratio), 0);
    }

    /**
     * Construit un texte standard (modifiable) pour expliquer la reduction.
     */
    public function buildDefaultReason(Student $student): ?string
    {
        $ratio = $this->getReductionRatio($student);
        if ($ratio <= 0) {
            return null;
        }

        $trimester = (int) $student->arrival_trimester;
        $ratioStr = $trimester === 2 ? '1/3' : '2/3';

        return "Nouveau élève (arrivée T{$trimester}) : réduction {$ratioStr} sur scolarité (inscription non réduite).";
    }
}
