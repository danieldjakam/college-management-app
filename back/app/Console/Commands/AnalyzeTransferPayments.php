<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyzeTransferPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:analyze-transfers
        {--fix : Corriger automatiquement les problèmes détectés}
        {--student-id= : Analyser un élève spécifique}
        {--student-number= : Analyser un élève par son matricule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyser et corriger les problèmes de paiements pour TOUS les élèves transférés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldFix = $this->option('fix');
        $specificStudentId = $this->option('student-id');
        $studentNumber = $this->option('student-number');

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  ANALYSE COMPLÈTE DES PAIEMENTS POUR ÉLÈVES TRANSFÉRÉS');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if (!$shouldFix) {
            $this->warn('MODE ANALYSE SEULEMENT: Aucune modification ne sera appliquée');
            $this->warn('Utilisez --fix pour corriger les problèmes détectés');
            $this->newLine();
        }

        // Trouver tous les élèves qui ont des paiements pending OU qui ont été transférés
        $query = Student::whereHas('payments', function($q) {
            $q->where(function($sq) {
                // Paiements pending
                $sq->where('status', 'pending')
                  // OU paiements avec mention de transfert
                  ->orWhere('notes', 'like', '%Transfert de%')
                  ->orWhere('notes', 'like', '%Correction automatique après transfert%');
            });
        })->with(['classSeries.schoolClass', 'payments' => function($q) {
            $q->orderBy('payment_date', 'asc');
        }]);

        // Filtrer par élève spécifique si demandé
        if ($specificStudentId) {
            $query->where('id', $specificStudentId);
            $this->info("Analyse de l'élève ID: {$specificStudentId}");
        } elseif ($studentNumber) {
            $query->where('student_number', $studentNumber);
            $this->info("Analyse de l'élève N°: {$studentNumber}");
        } else {
            $this->info("Analyse de tous les élèves ayant un historique de transfert...");
        }

        $transferredStudents = $query->get();

        if ($transferredStudents->isEmpty()) {
            $this->info('✅ Aucun élève transféré trouvé dans le système.');
            return 0;
        }

        $this->info("📊 {$transferredStudents->count()} élève(s) transféré(s) trouvé(s)");
        $this->newLine();

        $bar = $this->output->createProgressBar($transferredStudents->count());
        $bar->start();

        $results = [];
        $totalFixed = 0;
        $totalErrors = 0;
        $totalProblems = 0;

        foreach ($transferredStudents as $student) {
            $studentResult = $this->analyzeStudent($student, $shouldFix);

            if ($studentResult['has_problem']) {
                $results[] = $studentResult;
                $totalProblems++;

                if ($studentResult['fixed']) {
                    $totalFixed++;
                }
                if ($studentResult['error']) {
                    $totalErrors++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher les résultats
        if (empty($results)) {
            $this->info('═══════════════════════════════════════════════════════════');
            $this->info('✅ AUCUN PROBLÈME DÉTECTÉ!');
            $this->info('═══════════════════════════════════════════════════════════');
            $this->info('Tous les paiements des élèves transférés sont corrects.');
            return 0;
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  ÉLÈVES AVEC PROBLÈMES DE PAIEMENTS APRÈS TRANSFERT');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $headers = ['N° Élève', 'Nom', 'Classe Actuelle', 'Problème', 'Statut'];
        $rows = [];

        foreach ($results as $result) {
            $rows[] = [
                $result['student_number'],
                $result['student_name'],
                $result['current_class'],
                $result['problem_description'],
                $result['status']
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  STATISTIQUES');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("Total d'élèves transférés analysés: {$transferredStudents->count()}");
        $this->info("Élèves avec problèmes: {$totalProblems}");

        if ($shouldFix) {
            $this->info("✅ Problèmes corrigés: {$totalFixed}");
            $this->error("❌ Erreurs lors de la correction: {$totalErrors}");
        } else {
            $this->warn("Pour corriger ces problèmes, relancez avec --fix:");
            if ($specificStudentId) {
                $this->comment("  php artisan payments:analyze-transfers --fix --student-id={$specificStudentId}");
            } elseif ($studentNumber) {
                $this->comment("  php artisan payments:analyze-transfers --fix --student-number={$studentNumber}");
            } else {
                $this->comment("  php artisan payments:analyze-transfers --fix");
            }
        }

        $this->newLine();
        return 0;
    }

    /**
     * Analyser les paiements d'un élève transféré
     */
    private function analyzeStudent($student, $shouldFix)
    {
        $result = [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'current_class' => $student->classSeries ?
                $student->classSeries->schoolClass->name . ' ' . $student->classSeries->name :
                'Aucune classe',
            'has_problem' => false,
            'problem_description' => '',
            'status' => '',
            'fixed' => false,
            'error' => false
        ];

        // Récupérer tous les paiements de l'élève
        $payments = $student->payments;

        // Chercher les paiements pending
        $pendingPayments = $payments->where('status', 'pending');

        if ($pendingPayments->isEmpty()) {
            // Pas de paiement pending, tout est OK
            return $result;
        }

        // Il y a des paiements pending, c'est un problème
        $result['has_problem'] = true;
        $result['problem_description'] = sprintf(
            '%d paiement(s) en attente - Total: %s FCFA',
            $pendingPayments->count(),
            number_format($pendingPayments->sum('total_amount'), 0, ',', ' ')
        );

        if (!$shouldFix) {
            $result['status'] = '🔍 À CORRIGER';
            return $result;
        }

        // Essayer de corriger chaque paiement pending
        try {
            foreach ($pendingPayments as $payment) {
                $this->fixPayment($payment, $student);
            }

            $result['fixed'] = true;
            $result['status'] = '✅ CORRIGÉ';

        } catch (\Exception $e) {
            $result['error'] = true;
            $result['status'] = '❌ ERREUR: ' . substr($e->getMessage(), 0, 50);

            Log::error('Erreur lors de la correction du paiement', [
                'student_id' => $student->id,
                'student_name' => $result['student_name'],
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Corriger un paiement pending
     */
    private function fixPayment($payment, $student)
    {
        DB::beginTransaction();

        try {
            // Récupérer la tranche inscription
            $inscriptionTranche = PaymentTranche::where('order', 1)->active()->first();

            if (!$inscriptionTranche) {
                throw new \Exception('Tranche inscription introuvable');
            }

            if (!$student->classSeries) {
                throw new \Exception('Élève sans classe assignée');
            }

            // Calculer le montant d'inscription pour la classe actuelle
            $inscriptionAmount = $inscriptionTranche->getAmountForStudent(
                $student,
                $student->is_new,
                false,
                false,
                false
            );

            $totalPaid = $payment->total_amount;
            $remaining = $totalPaid - $inscriptionAmount;

            // Vérifier si le paiement a déjà des détails CORRECTS
            $existingDetails = PaymentDetail::where('payment_id', $payment->id)->get();
            $hasCorrectDetails = false;

            if ($existingDetails->isNotEmpty()) {
                // Vérifier si le montant d'inscription correspond à la classe actuelle
                $inscriptionDetail = $existingDetails->where('payment_tranche_id', $inscriptionTranche->id)->first();

                if ($inscriptionDetail) {
                    // Les détails sont corrects si:
                    // 1. Le montant d'inscription alloué = min(inscription classe actuelle, total payé)
                    // 2. Le total alloué = total payé
                    $expectedInscription = min($inscriptionAmount, $totalPaid);
                    $totalAllocated = $existingDetails->sum('amount_allocated');

                    if ($inscriptionDetail->amount_allocated == $expectedInscription && $totalAllocated == $totalPaid) {
                        $hasCorrectDetails = true;
                    }
                }
            }

            // Si les détails ne sont pas corrects, les recalculer
            if (!$hasCorrectDetails) {
                // Supprimer les anciens détails
                PaymentDetail::where('payment_id', $payment->id)->delete();

                // 1. Créer le détail pour l'inscription
                PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'payment_tranche_id' => $inscriptionTranche->id,
                    'amount_allocated' => min($inscriptionAmount, $totalPaid),
                    'previous_amount' => 0,
                    'new_total_amount' => min($inscriptionAmount, $totalPaid),
                    'is_fully_paid' => $totalPaid >= $inscriptionAmount,
                    'required_amount_at_time' => $inscriptionAmount,
                    'was_reduced' => false
                ]);

                // 2. Si reste > 0, affecter à la 1ère tranche
                if ($remaining > 0) {
                    $firstTranche = PaymentTranche::where('order', 2)->active()->first();

                    if ($firstTranche) {
                        $firstTrancheAmount = $firstTranche->getAmountForStudent(
                            $student,
                            $student->is_new,
                            false,
                            false,
                            false
                        );

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
            }

            // 3. Valider le paiement (que les détails aient été recalculés ou non)
            $noteText = $hasCorrectDetails
                ? sprintf('Validation automatique - Détails corrects (Script: %s)', now()->format('d/m/Y H:i'))
                : sprintf('Correction automatique - Inscription: %s FCFA, Reste: %s FCFA (Script: %s)',
                    number_format($inscriptionAmount, 0, ',', ' '),
                    number_format($remaining, 0, ',', ' '),
                    now()->format('d/m/Y H:i'));

            $payment->update([
                'status' => 'validated',
                'validation_date' => now(),
                'status_updated_at' => now(),
                'notes' => $payment->notes ? $payment->notes . ' | ' . $noteText : $noteText
            ]);

            DB::commit();

            Log::info('Paiement corrigé avec succès', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'inscription' => $inscriptionAmount,
                'remaining' => $remaining
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
