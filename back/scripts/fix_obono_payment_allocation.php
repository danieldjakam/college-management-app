#!/usr/bin/env php
<?php

/**
 * Script de correction pour OBONO MAKOK SYLVIE MURIEL
 *
 * PROBLÈME:
 * Après transfert de 2nd F8 vers 2nd C, la recalculation des paiements
 * a incorrectement alloué 67,000 FCFA à la 1ère tranche au lieu de 57,000 FCFA
 * et 0 FCFA à la 3ème tranche au lieu de 10,000 FCFA.
 *
 * SOLUTION:
 * Déplacer 10,000 FCFA de la 1ère tranche vers la 3ème tranche
 * en modifiant payment_detail ID 5104.
 *
 * USAGE:
 * php artisan tinker < scripts/fix_obono_payment_allocation.php
 */

echo "=========================================\n";
echo "CORRECTION OBONO MAKOK SYLVIE MURIEL\n";
echo "=========================================\n\n";

// Vérification préalable
echo "🔍 Étape 1: Vérification de l'état actuel...\n\n";

$student = DB::table('students')->where('id', 565)->first();

if (!$student) {
    echo "❌ ERREUR: Étudiante ID 565 non trouvée!\n";
    exit(1);
}

echo "Étudiante: {$student->first_name} {$student->last_name}\n";
echo "Classe ID: {$student->class_series_id}\n\n";

// Vérifier l'état actuel des tranches
$tranche1Total = DB::table('payment_details')
    ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
    ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
    ->where('payments.student_id', 565)
    ->where('payment_tranches.name', '1ère Tranche')
    ->sum('payment_details.amount_allocated');

$tranche3Total = DB::table('payment_details')
    ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
    ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
    ->where('payments.student_id', 565)
    ->where('payment_tranches.name', '3ème Tranche')
    ->sum('payment_details.amount_allocated');

echo "État actuel:\n";
echo "  1ère Tranche: $tranche1Total FCFA\n";
echo "  3ème Tranche: $tranche3Total FCFA\n\n";

// Vérifier si la correction est nécessaire
if ($tranche1Total == 57000 && $tranche3Total == 10000) {
    echo "✅ Les montants sont déjà corrects! Aucune correction nécessaire.\n";
    exit(0);
}

if ($tranche1Total != 67000) {
    echo "⚠️  ATTENTION: Le montant de la 1ère tranche ($tranche1Total) ne correspond pas à l'attendu (67000).\n";
    echo "Voulez-vous continuer? La correction pourrait ne pas être applicable.\n";
    // En production, on s'arrête ici pour vérifier
    exit(1);
}

echo "🔧 Étape 2: Application de la correction...\n\n";

// Trouver l'ID de la 3ème tranche
$tranche3 = DB::table('payment_tranches')->where('name', '3ème Tranche')->first();

if (!$tranche3) {
    echo "❌ ERREUR: 3ème Tranche non trouvée!\n";
    exit(1);
}

echo "ID de la 3ème Tranche: {$tranche3->id}\n\n";

// Vérifier que le payment_detail ID 5104 existe
$detailToMove = DB::table('payment_details')->where('id', 5104)->first();

if (!$detailToMove) {
    echo "❌ ERREUR: payment_detail ID 5104 non trouvé!\n";
    exit(1);
}

echo "Payment detail à modifier:\n";
echo "  ID: {$detailToMove->id}\n";
echo "  Payment ID: {$detailToMove->payment_id}\n";
echo "  Montant: {$detailToMove->amount_allocated} FCFA\n";
echo "  Tranche actuelle ID: {$detailToMove->payment_tranche_id}\n";
echo "  Nouvelle tranche ID: {$tranche3->id}\n\n";

// Appliquer la correction
$updated = DB::table('payment_details')
    ->where('id', 5104)
    ->update(['payment_tranche_id' => $tranche3->id]);

if ($updated === 1) {
    echo "✅ Payment detail ID 5104 déplacé vers 3ème tranche\n\n";
} else {
    echo "❌ ERREUR: La mise à jour a échoué (lignes affectées: $updated)\n";
    exit(1);
}

// Vérification finale
echo "🎯 Étape 3: Vérification finale...\n\n";

$tranche1FinalTotal = DB::table('payment_details')
    ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
    ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
    ->where('payments.student_id', 565)
    ->where('payment_tranches.name', '1ère Tranche')
    ->sum('payment_details.amount_allocated');

$tranche3FinalTotal = DB::table('payment_details')
    ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
    ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
    ->where('payments.student_id', 565)
    ->where('payment_tranches.name', '3ème Tranche')
    ->sum('payment_details.amount_allocated');

echo "État après correction:\n";
echo "  1ère Tranche: $tranche1FinalTotal FCFA (attendu: 57000)\n";
echo "  3ème Tranche: $tranche3FinalTotal FCFA (attendu: 10000)\n\n";

if ($tranche1FinalTotal == 57000 && $tranche3FinalTotal == 10000) {
    echo "✅ CORRECTION RÉUSSIE!\n\n";

    // Afficher le résumé complet
    echo "=== RÉSUMÉ COMPLET DES PAIEMENTS ===\n\n";

    $allTranches = ['Inscription', '1ère Tranche', '2ème Tranche', '3ème Tranche'];
    $totalPaid = 0;

    foreach ($allTranches as $trancheName) {
        $paid = DB::table('payment_details')
            ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
            ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
            ->where('payments.student_id', 565)
            ->where('payment_tranches.name', $trancheName)
            ->sum('payment_details.amount_allocated');

        echo "$trancheName: $paid FCFA\n";
        $totalPaid += $paid;
    }

    echo "\nTotal payé: $totalPaid FCFA\n\n";
    echo "=========================================\n";
    echo "FIN DE LA CORRECTION\n";
    echo "=========================================\n";
} else {
    echo "❌ PROBLÈME: Les montants ne correspondent pas aux valeurs attendues\n";
    exit(1);
}
