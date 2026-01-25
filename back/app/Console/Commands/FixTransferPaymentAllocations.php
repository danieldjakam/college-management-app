<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;

class FixTransferPaymentAllocations extends Command
{
    protected $signature = 'payments:fix-transfer-allocations
                            {--student-id= : ID spécifique d\'un étudiant à corriger}
                            {--dry-run : Simuler sans appliquer les modifications}
                            {--detailed : Afficher plus de détails}';

    protected $description = 'Corriger les allocations de paiements incorrectes après transferts de classe';

    public function handle()
    {
        $this->info('🔧 Correction des allocations de paiements après transferts');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $detailed = $this->option('detailed');
        $specificStudentId = $this->option('student-id');

        if ($dryRun) {
            $this->warn('⚠️  MODE SIMULATION - Aucune modification ne sera appliquée');
            $this->newLine();
        }

        // Trouver les étudiants avec surpaiement sur l'inscription
        $this->info('🔍 Recherche des étudiants avec surpaiements...');

        $query = "
            SELECT
                s.id AS student_id,
                s.name AS student_name,
                cs.name AS current_class,
                SUM(CASE WHEN pt.name = 'Inscription' THEN pd.amount_allocated ELSE 0 END) AS total_paid_inscription,
                (SELECT amount FROM class_payment_amounts cpa
                 JOIN school_classes sc ON sc.id = cpa.class_id
                 JOIN class_series css ON css.class_id = sc.id
                 WHERE css.id = s.class_series_id
                 AND cpa.payment_tranche_id = (SELECT id FROM payment_tranches WHERE name = 'Inscription' LIMIT 1)
                 LIMIT 1) AS required_inscription,
                (
                    SUM(CASE WHEN pt.name = 'Inscription' THEN pd.amount_allocated ELSE 0 END) -
                    (SELECT amount FROM class_payment_amounts cpa
                     JOIN school_classes sc ON sc.id = cpa.class_id
                     JOIN class_series css ON css.class_id = sc.id
                     WHERE css.id = s.class_series_id
                     AND cpa.payment_tranche_id = (SELECT id FROM payment_tranches WHERE name = 'Inscription' LIMIT 1)
                     LIMIT 1)
                ) AS overpayment
            FROM students s
            JOIN class_series cs ON s.class_series_id = cs.id
            JOIN payments p ON p.student_id = s.id
            JOIN payment_details pd ON pd.payment_id = p.id
            JOIN payment_tranches pt ON pt.id = pd.payment_tranche_id
            WHERE p.school_year_id = (SELECT id FROM school_years WHERE is_current = 1 LIMIT 1)
        ";

        if ($specificStudentId) {
            $query .= " AND s.id = " . intval($specificStudentId);
        }

        $query .= "
            GROUP BY s.id, s.name, cs.name, s.class_series_id
            HAVING overpayment > 0
            ORDER BY s.name
        ";

        $affectedStudents = DB::select($query);

        if (empty($affectedStudents)) {
            $this->info('✅ Aucun étudiant avec surpaiement détecté.');
            return Command::SUCCESS;
        }

        $this->warn('⚠️  ' . count($affectedStudents) . ' étudiant(s) affecté(s) détecté(s)');
        $this->newLine();

        $corrections = [];

        foreach ($affectedStudents as $studentData) {
            $this->info("📋 Étudiant: {$studentData->student_name} (ID: {$studentData->student_id})");
            $this->line("   Classe: {$studentData->current_class}");
            $this->line("   Inscription requise: {$studentData->required_inscription} FCFA");
            $this->line("   Inscription payée: {$studentData->total_paid_inscription} FCFA");
            $this->error("   ❌ Surpaiement: {$studentData->overpayment} FCFA");
            $this->newLine();

            // Correction pour cet étudiant
            $correction = $this->correctStudentAllocations(
                $studentData->student_id,
                $studentData->overpayment,
                $dryRun,
                $detailed
            );

            $corrections[] = $correction;
            $this->newLine();
            $this->line('─────────────────────────────────────────────');
            $this->newLine();
        }

        // Résumé
        $this->newLine();
        $this->info('═══════════════════════════════════════════════');
        $this->info('📊 RÉSUMÉ DES CORRECTIONS');
        $this->info('═══════════════════════════════════════════════');

        $totalFixed = count(array_filter($corrections, fn($c) => $c['success']));
        $totalFailed = count(array_filter($corrections, fn($c) => !$c['success']));

        $this->info("✅ Corrections réussies: {$totalFixed}");
        if ($totalFailed > 0) {
            $this->error("❌ Corrections échouées: {$totalFailed}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  MODE SIMULATION - Aucune modification appliquée');
            $this->info('💡 Relancez sans --dry-run pour appliquer les corrections');
        }

        return Command::SUCCESS;
    }

    private function correctStudentAllocations($studentId, $overpayment, $dryRun, $detailed)
    {
        DB::beginTransaction();

        try {
            $student = Student::findOrFail($studentId);

            // 1. Réduire l'inscription au montant requis
            $inscriptionTranche = PaymentTranche::where('name', 'Inscription')->first();

            // Obtenir le montant requis pour l'inscription dans la classe actuelle
            $requiredInscription = DB::table('class_payment_amounts')
                ->join('school_classes', 'school_classes.id', '=', 'class_payment_amounts.class_id')
                ->join('class_series', 'class_series.class_id', '=', 'school_classes.id')
                ->where('class_series.id', $student->class_series_id)
                ->where('class_payment_amounts.payment_tranche_id', $inscriptionTranche->id)
                ->value('class_payment_amounts.amount');

            // Trouver le dernier payment_detail de l'inscription avec surpaiement
            $lastInscriptionDetail = PaymentDetail::whereHas('payment', function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })
                ->where('payment_tranche_id', $inscriptionTranche->id)
                ->where('amount_allocated', '>', 0)
                ->orderBy('id', 'desc')
                ->first();

            if (!$lastInscriptionDetail) {
                throw new \Exception("Aucun détail d'inscription trouvé");
            }

            $this->line("   🔧 Correction du paiement #{$lastInscriptionDetail->payment_id}");

            // 2. Réduire le montant de l'inscription
            $excessAmount = $overpayment;
            $newInscriptionAmount = $lastInscriptionDetail->amount_allocated - $excessAmount;

            if ($detailed) {
                $this->line("      Inscription avant: {$lastInscriptionDetail->amount_allocated} FCFA");
                $this->line("      Inscription après: {$newInscriptionAmount} FCFA");
                $this->line("      Excédent à réallouer: {$excessAmount} FCFA");
            }

            if (!$dryRun) {
                $lastInscriptionDetail->update([
                    'amount_allocated' => $newInscriptionAmount,
                    'new_total_amount' => $requiredInscription,
                    'is_fully_paid' => true
                ]);
            }

            // 3. Réallouer l'excédent aux tranches non payées
            $tranches = PaymentTranche::where('is_active', true)
                ->where('name', '!=', 'Inscription')
                ->orderBy('order')
                ->get();

            $remainingToAllocate = $excessAmount;
            $payment = $lastInscriptionDetail->payment;

            foreach ($tranches as $tranche) {
                if ($remainingToAllocate <= 0) break;

                // Montant requis pour cette tranche
                $requiredAmount = DB::table('class_payment_amounts')
                    ->join('school_classes', 'school_classes.id', '=', 'class_payment_amounts.class_id')
                    ->join('class_series', 'class_series.class_id', '=', 'school_classes.id')
                    ->where('class_series.id', $student->class_series_id)
                    ->where('class_payment_amounts.payment_tranche_id', $tranche->id)
                    ->value('class_payment_amounts.amount');

                // Montant déjà payé
                $alreadyPaid = PaymentDetail::whereHas('payment', function($q) use ($studentId) {
                        $q->where('student_id', $studentId);
                    })
                    ->where('payment_tranche_id', $tranche->id)
                    ->sum('amount_allocated');

                $remaining = $requiredAmount - $alreadyPaid;

                if ($remaining > 0) {
                    $amountToAllocate = min($remaining, $remainingToAllocate);

                    // Chercher si un payment_detail existe déjà pour cette tranche dans ce paiement
                    $existingDetail = PaymentDetail::where('payment_id', $payment->id)
                        ->where('payment_tranche_id', $tranche->id)
                        ->first();

                    if ($existingDetail) {
                        $newAmount = $existingDetail->amount_allocated + $amountToAllocate;
                        $newTotal = $existingDetail->new_total_amount + $amountToAllocate;

                        if ($detailed) {
                            $this->line("      {$tranche->name}: +{$amountToAllocate} FCFA (total: {$newTotal} FCFA)");
                        }

                        if (!$dryRun) {
                            $existingDetail->update([
                                'amount_allocated' => $newAmount,
                                'new_total_amount' => $newTotal,
                                'is_fully_paid' => ($newTotal >= $requiredAmount)
                            ]);
                        }
                    } else {
                        if ($detailed) {
                            $this->line("      Créer {$tranche->name}: {$amountToAllocate} FCFA");
                        }

                        if (!$dryRun) {
                            PaymentDetail::create([
                                'payment_id' => $payment->id,
                                'payment_tranche_id' => $tranche->id,
                                'amount_allocated' => $amountToAllocate,
                                'previous_amount' => $alreadyPaid,
                                'new_total_amount' => $alreadyPaid + $amountToAllocate,
                                'required_amount_at_time' => $requiredAmount,
                                'is_fully_paid' => (($alreadyPaid + $amountToAllocate) >= $requiredAmount)
                            ]);
                        }
                    }

                    $remainingToAllocate -= $amountToAllocate;
                }
            }

            if ($remainingToAllocate > 0) {
                $this->warn("      ⚠️  Reste {$remainingToAllocate} FCFA non réalloué");
            }

            if (!$dryRun) {
                DB::commit();
                $this->info("   ✅ Correction appliquée avec succès");
            } else {
                DB::rollBack();
                $this->info("   ✅ Simulation réussie");
            }

            return [
                'student_id' => $studentId,
                'success' => true,
                'overpayment_fixed' => $overpayment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("   ❌ Erreur: " . $e->getMessage());

            if ($detailed) {
                $this->error("   " . $e->getTraceAsString());
            }

            return [
                'student_id' => $studentId,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
