<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentTranche;
use App\Models\StudentScholarship;
use App\Models\StudentDiscount;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Services\ManualDiscountService;

class ClassSchoolFeesSheetController extends Controller
{
    /**
     * Récupérer les données de la fiche de scolarité par classe
     */
    public function getClassFeesData($classId)
    {
        try {
            // Récupérer la classe série avec ses relations
            $classSeries = \App\Models\ClassSeries::with(['schoolClass.level.section'])->findOrFail($classId);

            // Récupérer tous les élèves de cette classe série
            $students = Student::where('class_series_id', $classId)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            // Récupérer toutes les tranches de paiement
            $tranches = PaymentTranche::orderBy('order')->get();

            // Calculer le montant total à payer pour cette classe (une seule fois)
            $montantTotalClasse = DB::table('class_payment_amounts')
                ->where('class_id', $classSeries->class_id)
                ->sum('amount') ?? 0;

            // Récupérer l'année scolaire courante
            $schoolYear = \App\Models\SchoolYear::where('is_current', true)->first()
                ?? \App\Models\SchoolYear::where('is_active', true)->first();

            // Service pour les bourses de classe
            $discountCalculator = new \App\Services\DiscountCalculatorService();
            $manualDiscountService = new ManualDiscountService();

            // Préparer les données des élèves avec leurs paiements
            $studentsData = [];
            $totals = [
                'tranches' => [],
                'rabais' => 0,
                'bourse' => 0,
                'total_paye' => 0,
                'reste_a_payer' => 0,
            ];

            // Initialiser les totaux par tranche
            foreach ($tranches as $tranche) {
                $totals['tranches'][$tranche->id] = 0;
            }

            foreach ($students as $index => $student) {
                // Récupérer les paiements de l'élève (status 'pending' = payé et en attente de validation)
                $paymentDetails = DB::table('payment_details')
                    ->join('payments', 'payments.id', '=', 'payment_details.payment_id')
                    ->where('payments.student_id', $student->id)
                    ->whereIn('payments.status', ['pending', 'validated', 'completed'])
                    ->select('payment_details.*')
                    ->get();

                // Calculer les montants par tranche (pas d'inscription séparée)
                $tranchesData = [];
                foreach ($tranches as $tranche) {
                    $trancheAmount = $paymentDetails->where('payment_tranche_id', $tranche->id)->sum('amount_allocated');
                    $tranchesData[$tranche->id] = (float) $trancheAmount;
                    $totals['tranches'][$tranche->id] += (float) $trancheAmount;
                }

                // Récupérer les rabais et bourses depuis les paiements
                $payments = Payment::where('student_id', $student->id)
                    ->whereIn('status', ['pending', 'validated', 'completed'])
                    ->get();

                $rabais = $payments->sum('reduction_amount') ?? 0;
                $bourse = $payments->sum(function ($payment) {
                    return ($payment->has_scholarship && $payment->scholarship_amount > 0) ? $payment->scholarship_amount : 0;
                });

                // Si pas de bourse dans les paiements, vérifier la bourse de classe configurée
                if ($bourse == 0) {
                    $classScholarship = $discountCalculator->getClassScholarship($student);
                    if ($classScholarship && $discountCalculator->isEligibleForScholarship(now())) {
                        $bourse = $classScholarship->amount;
                    }
                }

                // Ajouter la réduction manuelle (rabais fixe)
                if ($schoolYear) {
                    $manualDiscount = $manualDiscountService->getManualDiscountAmount($student, $schoolYear);
                    if ($manualDiscount > 0) {
                        $rabais += $manualDiscount;
                    }
                }

                // Calculer le total payé (somme de toutes les tranches)
                $totalPaye = array_sum($tranchesData);

                // Calculer le reste à payer (utiliser le montant de la classe)
                $resteAPayer = max(0, $montantTotalClasse - $totalPaye - $rabais - $bourse);

                $studentsData[] = [
                    'numero' => $index + 1,
                    'matricule' => $student->student_number ?? $student->id,
                    'nom' => $student->last_name ?? $student->name,
                    'prenom' => $student->first_name ?? '',
                    'tranches' => $tranchesData,
                    'rabais' => $rabais,
                    'bourse' => $bourse,
                    'total_paye' => $totalPaye,
                    'reste_a_payer' => $resteAPayer,
                ];

                // Mettre à jour les totaux
                $totals['rabais'] += (float) $rabais;
                $totals['bourse'] += (float) $bourse;
                $totals['total_paye'] += (float) $totalPaye;
                $totals['reste_a_payer'] += (float) $resteAPayer;
            }

            $level = $classSeries->schoolClass->level ?? null;

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => [
                        'id' => $classSeries->id,
                        'name' => $classSeries->getFullNameAttribute(),
                        'code' => $classSeries->code,
                        'school_class' => $classSeries->schoolClass,
                        'level' => $level,
                        'section' => $level ? $level->section : null,
                        'school_fees' => (float) $montantTotalClasse,
                    ],
                    'students' => $studentsData,
                    'tranches' => $tranches,
                    'totals' => $totals,
                    'nombre_eleves' => count($studentsData),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer et télécharger le PDF de la fiche de scolarité
     */
    public function downloadClassFeesPDF($classId)
    {
        try {
            // Récupérer la classe série pour obtenir le bon nom de fichier
            $classSeries = \App\Models\ClassSeries::with(['schoolClass.level.section'])->findOrFail($classId);

            // Récupérer les données
            $response = $this->getClassFeesData($classId);
            $responseData = json_decode($response->content(), true);

            if (!$responseData['success']) {
                return response()->json(['error' => 'Erreur lors de la récupération des données'], 500);
            }

            $data = $responseData['data'];

            // Récupérer les paramètres de l'école
            $schoolSettings = DB::table('school_settings')->first();

            // Préparer les données pour le PDF
            $pdfData = [
                'school' => [
                    'name' => $schoolSettings->school_name ?? 'Établissement Scolaire',
                    'logo' => public_path('storage/' . ($schoolSettings->school_logo ?? 'logo.png')),
                    'address' => $schoolSettings->school_address ?? '',
                    'phone' => $schoolSettings->school_phone ?? '',
                    'email' => $schoolSettings->school_email ?? '',
                ],
                'class' => $data['class'],
                'students' => $data['students'],
                'tranches' => $data['tranches'],
                'totals' => $data['totals'],
                'nombre_eleves' => $data['nombre_eleves'],
                'date_generation' => now()->format('d/m/Y'),
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('pdf.class-fees-sheet', $pdfData);
            $pdf->setPaper('A4', 'landscape');

            // Nom du fichier - utiliser le nom de la série plutôt que le nom complet pour éviter les doublons
            // Par exemple, au lieu de "FORM FIVE (ARTS) FORM FIVE (ARTS) A", on prend juste "FORM FIVE (ARTS) A"
            $className = $classSeries->name ?? $classSeries->code ?? 'classe_' . $classSeries->id;

            // Nettoyer les espaces et caractères spéciaux pour les noms de fichiers
            $className = str_replace(' ', '_', $className);
            $className = preg_replace('/[^A-Za-z0-9_\-]/', '_', $className);

            $fileName = 'Fiche_Scolarite_' . $className . '_' . now()->format('Y-m-d') . '.pdf';

            // Retourner le PDF
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer toutes les classes de base (SchoolClass)
     */
    public function getSchoolClasses(Request $request)
    {
        try {
            $query = \App\Models\SchoolClass::with(['level.section'])
                ->where('is_active', true);

            $schoolClasses = $query->orderBy('name')->get();

            $classes = $schoolClasses->map(function($sc) {
                return [
                    'id' => $sc->id,
                    'name' => $sc->name, // Ex: "6ème", "5ème"
                    'code' => $sc->code,
                    'level' => $sc->level,
                    'section' => $sc->level ? $sc->level->section : null,
                ];
            });

            return response()->json([
                'success' => true,
                'classes' => $classes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les séries (ClassSeries) d'une classe
     */
    public function getClassSeries(Request $request)
    {
        try {
            $query = \App\Models\ClassSeries::with(['schoolClass'])
                ->where('is_active', true);

            // Filtrer par classe (SchoolClass) si fourni
            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            $classSeries = $query->orderBy('name')->get();

            // Reformater les données pour le frontend
            $series = $classSeries->map(function($cs) {
                return [
                    'id' => $cs->id,
                    'name' => $cs->getFullNameAttribute(), // Ex: "6ème A"
                    'code' => $cs->code,
                    'school_class' => $cs->schoolClass,
                ];
            });

            return response()->json([
                'success' => true,
                'series' => $series
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séries: ' . $e->getMessage()
            ], 500);
        }
    }
}
