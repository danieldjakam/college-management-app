<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;
use App\Models\ClassScholarship;

class FixClassScholarshipAllocations extends Command
{
    protected $signature = 'payments:fix-class-scholarship-allocations
                            {--student-id= : ID spécifique d\'un étudiant à corriger}
                            {--dry-run : Simuler sans appliquer les modifications}
                            {--detailed : Afficher plus de détails}';

    protected $description = 'Corriger les allocations de paiements avec bourses de classe incorrectes';

    public function handle()
    {
        $this->info('🔧 Correction des allocations avec bourses de classe');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $detailed = $this->option('detailed');
        $specificStudentId = $this->option('student-id');

        if ($dryRun) {
            $this->warn('⚠️  MODE SIMULATION - Aucune modification ne sera appliquée');
            $this->newLine();
        }

        // Trouver les étudiants avec mauvaise allocation sur tranches avec bourse
        $this->info('🔍 Recherche des étudiants avec allocations incorrectes...');

        $query = "
            SELECT DISTINCT
                s.id AS student_id,
                s.name AS student_name,
                cs_table.name AS current_class,
                pt.id AS tranche_id,
                pt.name AS tranche_name,
                class_sch.amount AS scholarship_amount,
                SUM(pd.amount_allocated) AS paid_on_tranche,
                (
                    (SELECT cpa.amount FROM class_payment_amounts cpa
                     JOIN school_classes sc ON sc.id = cpa.class_id
                     JOIN class_series css ON css.class_id = sc.id
                     WHERE css.id = s.class_series_id
                     AND cpa.payment_tranche_id = pt.id
                     LIMIT 1) - class_sch.amount
                ) AS required_after_scholarship,
                (
                    SUM(pd.amount_allocated) -
                    (
                        (SELECT cpa.amount FROM class_payment_amounts cpa
                         JOIN school_classes sc ON sc.id = cpa.class_id
                         JOIN class_series css ON css.class_id = sc.id
                         WHERE css.id = s.class_series_id
                         AND cpa.payment_tranche_id = pt.id
                         LIMIT 1) - class_sch.amount
                    )
                ) AS overpayment
            FROM students s
            JOIN class_series cs_table ON s.class_series_id = cs_table.id
            JOIN payments p ON p.student_id = s.id
            JOIN payment_details pd ON pd.payment_id = p.id
            JOIN payment_tranches pt ON pt.id = pd.payment_tranche_id
            JOIN class_scholarships class_sch ON class_sch.payment_tranche_id = pt.id
            JOIN school_classes sc2 ON sc2.id = cs_table.class_id
            WHERE p.school_year_id = (SELECT id FROM school_years WHERE is_current = 1 LIMIT 1)
              AND class_sch.school_class_id = sc2.id
              AND class_sch.is_active = 1
              AND s.has_scholarship_enabled = 1
        ";

        if ($specificStudentId) {
            $query .= " AND s.id = " . intval($specificStudentId);
        }

        $query .= "
            GROUP BY s.id, s.name, cs_table.name, pt.id, pt.name, class_sch.amount, s.class_series_id
            HAVING overpayment > 0
            ORDER BY s.name, pt.order
        ";

        $affectedStudents = DB::select($query);

        if (empty($affectedStudents)) {
            $this->info('✅ Aucun étudiant avec mauvaise allocation détecté.');
            return Command::SUCCESS;
        }

        // Grouper par étudiant
        $studentGroups = [];
        foreach ($affectedStudents as $row) {
            $studentGroups[$row->student_id]['info'] = [
                'id' => $row->student_id,
                'name' => $row->student_name,
                'class' => $row->current_class
            ];
            $studentGroups[$row->student_id]['tranches'][] = $row;
        }

        $this->warn('⚠️  ' . count($studentGroups) . ' étudiant(s) affecté(s) détecté(s)');
        $this->newLine();

        $corrections = [];

        foreach ($studentGroups as $studentId => $studentData) {
            $info = $studentData['info'];
            $tranches = $studentData['tranches'];

            $this->info("📋 Étudiant: {$info['name']} (ID: {$info['id']})");
            $this->line("   Classe: {$info['class']}");
            $this->newLine();

            foreach ($tranches as $tranche) {
                $this->line("   📌 {$tranche->tranche_name}:");
                $this->line("      Bourse: {$tranche->scholarship_amount} FCFA");
                $this->line("      Requis après bourse: {$tranche->required_after_scholarship} FCFA");
                $this->line("      Payé: {$tranche->paid_on_tranche} FCFA");
                $this->error("      ❌ Surpaiement: {$tranche->overpayment} FCFA");
            }

            $this->newLine();

            // Correction pour cet étudiant
            $correction = $this->correctStudentAllocations(
                $info['id'],
                $tranches,
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

    private function correctStudentAllocations($studentId, $tranchesWithIssues, $dryRun, $detailed)
    {
        DB::beginTransaction();

        try {
            $student = Student::findOrFail($studentId);
            $schoolYear = DB::table('school_years')->where('is_current', 1)->first();

            // Calculer le total à redistribuer
            $totalToRedistribute = 0;
            foreach ($tranchesWithIssues as $tranche) {
                $totalToRedistribute += $tranche->overpayment;
            }

            $this->line("   🔧 Total à redistribuer: {$totalToRedistribute} FCFA");

            // 1. Réduire les tranches sur-payées
            foreach ($tranchesWithIssues as $tranche) {
                $details = PaymentDetail::whereHas('payment', function($q) use ($studentId, $schoolYear) {
                        $q->where('student_id', $studentId)
                          ->where('school_year_id', $schoolYear->id);
                    })
                    ->where('payment_tranche_id', $tranche->tranche_id)
                    ->orderBy('id', 'desc')
                    ->get();

                $toRemove = $tranche->overpayment;

                foreach ($details as $detail) {
                    if ($toRemove <= 0) break;

                    $removeFromThis = min($detail->amount_allocated, $toRemove);
                    $newAmount = $detail->amount_allocated - $removeFromThis;

                    if ($detailed) {
                        $this->line("      {$tranche->tranche_name} (payment #{$detail->payment_id}): {$detail->amount_allocated} → {$newAmount} FCFA");
                    }

                    if (!$dryRun) {
                        if ($newAmount > 0) {
                            $detail->update([
                                'amount_allocated' => $newAmount,
                                'new_total_amount' => $newAmount
                            ]);
                        } else {
                            $detail->delete();
                        }
                    }

                    $toRemove -= $removeFromThis;
                }
            }

            // 2. Trouver les tranches non payées pour redistribuer
            $allTranches = PaymentTranche::where('is_active', true)
                ->orderBy('order')
                ->get();

            $remainingToAllocate = $totalToRedistribute;
            $lastPayment = Payment::where('student_id', $studentId)
                ->where('school_year_id', $schoolYear->id)
                ->orderBy('id', 'desc')
                ->first();

            foreach ($allTranches as $tranche) {
                if ($remainingToAllocate <= 0) break;

                // Montant requis pour cette tranche (avec bourse si applicable)
                $baseAmount = DB::table('class_payment_amounts')
                    ->join('school_classes', 'school_classes.id', '=', 'class_payment_amounts.class_id')
                    ->join('class_series', 'class_series.class_id', '=', 'school_classes.id')
                    ->where('class_series.id', $student->class_series_id)
                    ->where('class_payment_amounts.payment_tranche_id', $tranche->id)
                    ->value('class_payment_amounts.amount');

                $scholarship = ClassScholarship::where('payment_tranche_id', $tranche->id)
                    ->whereHas('schoolClass', function($q) use ($student) {
                        $q->whereHas('series', function($q2) use ($student) {
                            $q2->where('id', $student->class_series_id);
                        });
                    })
                    ->where('is_active', true)
                    ->first();

                $requiredAmount = $baseAmount - ($scholarship ? $scholarship->amount : 0);

                // Montant déjà payé
                $alreadyPaid = PaymentDetail::whereHas('payment', function($q) use ($studentId, $schoolYear) {
                        $q->where('student_id', $studentId)
                          ->where('school_year_id', $schoolYear->id);
                    })
                    ->where('payment_tranche_id', $tranche->id)
                    ->sum('amount_allocated');

                $remaining = $requiredAmount - $alreadyPaid;

                if ($remaining > 0) {
                    $amountToAllocate = min($remaining, $remainingToAllocate);

                    if ($detailed) {
                        $this->line("      Ajouter à {$tranche->name}: +{$amountToAllocate} FCFA");
                    }

                    if (!$dryRun) {
                        // Vérifier si un payment_detail existe déjà pour ce payment et cette tranche
                        $existingDetail = PaymentDetail::where('payment_id', $lastPayment->id)
                            ->where('payment_tranche_id', $tranche->id)
                            ->first();

                        if ($existingDetail) {
                            // Mettre à jour le payment_detail existant
                            $existingDetail->update([
                                'amount_allocated' => $existingDetail->amount_allocated + $amountToAllocate,
                                'new_total_amount' => $alreadyPaid + $amountToAllocate,
                                'required_amount_at_time' => $requiredAmount,
                                'is_fully_paid' => (($alreadyPaid + $amountToAllocate) >= $requiredAmount)
                            ]);
                        } else {
                            // Créer un nouveau payment_detail
                            PaymentDetail::create([
                                'payment_id' => $lastPayment->id,
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
                'amount_fixed' => $totalToRedistribute - $remainingToAllocate
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
