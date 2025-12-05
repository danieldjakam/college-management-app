<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixObonoPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:obono-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correction allocation paiements OBONO MAKOK SYLVIE MURIEL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=========================================');
        $this->info('CORRECTION OBONO MAKOK SYLVIE MURIEL');
        $this->info('=========================================');
        $this->newLine();

        // Étape 1: Vérification
        $this->info('🔍 Étape 1: Vérification de l\'état actuel...');
        $this->newLine();

        $student = DB::table('students')->where('id', 565)->first();

        if (!$student) {
            $this->error('❌ ERREUR: Étudiante ID 565 non trouvée!');
            return 1;
        }

        $this->line("Étudiante: {$student->first_name} {$student->last_name}");
        $this->line("Classe ID: {$student->class_series_id}");
        $this->newLine();

        // Calculer les montants actuels
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

        $this->line("État actuel:");
        $this->line("  1ère Tranche: {$tranche1Total} FCFA");
        $this->line("  3ème Tranche: {$tranche3Total} FCFA");
        $this->newLine();

        // Vérifier si déjà corrigé
        if ($tranche1Total == 57000 && $tranche3Total == 10000) {
            $this->info('✅ Les montants sont déjà corrects! Aucune correction nécessaire.');
            return 0;
        }

        // Vérifier que les montants correspondent à ce qui est attendu
        if ($tranche1Total != 67000) {
            $this->warn("⚠️  ATTENTION: Le montant de la 1ère tranche ({$tranche1Total}) ne correspond pas à l'attendu (67000).");
            $this->error('La correction pourrait ne pas être applicable.');
            return 1;
        }

        // Étape 2: Application de la correction
        $this->info('🔧 Étape 2: Application de la correction...');
        $this->newLine();

        $tranche3 = DB::table('payment_tranches')->where('name', '3ème Tranche')->first();

        if (!$tranche3) {
            $this->error('❌ ERREUR: 3ème Tranche non trouvée!');
            return 1;
        }

        $this->line("ID de la 3ème Tranche: {$tranche3->id}");
        $this->newLine();

        // Vérifier que le payment_detail existe
        $detailToMove = DB::table('payment_details')->where('id', 5104)->first();

        if (!$detailToMove) {
            $this->error('❌ ERREUR: payment_detail ID 5104 non trouvé!');
            return 1;
        }

        $this->line("Payment detail à modifier:");
        $this->line("  ID: {$detailToMove->id}");
        $this->line("  Payment ID: {$detailToMove->payment_id}");
        $this->line("  Montant: {$detailToMove->amount_allocated} FCFA");
        $this->line("  Tranche actuelle ID: {$detailToMove->payment_tranche_id}");
        $this->line("  Nouvelle tranche ID: {$tranche3->id}");
        $this->newLine();

        // Appliquer la correction
        $updated = DB::table('payment_details')
            ->where('id', 5104)
            ->update(['payment_tranche_id' => $tranche3->id]);

        if ($updated === 1) {
            $this->info('✅ Payment detail ID 5104 déplacé vers 3ème tranche');
            $this->newLine();
        } else {
            $this->error("❌ ERREUR: La mise à jour a échoué (lignes affectées: {$updated})");
            return 1;
        }

        // Étape 3: Vérification finale
        $this->info('🎯 Étape 3: Vérification finale...');
        $this->newLine();

        $tranche1Final = DB::table('payment_details')
            ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
            ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
            ->where('payments.student_id', 565)
            ->where('payment_tranches.name', '1ère Tranche')
            ->sum('payment_details.amount_allocated');

        $tranche3Final = DB::table('payment_details')
            ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
            ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
            ->where('payments.student_id', 565)
            ->where('payment_tranches.name', '3ème Tranche')
            ->sum('payment_details.amount_allocated');

        $this->line("État après correction:");
        $this->line("  1ère Tranche: {$tranche1Final} FCFA (attendu: 57000)");
        $this->line("  3ème Tranche: {$tranche3Final} FCFA (attendu: 10000)");
        $this->newLine();

        if ($tranche1Final == 57000 && $tranche3Final == 10000) {
            $this->info('✅ CORRECTION RÉUSSIE!');
            $this->newLine();

            // Afficher le résumé complet
            $this->line('=== RÉSUMÉ COMPLET DES PAIEMENTS ===');
            $this->newLine();

            $allTranches = ['Inscription', '1ère Tranche', '2ème Tranche', '3ème Tranche'];
            $totalPaid = 0;

            foreach ($allTranches as $trancheName) {
                $paid = DB::table('payment_details')
                    ->join('payments', 'payment_details.payment_id', '=', 'payments.id')
                    ->join('payment_tranches', 'payment_details.payment_tranche_id', '=', 'payment_tranches.id')
                    ->where('payments.student_id', 565)
                    ->where('payment_tranches.name', $trancheName)
                    ->sum('payment_details.amount_allocated');

                $this->line("{$trancheName}: {$paid} FCFA");
                $totalPaid += $paid;
            }

            $this->newLine();
            $this->line("Total payé: {$totalPaid} FCFA");
            $this->newLine();
            $this->info('=========================================');
            $this->info('FIN DE LA CORRECTION');
            $this->info('=========================================');

            return 0;
        } else {
            $this->error('❌ PROBLÈME: Les montants ne correspondent pas aux valeurs attendues');
            return 1;
        }
    }
}
