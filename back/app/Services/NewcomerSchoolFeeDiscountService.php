<?php

namespace App\Services;

use App\Models\Student;

class NewcomerSchoolFeeDiscountService
{
    /**
     * Retourne le ratio de reduction pour la scolarite selon le trimestre d'arrivee.
     * DÉSACTIVÉ : Le calcul automatique (1/3, 2/3) est désactivé.
     * La réduction doit être saisie MANUELLEMENT au moment du paiement.
     */
    public function getReductionRatio(Student $student): float
    {
        // MODIFICATION : Retourne toujours 0 car la réduction est maintenant MANUELLE
        return 0.0;
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
     * MODIFIÉ : Ne mentionne plus le ratio car la réduction est saisie manuellement.
     */
    public function buildDefaultReason(Student $student): ?string
    {
        // Si l'élève n'a pas de trimestre d'arrivée, pas de motif
        if (!$student->arrival_trimester) {
            return null;
        }

        $trimester = (int) $student->arrival_trimester;

        // Construire un motif simple sans mentionner de pourcentage
        return "Nouveau élève - Arrivée au trimestre {$trimester} (réduction sur scolarité uniquement)";
    }
}
