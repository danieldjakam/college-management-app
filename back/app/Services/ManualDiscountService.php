<?php

namespace App\Services;

use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentManualDiscount;

class ManualDiscountService
{
    public function getManualDiscountForStudent(Student $student, SchoolYear $schoolYear): ?StudentManualDiscount
    {
        return StudentManualDiscount::where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->first();
    }

    public function getManualDiscountAmount(Student $student, SchoolYear $schoolYear): float
    {
        $d = $this->getManualDiscountForStudent($student, $schoolYear);
        return $d ? (float) $d->amount : 0.0;
    }

    /**
     * Répartit la réduction (montant fixe) sur les tranches éligibles (toutes sauf inscription),
     * en commençant par la première tranche de scolarité (order croissant).
     *
     * @param array $trancheRows array of ['tranche' => PaymentTranche, 'required' => float]
     * @return array keyed by tranche_id => discount_amount
     */
    public function distributeAcrossTranches(float $discountAmount, array $trancheRows): array
    {
        $remaining = max(0.0, $discountAmount);
        $map = [];

        // Trier par order croissant (1ère tranche d'abord)
        usort($trancheRows, function ($a, $b) {
            return ((int)$a['tranche']->order) <=> ((int)$b['tranche']->order);
        });

        foreach ($trancheRows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $tranche = $row['tranche'];
            $required = (float) ($row['required'] ?? 0);
            if ($required <= 0) {
                continue;
            }

            // on ne réduit jamais l'inscription (order=1)
            if ((int) $tranche->order === 1) {
                $map[$tranche->id] = 0;
                continue;
            }

            $apply = min($remaining, $required);
            $map[$tranche->id] = ($map[$tranche->id] ?? 0) + $apply;
            $remaining -= $apply;
        }

        return $map;
    }
}
