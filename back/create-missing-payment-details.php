<?php

/**
 * SCRIPT POUR CRÉER LES PAYMENT_DETAILS MANQUANTS
 * 
 * PROBLÈME : Après correction des réductions, certaines tranches n'ont pas 
 * de payment_details alors qu'elles devraient être complètement payées
 * 
 * SOLUTION : Créer les payment_details manquants pour toutes les tranches
 * qui devraient être payées selon la nouvelle répartition des réductions
 * 
 * CRÉÉ LE : <?= date('Y-m-d H:i:s') ?>

 * AUTEUR : Claude Code Assistant
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class MissingPaymentDetailsCreator 
{
    private $dryRun;
    private $createdCount = 0;
    private $skippedCount = 0;
    private $errorCount = 0;
    private $logFile;

    public function __construct($dryRun = true) 
    {
        $this->dryRun = $dryRun;
        $this->logFile = storage_path('logs/missing-payment-details-' . date('Y-m-d-H-i-s') . '.log');
        
        echo "=== CRÉATION DES PAYMENT_DETAILS MANQUANTS ===\n";
        echo "Mode: " . ($this->dryRun ? "SIMULATION (dry-run)" : "CRÉATION RÉELLE") . "\n";
        echo "Log: " . $this->logFile . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "=============================================\n";
    }

    /**
     * Analyser les payment_details manquants
     */
    public function analyze()
    {
        echo "\n🔍 ANALYSE DES PAYMENT_DETAILS MANQUANTS...\n";

        $paymentsWithReduction = Payment::where('has_reduction', true)
            ->with(['paymentDetails.paymentTranche', 'student.classSeries.paymentTranches'])
            ->get();

        $missingDetails = 0;
        $problemPayments = [];

        foreach ($paymentsWithReduction as $payment) {
            $missing = $this->findMissingDetailsForPayment($payment);
            if (count($missing) > 0) {
                $missingDetails += count($missing);
                $problemPayments[] = $payment;
            }
        }

        echo "• Paiements avec réduction: " . count($paymentsWithReduction) . "\n";
        echo "• Paiements avec payment_details manquants: " . count($problemPayments) . "\n";
        echo "• Total payment_details à créer: {$missingDetails}\n";

        return count($problemPayments);
    }

    /**
     * Trouver les payment_details manquants pour un paiement donné
     */
    private function findMissingDetailsForPayment(Payment $payment)
    {
        $student = $payment->student;
        if (!$student->classSeries) {
            return [];
        }

        $tranches = $student->classSeries->paymentTranches->sortBy('order');
        $existingDetails = $payment->paymentDetails->pluck('payment_tranche_id')->toArray();
        $missingTranches = [];

        // Calculer quelles tranches devraient être payées
        $totalAmount = $payment->total_amount;
        $reductionAmount = $payment->reduction_amount;
        $originalTotal = $totalAmount + $reductionAmount;

        // Calculer les montants normaux et avec réduction (logique haut→bas)
        $trancheAmounts = [];
        $calculatedTotal = 0;
        
        foreach ($tranches as $tranche) {
            $amount = DB::table('class_payment_amounts')
                ->where('class_id', $student->classSeries->class_id)
                ->where('payment_tranche_id', $tranche->id)
                ->value('amount') ?? 0;
            
            $trancheAmounts[$tranche->id] = $amount;
            $calculatedTotal += $amount;
        }

        // Appliquer la réduction haut→bas
        $remainingReduction = $reductionAmount;
        $finalAmounts = [];

        foreach ($tranches as $tranche) {
            $normalAmount = $trancheAmounts[$tranche->id];
            
            if ($remainingReduction > 0) {
                $reductionOnThisTranche = min($remainingReduction, $normalAmount);
                $finalAmount = $normalAmount - $reductionOnThisTranche;
                $remainingReduction -= $reductionOnThisTranche;
            } else {
                $finalAmount = $normalAmount;
            }
            
            $finalAmounts[$tranche->id] = $finalAmount;
        }

        // Identifier les tranches manquantes qui devraient avoir des payment_details
        foreach ($tranches as $tranche) {
            if (!in_array($tranche->id, $existingDetails)) {
                $expectedAmount = $finalAmounts[$tranche->id];
                if ($expectedAmount > 0) {
                    $missingTranches[] = [
                        'tranche' => $tranche,
                        'amount' => $expectedAmount,
                        'normal_amount' => $trancheAmounts[$tranche->id],
                        'reduction_applied' => $trancheAmounts[$tranche->id] - $expectedAmount
                    ];
                }
            }
        }

        return $missingTranches;
    }

    /**
     * Créer tous les payment_details manquants
     */
    public function create()
    {
        $problemCount = $this->analyze();
        
        if ($problemCount == 0) {
            echo "\n✅ Aucun payment_detail manquant détecté.\n";
            return;
        }

        echo "\n🔧 CRÉATION DES PAYMENT_DETAILS MANQUANTS...\n";

        $paymentsWithReduction = Payment::where('has_reduction', true)
            ->with(['paymentDetails.paymentTranche', 'student.classSeries.paymentTranches'])
            ->get();

        foreach ($paymentsWithReduction as $payment) {
            $this->createMissingDetailsForPayment($payment);
        }

        echo "\n📊 RÉSULTATS:\n";
        echo "• Payment_details créés: {$this->createdCount}\n";
        echo "• Paiements ignorés: {$this->skippedCount}\n";
        echo "• Erreurs: {$this->errorCount}\n";
    }

    /**
     * Créer les payment_details manquants pour un paiement spécifique
     */
    private function createMissingDetailsForPayment(Payment $payment)
    {
        try {
            $missingDetails = $this->findMissingDetailsForPayment($payment);
            
            if (count($missingDetails) == 0) {
                $this->skippedCount++;
                return;
            }

            $this->log("--- Création payment_details manquants pour paiement ID: {$payment->id} ---");
            $this->log("Élève: {$payment->student->first_name} {$payment->student->last_name}");
            $this->log("Payment_details manquants: " . count($missingDetails));

            foreach ($missingDetails as $missing) {
                $tranche = $missing['tranche'];
                $amount = $missing['amount'];
                $reductionApplied = $missing['reduction_applied'];

                $this->log("  → Création {$tranche->name}: {$amount} FCFA (réduction: {$reductionApplied})");

                if (!$this->dryRun) {
                    $detail = new PaymentDetail();
                    $detail->payment_id = $payment->id;
                    $detail->payment_tranche_id = $tranche->id;
                    $detail->amount_allocated = $amount;
                    $detail->new_total_amount = $amount;
                    $detail->required_amount_at_time = $missing['normal_amount'];
                    $detail->was_reduced = $reductionApplied > 0;
                    
                    if ($reductionApplied > 0) {
                        $detail->reduction_context = "Correction migration - Ancien système (haut→bas): {$amount} FCFA (réduction: {$reductionApplied})";
                    } else {
                        $detail->reduction_context = "Correction migration - Ancien système (haut→bas): {$amount} FCFA";
                    }
                    
                    $detail->is_fully_paid = true;
                    $detail->save();

                    $this->log("    ✅ Payment_detail créé avec succès");
                }

                $this->createdCount++;
            }

        } catch (Exception $e) {
            $this->log("❌ Erreur sur paiement {$payment->id}: " . $e->getMessage());
            $this->errorCount++;
        }

        $this->log("");
    }

    /**
     * Tester sur un échantillon
     */
    public function testSample($limit = 3)
    {
        echo "\n🧪 TEST SUR ÉCHANTILLON ({$limit} paiements)...\n";

        $payments = Payment::where('has_reduction', true)
            ->with(['paymentDetails.paymentTranche', 'student.classSeries.paymentTranches'])
            ->limit($limit)
            ->get();

        foreach ($payments as $payment) {
            echo "\n--- Test paiement ID: {$payment->id} ---\n";
            echo "Élève: {$payment->student->first_name} {$payment->student->last_name}\n";
            echo "Total: {$payment->total_amount} FCFA\n";
            echo "Réduction: {$payment->reduction_amount} FCFA\n";
            
            $missing = $this->findMissingDetailsForPayment($payment);
            echo "Payment_details manquants: " . count($missing) . "\n";
            
            if (count($missing) > 0) {
                echo "Détails manquants:\n";
                foreach ($missing as $m) {
                    echo "  - {$m['tranche']->name}: {$m['amount']} FCFA\n";
                }
            }
            
            echo "Payment_details existants:\n";
            foreach ($payment->paymentDetails as $detail) {
                echo "  - {$detail->paymentTranche->name}: {$detail->amount_allocated} FCFA\n";
            }
        }
    }

    /**
     * Vérifier les résultats après création
     */
    public function verify()
    {
        echo "\n✅ VÉRIFICATION POST-CRÉATION...\n";

        $problemCount = $this->analyze();
        echo "• Payment_details manquants restants: {$problemCount}\n";

        // Vérifier la solvabilité sur un échantillon
        $sampleStudents = Payment::where('has_reduction', true)
            ->with('student')
            ->take(10)
            ->get()
            ->pluck('student')
            ->unique('id');

        echo "• Test de solvabilité sur " . $sampleStudents->count() . " élèves:\n";
        
        foreach ($sampleStudents as $student) {
            $solvabilityStatus = $this->checkStudentSolvability($student);
            echo "  - {$student->first_name} {$student->last_name}: {$solvabilityStatus}\n";
        }
    }

    /**
     * Vérifier la solvabilité d'un élève (CORRIGÉ pour tenir compte des réductions)
     */
    private function checkStudentSolvability($student)
    {
        if (!$student->classSeries) return "❌ Pas de série";

        $tranches = $student->classSeries->paymentTranches ?? collect();
        $totalRequired = 0;
        $totalPaid = 0;
        $totalReductionApplied = 0;

        // Calculer les montants normaux requis
        foreach ($tranches as $tranche) {
            $requiredAmount = DB::table('class_payment_amounts')
                ->where('class_id', $student->classSeries->class_id)
                ->where('payment_tranche_id', $tranche->id)
                ->value('amount') ?? 0;
            
            $totalRequired += $requiredAmount;
        }

        // Calculer les montants payés et les réductions appliquées
        foreach ($student->payments as $payment) {
            if ($payment->validation_date) {
                $totalPaid += $payment->total_amount;
                $totalReductionApplied += $payment->reduction_amount ?? 0;
            }
        }

        // Le montant requis effectif = montant normal - réductions accordées
        $effectiveRequired = $totalRequired - $totalReductionApplied;
        
        $percentage = $effectiveRequired > 0 ? round(($totalPaid / $effectiveRequired) * 100) : 100;
        
        if ($totalPaid >= ($effectiveRequired - 1)) {
            return "✅ SOLVABLE ({$percentage}% - payé: {$totalPaid}, requis après réduction: {$effectiveRequired})";
        } else {
            $missing = $effectiveRequired - $totalPaid;
            return "❌ INSOLVABLE ({$percentage}% - payé: {$totalPaid}, requis: {$effectiveRequired}, manque: {$missing} FCFA)";
        }
    }

    /**
     * Logger les messages
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $message . "\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// === EXÉCUTION DU SCRIPT ===

if ($argc < 2) {
    echo "Usage: php create-missing-payment-details.php [test|analyze|dry-run|create|verify]\n";
    echo "\n";
    echo "Commandes:\n";
    echo "  test     - Tester sur un échantillon de paiements\n";
    echo "  analyze  - Analyser les payment_details manquants\n";
    echo "  dry-run  - Simulation complète (sans modification)\n";
    echo "  create   - Création réelle (MODIFIE LES DONNÉES)\n";
    echo "  verify   - Vérifier l'état après création\n";
    exit(1);
}

$command = $argv[1];
$creator = new MissingPaymentDetailsCreator($command !== 'create');

switch ($command) {
    case 'test':
        $creator->testSample();
        break;
        
    case 'analyze':
        $creator->analyze();
        break;
        
    case 'dry-run':
        $creator->create();
        break;
        
    case 'create':
        echo "⚠️  ATTENTION: Cette commande va CRÉER de nouveaux PAYMENT_DETAILS !\n";
        echo "Des enregistrements seront ajoutés à la base de données.\n";
        echo "Voulez-vous continuer ? (tapez 'OUI' pour confirmer): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if ($confirmation === 'OUI') {
            $creator->create();
        } else {
            echo "Opération annulée.\n";
        }
        break;
        
    case 'verify':
        $creator->verify();
        break;
        
    default:
        echo "Commande inconnue: {$command}\n";
        exit(1);
}

echo "\n=== FIN DU SCRIPT ===\n";