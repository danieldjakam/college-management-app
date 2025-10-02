<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;
use Illuminate\Support\Facades\DB;

class FixTransferPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:fix-transfers {--dry-run : Ne pas appliquer les corrections, seulement identifier les problèmes} {--student-id= : Corriger un élève spécifique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifier et corriger les paiements problématiques après transfert de classe';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificStudentId = $this->option('student-id');

        $this->info('===========================================');
        $this->info('  DIAGNOSTIC DES PAIEMENTS APRÈS TRANSFERT');
        $this->info('===========================================');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('MODE DRY-RUN: Aucune modification ne sera appliquée');
            $this->newLine();
        }

        // Récupérer les paiements pending (avec ou sans détails)
        // On va vérifier les détails après pour voir s'ils sont corrects
        $query = Payment::where('status', 'pending')
            ->where('total_amount', '>', 0);

        if ($specificStudentId) {
            $query->where('student_id', $specificStudentId);
            $this->info("Analyse de l'élève ID: {$specificStudentId}");
        } else {
            $this->info("Analyse de tous les paiements pending...");
        }

        $problematicPayments = $query->with(['student.classSeries.schoolClass'])->get();

        if ($problematicPayments->isEmpty()) {
            $this->info('✅ Aucun paiement problématique trouvé!');
            return 0;
        }

        $this->warn("⚠️  {$problematicPayments->count()} paiement(s) problématique(s) trouvé(s)");
        $this->newLine();

        $bar = $this->output->createProgressBar($problematicPayments->count());
        $bar->start();

        $fixed = 0;
        $errors = 0;
        $issues = [];

        foreach ($problematicPayments as $payment) {
            $student = $payment->student;

            if (!$student) {
                $this->newLine();
                $this->error("  ❌ Paiement ID {$payment->id}: Élève introuvable");
                $errors++;
                $bar->advance();
                continue;
            }

            $issue = [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'student_number' => $student->student_number,
                'current_class' => $student->classSeries ? $student->classSeries->name : 'N/A',
                'amount_paid' => number_format($payment->total_amount, 0, ',', ' ') . ' FCFA',
                'payment_date' => $payment->payment_date->format('d/m/Y'),
                'receipt' => $payment->receipt_number,
            ];

            // Calculer l'inscription pour la classe actuelle
            $inscriptionTranche = PaymentTranche::where('order', 1)->active()->first();

            if (!$inscriptionTranche || !$student->classSeries) {
                $issue['error'] = 'Tranche inscription ou classe introuvable';
                $issues[] = $issue;
                $errors++;
                $bar->advance();
                continue;
            }

            $inscriptionAmount = $inscriptionTranche->getAmountForStudent($student, $student->is_new, false, false, false);
            $remaining = $payment->total_amount - $inscriptionAmount;

            $issue['inscription_amount'] = number_format($inscriptionAmount, 0, ',', ' ') . ' FCFA';
            $issue['remaining'] = number_format($remaining, 0, ',', ' ') . ' FCFA';
            $issue['can_fix'] = $remaining >= 0;

            if (!$isDryRun && $issue['can_fix']) {
                // Appliquer la correction
                try {
                    DB::beginTransaction();

                    // Supprimer les anciens détails s'ils existent
                    PaymentDetail::where('payment_id', $payment->id)->delete();

                    // 1. Créer le détail pour l'inscription
                    PaymentDetail::create([
                        'payment_id' => $payment->id,
                        'payment_tranche_id' => $inscriptionTranche->id,
                        'amount_allocated' => $inscriptionAmount,
                        'previous_amount' => 0,
                        'new_total_amount' => $inscriptionAmount,
                        'is_fully_paid' => true,
                        'required_amount_at_time' => $inscriptionAmount,
                        'was_reduced' => false
                    ]);

                    // 2. Si reste > 0, affecter à la 1ère tranche
                    if ($remaining > 0) {
                        $firstTranche = PaymentTranche::where('order', 2)->active()->first();

                        if ($firstTranche) {
                            $firstTrancheAmount = $firstTranche->getAmountForStudent($student, $student->is_new, false, false, false);
                            $isFullyPaid = $remaining >= $firstTrancheAmount;

                            PaymentDetail::create([
                                'payment_id' => $payment->id,
                                'payment_tranche_id' => $firstTranche->id,
                                'amount_allocated' => $remaining,
                                'previous_amount' => 0,
                                'new_total_amount' => $remaining,
                                'is_fully_paid' => $isFullyPaid,
                                'required_amount_at_time' => $firstTrancheAmount,
                                'was_reduced' => false
                            ]);
                        }
                    }

                    // 3. Valider le paiement
                    $payment->update([
                        'status' => 'validated',
                        'validation_date' => now(),
                        'status_updated_at' => now(),
                        'notes' => sprintf(
                            'Correction automatique après transfert - Inscription: %s FCFA, Reste affecté à 1ère tranche: %s FCFA',
                            number_format($inscriptionAmount, 0, ',', ' '),
                            number_format($remaining, 0, ',', ' ')
                        )
                    ]);

                    DB::commit();
                    $fixed++;
                    $issue['status'] = '✅ CORRIGÉ';

                } catch (\Exception $e) {
                    DB::rollBack();
                    $issue['status'] = '❌ ERREUR: ' . $e->getMessage();
                    $errors++;
                }
            } else {
                $issue['status'] = $isDryRun ? '🔍 À CORRIGER' : ($issue['can_fix'] ? '⏭️  IGNORÉ' : '❌ IMPOSSIBLE');
            }

            $issues[] = $issue;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le tableau des résultats
        $this->info('RÉSUMÉ DES PAIEMENTS PROBLÉMATIQUES:');
        $this->newLine();

        $headers = ['ID Paiement', 'Élève', 'N° Élève', 'Classe', 'Montant Payé', 'Inscription', 'Reste', 'Statut'];
        $rows = [];

        foreach ($issues as $issue) {
            $rows[] = [
                $issue['payment_id'],
                $issue['student_name'],
                $issue['student_number'],
                $issue['current_class'],
                $issue['amount_paid'],
                $issue['inscription_amount'],
                $issue['remaining'],
                $issue['status']
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("===========================================");
        $this->info("  STATISTIQUES");
        $this->info("===========================================");
        $this->info("Total analysé: {$problematicPayments->count()}");
        if (!$isDryRun) {
            $this->info("✅ Corrigé: {$fixed}");
            $this->error("❌ Erreurs: {$errors}");
        }
        $this->newLine();

        if ($isDryRun && $issues) {
            $this->warn('Pour appliquer les corrections, relancez la commande sans --dry-run:');
            if ($specificStudentId) {
                $this->comment("  php artisan payments:fix-transfers --student-id={$specificStudentId}");
            } else {
                $this->comment("  php artisan payments:fix-transfers");
            }
        }

        return 0;
    }
}
