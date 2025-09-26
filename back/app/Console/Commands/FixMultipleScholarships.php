<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Student;

class FixMultipleScholarships extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scholarships:fix-multiple
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--report : Générer seulement un rapport}';

    /**
     * The console command description.
     */
    protected $description = 'Corriger les bourses multiples appliquées incorrectement aux étudiants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 === CORRECTION DES BOURSES MULTIPLES ===');
        $this->newLine();

        // Mode rapport uniquement
        if ($this->option('report')) {
            $this->generateReport();
            return 0;
        }

        // Identifier les étudiants avec bourses multiples
        $affectedStudents = $this->getStudentsWithMultipleScholarships();

        if (empty($affectedStudents)) {
            $this->info('✅ Aucun étudiant avec bourses multiples trouvé');
            return 0;
        }

        $this->warn("📊 {$affectedStudents->count()} étudiants affectés par des bourses multiples");
        $this->newLine();

        // Afficher le résumé
        $this->displaySummary($affectedStudents);

        // Mode dry-run
        if ($this->option('dry-run')) {
            $this->warn('🔍 MODE DRY-RUN - Aucune modification ne sera appliquée');
            $this->showWhatWouldBeChanged($affectedStudents);
            return 0;
        }

        // Demander confirmation
        if (!$this->confirm('Voulez-vous procéder à la correction ?')) {
            $this->info('❌ Correction annulée');
            return 0;
        }

        // Exécuter la correction
        $this->executeCorrection($affectedStudents);

        $this->info('✅ Correction terminée');
        return 0;
    }

    /**
     * Identifier les étudiants ayant reçu plusieurs bourses
     */
    private function getStudentsWithMultipleScholarships()
    {
        return DB::table('payments')
            ->select(
                'student_id',
                DB::raw('COUNT(*) as scholarship_count'),
                DB::raw('SUM(scholarship_amount) as total_scholarship_received'),
                DB::raw('GROUP_CONCAT(id ORDER BY created_at ASC) as payment_ids'),
                DB::raw('GROUP_CONCAT(scholarship_amount ORDER BY created_at ASC) as amounts'),
                DB::raw('GROUP_CONCAT(DATE(created_at) ORDER BY created_at ASC) as dates')
            )
            ->where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->where('school_year_id', 1)
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('scholarship_count')
            ->get();
    }

    /**
     * Afficher le résumé des problèmes
     */
    private function displaySummary($affectedStudents): void
    {
        $table = [];
        $totalExcessAmount = 0;

        foreach ($affectedStudents as $studentData) {
            $student = Student::find($studentData->student_id);
            $excessAmount = $studentData->total_scholarship_received - 20000; // 20k = montant normal
            $totalExcessAmount += $excessAmount;

            $table[] = [
                'ID' => $studentData->student_id,
                'Nom' => $student ? $student->name : 'Inconnu',
                'Numéro' => $student ? $student->student_number : 'N/A',
                'Bourses' => $studentData->scholarship_count,
                'Total reçu' => number_format($studentData->total_scholarship_received, 0, ',', ' ') . ' FCFA',
                'En trop' => number_format($excessAmount, 0, ',', ' ') . ' FCFA'
            ];
        }

        $this->table(
            ['ID', 'Nom', 'Numéro', 'Bourses', 'Total reçu', 'En trop'],
            $table
        );

        $this->newLine();
        $this->error("💰 Montant total des bourses en trop: " . number_format($totalExcessAmount, 0, ',', ' ') . " FCFA");
        $this->newLine();
    }

    /**
     * Montrer ce qui serait changé (mode dry-run)
     */
    private function showWhatWouldBeChanged($affectedStudents): void
    {
        $this->info('📋 Changements qui seraient appliqués:');
        $this->newLine();

        foreach ($affectedStudents as $studentData) {
            $student = Student::find($studentData->student_id);
            $paymentIds = explode(',', $studentData->payment_ids);
            $firstPaymentId = $paymentIds[0];
            $paymentsToFix = array_slice($paymentIds, 1);

            $this->info("👤 {$student->name} (ID: {$studentData->student_id})");
            $this->line("   ✅ Conservation de la bourse du paiement #{$firstPaymentId}");

            foreach ($paymentsToFix as $paymentId) {
                $payment = Payment::find($paymentId);
                $newAmount = $payment->total_amount + $payment->scholarship_amount;
                $this->line("   🔧 Paiement #{$paymentId}: Supprimer bourse de {$payment->scholarship_amount} FCFA");
                $this->line("      Montant: {$payment->total_amount} → {$newAmount} FCFA");
            }
            $this->newLine();
        }
    }

    /**
     * Exécuter la correction
     */
    private function executeCorrection($affectedStudents): void
    {
        $this->info('🔧 Exécution de la correction...');
        $progressBar = $this->output->createProgressBar($affectedStudents->count());

        foreach ($affectedStudents as $studentData) {
            $this->fixStudentMultipleScholarships($studentData);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Corriger les bourses multiples pour un étudiant
     */
    private function fixStudentMultipleScholarships($studentData): void
    {
        $student = Student::find($studentData->student_id);
        $paymentIds = explode(',', $studentData->payment_ids);

        DB::beginTransaction();

        try {
            // Garder la première bourse (chronologiquement)
            $firstPaymentId = $paymentIds[0];
            $paymentsToFix = array_slice($paymentIds, 1);

            foreach ($paymentsToFix as $paymentId) {
                $this->removeScholarshipFromPayment($paymentId);
            }

            // Vérification
            $finalCount = Payment::where('student_id', $studentData->student_id)
                ->where('has_scholarship', 1)
                ->where('scholarship_amount', '>', 0)
                ->count();

            if ($finalCount === 1) {
                DB::commit();
            } else {
                throw new \Exception("Nombre incorrect de bourses après correction: {$finalCount}");
            }

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("Erreur pour {$student->name}: " . $e->getMessage());
        }
    }

    /**
     * Supprimer la bourse d'un paiement
     */
    private function removeScholarshipFromPayment(string $paymentId): void
    {
        $payment = Payment::find($paymentId);
        $newTotalAmount = $payment->total_amount + $payment->scholarship_amount;

        DB::table('payments')
            ->where('id', $paymentId)
            ->update([
                'has_scholarship' => 0,
                'scholarship_amount' => 0.00,
                'total_amount' => $newTotalAmount,
                'updated_at' => now()
            ]);
    }

    /**
     * Générer un rapport
     */
    private function generateReport(): void
    {
        $this->info('📊 === RAPPORT DES BOURSES MULTIPLES ===');
        $this->newLine();

        $affectedStudents = $this->getStudentsWithMultipleScholarships();

        if ($affectedStudents->isEmpty()) {
            $this->info('✅ Aucun problème de bourses multiples détecté');
        } else {
            $this->displaySummary($affectedStudents);
        }

        // Statistiques globales
        $totalScholarships = Payment::where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->count();

        $totalAmount = Payment::where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->sum('scholarship_amount');

        $uniqueStudents = Payment::where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->distinct('student_id')
            ->count('student_id');

        $this->info('📈 Statistiques globales:');
        $this->line("   • Total paiements avec bourses: {$totalScholarships}");
        $this->line("   • Étudiants uniques avec bourses: {$uniqueStudents}");
        $this->line("   • Montant total des bourses: " . number_format($totalAmount, 0, ',', ' ') . " FCFA");
        $this->newLine();
    }
}