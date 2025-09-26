<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Student;

class FixMultipleScholarshipsSeeder extends Seeder
{
    /**
     * Script de correction pour les bourses multiples appliquées incorrectement
     *
     * PROBLÈME: Certains étudiants ont reçu plusieurs fois la même bourse de 20,000 FCFA
     * SOLUTION: Garder seulement la première bourse, supprimer les suivantes
     */
    public function run()
    {
        Log::info('🔧 Début de la correction des bourses multiples');

        // Étape 1: Identifier les étudiants avec bourses multiples
        $affectedStudents = $this->getStudentsWithMultipleScholarships();

        if (empty($affectedStudents)) {
            Log::info('✅ Aucun étudiant avec bourses multiples trouvé');
            return;
        }

        Log::info('📊 Étudiants affectés: ' . count($affectedStudents));

        // Étape 2: Traiter chaque étudiant
        foreach ($affectedStudents as $studentData) {
            $this->fixStudentMultipleScholarships($studentData);
        }

        Log::info('✅ Correction des bourses multiples terminée');
    }

    /**
     * Identifier les étudiants ayant reçu plusieurs bourses
     */
    private function getStudentsWithMultipleScholarships(): array
    {
        $query = "
            SELECT
                student_id,
                COUNT(*) as scholarship_count,
                SUM(scholarship_amount) as total_scholarship_received,
                GROUP_CONCAT(id ORDER BY created_at ASC) as payment_ids,
                GROUP_CONCAT(scholarship_amount ORDER BY created_at ASC) as amounts,
                GROUP_CONCAT(DATE(created_at) ORDER BY created_at ASC) as dates
            FROM payments
            WHERE has_scholarship = 1
            AND scholarship_amount > 0
            AND school_year_id = 1
            GROUP BY student_id
            HAVING COUNT(*) > 1
            ORDER BY scholarship_count DESC
        ";

        $results = DB::select($query);

        $affectedStudents = [];
        foreach ($results as $row) {
            $student = Student::find($row->student_id);
            if ($student) {
                $affectedStudents[] = [
                    'student_id' => $row->student_id,
                    'student_name' => $student->name,
                    'student_number' => $student->student_number,
                    'scholarship_count' => $row->scholarship_count,
                    'total_received' => $row->total_scholarship_received,
                    'payment_ids' => explode(',', $row->payment_ids),
                    'amounts' => explode(',', $row->amounts),
                    'dates' => explode(',', $row->dates)
                ];
            }
        }

        return $affectedStudents;
    }

    /**
     * Corriger les bourses multiples pour un étudiant
     */
    private function fixStudentMultipleScholarships(array $studentData): void
    {
        $studentId = $studentData['student_id'];
        $studentName = $studentData['student_name'];
        $paymentIds = $studentData['payment_ids'];

        Log::info("🔧 Correction pour {$studentName} (ID: {$studentId})");
        Log::info("   Bourses multiples détectées: " . $studentData['scholarship_count']);
        Log::info("   Montant total incorrect: " . number_format($studentData['total_received'], 0, ',', ' ') . " FCFA");

        DB::beginTransaction();

        try {
            // Garder la première bourse (chronologiquement)
            $firstPaymentId = $paymentIds[0];
            $paymentsToFix = array_slice($paymentIds, 1); // Tous sauf le premier

            Log::info("   ✅ Conservation de la bourse du paiement ID: {$firstPaymentId}");

            foreach ($paymentsToFix as $paymentId) {
                $this->removeScholarshipFromPayment($paymentId, $studentName);
            }

            // Vérification finale
            $finalScholarshipCount = Payment::where('student_id', $studentId)
                ->where('has_scholarship', 1)
                ->where('scholarship_amount', '>', 0)
                ->count();

            if ($finalScholarshipCount === 1) {
                Log::info("   ✅ Correction réussie pour {$studentName}");
                DB::commit();
            } else {
                Log::error("   ❌ Erreur: {$finalScholarshipCount} bourses restantes au lieu de 1");
                DB::rollback();
            }

        } catch (\Exception $e) {
            Log::error("   ❌ Erreur lors de la correction pour {$studentName}: " . $e->getMessage());
            DB::rollback();
        }
    }

    /**
     * Supprimer la bourse d'un paiement spécifique
     */
    private function removeScholarshipFromPayment(string $paymentId, string $studentName): void
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            Log::error("   ❌ Paiement {$paymentId} non trouvé");
            return;
        }

        $oldScholarshipAmount = $payment->scholarship_amount;
        $oldTotalAmount = $payment->total_amount;

        // Calculer le nouveau montant sans la bourse
        $newTotalAmount = $oldTotalAmount + $oldScholarshipAmount;

        // Mettre à jour le paiement
        $updated = DB::table('payments')
            ->where('id', $paymentId)
            ->update([
                'has_scholarship' => 0,
                'scholarship_amount' => 0.00,
                'total_amount' => $newTotalAmount,
                'updated_at' => now()
            ]);

        if ($updated) {
            Log::info("   🔧 Paiement {$paymentId}: Bourse supprimée ({$oldScholarshipAmount} FCFA)");
            Log::info("      Montant: {$oldTotalAmount} → {$newTotalAmount} FCFA");
        } else {
            Log::error("   ❌ Échec de la mise à jour du paiement {$paymentId}");
        }
    }

    /**
     * Afficher un rapport de correction
     */
    public function generateReport(): void
    {
        Log::info('📊 === RAPPORT DE CORRECTION DES BOURSES MULTIPLES ===');

        // Vérifier s'il reste des bourses multiples
        $remainingIssues = $this->getStudentsWithMultipleScholarships();

        if (empty($remainingIssues)) {
            Log::info('✅ Aucun problème de bourses multiples détecté');
        } else {
            Log::warning('⚠️  Il reste ' . count($remainingIssues) . ' étudiants avec des bourses multiples');
            foreach ($remainingIssues as $issue) {
                Log::warning("   - {$issue['student_name']}: {$issue['scholarship_count']} bourses");
            }
        }

        // Statistiques globales
        $totalScholarships = Payment::where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->count();

        $totalScholarshipAmount = Payment::where('has_scholarship', 1)
            ->where('scholarship_amount', '>', 0)
            ->sum('scholarship_amount');

        Log::info("📈 Statistiques actuelles:");
        Log::info("   Total paiements avec bourses: {$totalScholarships}");
        Log::info("   Montant total des bourses: " . number_format($totalScholarshipAmount, 0, ',', ' ') . " FCFA");

        Log::info('📊 === FIN DU RAPPORT ===');
    }
}