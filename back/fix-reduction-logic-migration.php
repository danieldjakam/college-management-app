<?php

/**
 * SCRIPT DE CORRECTION DE LA MIGRATION DES RÉDUCTIONS
 * 
 * PROBLÈME : Changement du système de réduction de "haut→bas" vers "bas→haut"
 * a créé des incohérences pour les paiements en cours de traitement.
 * 
 * STRATÉGIE : 
 * 1. Identifier les paiements mixtes (ancien + nouveau système)
 * 2. Recalculer ENTIÈREMENT selon l'ancienne logique (haut→bas)
 * 3. Préserver les paiements 100% nouveau système
 * 
 * SÉCURITÉ : Mode dry-run par défaut, logs détaillés
 * 
 * CRÉÉ LE : <?= date('Y-m-d H:i:s') ?>

 * AUTEUR : Claude Code Assistant
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Student;
use App\Services\DiscountCalculatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class ReductionLogicMigrationFixer 
{
    private $dryRun;
    private $fixedCount = 0;
    private $skippedCount = 0;
    private $errorCount = 0;
    private $logFile;

    public function __construct($dryRun = true) 
    {
        $this->dryRun = $dryRun;
        $this->logFile = storage_path('logs/reduction-migration-fix-' . date('Y-m-d-H-i-s') . '.log');
        
        echo "=== CORRECTION MIGRATION RÉDUCTIONS (HAUT→BAS) ===" . PHP_EOL;
        echo "Mode: " . ($this->dryRun ? "SIMULATION (dry-run)" : "CORRECTION RÉELLE") . PHP_EOL;
        echo "Log: " . $this->logFile . PHP_EOL;
        echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "================================================" . PHP_EOL;
    }

    /**
     * Analyser les paiements problématiques
     */
    public function analyze()
    {
        echo "\n🔍 ANALYSE DES PAIEMENTS PROBLÉMATIQUES..." . PHP_EOL;

        // Paiements avec réductions
        $paymentsWithReduction = Payment::where('has_reduction', true)->count();
        echo "• Total paiements avec réduction: {$paymentsWithReduction}" . PHP_EOL;

        // Paiements mixtes (ancien + nouveau système)
        $mixedPayments = $this->identifyMixedPayments();
        echo "• Paiements mixtes (à corriger): " . count($mixedPayments) . PHP_EOL;

        // Paiements 100% nouveau système
        $newSystemPayments = $this->identifyNewSystemPayments();
        echo "• Paiements nouveau système (à préserver): " . count($newSystemPayments) . PHP_EOL;

        return count($mixedPayments);
    }

    /**
     * Identifier les paiements mixtes (problématiques)
     */
    private function identifyMixedPayments()
    {
        $paymentsWithReduction = Payment::where('has_reduction', true)
            ->with('paymentDetails')
            ->get();

        $mixedPayments = [];

        foreach ($paymentsWithReduction as $payment) {
            $hasOldSystem = false;
            $hasNewSystem = false;

            foreach ($payment->paymentDetails as $detail) {
                if (strpos($detail->reduction_context, 'Montant normal') !== false) {
                    $hasOldSystem = true;
                }
                if (strpos($detail->reduction_context, 'dernières tranches') !== false) {
                    $hasNewSystem = true;
                }
            }

            // Paiement mixte = a les deux systèmes
            if ($hasOldSystem && $hasNewSystem) {
                $mixedPayments[] = $payment;
            }
        }

        return $mixedPayments;
    }

    /**
     * Identifier les paiements 100% nouveau système (à préserver)
     */
    private function identifyNewSystemPayments()
    {
        $paymentsWithReduction = Payment::where('has_reduction', true)
            ->with('paymentDetails')
            ->get();

        $newSystemPayments = [];

        foreach ($paymentsWithReduction as $payment) {
            $hasOldSystem = false;
            $hasNewSystem = false;

            foreach ($payment->paymentDetails as $detail) {
                if (strpos($detail->reduction_context, 'Montant normal') !== false) {
                    $hasOldSystem = true;
                }
                if (strpos($detail->reduction_context, 'dernières tranches') !== false) {
                    $hasNewSystem = true;
                }
            }

            // Paiement 100% nouveau = que le nouveau système
            if (!$hasOldSystem && $hasNewSystem) {
                $newSystemPayments[] = $payment;
            }
        }

        return $newSystemPayments;
    }

    /**
     * Corriger tous les paiements mixtes
     */
    public function fix()
    {
        $problemCount = $this->analyze();
        
        if ($problemCount == 0) {
            echo "\n✅ Aucun paiement mixte détecté. Pas de correction nécessaire." . PHP_EOL;
            return;
        }

        echo "\n🔧 CORRECTION DES PAIEMENTS MIXTES..." . PHP_EOL;

        $mixedPayments = $this->identifyMixedPayments();

        foreach ($mixedPayments as $payment) {
            $this->fixMixedPayment($payment);
        }

        echo "\n📊 RÉSULTATS:" . PHP_EOL;
        echo "• Paiements corrigés: {$this->fixedCount}" . PHP_EOL;
        echo "• Paiements ignorés: {$this->skippedCount}" . PHP_EOL;
        echo "• Erreurs: {$this->errorCount}" . PHP_EOL;
    }

    /**
     * Corriger un paiement mixte en appliquant l'ancienne logique (haut→bas)
     */
    private function fixMixedPayment(Payment $payment)
    {
        try {
            $this->log("--- Correction paiement mixte ID: {$payment->id} ---");
            $this->log("Élève: {$payment->student->first_name} {$payment->student->last_name}");
            $this->log("Total payment: {$payment->total_amount} FCFA");
            $this->log("Réduction: {$payment->reduction_amount} FCFA");

            // Obtenir les tranches de la classe
            $student = $payment->student;
            if (!$student->classSeries) {
                $this->log("❌ Élève sans série de classe");
                $this->errorCount++;
                return;
            }

            $tranches = $student->classSeries->paymentTranches->sortBy('order');
            
            // Calculer les montants normaux (sans réduction)
            $trancheAmounts = [];
            $totalNormal = 0;

            foreach ($tranches as $tranche) {
                $amount = DB::table('class_payment_amounts')
                    ->where('class_id', $student->classSeries->class_id)
                    ->where('payment_tranche_id', $tranche->id)
                    ->value('amount') ?? 0;
                
                $trancheAmounts[$tranche->id] = $amount;
                $totalNormal += $amount;
            }

            // Calculer la réduction à appliquer (ancien système : haut→bas)
            $reductionAmount = $payment->reduction_amount;
            $remainingReduction = $reductionAmount;

            $this->log("Total normal: {$totalNormal} FCFA, Réduction à appliquer: {$reductionAmount} FCFA");

            // Appliquer la réduction du haut vers le bas (première → dernière tranche)
            $newAmounts = [];
            foreach ($tranches as $tranche) {
                $normalAmount = $trancheAmounts[$tranche->id];
                
                if ($remainingReduction > 0) {
                    $reductionOnThisTranche = min($remainingReduction, $normalAmount);
                    $newAmount = $normalAmount - $reductionOnThisTranche;
                    $remainingReduction -= $reductionOnThisTranche;
                    
                    $this->log("  {$tranche->name}: {$normalAmount} → {$newAmount} FCFA (réduction: {$reductionOnThisTranche})");
                } else {
                    $newAmount = $normalAmount;
                    $this->log("  {$tranche->name}: {$normalAmount} FCFA (pas de réduction)");
                }
                
                $newAmounts[$tranche->id] = $newAmount;
            }

            // Vérifier la cohérence
            $totalRecalculated = array_sum($newAmounts);
            $expectedTotal = $payment->total_amount;
            
            if (abs($totalRecalculated - $expectedTotal) <= 1) {
                $this->log("✅ Cohérence OK: {$totalRecalculated} ≈ {$expectedTotal}");
                
                // Appliquer les corrections aux payment_details
                if (!$this->dryRun) {
                    $this->updatePaymentDetails($payment, $newAmounts, $tranches);
                }
                
                $this->fixedCount++;
            } else {
                $this->log("⚠️ Incohérence: {$totalRecalculated} ≠ {$expectedTotal}");
                $this->errorCount++;
            }

        } catch (Exception $e) {
            $this->log("❌ Erreur sur paiement {$payment->id}: " . $e->getMessage());
            $this->errorCount++;
        }

        $this->log("");
    }

    /**
     * Mettre à jour les payment_details avec les nouveaux montants
     */
    private function updatePaymentDetails(Payment $payment, array $newAmounts, $tranches)
    {
        foreach ($tranches as $tranche) {
            $newAmount = $newAmounts[$tranche->id];
            
            // Trouver le payment_detail correspondant
            $detail = PaymentDetail::where('payment_id', $payment->id)
                ->where('payment_tranche_id', $tranche->id)
                ->first();
            
            if ($detail) {
                $detail->amount_allocated = $newAmount;
                $detail->new_total_amount = $newAmount;
                $detail->reduction_context = $newAmount > 0 ? 
                    "Correction migration - Ancien système (haut→bas): {$newAmount} FCFA" :
                    "Correction migration - Montant soldé par réduction";
                $detail->save();
                
                $this->log("  ✅ Updated {$tranche->name}: {$detail->amount_allocated} → {$newAmount}");
            }
        }
    }

    /**
     * Tester sur un échantillon
     */
    public function testSample($limit = 3)
    {
        echo "\n🧪 TEST SUR ÉCHANTILLON ({$limit} paiements mixtes)..." . PHP_EOL;

        $mixedPayments = $this->identifyMixedPayments();
        
        foreach (array_slice($mixedPayments, 0, $limit) as $payment) {
            echo "\n--- Test paiement mixte ID: {$payment->id} ---" . PHP_EOL;
            echo "Élève: {$payment->student->first_name} {$payment->student->last_name}" . PHP_EOL;
            echo "Total: {$payment->total_amount} FCFA" . PHP_EOL;
            echo "Réduction: {$payment->reduction_amount} FCFA" . PHP_EOL;
            echo "Détails actuels:" . PHP_EOL;
            
            foreach ($payment->paymentDetails as $detail) {
                $type = (strpos($detail->reduction_context, 'dernières tranches') !== false) ? 'NOUVEAU' : 'ANCIEN';
                echo "  - {$detail->paymentTranche->name}: {$detail->amount_allocated} FCFA ({$type})" . PHP_EOL;
            }
        }
    }

    /**
     * Vérifier les résultats après correction
     */
    public function verify()
    {
        echo "\n✅ VÉRIFICATION POST-CORRECTION..." . PHP_EOL;

        $mixedPayments = $this->identifyMixedPayments();
        echo "• Paiements mixtes restants: " . count($mixedPayments) . PHP_EOL;

        // Test de solvabilité sur quelques élèves
        $sampleStudents = Payment::where('has_reduction', true)
            ->with('student')
            ->take(5)
            ->get()
            ->pluck('student');

        echo "• Test de solvabilité sur " . $sampleStudents->count() . " élèves:" . PHP_EOL;
        
        foreach ($sampleStudents as $student) {
            $solvabilityStatus = $this->checkStudentSolvability($student);
            echo "  - {$student->first_name} {$student->last_name}: {$solvabilityStatus}" . PHP_EOL;
        }
    }

    /**
     * Vérifier la solvabilité d'un élève
     */
    private function checkStudentSolvability($student)
    {
        if (!$student->classSeries) return "❌ Pas de série";

        $tranches = $student->classSeries->paymentTranches ?? collect();
        $totalRequired = 0;
        $totalPaid = 0;

        foreach ($tranches as $tranche) {
            $requiredAmount = DB::table('class_payment_amounts')
                ->where('class_id', $student->classSeries->class_id)
                ->where('payment_tranche_id', $tranche->id)
                ->value('amount') ?? 0;
            
            $paidAmount = 0;
            foreach ($student->payments as $payment) {
                if ($payment->validation_date) {
                    $details = $payment->paymentDetails->where('payment_tranche_id', $tranche->id);
                    foreach ($details as $detail) {
                        $paidAmount += $detail->amount_allocated;
                    }
                }
            }
            
            $totalRequired += $requiredAmount;
            $totalPaid += $paidAmount;
        }

        $percentage = $totalRequired > 0 ? round(($totalPaid / $totalRequired) * 100) : 0;
        
        if ($totalPaid >= ($totalRequired - 1)) {
            return "✅ SOLVABLE ({$percentage}%)";
        } else {
            return "❌ INSOLVABLE ({$percentage}% - manque " . ($totalRequired - $totalPaid) . " FCFA)";
        }
    }

    /**
     * Logger les messages
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        echo $message . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// === EXÉCUTION DU SCRIPT ===

if ($argc < 2) {
    echo "Usage: php fix-reduction-logic-migration.php [test|analyze|dry-run|fix|verify]" . PHP_EOL;
    echo "" . PHP_EOL;
    echo "Commandes:" . PHP_EOL;
    echo "  test     - Tester sur un échantillon de paiements mixtes" . PHP_EOL;
    echo "  analyze  - Analyser les paiements problématiques" . PHP_EOL;
    echo "  dry-run  - Simulation complète (sans modification)" . PHP_EOL;
    echo "  fix      - Correction réelle (MODIFIE LES DONNÉES)" . PHP_EOL;
    echo "  verify   - Vérifier l'état après correction" . PHP_EOL;
    exit(1);
}

$command = $argv[1];
$fixer = new ReductionLogicMigrationFixer($command !== 'fix');

switch ($command) {
    case 'test':
        $fixer->testSample();
        break;
        
    case 'analyze':
        $fixer->analyze();
        break;
        
    case 'dry-run':
        $fixer->fix();
        break;
        
    case 'fix':
        echo "⚠️  ATTENTION: Cette commande va MODIFIER LES DONNÉES !" . PHP_EOL;
        echo "Les paiements mixtes seront recalculés selon l'ancienne logique (haut→bas)." . PHP_EOL;
        echo "Les paiements 100% nouveau système seront préservés." . PHP_EOL;
        echo "Voulez-vous continuer ? (tapez 'OUI' pour confirmer): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if ($confirmation === 'OUI') {
            $fixer->fix();
        } else {
            echo "Opération annulée." . PHP_EOL;
        }
        break;
        
    case 'verify':
        $fixer->verify();
        break;
        
    default:
        echo "Commande inconnue: {$command}" . PHP_EOL;
        exit(1);
}

echo "\n=== FIN DU SCRIPT ===" . PHP_EOL;