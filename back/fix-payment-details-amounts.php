<?php

/**
 * SCRIPT DE RÉPARATION DES MONTANTS DE PAYMENT_DETAILS
 * 
 * PROBLÈME : Les amount_paid dans payment_details sont vides/null alors que 
 * les reduction_context contiennent les bonnes informations et les totaux 
 * dans payments sont corrects.
 * 
 * SOLUTION : Reconstituer les amount_paid depuis les reduction_context
 * 
 * CRÉÉ LE : <?= date('Y-m-d H:i:s') ?>

 * AUTEUR : Claude Code Assistant
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class PaymentDetailsRepairer 
{
    private $dryRun;
    private $repairedCount = 0;
    private $errorCount = 0;
    private $logFile;

    public function __construct($dryRun = true) 
    {
        $this->dryRun = $dryRun;
        $this->logFile = storage_path('logs/payment-repair-' . date('Y-m-d-H-i-s') . '.log');
        
        echo "=== SCRIPT DE RÉPARATION DES PAYMENT_DETAILS ===" . PHP_EOL;
        echo "Mode: " . ($this->dryRun ? "SIMULATION (dry-run)" : "RÉPARATION RÉELLE") . PHP_EOL;
        echo "Log: " . $this->logFile . PHP_EOL;
        echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "===============================================" . PHP_EOL;
    }

    /**
     * Analyser le problème avant réparation
     */
    public function analyze()
    {
        echo "\n🔍 ANALYSE PRÉLIMINAIRE..." . PHP_EOL;

        // Compter les paiements avec réductions
        $paymentsWithReduction = Payment::where('has_reduction', true)->count();
        echo "• Paiements avec réduction: {$paymentsWithReduction}" . PHP_EOL;

        // Compter les payment_details avec amount_allocated vide
        $emptyAmountPaid = PaymentDetail::whereHas('payment', function($q) {
            $q->where('has_reduction', true);
        })
        ->where(function($q) {
            $q->whereNull('amount_allocated')
              ->orWhere('amount_allocated', 0)
              ->orWhere('amount_allocated', '');
        })
        ->count();

        echo "• Payment_details avec amount_paid vide: {$emptyAmountPaid}" . PHP_EOL;

        // Compter ceux avec reduction_context
        $withContext = PaymentDetail::whereHas('payment', function($q) {
            $q->where('has_reduction', true);
        })
        ->whereNotNull('reduction_context')
        ->count();

        echo "• Payment_details avec reduction_context: {$withContext}" . PHP_EOL;

        if ($emptyAmountPaid > 0) {
            echo "\n⚠️  PROBLÈME DÉTECTÉ: {$emptyAmountPaid} payment_details ont des montants vides!" . PHP_EOL;
            return true;
        } else {
            echo "\n✅ Aucun problème détecté." . PHP_EOL;
            return false;
        }
    }

    /**
     * Réparer tous les payment_details problématiques
     */
    public function repair()
    {
        if (!$this->analyze()) {
            return;
        }

        echo "\n🔧 DÉMARRAGE DE LA RÉPARATION..." . PHP_EOL;

        // Récupérer tous les paiements avec réduction
        $payments = Payment::where('has_reduction', true)
            ->with(['paymentDetails.paymentTranche', 'student'])
            ->get();

        echo "• Paiements à traiter: {$payments->count()}" . PHP_EOL;

        foreach ($payments as $payment) {
            $this->repairPayment($payment);
        }

        echo "\n📊 RÉSULTATS:" . PHP_EOL;
        echo "• Paiements réparés: {$this->repairedCount}" . PHP_EOL;
        echo "• Erreurs: {$this->errorCount}" . PHP_EOL;
        echo "• Log détaillé: {$this->logFile}" . PHP_EOL;
    }

    /**
     * Réparer un paiement spécifique
     */
    private function repairPayment(Payment $payment)
    {
        try {
            $this->log("--- Réparation paiement ID: {$payment->id} ---");
            $this->log("Élève: {$payment->student->first_name} {$payment->student->last_name}");
            $this->log("Total payment: {$payment->total_amount} FCFA");
            $this->log("Réduction: {$payment->reduction_amount} FCFA");

            $totalRecalculated = 0;
            $detailsUpdated = 0;

            foreach ($payment->paymentDetails as $detail) {
                $newAmount = $this->extractAmountFromContext($detail);
                
                if ($newAmount !== null) {
                    $this->log("  - {$detail->paymentTranche->name}: {$detail->amount_allocated} → {$newAmount} FCFA");
                    
                    if (!$this->dryRun) {
                        $detail->amount_allocated = $newAmount;
                        $detail->save();
                    }
                    
                    $totalRecalculated += $newAmount;
                    $detailsUpdated++;
                } else {
                    $this->log("  - {$detail->paymentTranche->name}: IMPOSSIBLE À CALCULER");
                    $this->log("    Context: {$detail->reduction_context}");
                }
            }

            // Vérifier la cohérence
            $totalExpected = $payment->total_amount;
            $difference = abs($totalRecalculated - $totalExpected);

            if ($difference <= 1) { // Tolérance de 1 FCFA pour arrondis
                $this->log("✅ Cohérence OK: {$totalRecalculated} ≈ {$totalExpected}");
                $this->repairedCount++;
            } else {
                $this->log("⚠️  Incohérence: {$totalRecalculated} ≠ {$totalExpected} (diff: {$difference})");
                $this->errorCount++;
            }

            $this->log("Details mis à jour: {$detailsUpdated}");
            
        } catch (Exception $e) {
            $this->log("❌ Erreur sur paiement {$payment->id}: " . $e->getMessage());
            $this->errorCount++;
        }

        $this->log(""); // Ligne vide
    }

    /**
     * Extraire le montant depuis le reduction_context
     */
    private function extractAmountFromContext(PaymentDetail $detail)
    {
        $context = $detail->reduction_context;
        
        if (!$context) {
            return null;
        }

        // Cas 1: "Montant normal - X FCFA"
        if (preg_match('/Montant normal - ([\d,]+(?:\.\d+)?) FCFA/', $context, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        // Cas 2: "Nouvelle réduction X% sur dernières tranches - Normal: X FCFA, Réduit: Y FCFA"
        if (preg_match('/Réduit: ([\d,]+(?:\.\d+)?) FCFA/', $context, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        // Cas 3: "Bourse X% - Normal: X FCFA, Avec bourse: Y FCFA"
        if (preg_match('/Avec bourse: ([\d,]+(?:\.\d+)?) FCFA/', $context, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        // Cas 4: Autres formats potentiels
        if (preg_match('/([\d,]+(?:\.\d+)?) FCFA/', $context, $matches)) {
            // Prendre le dernier montant trouvé (souvent le montant final)
            preg_match_all('/([\d,]+(?:\.\d+)?) FCFA/', $context, $allMatches);
            return (float) str_replace(',', '', end($allMatches[1]));
        }

        return null;
    }

    /**
     * Tester sur un échantillon
     */
    public function testSample($limit = 5)
    {
        echo "\n🧪 TEST SUR ÉCHANTILLON ({$limit} paiements)..." . PHP_EOL;

        $payments = Payment::where('has_reduction', true)
            ->with(['paymentDetails.paymentTranche', 'student'])
            ->limit($limit)
            ->get();

        foreach ($payments as $payment) {
            echo "\n--- Test paiement ID: {$payment->id} ---" . PHP_EOL;
            echo "Élève: {$payment->student->first_name} {$payment->student->last_name}" . PHP_EOL;
            echo "Total: {$payment->total_amount} FCFA" . PHP_EOL;
            
            $totalCalculated = 0;
            foreach ($payment->paymentDetails as $detail) {
                $amount = $this->extractAmountFromContext($detail);
                echo "  - {$detail->paymentTranche->name}: ";
                echo "Actuel={$detail->amount_allocated}, ";
                echo "Calculé={$amount}, ";
                echo "Context: " . substr($detail->reduction_context, 0, 50) . "..." . PHP_EOL;
                
                if ($amount) $totalCalculated += $amount;
            }
            
            echo "Total calculé: {$totalCalculated} FCFA" . PHP_EOL;
            echo "Différence: " . abs($totalCalculated - $payment->total_amount) . " FCFA" . PHP_EOL;
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

    /**
     * Vérifier les réparations
     */
    public function verify()
    {
        echo "\n✅ VÉRIFICATION POST-RÉPARATION..." . PHP_EOL;

        // Compter les payment_details encore vides
        $stillEmpty = PaymentDetail::whereHas('payment', function($q) {
            $q->where('has_reduction', true);
        })
        ->where(function($q) {
            $q->whereNull('amount_allocated')
              ->orWhere('amount_allocated', 0)
              ->orWhere('amount_allocated', '');
        })
        ->count();

        echo "• Payment_details encore vides: {$stillEmpty}" . PHP_EOL;

        // Vérifier la cohérence des totaux
        $incoherentPayments = 0;
        $payments = Payment::where('has_reduction', true)->with('paymentDetails')->get();
        
        foreach ($payments as $payment) {
            $totalDetails = $payment->paymentDetails->sum('amount_allocated');
            $difference = abs($totalDetails - $payment->total_amount);
            
            if ($difference > 1) {
                $incoherentPayments++;
            }
        }

        echo "• Paiements avec incohérence: {$incoherentPayments}" . PHP_EOL;

        if ($stillEmpty == 0 && $incoherentPayments == 0) {
            echo "🎉 RÉPARATION RÉUSSIE ! Tous les montants sont cohérents." . PHP_EOL;
        } else {
            echo "⚠️  Il reste des problèmes à résoudre." . PHP_EOL;
        }
    }
}

// === EXÉCUTION DU SCRIPT ===

if ($argc < 2) {
    echo "Usage: php fix-payment-details-amounts.php [test|dry-run|repair|verify]" . PHP_EOL;
    echo "" . PHP_EOL;
    echo "Commandes:" . PHP_EOL;
    echo "  test     - Tester sur un échantillon de 5 paiements" . PHP_EOL;
    echo "  dry-run  - Simulation complète (sans modification)" . PHP_EOL;
    echo "  repair   - Réparation réelle (MODIFIE LES DONNÉES)" . PHP_EOL;
    echo "  verify   - Vérifier l'état après réparation" . PHP_EOL;
    exit(1);
}

$command = $argv[1];
$repairer = new PaymentDetailsRepairer($command !== 'repair');

switch ($command) {
    case 'test':
        $repairer->testSample();
        break;
        
    case 'dry-run':
        $repairer->repair();
        break;
        
    case 'repair':
        echo "⚠️  ATTENTION: Cette commande va MODIFIER LES DONNÉES !" . PHP_EOL;
        echo "Voulez-vous continuer ? (tapez 'OUI' pour confirmer): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if ($confirmation === 'OUI') {
            $repairer->repair();
        } else {
            echo "Opération annulée." . PHP_EOL;
        }
        break;
        
    case 'verify':
        $repairer->verify();
        break;
        
    default:
        echo "Commande inconnue: {$command}" . PHP_EOL;
        exit(1);
}

echo "\n=== FIN DU SCRIPT ===" . PHP_EOL;