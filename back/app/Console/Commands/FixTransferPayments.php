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

        // Récupérer UNIQUEMENT les paiements pending qui mentionnent un transfert dans les notes
        // Ces paiements ont été créés AVANT le transfert et ont des montants de l'ancienne classe
        $query = Payment::where('status', 'pending')
            ->where('total_amount', '>', 0)
            ->where(function($q) {
                $q->where('notes', 'like', '%Transfert de%')
                  ->orWhere('notes', 'like', '%transfert%')
                  ->orWhereNull('notes'); // Inclure aussi les paiements sans notes qui pourraient être problématiques
            });

        if ($specificStudentId) {
            $query->where('student_id', $specificStudentId);
            $this->info("Analyse de l'élève ID: {$specificStudentId}");
        } else {
            $this->info("Analyse des paiements pending liés aux transferts de classe...");
        }

        $allPendingPayments = $query->with(['student.classSeries.schoolClass'])->get();

        // Filtrer manuellement pour ne garder QUE les paiements réellement liés à des transferts
        $problematicPayments = $allPendingPayments->filter(function($payment) {
            // Si le paiement contient explicitement "Transfert" dans les notes, le garder
            if ($payment->notes && (
                str_contains($payment->notes, 'Transfert de') ||
                str_contains($payment->notes, 'transfert')
            )) {
                return true;
            }

            // Sinon, vérifier si l'élève a des paiements validés avec mention de transfert
            // Cela indique qu'il a été transféré et que ce paiement pending pourrait être lié
            $hasTransferHistory = Payment::where('student_id', $payment->student_id)
                ->where('status', 'validated')
                ->where(function($q) {
                    $q->where('notes', 'like', '%Transfert de%')
                      ->orWhere('notes', 'like', '%Correction automatique après transfert%');
                })
                ->exists();

            return $hasTransferHistory;
        });

        if ($problematicPayments->isEmpty()) {
            $this->info('✅ Aucun paiement problématique lié à un transfert trouvé!');
            $this->info('Note: Seuls les paiements des élèves transférés sont analysés.');
            return 0;
        }

        $this->warn("⚠️  {$problematicPayments->count()} paiement(s) problématique(s) trouvé(s) pour des élèves transférés");
        $this->info('Ces paiements sont en attente de validation suite à un transfert de classe.');
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

            // Extraire l'info de transfert depuis les notes si disponible
            $transferInfo = 'Oui';
            if ($payment->notes && str_contains($payment->notes, 'Transfert de')) {
                // Extraire "de X vers Y" depuis les notes
                preg_match('/Transfert de (.+?) vers (.+?)( -|$)/', $payment->notes, $matches);
                if (count($matches) >= 3) {
                    $transferInfo = $matches[1] . ' → ' . $matches[2];
                }
            }

            $issue = [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'student_number' => $student->student_number,
                'current_class' => $student->classSeries ? $student->classSeries->name : 'N/A',
                'transfer_info' => $transferInfo,
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
        $this->info('RÉSUMÉ DES PAIEMENTS PROBLÉMATIQUES (ÉLÈVES TRANSFÉRÉS):');
        $this->newLine();

        $headers = ['ID', 'Élève', 'N° Élève', 'Classe Actuelle', 'Transfert', 'Montant Payé', 'Inscription', 'Reste', 'Statut'];
        $rows = [];

        foreach ($issues as $issue) {
            $rows[] = [
                $issue['payment_id'],
                $issue['student_name'],
                $issue['student_number'],
                $issue['current_class'],
                $issue['transfer_info'],
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
