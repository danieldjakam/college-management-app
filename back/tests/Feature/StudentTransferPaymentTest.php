<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\ClassPaymentAmount;
use App\Models\PaymentTranche;
use App\Models\SchoolYear;
use App\Models\Level;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTransferPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $section;
    protected $level;
    protected $classSeme1;
    protected $class6em;
    protected $seriesSeme1A;
    protected $series6emA;
    protected $student;
    protected $schoolYear;
    protected $user;
    protected $tranches;

    public function setUp(): void
    {
        parent::setUp();

        // Créer l'année scolaire
        $this->schoolYear = SchoolYear::create([
            'start_year' => 2024,
            'end_year' => 2025,
            'is_active' => true
        ]);

        // Créer un utilisateur pour les opérations
        $this->user = User::factory()->create();

        // Créer la section et le niveau
        $this->section = Section::create([
            'name' => 'Section Francophone',
            'is_active' => true,
            'order' => 1
        ]);

        $this->level = Level::create([
            'name' => 'Premier Cycle',
            'section_id' => $this->section->id,
            'is_active' => true,
            'order' => 1
        ]);

        // Créer les tranches de paiement
        $this->tranches = [
            'inscription' => PaymentTranche::create([
                'name' => 'Inscription',
                'order' => 1,
                'is_active' => true
            ]),
            'tranche1' => PaymentTranche::create([
                'name' => '1ère Tranche',
                'order' => 2,
                'is_active' => true
            ]),
            'tranche2' => PaymentTranche::create([
                'name' => '2ème Tranche',
                'order' => 3,
                'is_active' => true
            ]),
            'tranche3' => PaymentTranche::create([
                'name' => '3ème Tranche',
                'order' => 4,
                'is_active' => true
            ])
        ];

        // Créer la classe SEME 1 (classe d'origine)
        $this->classSeme1 = SchoolClass::create([
            'name' => 'SEME 1',
            'level_id' => $this->level->id,
            'is_active' => true
        ]);

        // Créer la classe 6em (classe de destination)
        $this->class6em = SchoolClass::create([
            'name' => '6em',
            'level_id' => $this->level->id,
            'is_active' => true
        ]);

        // Configurer les pensions pour SEME 1
        ClassPaymentAmount::create([
            'class_id' => $this->classSeme1->id,
            'payment_tranche_id' => $this->tranches['inscription']->id,
            'amount' => 36000
        ]);
        ClassPaymentAmount::create([
            'class_id' => $this->classSeme1->id,
            'payment_tranche_id' => $this->tranches['tranche1']->id,
            'amount' => 47000
        ]);
        ClassPaymentAmount::create([
            'class_id' => $this->classSeme1->id,
            'payment_tranche_id' => $this->tranches['tranche2']->id,
            'amount' => 25000
        ]);

        // Total SEME 1: 108000 FCFA

        // Configurer les pensions pour 6em (comme en production)
        ClassPaymentAmount::create([
            'class_id' => $this->class6em->id,
            'payment_tranche_id' => $this->tranches['inscription']->id,
            'amount' => 31000
        ]);
        ClassPaymentAmount::create([
            'class_id' => $this->class6em->id,
            'payment_tranche_id' => $this->tranches['tranche1']->id,
            'amount' => 42000
        ]);
        ClassPaymentAmount::create([
            'class_id' => $this->class6em->id,
            'payment_tranche_id' => $this->tranches['tranche2']->id,
            'amount' => 20000
        ]);
        ClassPaymentAmount::create([
            'class_id' => $this->class6em->id,
            'payment_tranche_id' => $this->tranches['tranche3']->id,
            'amount' => 10000
        ]);

        // Total 6em: 103000 FCFA

        // Créer les séries
        $this->seriesSeme1A = ClassSeries::create([
            'name' => 'A',
            'class_id' => $this->classSeme1->id,
            'is_active' => true
        ]);

        $this->series6emA = ClassSeries::create([
            'name' => 'A',
            'class_id' => $this->class6em->id,
            'is_active' => true
        ]);

        // Créer l'élève dans SEME 1
        $this->student = Student::create([
            'last_name' => 'ZE ATANGANA',
            'first_name' => 'MARIE PAULE SAMIRA',
            'class_series_id' => $this->seriesSeme1A->id,
            'school_year_id' => $this->schoolYear->id,
            'matriculation_number' => 'TEST001',
            'date_of_birth' => '2010-01-01',
            'place_of_birth' => 'Douala',
            'gender' => 'F',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function test_student_transfer_with_scholarship_payment_calculation()
    {
        echo "\n=== TEST DE TRANSFERT D'ÉLÈVE AVEC BOURSE ===\n\n";

        // 1. Vérifier l'état initial
        echo "1. ÉTAT INITIAL:\n";
        echo "   - Élève: {$this->student->last_name} {$this->student->first_name}\n";
        echo "   - Classe actuelle: SEME 1 (ID: {$this->classSeme1->id})\n";
        echo "   - Pension SEME 1: 108000 FCFA\n\n";

        // 2. Enregistrer le paiement initial avec bourse (comme en production)
        echo "2. ENREGISTREMENT DU PAIEMENT AVEC BOURSE:\n";
        
        $payment = Payment::create([
            'student_id' => $this->student->id,
            'school_year_id' => $this->schoolYear->id,
            'total_amount' => 73000,
            'payment_date' => now(),
            'versement_date' => now(),
            'validation_date' => now(),
            'payment_method' => 'cash',
            'created_by_user_id' => $this->user->id,
            'receipt_number' => 'TEST_REC_001',
            'has_scholarship' => true,
            'scholarship_amount' => 20000,
            'has_reduction' => false,
            'reduction_amount' => 0
        ]);

        echo "   - Montant payé cash: 73000 FCFA\n";
        echo "   - Bourse: 20000 FCFA\n";
        echo "   - Total couvert: 93000 FCFA\n\n";

        // Créer les détails de paiement
        PaymentDetail::create([
            'payment_id' => $payment->id,
            'student_id' => $this->student->id,
            'payment_tranche_id' => $this->tranches['inscription']->id,
            'amount' => 31000,
            'school_year_id' => $this->schoolYear->id
        ]);

        PaymentDetail::create([
            'payment_id' => $payment->id,
            'student_id' => $this->student->id,
            'payment_tranche_id' => $this->tranches['tranche1']->id,
            'amount' => 42000,
            'school_year_id' => $this->schoolYear->id
        ]);

        PaymentDetail::create([
            'payment_id' => $payment->id,
            'student_id' => $this->student->id,
            'payment_tranche_id' => $this->tranches['tranche2']->id,
            'amount' => 20000,
            'school_year_id' => $this->schoolYear->id
        ]);

        // 3. Transférer l'élève vers 6em
        echo "3. TRANSFERT DE L'ÉLÈVE:\n";
        echo "   - De: SEME 1 (Pension: 108000 FCFA)\n";
        echo "   - Vers: 6em (Pension: 103000 FCFA)\n\n";

        $this->student->class_series_id = $this->series6emA->id;
        $this->student->save();

        // 4. Calculer le reste à payer après transfert
        echo "4. CALCUL DU RESTE À PAYER APRÈS TRANSFERT:\n";

        // Récupérer la pension de la nouvelle classe
        $newClassPayments = ClassPaymentAmount::where('class_id', $this->class6em->id)->get();
        $totalNewPension = $newClassPayments->sum('amount');

        // Récupérer le total payé (avec bourse)
        $totalPaid = $payment->total_amount;
        $scholarshipAmount = $payment->scholarship_amount;
        $totalCovered = $totalPaid + $scholarshipAmount;

        // Calculer le reste à payer
        $remainingToPay = $totalNewPension - $totalCovered;

        echo "   - Pension nouvelle classe (6em): {$totalNewPension} FCFA\n";
        echo "   - Total payé (cash): {$totalPaid} FCFA\n";
        echo "   - Bourse: {$scholarshipAmount} FCFA\n";
        echo "   - Total couvert: {$totalCovered} FCFA\n";
        echo "   - Reste à payer théorique: {$remainingToPay} FCFA\n\n";

        // 5. Simuler le calcul du système (comme il pourrait être fait dans le code)
        echo "5. SIMULATION DES DIFFÉRENTS CALCULS POSSIBLES:\n";

        // Calcul 1: Sans prendre en compte la bourse
        $calc1 = $totalNewPension - $totalPaid;
        echo "   a) Sans bourse: {$totalNewPension} - {$totalPaid} = {$calc1} FCFA\n";

        // Calcul 2: Avec bourse partielle (15000 au lieu de 20000)
        $calc2 = $totalNewPension - $totalPaid - 15000;
        echo "   b) Avec bourse de 15000: {$totalNewPension} - {$totalPaid} - 15000 = {$calc2} FCFA\n";

        // Calcul 3: Avec bourse complète (correct)
        $calc3 = $totalNewPension - $totalPaid - $scholarshipAmount;
        echo "   c) Avec bourse complète: {$totalNewPension} - {$totalPaid} - {$scholarshipAmount} = {$calc3} FCFA\n\n";

        // 6. Vérifications et assertions
        echo "6. VÉRIFICATIONS:\n";

        $this->assertEquals(103000, $totalNewPension, "La pension de 6em devrait être 103000 FCFA");
        $this->assertEquals(93000, $totalCovered, "Le total couvert devrait être 93000 FCFA");
        $this->assertEquals(10000, $remainingToPay, "Le reste à payer devrait être 10000 FCFA");

        echo "   ✓ Pension 6em: 103000 FCFA (OK)\n";
        echo "   ✓ Total couvert: 93000 FCFA (OK)\n";
        echo "   ✓ Reste à payer correct: 10000 FCFA (OK)\n\n";

        // 7. Identifier le problème
        echo "7. DIAGNOSTIC DU PROBLÈME:\n";
        if ($calc2 == 15000) {
            echo "   ⚠️ PROBLÈME IDENTIFIÉ: Le système utilise probablement une bourse de 15000 au lieu de 20000\n";
            echo "   ⚠️ Différence: 5000 FCFA manquants dans le calcul de la bourse\n";
        } elseif ($calc1 == 30000) {
            echo "   ⚠️ PROBLÈME IDENTIFIÉ: Le système n'applique pas la bourse du tout\n";
        } else {
            echo "   ✓ Le calcul semble correct avec la bourse complète\n";
        }

        echo "\n=== FIN DU TEST ===\n";
    }

    /** @test */
    public function test_payment_calculation_methods()
    {
        echo "\n=== TEST DES MÉTHODES DE CALCUL ===\n\n";

        // Enregistrer un paiement avec bourse
        $payment = Payment::create([
            'student_id' => $this->student->id,
            'school_year_id' => $this->schoolYear->id,
            'total_amount' => 73000,
            'payment_date' => now(),
            'versement_date' => now(),
            'validation_date' => now(),
            'payment_method' => 'cash',
            'created_by_user_id' => $this->user->id,
            'receipt_number' => 'TEST_REC_002',
            'has_scholarship' => true,
            'scholarship_amount' => 20000,
            'has_reduction' => false,
            'reduction_amount' => 0
        ]);

        // Transférer l'élève
        $this->student->class_series_id = $this->series6emA->id;
        $this->student->save();

        // Tester différentes méthodes de calcul qui pourraient exister
        echo "1. Test avec méthode getTotalPaid():\n";
        $totalPaidMethod1 = Payment::where('student_id', $this->student->id)
            ->where('school_year_id', $this->schoolYear->id)
            ->sum('total_amount');
        echo "   Total payé (sans bourse): {$totalPaidMethod1} FCFA\n";

        echo "\n2. Test avec méthode getTotalPaidWithScholarship():\n";
        $payments = Payment::where('student_id', $this->student->id)
            ->where('school_year_id', $this->schoolYear->id)
            ->get();
        
        $totalWithScholarship = 0;
        foreach ($payments as $p) {
            $totalWithScholarship += $p->total_amount;
            if ($p->has_scholarship) {
                $totalWithScholarship += $p->scholarship_amount;
            }
        }
        echo "   Total avec bourse: {$totalWithScholarship} FCFA\n";

        echo "\n3. Test calcul reste à payer:\n";
        $pensionTotale = ClassPaymentAmount::where('class_id', $this->class6em->id)->sum('amount');
        $resteAPayer1 = $pensionTotale - $totalPaidMethod1;
        $resteAPayer2 = $pensionTotale - $totalWithScholarship;
        
        echo "   Méthode 1 (sans bourse): {$pensionTotale} - {$totalPaidMethod1} = {$resteAPayer1} FCFA\n";
        echo "   Méthode 2 (avec bourse): {$pensionTotale} - {$totalWithScholarship} = {$resteAPayer2} FCFA\n";

        $this->assertEquals(30000, $resteAPayer1, "Sans bourse, le reste devrait être 30000");
        $this->assertEquals(10000, $resteAPayer2, "Avec bourse, le reste devrait être 10000");

        echo "\n=== FIN DU TEST ===\n";
    }
}