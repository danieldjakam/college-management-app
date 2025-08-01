<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\PaymentTranche;
use App\Models\ClassPaymentAmount;
use App\Models\SchoolSetting;
use App\Services\DiscountCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Obtenir l'année scolaire de travail de l'utilisateur connecté
     */
    private function getUserWorkingYear()
    {
        $user = Auth::user();
        
        // Si l'utilisateur a une année de travail définie, l'utiliser
        if ($user && $user->working_school_year_id) {
            $workingYear = SchoolYear::find($user->working_school_year_id);
            if ($workingYear && $workingYear->is_active) {
                return $workingYear;
            }
        }
        
        // Sinon, utiliser l'année courante par défaut
        $currentYear = SchoolYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            // Si aucune année courante, prendre la première année active
            $currentYear = SchoolYear::where('is_active', true)->first();
        }
        
        return $currentYear;
    }

    /**
     * Obtenir les informations de paiement d'un étudiant
     */
    public function getStudentPaymentInfo($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            // Récupérer l'étudiant avec ses relations
            $student = Student::with([
                'classSeries.schoolClass',
                'schoolYear'
            ])->find($studentId);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ], 404);
            }

            // Récupérer les tranches de paiement configurées pour la classe
            $paymentTranches = PaymentTranche::active()
                ->ordered()
                ->with(['classPaymentAmounts' => function ($query) use ($student) {
                    $query->where('class_id', $student->classSeries->schoolClass->id);
                }])
                ->get();

            // Récupérer les paiements déjà effectués pour cet étudiant cette année
            $existingPayments = Payment::forStudent($studentId)
                ->forYear($workingYear->id)
                ->with(['paymentDetails.paymentTranche'])
                ->orderBy('payment_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculer le statut de paiement pour chaque tranche
            $paymentStatus = [];
            $totalPaid = 0;
            $totalRequired = 0;

            foreach ($paymentTranches as $tranche) {
                // Utiliser la nouvelle méthode simplifiée qui gère les montants par défaut et les bourses
                $requiredAmount = $tranche->getAmountForStudent($student, true, false, true); // Le paramètre isNewStudent n'a plus d'effet
                if ($requiredAmount <= 0) continue;
                
                // La RAME est optionnelle et ne compte pas dans le total obligatoire à payer
                $isRame = (strtoupper($tranche->name) === 'RAME' || stripos($tranche->name, 'RAME') !== false);
                if (!$isRame) {
                    $totalRequired += $requiredAmount;
                }

                // Calculer le montant déjà payé pour cette tranche
                // Comme les paiements sont triés par date, on prend le dernier new_total_amount
                // qui représente le montant cumulé le plus récent pour cette tranche
                $paidAmount = 0;
                foreach ($existingPayments as $payment) {
                    $detail = $payment->paymentDetails->where('payment_tranche_id', $tranche->id)->first();
                    if ($detail) {
                        // Chaque new_total_amount est cumulatif, donc on prend toujours le plus récent
                        $paidAmount = $detail->new_total_amount;
                        // On ne fait pas break car on veut le plus récent (dernier dans l'ordre chronologique)
                    }
                }

                // Seules les tranches non-RAME comptent dans le total payé obligatoire
                if (!$isRame) {
                    $totalPaid += $paidAmount;
                }

                $paymentStatus[] = [
                    'tranche' => $tranche,
                    'required_amount' => $requiredAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => max(0, $requiredAmount - $paidAmount),
                    'is_fully_paid' => $paidAmount >= $requiredAmount,
                    'is_optional' => $isRame // Marquer la RAME comme optionnelle
                ];
            }

            // Obtenir les informations de réduction
            $discountCalculator = new DiscountCalculatorService();
            $discountInfo = $discountCalculator->getDiscountInfo($student);
            
            // Si l'étudiant est éligible à une réduction, recalculer les montants avec réduction
            if ($discountInfo['eligible_for_reduction']) {
                $paymentStatusWithReduction = [];
                $totalPaidWithReduction = 0;
                $totalRequiredWithReduction = 0;
                
                foreach ($paymentTranches as $tranche) {
                    // Montant requis avec réduction et bourses appliquées
                    $requiredAmountWithReduction = $tranche->getAmountForStudent($student, true, true, true);
                    if ($requiredAmountWithReduction <= 0) continue;
                    
                    // La RAME reste optionnelle même avec réduction
                    $isRame = (strtoupper($tranche->name) === 'RAME' || stripos($tranche->name, 'RAME') !== false);
                    if (!$isRame) {
                        $totalRequiredWithReduction += $requiredAmountWithReduction;
                    }

                    // Le montant payé reste le même (calculé précédemment)
                    $paidAmount = 0;
                    foreach ($existingPayments as $payment) {
                        $detail = $payment->paymentDetails->where('payment_tranche_id', $tranche->id)->first();
                        if ($detail) {
                            $paidAmount = $detail->new_total_amount;
                        }
                    }

                    // Seules les tranches non-RAME comptent dans le total payé obligatoire
                    if (!$isRame) {
                        $totalPaidWithReduction += $paidAmount;
                    }

                    $paymentStatusWithReduction[] = [
                        'tranche' => $tranche,
                        'required_amount' => $requiredAmountWithReduction,
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => max(0, $requiredAmountWithReduction - $paidAmount),
                        'is_fully_paid' => $paidAmount >= $requiredAmountWithReduction,
                        'is_optional' => $isRame // Marquer la RAME comme optionnelle
                    ];
                }
                
                // Utiliser les montants avec réduction si l'étudiant y est éligible
                $paymentStatus = $paymentStatusWithReduction;
                $totalRequired = $totalRequiredWithReduction;
                $totalPaid = $totalPaidWithReduction;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'school_year' => $workingYear,
                    'payment_status' => $paymentStatus,
                    'total_required' => $totalRequired,
                    'total_paid' => $totalPaid,
                    'total_remaining' => max(0, $totalRequired - $totalPaid),
                    'existing_payments' => $existingPayments,
                    'discount_info' => $discountInfo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getStudentPaymentInfo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau paiement
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,transfer,check,rame_physical',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'payment_date' => 'nullable|date',
            'is_rame_physical' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            DB::beginTransaction();

            // Récupérer les informations de l'étudiant
            $student = Student::with('classSeries.schoolClass')->find($request->student_id);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ], 404);
            }

            // Vérifier que le montant ne dépasse pas le solde restant
            $paymentInfo = $this->getStudentPaymentInfo($request->student_id);
            if (!$paymentInfo->getData()->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de récupérer les informations de paiement'
                ], 400);
            }
            
            $totalRemaining = $paymentInfo->getData()->data->total_remaining;
            $totalRequired = $paymentInfo->getData()->data->total_required;
            
            // Déterminer si c'est un paiement complet (TOUTE la scolarité)
            $isFullPayment = ($request->amount >= $totalRemaining);
            
            // Vérifier s'il y a des paiements existants pour cette année
            $existingPayments = Payment::forStudent($request->student_id)
                ->forYear($workingYear->id)
                ->count();
            $hasExistingPayments = ($existingPayments > 0);
            
            // Calculer les réductions applicables avec la nouvelle logique
            $discountCalculator = new DiscountCalculatorService();
            $discountResult = $discountCalculator->calculateDiscounts(
                $student, 
                $request->amount, 
                $request->payment_date,
                $isFullPayment,
                $totalRequired,
                $hasExistingPayments
            );
            if ($request->amount > $totalRemaining) {
                return response()->json([
                    'success' => false,
                    'message' => "Le montant saisi (" . number_format($request->amount, 0, ',', ' ') . " FCFA) est supérieur au montant restant (" . number_format($totalRemaining, 0, ',', ' ') . " FCFA)."
                ], 422);
            }
            
            // Générer le numéro de reçu
            $receiptNumber = Payment::generateReceiptNumber($workingYear, 
                $request->payment_date ? \Carbon\Carbon::parse($request->payment_date) : now());

            // Créer le paiement principal avec les informations de réduction
            $payment = Payment::create([
                'student_id' => $request->student_id,
                'school_year_id' => $workingYear->id,
                'total_amount' => $discountResult['final_amount'], // Montant après réductions
                'payment_date' => $request->payment_date ?: now()->toDateString(),
                'payment_method' => $request->payment_method === 'rame_physical' ? 'cash' : $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by_user_id' => Auth::id(),
                'receipt_number' => $receiptNumber,
                'is_rame_physical' => $request->payment_method === 'rame_physical' || $request->is_rame_physical,
                'has_scholarship' => $discountResult['has_scholarship'],
                'scholarship_amount' => $discountResult['scholarship_amount'],
                'has_reduction' => $discountResult['has_reduction'],
                'reduction_amount' => $discountResult['reduction_amount'],
                'discount_reason' => $discountResult['discount_reason']
            ]);

            // Calculer la répartition du paiement
            $this->allocatePaymentToTranches($payment, $student, $workingYear);

            DB::commit();

            // Recharger le paiement avec ses relations
            $payment->load(['paymentDetails.paymentTranche', 'student', 'schoolYear']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Paiement enregistré avec succès'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PaymentController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Répartir le montant du paiement sur les tranches
     */
    private function allocatePaymentToTranches(Payment $payment, Student $student, SchoolYear $workingYear)
    {
        $remainingAmount = $payment->total_amount;

        // Récupérer les tranches de paiement dans l'ordre
        $paymentTranches = PaymentTranche::active()
            ->ordered()
            ->with(['classPaymentAmounts' => function ($query) use ($student) {
                $query->where('class_id', $student->classSeries->schoolClass->id);
            }])
            ->get();

        // Si c'est une RAME physique, prioriser la tranche RAME
        if ($payment->is_rame_physical) {
            $rameTranche = $paymentTranches->where('name', 'RAME')->first();
            if ($rameTranche) {
                // Réorganiser pour mettre la RAME en premier
                $paymentTranches = $paymentTranches->reject(function ($tranche) {
                    return $tranche->name === 'RAME';
                })->prepend($rameTranche);
            }
        }

        // Récupérer les montants déjà payés pour chaque tranche (triés par date)
        $existingPayments = Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('id', '!=', $payment->id) // Exclure le paiement actuel
            ->with(['paymentDetails.paymentTranche'])
            ->orderBy('payment_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($paymentTranches as $tranche) {
            if ($remainingAmount <= 0) break;

            // Utiliser la nouvelle méthode qui gère les montants par défaut, réductions ET bourses
            // Si le paiement a une réduction, appliquer la réduction aux montants requis par tranche
            $requiredAmount = $tranche->getAmountForStudent($student, true, $payment->has_reduction, true);
            if ($requiredAmount <= 0) continue;

            // Calculer le montant déjà payé pour cette tranche
            // Comme les paiements sont triés par date, on prend le dernier new_total_amount
            // qui représente le montant cumulé le plus récent pour cette tranche
            $previouslyPaid = 0;
            foreach ($existingPayments as $existingPayment) {
                $detail = $existingPayment->paymentDetails->where('payment_tranche_id', $tranche->id)->first();
                if ($detail) {
                    // Chaque new_total_amount est cumulatif, donc on prend le plus récent
                    $previouslyPaid = $detail->new_total_amount;
                }
            }

            // Calculer combien on peut allouer à cette tranche
            $remainingForTranche = $requiredAmount - $previouslyPaid;
            if ($remainingForTranche <= 0) continue; // Cette tranche est déjà payée

            $allocatedAmount = min($remainingAmount, $remainingForTranche);
            $newTotalAmount = $previouslyPaid + $allocatedAmount;

            // Créer le détail de paiement
            $paymentDetail = PaymentDetail::create([
                'payment_id' => $payment->id,
                'payment_tranche_id' => $tranche->id,
                'amount_allocated' => $allocatedAmount,
                'previous_amount' => $previouslyPaid,
                'new_total_amount' => $newTotalAmount,
                'is_fully_paid' => $newTotalAmount >= $requiredAmount
            ]);

            // Log pour debug
            Log::info("Payment allocation:", [
                'payment_id' => $payment->id,
                'tranche_name' => $tranche->name,
                'required_amount' => $requiredAmount,
                'previously_paid' => $previouslyPaid,
                'allocated_amount' => $allocatedAmount,
                'new_total_amount' => $newTotalAmount,
                'is_fully_paid' => $newTotalAmount >= $requiredAmount,
                'has_reduction' => $payment->has_reduction
            ]);

            $remainingAmount -= $allocatedAmount;
        }
    }

    /**
     * Obtenir l'historique des paiements d'un étudiant
     */
    public function getStudentPaymentHistory($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $payments = Payment::forStudent($studentId)
                ->forYear($workingYear->id)
                ->with([
                    'paymentDetails.paymentTranche',
                    'createdByUser'
                ])
                ->orderBy('payment_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getStudentPaymentHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un reçu de paiement
     */
    public function generateReceipt($paymentId)
    {
        try {
            $payment = Payment::with([
                'student.classSeries.schoolClass.level.section',
                'paymentDetails.paymentTranche',
                'createdByUser',
                'schoolYear'
            ])->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé'
                ], 404);
            }

            // Générer le HTML du reçu
            $html = $this->generateReceiptHtml($payment);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'html' => $html
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@generateReceipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML du reçu
     */
    private function generateReceiptHtml(Payment $payment)
    {
        $student = $payment->student;
        $workingYear = $payment->schoolYear;
        
        // Récupérer les paramètres de l'école
        $schoolSettings = SchoolSetting::getSettings();

        // Récupérer l'historique complet des paiements de l'étudiant pour cette année
        $allPayments = Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->with(['paymentDetails.paymentTranche', 'createdByUser'])
            ->orderBy('payment_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Récupérer l'état actuel des paiements par tranche
        $paymentInfo = $this->getStudentPaymentInfo($student->id);
        $paymentData = $paymentInfo->getData();
        
        if (!$paymentData->success) {
            $paymentStatus = [];
            $totals = ['required' => 0, 'paid' => 0, 'remaining' => 0];
        } else {
            // Convertir en tableau pour éviter les problèmes d'objet
            $paymentStatus = json_decode(json_encode($paymentData->data->payment_status), true) ?? [];
            $totals = [
                'required' => $paymentData->data->total_required ?? 0,
                'paid' => $paymentData->data->total_paid ?? 0,
                'remaining' => $paymentData->data->total_remaining ?? 0
            ];
        }

        $html = '
        <div class="receipt" style="max-width: 800px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 12px;">
            <div class="receipt-header" style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 15px;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px;">
                        LOGO
                    </div>
                    <div>
                        <h1 style="margin: 0; color: #333; font-size: 18px;">COLLÈGE POLYVALENT BILINGUE DE DOUALA</h1>
                    </div>
                </div>
                <p style="margin: 5px 0; color: #333; font-weight: bold; font-size: 14px;">REÇU PENSION/FRAIS DIVERS</p>
            </div>
            
            <div class="receipt-info" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div>
                        <p style="margin: 2px 0;"><strong>Année académique :</strong> ' . $workingYear->name . '</p>
                        <p style="margin: 2px 0;"><strong>Matricule :</strong> ' . ($student->student_number ?? 'N/A') . '</p>
                        <p style="margin: 2px 0;"><strong>Nom :</strong> ' . strtoupper($student->last_name) . ' ' . strtoupper($student->first_name) . '</p>
                        <p style="margin: 2px 0;"><strong>Classe :</strong> ' . strtoupper($student->classSeries->schoolClass->name ?? 'N/A') . '</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 2px 0;"><strong>Le :</strong> ' . now()->format('d/m/Y H:i:s') . '</p>
                        <p style="margin: 2px 0;"><strong>Inscription :</strong> ' . number_format($student->getNetRegistrationFee(), 0, ',', ' ') . '</p>
                        <p style="margin: 2px 0;"><strong>Le :</strong> ' . ($student->created_at ? $student->created_at->format('d/m/Y') : 'N/A') . '</p>
                        <p style="margin: 2px 0;"><strong>Banque :</strong> ' . strtoupper($payment->payment_method === 'cash' ? 'LOCAL' : ($schoolSettings->bank_name ?: 'C4ED')) . '</p>
                    </div>
                </div>
                <p style="margin: 2px 0;"><strong>Motif du rabais :</strong> ' . ($payment->discount_reason ?: 'Aucun') . '</p>
            </div>
            
            <div class="payment-history" style="margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">N° Op</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Banque</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Date validation</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Type paiement</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Montant payé</th>
                        </tr>
                    </thead>
                    <tbody>';

        $opNumber = 2655;
        foreach ($allPayments as $historyPayment) {
            foreach ($historyPayment->paymentDetails as $detail) {
                // Déterminer le libellé de la tranche avec mention RAME physique si applicable
                $trancheName = strtoupper($detail->paymentTranche->name);
                if ($historyPayment->is_rame_physical && $detail->paymentTranche->name === 'RAME') {
                    $trancheName = 'RAME PHYSIQUE';
                }
                
                $html .= '
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;">' . $opNumber . '</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;">' . strtoupper($historyPayment->payment_method === 'cash' ? 'LOCAL' : ($schoolSettings->bank_name ?: 'C4ED')) . '</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;">' . $historyPayment->payment_date->format('d/m/Y') . '</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;">' . $trancheName . '</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">' . number_format($detail->amount_allocated, 0, ',', ' ') . '</td>
                        </tr>';
                $opNumber++;
            }
        }

        $html .= '
                    </tbody>
                </table>
            </div>';

        // Ajouter section pour bourses/réductions si applicable
        if ($payment->has_scholarship || $payment->has_reduction) {
            $html .= '
            <div class="discount-section" style="margin-bottom: 20px; background-color: #f8f9fa; padding: 10px; border: 1px solid #000;">
                <h4 style="margin: 0 0 10px 0; color: #28a745; text-align: center;">BOURSES ET RÉDUCTIONS APPLIQUÉES</h4>';
                
            if ($payment->has_scholarship) {
                $html .= '
                <p style="margin: 5px 0; color: #28a745; text-align: center;">
                    <strong>✓ Bourse:</strong> ' . number_format($payment->scholarship_amount, 0, ',', ' ') . ' FCFA
                </p>';
            }
            
            if ($payment->has_reduction) {
                $html .= '
                <p style="margin: 5px 0; color: #28a745; text-align: center;">
                    <strong>✓ Réduction:</strong> ' . number_format($payment->reduction_amount, 0, ',', ' ') . ' FCFA
                </p>';
            }
            
            $totalDiscount = $payment->scholarship_amount + $payment->reduction_amount;
            $html .= '
                <p style="margin: 10px 0 5px 0; font-weight: bold; text-align: center; color: #333;">
                    Montant initial: ' . number_format($payment->total_amount + $totalDiscount, 0, ',', ' ') . ' FCFA
                </p>
                <p style="margin: 5px 0; font-weight: bold; text-align: center; color: #dc3545;">
                    Total réduction: ' . number_format($totalDiscount, 0, ',', ' ') . ' FCFA
                </p>
                <p style="margin: 5px 0; font-weight: bold; text-align: center; color: #28a745;">
                    Montant final: ' . number_format($payment->total_amount, 0, ',', ' ') . ' FCFA
                </p>
            </div>';
        }

        $html .= '
            
            <div class="payment-status" style="margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 11px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;" rowspan="2">Reste à payer par tranche</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Tranche1</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Tranche2</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Tranche3</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Bourse</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center;">Rabais</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold;">Total payé</th>
                        </tr>
                        <tr style="background-color: #f5f5f5;">';

        // Dates limites pour chaque tranche (exemple)
        $trancheDates = [
            1 => '(4.10.2024)',
            2 => '(6.11.2024)', 
            3 => '(4.1.2025)'
        ];

        foreach ($trancheDates as $num => $date) {
            $html .= '<th style="border: 1px solid #000; padding: 4px; text-align: center; font-size: 10px;">' . $date . '</th>';
        }
        $html .= '<th style="border: 1px solid #000; padding: 4px;"></th><th style="border: 1px solid #000; padding: 4px;"></th><th style="border: 1px solid #000; padding: 4px; font-weight: bold;">' . number_format($totals['paid'], 0, ',', ' ') . '</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;"><strong>Date limite de paiement</strong></td>';

        // Montants par tranche
        $trancheAmounts = [];
        foreach ($paymentStatus as $status) {
            $trancheAmounts[$status['tranche']['name']] = [
                'required' => $status['required_amount'],
                'paid' => $status['paid_amount'],
                'remaining' => $status['remaining_amount']
            ];
        }

        // Afficher les montants pour les 3 premières tranches
        for ($i = 1; $i <= 3; $i++) {
            $trancheName = "Tranche $i";
            $amount = 0;
            foreach ($trancheAmounts as $name => $data) {
                if (stripos($name, "tranche $i") !== false || stripos($name, "$i") !== false) {
                    $amount = $data['required'];
                    break;
                }
            }
            $html .= '<td style="border: 1px solid #000; padding: 6px; text-align: right;">' . number_format($amount, 0, ',', ' ') . '</td>';
        }

        $html .= '
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; background-color: #e6f3ff;">' . number_format($totals['required'], 0, ',', ' ') . '</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;"><strong>Montant dû</strong></td>';

        // Montants dus par tranche
        for ($i = 1; $i <= 3; $i++) {
            $amount = 0;
            foreach ($trancheAmounts as $name => $data) {
                if (stripos($name, "tranche $i") !== false || stripos($name, "$i") !== false) {
                    $amount = $data['required'];
                    break;
                }
            }
            $html .= '<td style="border: 1px solid #000; padding: 6px; text-align: right;">' . number_format($amount, 0, ',', ' ') . '</td>';
        }

        $html .= '
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold;">Reste à payer total</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;"><strong>Montant payé</strong></td>';

        // Montants payés par tranche
        for ($i = 1; $i <= 3; $i++) {
            $amount = 0;
            foreach ($trancheAmounts as $name => $data) {
                if (stripos($name, "tranche $i") !== false || stripos($name, "$i") !== false) {
                    $amount = $data['paid'];
                    break;
                }
            }
            $html .= '<td style="border: 1px solid #000; padding: 6px; text-align: right;">' . number_format($amount, 0, ',', ' ') . '</td>';
        }

        $html .= '
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; background-color: #e6ffe6;">' . number_format($totals['remaining'], 0, ',', ' ') . '</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 6px; text-align: center;"><strong>Reste à payer</strong></td>';

        // Reste à payer par tranche
        for ($i = 1; $i <= 3; $i++) {
            $amount = 0;
            foreach ($trancheAmounts as $name => $data) {
                if (stripos($name, "tranche $i") !== false || stripos($name, "$i") !== false) {
                    $amount = $data['remaining'];
                    break;
                }
            }
            $html .= '<td style="border: 1px solid #000; padding: 6px; text-align: right;">' . number_format($amount, 0, ',', ' ') . '</td>';
        }

        $html .= '
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right;">0</td>
                            <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; background-color: #ffe6e6;">' . number_format($totals['remaining'], 0, ',', ' ') . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="receipt-footer" style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div style="font-size: 10px;">
                        <p style="margin: 2px 0;"><strong>N.B :</strong> Vos dossiers ne seront transmis qu\'après paiement de la totalité des frais de scolarité sollicités</p>
                        <p style="margin: 2px 0;">Les frais d\'inscription et d\'étude de dossier ne sont pas remboursables</p>
                        <p style="margin: 2px 0;">Registration and studying documents fees are not refundable after admission</p>
                        <p style="margin: 10px 0 2px 0;"><strong>B.P :</strong> 4100</p>
                        <p style="margin: 2px 0;"><strong>Tél :</strong> 233 43 25 47</p>
                        <p style="margin: 2px 0;"><strong>Site web :</strong> www.cpdyassa.com</p>
                        <p style="margin: 2px 0;"><strong>Email :</strong> contact@cpdyassa.com</p>
                    </div>
                    <div style="text-align: center; position: relative;">
                        <div style="width: 120px; height: 120px; border: 3px solid #d32f2f; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(211, 47, 47, 0.1); margin: 0 auto 10px;">
                            <div style="text-align: center; color: #d32f2f; font-weight: bold; font-size: 12px; line-height: 1.2;">
                                <div>COLLÈGE POLYVALENT</div>
                                <div>BILINGUE DE DOUALA</div>
                                <div style="font-size: 10px; margin-top: 5px;">Service Comptabilité</div>
                            </div>
                        </div>
                        <p style="margin: 5px 0; font-size: 11px;"><strong>Validé par :</strong></p>
                        <p style="margin: 5px 0; font-size: 11px;">' . ($payment->createdByUser->name ?? 'KENGNI') . '</p>
                    </div>
                </div>
            </div>
        </div>';

        return $html;
    }

    /**
     * Obtenir les statistiques de paiement (pour les rapports)
     */
    public function getPaymentStats(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $query = Payment::forYear($workingYear->id);

            // Filtrer par période si spécifiée
            if ($request->start_date && $request->end_date) {
                $query->betweenDates($request->start_date, $request->end_date);
            }

            // Filtrer par série si spécifiée
            if ($request->series_id) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_series_id', $request->series_id);
                });
            }

            $payments = $query->with([
                'student.classSeries',
                'paymentDetails.paymentTranche'
            ])->get();

            $stats = [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('total_amount'),
                'by_method' => $payments->groupBy('payment_method')->map->count(),
                'by_tranche' => [],
                'by_date' => $payments->groupBy(function ($payment) {
                    return $payment->payment_date->format('Y-m-d');
                })->map(function ($dayPayments) {
                    return [
                        'count' => $dayPayments->count(),
                        'amount' => $dayPayments->sum('total_amount')
                    ];
                })
            ];

            // Statistiques par tranche
            foreach ($payments as $payment) {
                foreach ($payment->paymentDetails as $detail) {
                    $trancheName = $detail->paymentTranche->name;
                    if (!isset($stats['by_tranche'][$trancheName])) {
                        $stats['by_tranche'][$trancheName] = [
                            'count' => 0,
                            'amount' => 0
                        ];
                    }
                    $stats['by_tranche'][$trancheName]['count']++;
                    $stats['by_tranche'][$trancheName]['amount'] += $detail->amount_allocated;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'stats' => $stats,
                    'school_year' => $workingYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getPaymentStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}