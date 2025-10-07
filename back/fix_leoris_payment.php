<?php

/**
 * Script de correction du paiement de LEORIS DIANE MBOKOU
 * Après transfert de 2nd F8 A vers 2nd C
 * Réaffectation de 10 000 FCFA de trop vers les tranches suivantes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CORRECTION PAIEMENT LEORIS DIANE MBOKOU ===" . PHP_EOL . PHP_EOL;

try {
    DB::beginTransaction();

    $studentId = 1336;
    $paymentId = 1400;
    $paymentDetailId = 2220;

    // 1. Récupérer l'élève
    $student = DB::table('students')->find($studentId);
    echo "✅ Élève trouvé: " . $student->first_name . " " . $student->last_name . PHP_EOL;
    echo "   Classe actuelle: 2nd C (ID: {$student->class_series_id})" . PHP_EOL . PHP_EOL;

    // 2. Récupérer la série actuelle pour obtenir la classe
    $serie = DB::table('class_series')->find($student->class_series_id);
    $classId = $serie->class_id;
    echo "📚 Classe ID: {$classId}" . PHP_EOL . PHP_EOL;

    // 3. Récupérer les montants de la nouvelle classe (2nd C)
    $classAmounts = DB::table('class_payment_amounts')
        ->where('class_id', $classId)
        ->orderBy('payment_tranche_id')
        ->get()
        ->keyBy('payment_tranche_id');

    echo "💰 MONTANTS POUR 2nd C:" . PHP_EOL;
    foreach ($classAmounts as $trancheId => $amount) {
        $tranche = DB::table('payment_tranches')->find($trancheId);
        echo "   - {$tranche->name}: " . number_format($amount->amount, 0) . " FCFA" . PHP_EOL;
    }
    echo PHP_EOL;

    // 4. Récupérer le paiement actuel
    $payment = DB::table('payments')->find($paymentId);
    echo "📋 PAIEMENT ACTUEL:" . PHP_EOL;
    echo "   ID: {$payment->id}" . PHP_EOL;
    echo "   Montant: {$payment->total_amount} FCFA" . PHP_EOL;
    echo "   Status: {$payment->status}" . PHP_EOL . PHP_EOL;

    // 5. Récupérer le détail du paiement (Inscription)
    $paymentDetail = DB::table('payment_details')->find($paymentDetailId);
    echo "📝 DÉTAIL PAIEMENT (Inscription):" . PHP_EOL;
    echo "   Montant alloué: {$paymentDetail->amount_allocated} FCFA" . PHP_EOL;
    echo "   Montant requis pour 2nd C: 31,000 FCFA" . PHP_EOL;
    echo "   Excédent: " . ($paymentDetail->amount_allocated - 31000) . " FCFA" . PHP_EOL . PHP_EOL;

    // 6. CORRECTION: Mettre à jour l'inscription à 31 000 FCFA
    echo "🔧 ÉTAPE 1: Ajustement de l'inscription..." . PHP_EOL;
    DB::table('payment_details')
        ->where('id', $paymentDetailId)
        ->update([
            'amount_allocated' => 31000,
            'new_total_amount' => 31000,
            'required_amount_at_time' => 31000,
            'is_fully_paid' => 1,
            'updated_at' => now()
        ]);
    echo "   ✅ Inscription mise à jour: 31,000 FCFA" . PHP_EOL . PHP_EOL;

    // 7. Créer un nouveau détail pour la 1ère tranche avec 10 000 FCFA
    echo "🔧 ÉTAPE 2: Application de 10,000 FCFA sur la 1ère tranche..." . PHP_EOL;

    $tranche1Id = 3; // 1ère Tranche
    $tranche1Amount = $classAmounts[$tranche1Id]->amount; // 57,000 FCFA

    // Créer le détail de paiement pour la 1ère tranche
    $newDetailId = DB::table('payment_details')->insertGetId([
        'payment_id' => $paymentId,
        'payment_tranche_id' => $tranche1Id,
        'amount_allocated' => 10000,
        'previous_amount' => 0,
        'new_total_amount' => 10000,
        'required_amount_at_time' => $tranche1Amount,
        'was_reduced' => 0,
        'reduction_context' => null,
        'is_fully_paid' => 0, // Pas complètement payée (reste 47,000 FCFA)
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "   ✅ 1ère tranche: 10,000 FCFA payés (reste: " . ($tranche1Amount - 10000) . " FCFA)" . PHP_EOL . PHP_EOL;

    // 8. Mettre à jour le paiement principal
    echo "🔧 ÉTAPE 3: Mise à jour du paiement principal..." . PHP_EOL;
    DB::table('payments')
        ->where('id', $paymentId)
        ->update([
            'total_amount' => 41000, // Le montant total reste 41,000 FCFA
            'status' => 'validated', // Valider le paiement
            'validation_date' => now(),
            'notes' => 'Paiement ajusté après transfert de 2nd F8 A vers 2nd C. Inscription: 31,000 FCFA + 1ère tranche (partiel): 10,000 FCFA',
            'updated_at' => now()
        ]);
    echo "   ✅ Paiement validé et ajusté" . PHP_EOL . PHP_EOL;

    // 9. Vérification finale
    echo "=== VÉRIFICATION FINALE ===" . PHP_EOL . PHP_EOL;

    $updatedDetails = DB::table('payment_details')
        ->where('payment_id', $paymentId)
        ->get();

    echo "📊 RÉSUMÉ DES PAIEMENTS:" . PHP_EOL;
    $totalPaid = 0;
    foreach ($updatedDetails as $detail) {
        $tranche = DB::table('payment_tranches')->find($detail->payment_tranche_id);
        $requiredAmount = $classAmounts[$detail->payment_tranche_id]->amount ?? 0;
        $remaining = $requiredAmount - $detail->amount_allocated;

        echo "   - {$tranche->name}: " . number_format($detail->amount_allocated, 0) . " FCFA payés";
        echo " / " . number_format($requiredAmount, 0) . " FCFA";
        echo " (reste: " . number_format($remaining, 0) . " FCFA)" . PHP_EOL;

        $totalPaid += $detail->amount_allocated;
    }

    echo PHP_EOL;
    echo "💰 Total payé: " . number_format($totalPaid, 0) . " FCFA" . PHP_EOL;

    // Calculer le montant total à payer pour l'année
    $totalRequired = $classAmounts->sum('amount');
    $totalRemaining = $totalRequired - $totalPaid;

    echo "💰 Total année scolaire 2nd C: " . number_format($totalRequired, 0) . " FCFA" . PHP_EOL;
    echo "💰 Reste à payer: " . number_format($totalRemaining, 0) . " FCFA" . PHP_EOL;
    echo PHP_EOL;

    // Commit de la transaction
    DB::commit();

    echo "✅ ✅ ✅ CORRECTION RÉUSSIE !" . PHP_EOL;
    echo PHP_EOL;
    echo "📋 RÉSUMÉ:" . PHP_EOL;
    echo "   1. Inscription ajustée: 41,000 → 31,000 FCFA" . PHP_EOL;
    echo "   2. Excédent de 10,000 FCFA appliqué sur la 1ère tranche" . PHP_EOL;
    echo "   3. Paiement validé" . PHP_EOL;
    echo "   4. Total payé: 41,000 FCFA (Inscription: 31,000 + 1ère tranche partielle: 10,000)" . PHP_EOL;
    echo "   5. Reste à payer: " . number_format($totalRemaining, 0) . " FCFA" . PHP_EOL;

} catch (Exception $e) {
    DB::rollBack();
    echo PHP_EOL . "❌ ERREUR : " . $e->getMessage() . PHP_EOL;
    echo "Aucune modification n'a été appliquée (rollback effectué)" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== FIN DU SCRIPT ===" . PHP_EOL;
