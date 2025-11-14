<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateStudentPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:recalculate
                            {student_id : ID de l\'élève}
                            {--dry-run : Simuler sans modifier les données}
                            {--details : Afficher les détails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculer la répartition des paiements d\'un élève selon sa classe actuelle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studentId = $this->argument('student_id');
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('details');

        $this->info("=== RECALCUL DES PAIEMENTS ===");
        $this->info("Élève ID: {$studentId}");
        if ($dryRun) {
            $this->warn("MODE SIMULATION - Aucune modification ne sera effectuée");
        }
        $this->newLine();

        // Récupérer l'élève
        $student = Student::with(['classSeries.schoolClass', 'schoolYear'])->find($studentId);

        if (!$student) {
            $this->error("Élève non trouvé avec l'ID: {$studentId}");
            return 1;
        }

        $this->info("Élève: {$student->first_name} {$student->last_name}");
        $this->info("Matricule: {$student->matricule}");
        $this->info("Classe actuelle: " . ($student->classSeries->schoolClass->name ?? 'N/A') . " - " . ($student->classSeries->name ?? 'N/A'));
        $this->info("Année scolaire: " . ($student->schoolYear->name ?? 'N/A'));
        $this->newLine();

        // Récupérer tous les paiements
        $allPayments = Payment::where('student_id', $studentId)
            ->where('school_year_id', $student->school_year_id)
            ->where('is_rame_physical', false)
            ->with(['paymentDetails.paymentTranche'])
            ->orderBy('payment_date', 'asc')
            ->get();

        if ($allPayments->isEmpty()) {
            $this->warn("Aucun paiement trouvé pour cet élève");
            return 0;
        }

        $this->info("Paiements trouvés: {$allPayments->count()}");
        $totalPaid = $allPayments->sum('total_amount');
        $this->info("Total payé: " . number_format($totalPaid, 0, ',', ' ') . " FCFA");
        $this->newLine();

        // Afficher l'ancienne répartition
        if ($verbose) {
            $this->info("=== ANCIENNE RÉPARTITION ===");
            foreach ($allPayments as $payment) {
                $this->line("Paiement #{$payment->id} ({$payment->receipt_number}) - " . number_format($payment->total_amount, 0, ',', ' ') . " FCFA");
                foreach ($payment->paymentDetails as $detail) {
                    $this->line("  → {$detail->paymentTranche->name}: " . number_format($detail->amount_allocated, 0, ',', ' ') . " FCFA");
                }
            }
            $this->newLine();
        }

        // Récupérer les tranches pour la classe actuelle
        $allTranches = PaymentTranche::active()->ordered()->get();

        if ($allTranches->isEmpty()) {
            $this->error("Aucune tranche de paiement configurée");
            return 1;
        }

        // Calculer la nouvelle répartition
        $this->info("=== CALCUL DE LA NOUVELLE RÉPARTITION ===");
        $newAllocation = [];
        $remainingAmount = $totalPaid;

        foreach ($allTranches as $tranche) {
            if ($remainingAmount <= 0) break;

            $requiredAmount = $tranche->getAmountForStudent($student, $student->is_new, false, false, false);

            if ($requiredAmount <= 0) continue;

            $allocatedAmount = min($remainingAmount, $requiredAmount);

            $newAllocation[] = [
                'tranche_id' => $tranche->id,
                'tranche_name' => $tranche->name,
                'required_amount' => $requiredAmount,
                'allocated_amount' => $allocatedAmount,
                'is_fully_paid' => $allocatedAmount >= $requiredAmount
            ];

            $status = $allocatedAmount >= $requiredAmount ? '✅ SOLDÉE' : '⚠️ PARTIEL';
            $this->line("{$tranche->name}: " . number_format($allocatedAmount, 0, ',', ' ') . " / " . number_format($requiredAmount, 0, ',', ' ') . " FCFA {$status}");

            $remainingAmount -= $allocatedAmount;
        }

        $this->newLine();
        $this->info("Solde restant: " . number_format($remainingAmount, 0, ',', ' ') . " FCFA");
        $this->newLine();

        if ($dryRun) {
            $this->warn("MODE SIMULATION - Aucune modification effectuée");
            $this->info("Relancez sans --dry-run pour appliquer les changements");
            return 0;
        }

        // Appliquer les modifications
        $this->warn("Application des modifications...");

        try {
            DB::beginTransaction();

            // Supprimer les anciens payment_details
            $deletedCount = PaymentDetail::whereIn('payment_id', $allPayments->pluck('id'))->delete();
            $this->info("Anciens détails supprimés: {$deletedCount}");

            // Redistribuer
            $this->redistributePaymentDetails($allPayments, $newAllocation);

            // Ajouter une note
            $transferNote = sprintf(
                'Paiements recalculés manuellement le %s via commande artisan',
                now()->format('d/m/Y H:i')
            );

            foreach ($allPayments as $payment) {
                $existingNotes = $payment->notes ? $payment->notes . "\n" : '';
                $payment->update([
                    'notes' => $existingNotes . $transferNote
                ]);
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ RECALCUL TERMINÉ AVEC SUCCÈS");
            $this->info("Les reçus peuvent maintenant être régénérés avec les nouveaux montants");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erreur lors du recalcul: " . $e->getMessage());
            Log::error('Error in RecalculateStudentPayments command', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Redistribuer les payment_details (copie de StudentController)
     */
    private function redistributePaymentDetails($payments, $newAllocation)
    {
        if (empty($newAllocation)) {
            return;
        }

        $allocationIndex = 0;
        $currentTranche = $newAllocation[$allocationIndex];
        $remainingInCurrentTranche = $currentTranche['allocated_amount'];

        foreach ($payments as $payment) {
            $paymentAmount = $payment->total_amount;
            $remainingInPayment = $paymentAmount;

            while ($remainingInPayment > 0 && $allocationIndex < count($newAllocation)) {
                $currentTranche = $newAllocation[$allocationIndex];

                if ($remainingInCurrentTranche <= 0) {
                    $allocationIndex++;
                    if ($allocationIndex >= count($newAllocation)) {
                        break;
                    }
                    $currentTranche = $newAllocation[$allocationIndex];
                    $remainingInCurrentTranche = $currentTranche['allocated_amount'];
                }

                $amountToAllocate = min($remainingInPayment, $remainingInCurrentTranche);

                if ($amountToAllocate > 0) {
                    PaymentDetail::create([
                        'payment_id' => $payment->id,
                        'payment_tranche_id' => $currentTranche['tranche_id'],
                        'amount_allocated' => $amountToAllocate,
                        'previous_amount' => 0,
                        'new_total_amount' => $amountToAllocate,
                        'is_fully_paid' => $currentTranche['is_fully_paid'] && ($amountToAllocate == $remainingInCurrentTranche),
                        'required_amount_at_time' => $currentTranche['required_amount'],
                        'was_reduced' => false
                    ]);

                    $remainingInPayment -= $amountToAllocate;
                    $remainingInCurrentTranche -= $amountToAllocate;
                }
            }
        }

        $this->info("Nouveaux détails créés avec succès");
    }
}
