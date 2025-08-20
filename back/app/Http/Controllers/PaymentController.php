<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\PaymentTranche;
use App\Services\DiscountCalculatorService;
use App\Services\PaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\SchoolSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    protected $paymentStatusService;
    protected $discountCalculatorService;

    public function __construct(PaymentStatusService $paymentStatusService, DiscountCalculatorService $discountCalculatorService)
    {
        $this->paymentStatusService = $paymentStatusService;
        $this->discountCalculatorService = $discountCalculatorService;
    }

    private function getUserWorkingYear()
    {
        $user = Auth::user();
        if ($user && $user->working_school_year_id) {
            $workingYear = SchoolYear::find($user->working_school_year_id);
            if ($workingYear && $workingYear->is_active) {
                return $workingYear;
            }
        }
        return SchoolYear::where('is_current', true)->first() ?? SchoolYear::where('is_active', true)->first();
    }

    public function getStudentPaymentInfo($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::with(['classSeries.schoolClass', 'schoolYear'])->find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            $paymentStatus = $this->paymentStatusService->getStatusForStudent($student, $workingYear);

            // La réponse inclut maintenant `payment_status` pour la compatibilité frontend
            $response_data = [
                'student' => $student,
                'school_year' => $workingYear,
                'payment_status' => $paymentStatus->tranche_status, // Montants normaux
                'total_required' => $paymentStatus->total_required, // Montants normaux affichés
                'total_paid' => $paymentStatus->total_paid,
                'total_remaining' => $paymentStatus->total_remaining,
                'total_scholarship_amount' => $paymentStatus->total_scholarship_amount,
                'has_scholarships' => $paymentStatus->has_scholarships,
                'existing_payments' => $paymentStatus->existing_payments,
                'discount_info' => [
                    'is_eligible' => $paymentStatus->is_eligible_for_discount,
                    'deadline' => $paymentStatus->discount_deadline ? (is_string($paymentStatus->discount_deadline) ? $paymentStatus->discount_deadline : $paymentStatus->discount_deadline->format('d/m/Y')) : null,
                    'percentage' => $paymentStatus->discount_percentage ?? 0,
                    'amount' => $paymentStatus->discount_amount ?? 0,
                    'amount_to_pay_with_discount' => $paymentStatus->amount_to_pay_with_discount ?? 0,
                ],
            ];

            return response()->json(['success' => true, 'data' => $response_data]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getStudentPaymentInfo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStudentPaymentHistory($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            $payments = Payment::with(['paymentDetails.paymentTranche', 'createdByUser'])
                ->where('student_id', $studentId)
                ->where('school_year_id', $workingYear->id)
                ->orderBy('payment_date', 'desc')
                ->get();

            // Formater les données pour le frontend
            $formattedPayments = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'total_amount' => $payment->total_amount,
                    'payment_date' => $payment->payment_date,
                    'versement_date' => $payment->versement_date,
                    'validation_date' => $payment->validation_date,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'receipt_number' => $payment->receipt_number,
                    'notes' => $payment->notes,
                    'has_reduction' => $payment->has_reduction,
                    'reduction_amount' => $payment->reduction_amount,
                    'discount_reason' => $payment->discount_reason,
                    'has_scholarship' => $payment->has_scholarship,
                    'scholarship_amount' => $payment->scholarship_amount,
                    'is_rame_physical' => $payment->is_rame_physical,
                    'created_by_user' => $payment->createdByUser ? [
                        'id' => $payment->createdByUser->id,
                        'name' => $payment->createdByUser->name
                    ] : null,
                    'payment_details' => $payment->paymentDetails->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'payment_tranche_id' => $detail->payment_tranche_id,
                            'amount_allocated' => $detail->amount_allocated,
                            'previous_amount' => $detail->previous_amount,
                            'new_total_amount' => $detail->new_total_amount,
                            'is_fully_paid' => $detail->is_fully_paid,
                            'was_reduced' => $detail->was_reduced,
                            'payment_tranche' => $detail->paymentTranche ? [
                                'id' => $detail->paymentTranche->id,
                                'name' => $detail->paymentTranche->name,
                                'description' => $detail->paymentTranche->description,
                                'order' => $detail->paymentTranche->order
                            ] : null
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPayments,
                'message' => 'Historique des paiements récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getStudentPaymentHistory: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,card,transfer,check',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'payment_date' => 'required|date',
            'versement_date' => 'required|date',
            'apply_global_discount' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::with('classSeries.schoolClass')->find($request->student_id);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            $paymentStatus = $this->paymentStatusService->getStatusForStudent($student, $workingYear);

            if ($request->amount > $paymentStatus->total_remaining) {
                return response()->json([
                    'success' => false,
                    'message' => "Le montant saisi (" . number_format($request->amount, 0, ',', ' ') . " FCFA) est supérieur au montant restant (" . number_format($paymentStatus->total_remaining, 0, ',', ' ') . " FCFA)."
                ], 422);
            }

            // Déterminer le type de paiement et calculer les réductions/bourses
            $paymentType = 'normal'; // Par défaut

            // Vérification de sécurité : un étudiant ne peut pas avoir à la fois une bourse ET une réduction
            $hasScholarship = $this->discountCalculatorService->getClassScholarship($student) !== null;

            // Si le frontend demande explicitement une réduction globale
            if ($request->apply_global_discount === true) {
                \Log::info('Frontend requests global discount');

                // SÉCURITÉ : Refuser si l'étudiant a une bourse
                if ($hasScholarship) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet étudiant bénéficie déjà d\'une bourse de classe. Les bourses et réductions ne sont pas cumulables.'
                    ], 422);
                }

                // Vérifier que l'étudiant est bien éligible
                $isEligible = $this->discountCalculatorService->isEligibleForGlobalDiscount(
                    $student,
                    $request->amount,
                    $paymentStatus->total_remaining,
                    $request->versement_date,
                    $paymentStatus->has_existing_payments
                );

                \Log::info('Discount eligibility check', [
                    'is_eligible' => $isEligible,
                    'amount' => $request->amount,
                    'total_remaining' => $paymentStatus->total_remaining,
                    'versement_date' => $request->versement_date,
                    'has_existing_payments' => $paymentStatus->has_existing_payments
                ]);

                if ($isEligible) {
                    $paymentType = 'global_discount';
                }
            } else {
                // Sinon, utiliser la logique automatique
                $paymentType = $this->discountCalculatorService->getPaymentType(
                    $student,
                    $request->amount,
                    $paymentStatus->total_remaining,
                    $request->versement_date,
                    $paymentStatus->has_existing_payments
                );
            }

            $discountResult = [];
            $scholarshipInfo = [];

            switch ($paymentType) {
                case 'scholarship':
                    // Cas avec bourse
                    $scholarshipInfo = $this->calculateScholarshipInfo($student, $paymentStatus->payment_tranches);
                    $discountResult = [
                        'final_amount' => $request->amount,
                        'has_reduction' => false,
                        'reduction_amount' => 0,
                        'discount_reason' => null
                    ];
                    break;

                case 'global_discount':
                    // Cas avec réduction globale - le montant frontend est déjà réduit
                    $discountPercentage = $this->discountCalculatorService->getDiscountPercentage();
                    $originalAmount = $request->amount / (1 - $discountPercentage / 100); // Recalculer le montant original
                    $discountAmount = $originalAmount - $request->amount;

                    $discountResult = [
                        'final_amount' => $request->amount, // Utiliser le montant déjà réduit du frontend
                        'has_reduction' => true,
                        'reduction_amount' => $discountAmount,
                        'discount_reason' => "Réduction {$discountPercentage}% - Paiement intégral avant échéance"
                    ];
                    $scholarshipInfo = [
                        'has_scholarship' => false,
                        'scholarship_amount' => 0
                    ];
                    break;

                default:
                    // Cas normal
                    $discountResult = [
                        'final_amount' => $request->amount,
                        'has_reduction' => false,
                        'reduction_amount' => 0,
                        'discount_reason' => null
                    ];
                    $scholarshipInfo = [
                        'has_scholarship' => false,
                        'scholarship_amount' => 0
                    ];
                    break;
            }

            DB::beginTransaction();

            $receiptNumber = Payment::generateReceiptNumber($workingYear, $request->payment_date, rand(1, 9999));

            $payment = Payment::create([
                'student_id' => $request->student_id,
                'school_year_id' => $workingYear->id,
                'total_amount' => $discountResult['final_amount'],
                'payment_date' => $request->payment_date,
                'versement_date' => $request->versement_date,
                'validation_date' => now(),
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by_user_id' => Auth::id(),
                'receipt_number' => $receiptNumber,
                'is_rame_physical' => false,
                'has_scholarship' => $scholarshipInfo['has_scholarship'],
                'scholarship_amount' => $scholarshipInfo['scholarship_amount'],
                'has_reduction' => $discountResult['has_reduction'],
                'reduction_amount' => $discountResult['reduction_amount'],
                'discount_reason' => $discountResult['discount_reason']
            ]);

            // Debug: Log du type de paiement
            \Log::info('Payment allocation debug', [
                'payment_type' => $paymentType,
                'apply_global_discount' => $request->apply_global_discount,
                'student_id' => $student->id,
                'amount' => $request->amount
            ]);

            // Allouer le paiement selon le type
            if ($paymentType === 'global_discount') {
                \Log::info('Using global discount allocation');
                $this->allocatePaymentToTranchesWithLastTrancheDiscount($payment, $student, $workingYear, $paymentStatus->payment_tranches);
            } else {
                \Log::info('Using normal allocation');
                $this->allocatePaymentToTranches($payment, $student, $workingYear, $paymentStatus->payment_tranches);
            }

            DB::commit();

            $payment->load(['paymentDetails.paymentTranche', 'student', 'schoolYear']);

            // Envoyer la notification WhatsApp avec le reçu au parent
            try {
                $whatsAppService = new \App\Services\WhatsAppService();
                $whatsAppService->sendPaymentNotification($payment);

                \Log::info('Notification de paiement WhatsApp envoyée', [
                    'payment_id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'parent_phone' => $payment->student->parent_phone ?? 'N/A'
                ]);
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de l\'envoi de la notification WhatsApp pour paiement', [
                    'payment_id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer le paiement si l'envoi WhatsApp échoue
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Paiement enregistré avec succès'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PaymentController@store: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function allocatePaymentToTranches(Payment $payment, Student $student, SchoolYear $workingYear, $paymentTranches)
    {
        $remainingAmountToAllocate = $payment->total_amount;

        $existingPayments = Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('id', '!=', $payment->id)
            ->where('is_rame_physical', false)
            ->with(['paymentDetails.paymentTranche'])
            ->get();

        foreach ($paymentTranches as $tranche) {
            if ($remainingAmountToAllocate <= 0) break;

            $requiredAmount = $tranche->getAmountForStudent($student, false, $payment->has_reduction, true);
            if ($requiredAmount <= 0) continue;

            $previouslyPaid = 0;
            foreach ($existingPayments as $existingPayment) {
                $detail = $existingPayment->paymentDetails->where('payment_tranche_id', $tranche->id)->first();
                if ($detail) {
                    $previouslyPaid = $detail->new_total_amount;
                }
            }

            $remainingForTranche = $requiredAmount - $previouslyPaid;
            if ($remainingForTranche <= 0) continue;

            $allocatedAmount = min($remainingAmountToAllocate, $remainingForTranche);
            $newTotalAmount = $previouslyPaid + $allocatedAmount;

            PaymentDetail::create([
                'payment_id' => $payment->id,
                'payment_tranche_id' => $tranche->id,
                'amount_allocated' => $allocatedAmount,
                'previous_amount' => $previouslyPaid,
                'new_total_amount' => $newTotalAmount,
                'is_fully_paid' => $newTotalAmount >= $requiredAmount,
                'required_amount_at_time' => $requiredAmount,
                'was_reduced' => $payment->has_reduction,
                'reduction_context' => $payment->has_reduction ? "Réduction appliquée sur le paiement global" : null
            ]);

            $remainingAmountToAllocate -= $allocatedAmount;
        }
    }

    /**
     * Marquer la RAME comme apportée physiquement
     */
    public function payRamePhysically(Request $request, $studentId)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
            'payment_date' => 'required|date',
            'versement_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Données invalides', 'errors' => $validator->errors()], 422);
        }

        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::with('classSeries.schoolClass')->find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            // Utiliser le système student_rame_status au lieu des tranches de paiement
            $rameStatus = \App\Models\StudentRameStatus::getOrCreateForStudent($studentId, $workingYear->id);

            // Vérifier si la RAME n'a pas déjà été marquée comme apportée
            if ($rameStatus->has_brought_rame) {
                return response()->json([
                    'success' => false,
                    'message' => 'La RAME a déjà été marquée comme apportée pour cet étudiant'
                ], 422);
            }

            // Marquer la RAME comme apportée dans student_rame_status
            $rameStatus->markAsBrought(Auth::id(), $request->notes);

            // Enregistrer les dates de versement et validation si fournies
            if ($request->versement_date) {
                $rameStatus->deposit_date = $request->versement_date;
                $rameStatus->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'receipt_number' => $receiptNumber
                ],
                'message' => 'RAME payée physiquement avec succès'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PaymentController@payRamePhysically: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement RAME physique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut RAME d'un étudiant
     */
    public function getRameStatus($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            // Utiliser le système student_rame_status
            $rameStatus = \App\Models\StudentRameStatus::getOrCreateForStudent($studentId, $workingYear->id);

            $status = [
                'rame_available' => true,
                'has_brought_rame' => $rameStatus->has_brought_rame,
                'marked_date' => $rameStatus->marked_date,
                'deposit_date' => $rameStatus->deposit_date,
                'marked_by_user_id' => $rameStatus->marked_by_user_id,
                'notes' => $rameStatus->notes,
                'can_mark_as_brought' => !$rameStatus->has_brought_rame
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getRameStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut RAME',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcule les informations de bourse pour un paiement
     */
    private function calculateScholarshipInfo(Student $student, $paymentTranches): array
    {
        $totalScholarshipAmount = 0;
        $hasScholarship = false;

        $scholarship = $this->discountCalculatorService->getClassScholarship($student);

        if ($scholarship && $this->discountCalculatorService->isEligibleForScholarship(now())) {
            // Le montant de la bourse est directement le montant configuré
            // Il s'applique à la tranche spécifiée
            foreach ($paymentTranches as $tranche) {
                if ($tranche->id == $scholarship->payment_tranche_id) {
                    $totalScholarshipAmount = $scholarship->amount;
                    $hasScholarship = true;
                    break; // Une seule tranche peut être affectée
                }
            }
        }

        return [
            'has_scholarship' => $hasScholarship,
            'scholarship_amount' => $totalScholarshipAmount
        ];
    }

    /**
     * Nouvelle méthode : Allouer le paiement aux tranches avec réduction appliquée sur les dernières tranches
     */
    private function allocatePaymentToTranchesWithLastTrancheDiscount(Payment $payment, Student $student, SchoolYear $workingYear, $paymentTranches)
    {
        $remainingAmountToAllocate = $payment->total_amount;
        $schoolSettings = \App\Models\SchoolSetting::getSettings();
        $discountPercentage = $schoolSettings->reduction_percentage ?? 0;

        // Utiliser la nouvelle logique de calcul des montants
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $reductionResult = $discountCalculator->calculateAmountsWithLastTrancheReduction($student, $paymentTranches);

        $existingPayments = Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('id', '!=', $payment->id)
            ->where('is_rame_physical', false)
            ->with(['paymentDetails.paymentTranche'])
            ->get();

        // Calculer ce qui a été payé précédemment par tranche
        $paidPerTranche = [];
        foreach ($existingPayments as $existingPayment) {
            foreach ($existingPayment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;
            }
        }

        // Allouer le paiement selon la nouvelle logique
        foreach ($reductionResult['tranches'] as $trancheData) {
            if ($remainingAmountToAllocate <= 0) break;

            $tranche = $trancheData['tranche'];
            $normalAmount = $trancheData['normal_amount'];
            $reducedAmount = $trancheData['reduced_amount'];
            $reductionApplied = $trancheData['reduction_applied'];

            if ($reducedAmount <= 0) continue;

            $previouslyPaid = $paidPerTranche[$tranche->id] ?? 0;
            $amountStillNeeded = max(0, $reducedAmount - $previouslyPaid);
            $amountToAllocate = min($remainingAmountToAllocate, $amountStillNeeded);

            if ($amountToAllocate > 0) {
                $newTotalAmount = $previouslyPaid + $amountToAllocate;

                \App\Models\PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'payment_tranche_id' => $tranche->id,
                    'amount_allocated' => $amountToAllocate,
                    'previous_amount' => $previouslyPaid,
                    'new_total_amount' => $newTotalAmount,
                    'is_fully_paid' => $newTotalAmount >= $reducedAmount,
                    'required_amount_at_time' => $reducedAmount,
                    'was_reduced' => $reductionApplied > 0,
                    'reduction_context' => $reductionApplied > 0
                        ? "Nouvelle réduction {$discountPercentage}% sur dernières tranches - Normal: " . number_format($normalAmount, 0) . " FCFA, Réduit: " . number_format($reducedAmount, 0) . " FCFA"
                        : "Montant normal - " . number_format($normalAmount, 0) . " FCFA"
                ]);

                $remainingAmountToAllocate -= $amountToAllocate;
            }
        }

        return $remainingAmountToAllocate;
    }

    /**
     * Ancienne méthode : Allouer le paiement aux tranches avec réduction globale proportionnelle
     */
    private function allocatePaymentToTranchesWithGlobalDiscount(Payment $payment, Student $student, SchoolYear $workingYear, $paymentTranches)
    {
        $remainingAmountToAllocate = $payment->total_amount;
        $schoolSettings = \App\Models\SchoolSetting::getSettings();
        $discountPercentage = $schoolSettings->reduction_percentage ?? 0;

        $existingPayments = Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('id', '!=', $payment->id)
            ->where('is_rame_physical', false)
            ->with(['paymentDetails.paymentTranche'])
            ->get();

        foreach ($paymentTranches as $tranche) {
            if ($remainingAmountToAllocate <= 0) break;

            // Calculer les montants normal et réduit
            $normalAmount = $tranche->getAmountForStudent($student, false, false, false, false);
            $reducedAmount = $tranche->getAmountForStudent($student, false, false, false, true);

            if ($reducedAmount <= 0) continue;

            // Calculer ce qui a été payé précédemment sur cette tranche
            $previouslyPaid = 0;
            foreach ($existingPayments as $existingPayment) {
                $detail = $existingPayment->paymentDetails->where('payment_tranche_id', $tranche->id)->first();
                if ($detail) {
                    $previouslyPaid += $detail->amount_allocated;
                }
            }

            // Ce qui reste à payer sur cette tranche (montant réduit)
            $amountStillNeeded = max(0, $reducedAmount - $previouslyPaid);
            $amountToAllocate = min($remainingAmountToAllocate, $amountStillNeeded);

            if ($amountToAllocate > 0) {
                $newTotalAmount = $previouslyPaid + $amountToAllocate;

                \App\Models\PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'payment_tranche_id' => $tranche->id,
                    'amount_allocated' => $amountToAllocate,
                    'previous_amount' => $previouslyPaid,
                    'new_total_amount' => $newTotalAmount,
                    'is_fully_paid' => $newTotalAmount >= $reducedAmount,
                    'required_amount_at_time' => $reducedAmount, // Stocker le montant réduit
                    'was_reduced' => true,
                    'reduction_context' => "Réduction globale {$discountPercentage}% appliquée - Normal: " . number_format($normalAmount, 0) . " FCFA"
                ]);

                $remainingAmountToAllocate -= $amountToAllocate;
            }
        }
    }

    /**
     * Générer le reçu de paiement en HTML
     */
    public function generateReceipt($paymentId)
    {
        try {
            $payment = Payment::with([
                'student.classSeries.schoolClass',
                'paymentDetails.paymentTranche',
                'schoolYear',
                'createdByUser'
            ])->findOrFail($paymentId);

            $schoolSettings = \App\Models\SchoolSetting::getSettings();

            // Générer le HTML du reçu
            $receiptHtml = $this->generateReceiptHtmlForPDF($payment, $schoolSettings);

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $receiptHtml,
                    'payment' => $payment,
                    'filename' => "Recu_{$payment->receipt_number}.pdf"
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating receipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer et télécharger directement le PDF du reçu
     */
    public function downloadReceiptPDF($paymentId)
    {
        try {
            $payment = Payment::with([
                'student.classSeries.schoolClass',
                'paymentDetails.paymentTranche',
                'schoolYear',
                'createdByUser'
            ])->findOrFail($paymentId);

            $schoolSettings = \App\Models\SchoolSetting::getSettings();

            // Générer le HTML du reçu adapté pour PDF
            $receiptHtml = $this->generateReceiptHtmlForPDF($payment, $schoolSettings);

            // Configuration DomPDF
            $pdf = Pdf::loadHtml($receiptHtml);
            $pdf->setPaper('A4', 'landscape');

            // Nom du fichier
            $filename = "Recu_{$payment->receipt_number}.pdf";

            // Retourner le PDF en téléchargement
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error generating PDF receipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML du reçu de paiement
     */
    private function generateReceiptHtml($payment, $schoolSettings)
    {
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';

        if ($schoolSettings->school_logo) {
            // Le chemin stocké peut être avec ou sans le préfixe 'public/'
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);

            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
                \Log::info('Logo base64 generated successfully from: ' . $schoolSettings->school_logo);
            } else {
                \Log::info('Logo file not found at: ' . $logoPath);
            }
        } else {
            \Log::info('No school logo configured');
        }

        $student = $payment->student;
        $schoolClass = $student->classSeries->schoolClass ?? null;

        // Formatage des montants
        $formatAmount = function ($amount) {
            return number_format($amount, 0, ',', ' ');
        };

        // Obtenir le statut récapitulatif des paiements AU MOMENT de ce paiement
        $workingYear = $payment->schoolYear;
        $paymentStatus = $this->getPaymentStatusAtTime($student, $payment);

        // Vérifier si l'élève a payé sa RAME (physique ou électronique)
        $hasRamePaid = $this->checkIfRamePaid($student, $workingYear, $payment);

        // Générer le tableau des détails de paiement
        $paymentDetailsRows = '';
        $operationNumber = 1;

        // Ajouter TOUJOURS la ligne RAME en premier
        $rameValidationDate = \Carbon\Carbon::parse($payment->versement_date)->format('d/m/Y');
        if ($hasRamePaid['paid']) {
            $rameBankName = 'local';
            $rameAmount = '1';
        } else {
            $rameBankName = 'N/A';
            $rameAmount = '0';
        }

        $ramePaymentValidationDate = \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y');
        $paymentDetailsRows .= "
            <tr>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$operationNumber}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$rameBankName}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$rameValidationDate}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$ramePaymentValidationDate}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>RAME</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>{$rameAmount}</td>
            </tr>
        ";
        $operationNumber++;

        // Ensuite, ajouter les autres détails de paiement
        foreach ($payment->paymentDetails as $detail) {
            $trancheName = $detail->paymentTranche->name;
            $versementDate = \Carbon\Carbon::parse($payment->versement_date)->format('d/m/Y');
            $validationDate = \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y');
            $paymentType = $trancheName; // Afficher la tranche affectée
            $bankName = $schoolSettings->bank_name ?? 'N/A';
            $amount = $formatAmount($detail->amount_allocated);

            $paymentDetailsRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$operationNumber}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$bankName}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$versementDate}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$validationDate}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$paymentType}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>{$amount}</td>
                </tr>
            ";
            $operationNumber++;
        }

        // Générer le tableau récapitulatif des tranches
        $recapRows = '';
        $totalRequired = 0;
        $totalPaid = 0;
        $totalDiscount = 0;
        $totalScholarship = 0;
        $totalRemaining = 0;

        foreach ($paymentStatus->tranche_status as $tranche) {
            $trancheRequired = $tranche['required_amount'];
            $tranchePaid = $tranche['paid_amount'];
            $trancheRemaining = $tranche['remaining_amount'];

            // Montants de réduction et bourse
            $discountAmount = $tranche['has_global_discount'] ? $tranche['global_discount_amount'] : 0;
            $scholarshipAmount = $tranche['has_scholarship'] ? $tranche['scholarship_amount'] : 0;

            // Calculer le reste effectif après bourses/réductions
            $effectiveRemaining = $trancheRemaining;
            if ($scholarshipAmount > 0) {
                $effectiveRemaining = max(0, $trancheRemaining - $scholarshipAmount);
            } elseif ($discountAmount > 0) {
                $effectiveRemaining = max(0, $trancheRemaining - $discountAmount);
            }

            // Déterminer le statut de paiement de la tranche
            $trancheStatus = '';
            if ($effectiveRemaining <= 0) {
                $trancheStatus = "<span style='color: #28a745; font-weight: bold;'>PAYÉ</span>";
            } else {
                $trancheStatus = "<span style='color: #dc3545; font-weight: bold;'>NON PAYÉ</span>";
            }

            $recapRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$tranche['tranche_name']}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($trancheRequired) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($tranchePaid) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($discountAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($scholarshipAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($effectiveRemaining) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$trancheStatus}</td>
                </tr>
            ";

            $totalRequired += $trancheRequired;
            $totalPaid += $tranchePaid;
            $totalDiscount += $discountAmount;
            $totalScholarship += $scholarshipAmount;
            $totalRemaining += $effectiveRemaining;
        }

        // Ajouter la ligne de total
        $recapRows .= "
            <tr style='font-weight: bold; background-color: #f0f0f0;'>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>TOTAL</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalRequired) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalPaid) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalDiscount) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalScholarship) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalRemaining) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>-</td>
            </tr>
        ";

        // Informations sur les avantages
        $benefitInfo = '';
        if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
            $benefitInfo = "Bourse: " . $formatAmount($payment->scholarship_amount) . " FCFA";
        } elseif ($payment->has_reduction && $payment->reduction_amount > 0) {
            $benefitInfo = "Réduction: " . $formatAmount($payment->reduction_amount) . " FCFA";
        }


        // Créer le contenu du reçu une seule fois
        $receiptContent = "
            <div class='receipt-copy'>
                <div class='date-time'>
                    Le " . now()->format('d/m/Y H:i:s') . "
                </div>

                <div class='header'>
                    " . ($logoBase64 ? "<img src='" . $logoBase64 . "' alt='Logo école' class='logo'>" : "") . "
                    <div class='school-name'>{$schoolSettings->school_name}</div>
                    <div class='academic-year'>Année académique : " . $workingYear->name . "</div>
                    <div class='receipt-title'>REÇU PENSION/FRAIS DIVERS</div>
                </div>

                <div class='student-info'>
                    <div><strong>Matricule :</strong> " . ($student->student_number ?? 'N/A') . "</div>
                    <div><strong>Nom :</strong> {$student->last_name} <span class='float-right'><strong>Prénom : </strong>{$student->first_name}</span></div>
                    <div><strong>Classe :</strong> " . ($schoolClass ? $schoolClass->name : 'Non défini') . "</div>
                    <div><strong>Inscription :</strong> " . $formatAmount($paymentStatus->tranche_status[0]['required_amount'] ?? 0) . " <span class='float-right'><strong>Banque :</strong> " . ($schoolSettings->bank_name ?? 'N/A') . "</span></div>
                    <div><strong>Date de validation :</strong> " . \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') . " <span class='float-right'><strong>Reçu N° :</strong> {$payment->receipt_number}</span></div>
                    " . ($benefitInfo ? "<div><strong>Motif ou rabais :</strong> {$benefitInfo}</div>" : "") . "
                </div>

                <table class='payment-table'>
                    <thead>
                        <tr>
                            <th>N° Op</th>
                            <th>Banque</th>
                            <th>Date versement</th>
                            <th>Date de validation</th>
                            <th>Tranche affectée</th>
                            <th>Montant payé</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$paymentDetailsRows}
                    </tbody>
                </table>

                <div class='recap-section'>
                    <strong>Reste à payer par tranche</strong>
                    <table class='recap-table'>
                        <thead>
                            <tr>
                                <th>Tranche</th>
                                <th>Montant normal</th>
                                <th>Montant payé</th>
                                <th>Réduction</th>
                                <th>Bourse</th>
                                <th>Reste à payer</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$recapRows}
                        </tbody>
                    </table>
                </div>

                <div class='footer-info'>
                    <div><strong>N.B :</strong> Vos dossiers ne seront transmis qu'après paiement de la totalité des frais scolaires.</div>
                    <div>Les frais de scolarité et d'étude de dossier ne sont pas remboursables en cas d'abandon ou d'exclusion.</div>
                    <div><em>Registration and studying documents fees are not refundable in case of withdrawal or exclusion.</em></div>
                    <div class='contact-info'>
                        <div class='contact-left'>
                            <div><strong>B.P :</strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[0] : '4100') . "</div>
                            <div><strong>Tél :</strong> " . ($schoolSettings->school_phone ?? '233 43 25 47') . "</div>
                            <div><strong>Site web :</strong> " . ($schoolSettings->website ?? 'www.cpdyassa.com') . "</div>
                        </div>
                        <div class='contact-right'>
                            <div>" . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[1] ?? 'Douala' : 'Douala') . "</div>
                            <div><strong>Email :</strong> " . ($schoolSettings->school_email ?? 'contact@cpdyassa.com') . "</div>
                        </div>
                    </div>
                </div>

                <div class='signature-section'>
                    <div>Validé par " . ($payment->createdByUser ? $payment->createdByUser->name : 'Comptable') . "</div>
                    <div class='signature-line'>_____________________</div>
                </div>
            </div>
        ";

        // HTML du reçu en format A4 paysage - double exemplaire côte à côte
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Reçu de Paiement - {$payment->receipt_number}</title>
            <style>
                @page {
                    size: A4 landscape;
                    margin: 0.5cm;
                }

                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    font-size: 12px;
                    line-height: 1.3;
                    color: #000;
                    background-color: white;
                }

                .receipt-main-container {
                    height: 148mm;
                    display: flex;
                    flex-direction: row;
                    gap: 10px;
                    max-width: 100%;
                    justify-content: center;
                }

                .receipt-copy {
                    width: 147mm;
                    height: 148mm;
                    padding: 8px;
                    border: 1px solid #000;
                    border-right: 1px dashed #000;
                    background-color: white;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    box-sizing: border-box;
                }

                .receipt-copy:last-child {
                    margin-bottom: 0;
                }

                .copy-label {
                    position: absolute;
                    top: 1px;
                    right: 2px;
                    font-size: 4px;
                    font-weight: bold;
                    color: #666;
                    background: #f0f0f0;
                    padding: 1px;
                    border: 1px solid #ccc;
                }

                .header {
                    text-align: center;
                    margin-bottom: 6px;
                    position: relative;
                    border-bottom: 2px solid #000;
                    padding-bottom: 4px;
                }

                .logo {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 35px;
                    height: 35px;
                    object-fit: contain;
                }

                .school-name {
                    font-size: 12px;
                    font-weight: bold;
                    margin-bottom: 4px;
                    color: #000;
                }

                .academic-year {
                    font-size: 10px;
                    margin-bottom: 4px;
                    color: #000;
                }

                .receipt-title {
                    font-size: 11px;
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 6px 0;
                    color: #000;
                }

                .date-time {
                    position: absolute;
                    top: 0;
                    left: 0;
                    font-size: 6px;
                    color: #666;
                    background: #f0f0f0;
                    padding: 2px 4px;
                }

                .student-info {
                    background: #f9f9f9;
                    padding: 6px;
                    border: 1px solid #ccc;
                    margin-bottom: 6px;
                    font-size: 9px;
                }

                .student-info h4 {
                    margin: 0 0 6px 0;
                    color: #000;
                    font-size: 10px;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 3px;
                }

                .student-info div {
                    margin: 3px 0;
                    line-height: 1.4;
                }

                .student-info strong {
                    color: #000;
                    display: inline-block;
                    min-width: 60px;
                    font-size: 9px;
                }

                .payment-details {
                    background: #fff;
                    border: 1px solid #000;
                    padding: 4px;
                    margin-bottom: 6px;
                    flex: 1;
                }

                .payment-details h4 {
                    margin: 0 0 6px 0;
                    color: #000;
                    font-size: 10px;
                    text-align: center;
                }

                .payment-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 6px 0;
                    font-size: 8px;
                }

                .payment-table th {
                    background: #e0e0e0;
                    color: #000;
                    padding: 4px 3px;
                    border: 1px solid #000;
                    font-weight: bold;
                    text-align: center;
                    font-size: 8px;
                }

                .payment-table td {
                    border: 1px solid #000;
                    padding: 4px 3px;
                    text-align: center;
                    font-size: 8px;
                }

                .payment-table tr:nth-child(even) td {
                    background: #f9f9f9;
                }

                .recap-section {
                    margin: 6px 0;
                }

                .recap-section h4 {
                    color: #000;
                    font-size: 10px;
                    margin-bottom: 6px;
                    text-align: center;
                    background: #f0f0f0;
                    padding: 4px;
                }

                .recap-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 7px;
                }

                .recap-table th {
                    background: #e0e0e0;
                    color: #000;
                    padding: 3px 2px;
                    border: 1px solid #000;
                    font-weight: bold;
                    text-align: center;
                    font-size: 7px;
                }

                .recap-table td {
                    border: 1px solid #000;
                    padding: 3px 2px;
                    text-align: center;
                    font-size: 7px;
                }

                .recap-table tr:nth-child(even) td {
                    background: #f9f9f9;
                }

                .recap-table tr:last-child td {
                    background: #f0f0f0 !important;
                    font-weight: bold;
                    border: 1px solid #000;
                }

                .footer-info {
                    margin-top: 6px;
                    font-size: 8px;
                    line-height: 1.2;
                    text-align: justify;
                    background: #f9f9f9;
                    padding: 4px;
                    border-left: 2px solid #000;
                }

                .footer-info > div {
                    margin: 3px 0;
                }

                .footer-info strong {
                    color: #000;
                }

                .contact-info {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 6px;
                    background: white;
                    padding: 4px;
                    border: 1px solid #ccc;
                }

                .contact-left, .contact-right {
                    flex: 1;
                }

                .contact-left div, .contact-right div {
                    margin: 2px 0;
                    font-size: 8px;
                }

                .signature-section {
                    margin-top: 6px;
                    text-align: right;
                    font-size: 8px;
                    background: #f0f0f0;
                    padding: 4px;
                }

                .signature-line {
                    margin-top: 6px;
                    font-size: 8px;
                    font-weight: bold;
                    color: #000;
                }

                .amount-highlight {
                    background: #fff3cd;
                    padding: 1px 4px;
                    border-radius: 3px;
                    font-weight: bold;
                    color: #856404;
                }

                .status-paid {
                    color: #27ae60;
                    font-weight: bold;
                    background: #d4edda;
                    padding: 2px 4px;
                    border-radius: 3px;
                }

                .status-unpaid {
                    color: #dc3545;
                    font-weight: bold;
                    background: #f8d7da;
                    padding: 2px 4px;
                    border-radius: 3px;
                }

                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }

                    .no-print {
                        display: none !important;
                    }

                    .receipt-copy {
                        border: 2px solid #000 !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class='receipt-main-container'>
               {$receiptContent}
            </div>
        </body>
        </html>
        ";

        return $html;
    }

    /**
     * Obtenir le statut des paiements AU MOMENT d'un paiement spécifique
     */
    private function getPaymentStatusAtTime($student, $specificPayment)
    {
        $workingYear = $specificPayment->schoolYear;

        // Récupérer tous les paiements JUSQU'À et Y COMPRIS ce paiement spécifique
        $paymentsUpToThis = \App\Models\Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('is_rame_physical', false)
            ->where('created_at', '<=', $specificPayment->created_at)
            ->with(['paymentDetails.paymentTranche'])
            ->orderBy('payment_date', 'asc')
            ->get();

        // Utiliser le PaymentStatusService avec les paiements filtrés
        return $this->paymentStatusService->getStatusForStudentWithPayments($student, $workingYear, $paymentsUpToThis);
    }

    /**
     * Obtenir le libellé du type de paiement
     */
    private function getPaymentTypeLabel($payment)
    {
        if ($payment->is_rame_physical) {
            return 'RAME';
        }

        $method = strtoupper($payment->payment_method);
        switch ($method) {
            case 'CASH':
                return 'BK'; // Espèces
            case 'CARD':
                return 'CB'; // Carte bancaire
            case 'TRANSFER':
                return 'VIR'; // Virement
            case 'CHECK':
                return 'CHQ'; // Chèque
            default:
                return $method;
        }
    }

    /**
     * Obtenir les informations de paiement avec prévisualisation des réductions
     */
    public function getStudentPaymentInfoWithDiscount($studentId)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $student = Student::with(['classSeries.schoolClass', 'schoolYear'])->find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            $paymentStatus = $this->paymentStatusService->getStatusForStudent($student, $workingYear);

            // Vérifier l'éligibilité aux réductions
            if (!$paymentStatus->is_eligible_for_discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet étudiant n\'est pas éligible aux réductions',
                    'reasons' => [
                        'has_scholarships' => $paymentStatus->has_scholarships,
                        'has_existing_payments' => $paymentStatus->has_existing_payments,
                        'deadline_passed' => $paymentStatus->discount_deadline ? now()->isAfter($paymentStatus->discount_deadline) : false
                    ]
                ]);
            }

            // Obtenir les détails avec réduction
            $discountDetails = $this->paymentStatusService->getTranchesWithDiscount($student, $workingYear);

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => $student,
                    'school_year' => $workingYear,
                    'is_eligible_for_discount' => true,
                    'discount_percentage' => $discountDetails['discount_percentage'],
                    'discount_deadline' => $paymentStatus->discount_deadline ? $paymentStatus->discount_deadline->format('d/m/Y') : null,
                    'normal_totals' => [
                        'total_required' => $discountDetails['total_normal'],
                        'total_discount' => $discountDetails['total_discount_amount'],
                        'total_with_discount' => $discountDetails['total_with_discount']
                    ],
                    'tranches_with_discount' => $discountDetails['tranches'],
                    'payment_amount_required' => $discountDetails['total_with_discount'] // Montant exact à payer
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getStudentPaymentInfoWithDiscount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations de réduction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de paiement pour le dashboard
     */
    public function getPaymentStats()
    {
        try {
            $workingYear = $this->getUserWorkingYear();

            if (!$workingYear) {
                // Retourner des stats vides plutôt qu'une erreur
                return response()->json([
                    'success' => true,
                    'data' => [
                        'overview' => [
                            'total_students' => 0,
                            'total_payments' => 0,
                            'total_amount_collected' => 0,
                            'monthly_payments' => 0,
                            'monthly_amount' => 0,
                            'weekly_payments' => 0,
                            'weekly_amount' => 0
                        ],
                        'reductions' => [
                            'total_reduction_amount' => 0,
                            'reduction_count' => 0,
                            'scholarship_count' => 0
                        ],
                        'payment_methods' => [],
                        'recent_payments' => []
                    ]
                ]);
            }

            // Statistiques globales
            $totalStudents = Student::where('school_year_id', $workingYear->id)
                ->where('is_active', true)
                ->count();

            $totalPayments = Payment::where('school_year_id', $workingYear->id)->count();

            $totalAmountCollected = Payment::where('school_year_id', $workingYear->id)
                ->sum('total_amount');

            // Paiements du mois actuel
            $currentMonth = now()->format('Y-m');
            $monthlyPayments = Payment::where('school_year_id', $workingYear->id)
                ->whereRaw("DATE_FORMAT(payment_date, '%Y-%m') = ?", [$currentMonth])
                ->count();

            $monthlyAmount = Payment::where('school_year_id', $workingYear->id)
                ->whereRaw("DATE_FORMAT(payment_date, '%Y-%m') = ?", [$currentMonth])
                ->sum('total_amount');

            // Paiements de la semaine actuelle
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();
            $weeklyPayments = Payment::where('school_year_id', $workingYear->id)
                ->whereBetween('payment_date', [$weekStart, $weekEnd])
                ->count();

            $weeklyAmount = Payment::where('school_year_id', $workingYear->id)
                ->whereBetween('payment_date', [$weekStart, $weekEnd])
                ->sum('total_amount');

            // Répartition par méthode de paiement
            $paymentMethods = Payment::where('school_year_id', $workingYear->id)
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->payment_method,
                        'count' => $item->count,
                        'total' => $item->total,
                        'label' => $this->getPaymentMethodLabel($item->payment_method)
                    ];
                });

            // Statistiques des réductions
            $totalReductions = Payment::where('school_year_id', $workingYear->id)
                ->where('has_reduction', true)
                ->sum('reduction_amount');

            $reductionCount = Payment::where('school_year_id', $workingYear->id)
                ->where('has_reduction', true)
                ->count();

            // Étudiants avec bourses - compter via les classes qui ont des bourses actives
            $scholarshipCount = Student::where('school_year_id', $workingYear->id)
                ->where('is_active', true)
                ->whereHas('classSeries.schoolClass.classScholarships', function ($query) {
                    $query->where('is_active', true);
                })
                ->count();

            // Paiements récents (5 derniers)
            $recentPayments = Payment::with(['student.classSeries.schoolClass', 'paymentDetails.paymentTranche'])
                ->where('school_year_id', $workingYear->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($payment) {
                    $student = $payment->student;
                    $studentName = $student ?
                        ($student->last_name . ' ' . $student->first_name) :
                        'N/A';

                    $className = 'N/A';
                    if ($student && $student->classSeries && $student->classSeries->schoolClass) {
                        $className = $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name;
                    }

                    return [
                        'id' => $payment->id,
                        'receipt_number' => $payment->receipt_number,
                        'student_name' => $studentName,
                        'class' => $className,
                        'amount' => $payment->total_amount,
                        'method' => $this->getPaymentMethodLabel($payment->payment_method),
                        'payment_method' => $payment->payment_method,
                        'is_rame_physical' => $payment->is_rame_physical,
                        'payment_date' => $payment->payment_date->format('Y-m-d'),
                        'date' => $payment->payment_date->format('d/m/Y'),
                        'time' => $payment->created_at->format('H:i'),
                        'payment_details' => $payment->paymentDetails->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'amount_allocated' => $detail->amount_allocated,
                                'payment_tranche' => [
                                    'id' => $detail->paymentTranche->id,
                                    'name' => $detail->paymentTranche->name
                                ]
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_students' => $totalStudents,
                        'total_payments' => $totalPayments,
                        'total_amount_collected' => $totalAmountCollected,
                        'monthly_payments' => $monthlyPayments,
                        'monthly_amount' => $monthlyAmount,
                        'weekly_payments' => $weeklyPayments,
                        'weekly_amount' => $weeklyAmount
                    ],
                    'reductions' => [
                        'total_reduction_amount' => $totalReductions,
                        'reduction_count' => $reductionCount,
                        'scholarship_count' => $scholarshipCount
                    ],
                    'payment_methods' => $paymentMethods,
                    'recent_payments' => $recentPayments
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

    /**
     * Vérifier si l'élève a apporté sa RAME
     */
    private function checkIfRamePaid($student, $workingYear, $currentPayment)
    {
        $rameStatus = \App\Models\StudentRameStatus::where('student_id', $student->id)
            ->where('school_year_id', $workingYear->id)
            ->first();

        if ($rameStatus && $rameStatus->has_brought_rame) {
            return ['paid' => true, 'type' => 'physical'];
        }

        return ['paid' => false, 'type' => null];
    }

    /**
     * Obtenir le libellé d'une méthode de paiement
     */
    private function getPaymentMethodLabel($method)
    {
        $methods = [
            'cash' => 'Bancaire',
            'card' => 'Carte bancaire',
            'transfer' => 'Virement',
            'check' => 'Chèque',
            'rame_physical' => 'RAME Physique'
        ];

        return $methods[$method] ?? ucfirst($method);
    }

    /**
     * Générer le HTML du reçu adapté pour PDF (identique au format original complet)
     */
    private function generateReceiptHtmlForPDF($payment, $schoolSettings)
    {
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';

        if ($schoolSettings->school_logo) {
            // Le chemin stocké peut être avec ou sans le préfixe 'public/'
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);

            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
                \Log::info('Logo base64 generated successfully from: ' . $schoolSettings->school_logo);
            } else {
                \Log::info('Logo file not found at: ' . $logoPath);
            }
        } else {
            \Log::info('No school logo configured');
        }

        // Réutiliser exactement la même logique que generateReceiptHtml mais optimisé pour PDF
        $student = $payment->student;
        $schoolClass = $student->classSeries->schoolClass ?? null;

        // Formatage des montants
        $formatAmount = function ($amount) {
            return number_format($amount, 0, ',', ' ');
        };

        // Obtenir le statut récapitulatif des paiements AU MOMENT de ce paiement
        $workingYear = $payment->schoolYear;
        $paymentStatus = $this->getPaymentStatusAtTime($student, $payment);

        // Vérifier si l'élève a payé sa RAME (physique ou électronique)
        $hasRamePaid = $this->checkIfRamePaid($student, $workingYear, $payment);

        // Générer le tableau des détails de paiement
        $paymentDetailsRows = '';
        $operationNumber = 1;

        // Ajouter TOUJOURS la ligne RAME en premier
        $rameValidationDate = \Carbon\Carbon::parse($payment->versement_date)->format('d/m/Y');
        if ($hasRamePaid['paid']) {
            $rameBankName = 'local';
            $rameAmount = '1';
        } else {
            $rameBankName = 'N/A';
            $rameAmount = '0';
        }

        $ramePaymentValidationDate = \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y');
        $paymentDetailsRows .= "
            <tr>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$operationNumber}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$rameBankName}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$rameValidationDate}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$ramePaymentValidationDate}</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>RAME</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>{$rameAmount}</td>
            </tr>
        ";
        $operationNumber++;

        // Ensuite, ajouter les autres détails de paiement
        foreach ($payment->paymentDetails as $detail) {
            $trancheName = $detail->paymentTranche->name;
            $versementDate = \Carbon\Carbon::parse($payment->versement_date)->format('d/m/Y');
            $validationDate = \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y');
            $paymentType = $trancheName; // Afficher la tranche affectée
            $bankName = $schoolSettings->bank_name ?? 'N/A';
            $amount = $formatAmount($detail->amount_allocated);

            $paymentDetailsRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$operationNumber}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$bankName}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$versementDate}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$validationDate}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$paymentType}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>{$amount}</td>
                </tr>
            ";
            $operationNumber++;
        }

        // Générer le tableau récapitulatif des tranches (identique à l'original)
        $recapRows = '';
        $totalRequired = 0;
        $totalPaid = 0;
        $totalDiscount = 0;
        $totalScholarship = 0;
        $totalRemaining = 0;

        foreach ($paymentStatus->tranche_status as $tranche) {
            $trancheRequired = $tranche['required_amount'];
            $tranchePaid = $tranche['paid_amount'];
            $trancheRemaining = $tranche['remaining_amount'];

            // Montants de réduction et bourse
            $discountAmount = $tranche['has_global_discount'] ? $tranche['global_discount_amount'] : 0;
            $scholarshipAmount = $tranche['has_scholarship'] ? $tranche['scholarship_amount'] : 0;

            // Calculer le reste effectif après bourses/réductions
            $effectiveRemaining = $trancheRemaining;
            if ($scholarshipAmount > 0) {
                $effectiveRemaining = max(0, $trancheRemaining - $scholarshipAmount);
            } elseif ($discountAmount > 0) {
                $effectiveRemaining = max(0, $trancheRemaining - $discountAmount);
            }

            // Déterminer le statut de paiement de la tranche
            $trancheStatus = '';
            if ($effectiveRemaining <= 0) {
                $trancheStatus = "<span style='color: #28a745; font-weight: bold;'>PAYÉ</span>";
            } else {
                $trancheStatus = "<span style='color: #dc3545; font-weight: bold;'>NON PAYÉ</span>";
            }

            $recapRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$tranche['tranche_name']}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($trancheRequired) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($tranchePaid) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($discountAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($scholarshipAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($effectiveRemaining) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$trancheStatus}</td>
                </tr>
            ";

            $totalRequired += $trancheRequired;
            $totalPaid += $tranchePaid;
            $totalDiscount += $discountAmount;
            $totalScholarship += $scholarshipAmount;
            $totalRemaining += $effectiveRemaining;
        }

        // Ajouter la ligne de total
        $recapRows .= "
            <tr style='font-weight: bold; background-color: #f0f0f0;'>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>TOTAL</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalRequired) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalPaid) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalDiscount) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalScholarship) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($totalRemaining) . "</td>
                <td style='border: 1px solid #000; padding: 4px; text-align: center;'>-</td>
            </tr>
        ";

        // Informations sur les avantages
        $benefitInfo = '';
        if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
            $benefitInfo = "Bourse: " . $formatAmount($payment->scholarship_amount) . " FCFA";
        } elseif ($payment->has_reduction && $payment->reduction_amount > 0) {
            $benefitInfo = "Réduction: " . $formatAmount($payment->reduction_amount) . " FCFA";
        }

        // Créer le contenu du reçu une seule fois pour réutilisation
        $receiptContent = "
            <div class='receipt-copy' style='border-right: 2px dashed #000'>
                <div class='copy-label'>EXEMPLAIRE PARENTS</div>
                <div class='date-time'>
                    Généré le " . now()->format('d/m/Y à H:i:s') . "
                </div>

                <div class='header'>
                    " . ($logoBase64 ? "<img src='" . $logoBase64 . "' alt='Logo école' class='logo'>" : "") . "
                    <div class='school-name'>{$schoolSettings->school_name}</div>
                    <div class='academic-year'>Année académique : " . $workingYear->name . "</div>
                    <div class='receipt-title'>REÇU DE PAIEMENT - N° {$payment->receipt_number}</div>
                </div>

                <div class='student-info'>
                    <h4>Informations de l'Eleve</h4>
                    <div><strong>Matricule :</strong> " . ($student->student_number ?? 'N/A') . "</div>
                    <div><strong>Nom :</strong> {$student->last_name} {$student->first_name}</div>
                    <div><strong>Classe :</strong> " . ($schoolClass ? $schoolClass->name : 'Non défini') . "</div>
                    <div><strong>Date validation :</strong> " . \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') . "</div>
                    <div><strong>Banque :</strong> " . ($schoolSettings->bank_name ?? 'N/A') . "</div>
                    " . ($benefitInfo ? "<div><strong>Avantage :</strong> <span class='amount-highlight'>{$benefitInfo}</span></div>" : "") . "
                </div>

                <div class='payment-details'>
                    <h4>Détails du Paiement</h4>
                    <table class='payment-table'>
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Banque</th>
                                <th>Date versement</th>
                                <th>Date de validation</th>
                                <th>Tranche</th>
                                <th>Montant (FCFA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$paymentDetailsRows}
                        </tbody>
                    </table>
                </div>

                <div class='recap-section'>
                    <h4>Récapitulatif par Tranche</h4>
                    <table class='recap-table'>
                        <thead>
                            <tr>
                                <th>Tranche</th>
                                <th>Normal</th>
                                <th>Payé</th>
                                <th>Réduc.</th>
                                <th>Bourse</th>
                                <th>Reste</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$recapRows}
                        </tbody>
                    </table>
                </div>

                <div class='footer-info'>
                    <div><strong>N.B :</strong> Vos dossiers d'examen ne seront transmis qu'après paiement de la totalite des frais de scolarite.</div>
                    <div>Les frais d'inscription et d'etude de dossier ne sont pas remboursables, ni substituables.</div>
                    <div>Registration and studying documents fees are not refundable or transferable</div>

                    <div class='contact-info'>
                        <div class='contact-left'>
                            <div><strong></strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[0] : '4100') . "</div>
                            <div><strong></strong> " . ($schoolSettings->school_phone ?? '233 43 25 47') . "</div>
                            <div><strong></strong> " . ($schoolSettings->website ?? 'www.cpdyassa.com') . "</div>
                        </div>
                        <div class='contact-right'>
                            <div><strong></strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[1] ?? 'Douala' : 'Douala') . "</div>
                            <div><strong></strong> " . ($schoolSettings->school_email ?? 'contact@cpdyassa.com') . "</div>
                        </div>
                    </div>
                </div>

                <div class='signature-section'>
                    <div>Validé par : " . ($payment->createdByUser ? $payment->createdByUser->name : 'Comptable') . "</div>
                    <div class='signature-line'>Signature : _____________</div>
                </div>
            </div>
        ";

        // HTML du reçu optimisé pour PDF en format A4 paysage - double exemplaire côte à côte
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Reçu de Paiement - {$payment->receipt_number}</title>
            <style>
                @page {
                    size: A4 landscape;
                    margin: 0.5cm;
                }

                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    font-size: 12px;
                    line-height: 1.2;
                    color: #000;
                    background-color: white;
                }

                .receipt-main-container {
                    width: 100%;
                    height: auto;
                    display: flex;
                    flex-direction: row;
                    gap: 3mm;
                    justify-content: center;
                    align-items: flex-start;
                }

                .receipt-copy {
                    width: 140mm;
                    height: auto;
                    min-height: 180mm;
                    padding: 6px;
                    border: 1px solid #000;

                    background-color: white;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    box-sizing: border-box;
                    float: left;
                }

                .copy-label {
                    position: absolute;
                    top: 1px;
                    right: 2px;
                    font-size: 8px;
                    font-weight: bold;
                    color: #666;
                    background: #f0f0f0;
                    padding: 1px;
                    border: 1px solid #ccc;
                }

                .date-time {
                    position: absolute;
                    top: 0;
                    left: 0;
                    font-size: 8px;
                    color: #666;
                    background: #f0f0f0;
                    padding: 2px 3px;
                }

                .header {
                    text-align: center;
                    margin-bottom: 4px;
                    position: relative;
                    border-bottom: 1px solid #000;
                    padding-bottom: 3px;
                }

                .logo {
                    position: absolute;
                    left: 0;
                    top: 10px;
                    width: 35px;
                    height: 35px;
                    object-fit: contain;
                }

                .school-name {
                    font-size: 9px;
                    font-weight: bold;
                    margin-bottom: 2px;
                    color: #000;
                }

                .academic-year {
                    font-size: 12px;
                    margin-bottom: 2px;
                    color: #000;
                }

                .receipt-title {
                    font-size: 14px;
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 3px 0;
                    color: #000;
                }

                .student-info {
                    background: #f9f9f9;
                    padding: 4px;
                    border: 1px solid #ccc;
                    margin-bottom: 8px;
                    font-size: 10px;
                }

                .student-info h4 {
                    margin: 0 30px 0px 0px;
                    color: #000;
                    font-size: 12px;
                    text-align: center;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 5px;
                }

                .student-info div {
                    // margin: 2px 0;
                    line-height: 1.3;
                }

                .student-info strong {
                    color: #000;
                    // display: inline-block;
                    min-width: 35px;
                    font-size: 12px;
                }

                .payment-details {
                    background: #fff;
                    border: 1px solid #000;
                    padding: 3px;
                    margin-bottom: 4px;
                    flex: 1;
                }

                .payment-details h4 {
                    margin: 0 0 3px 0;
                    color: #000;
                    font-size: 12px;
                    text-align: center;
                    background: #f0f0f0;
                    padding: 2px;
                }

                .payment-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 3px 0;
                    font-size: 10px;
                }

                .payment-table th {
                    background: #e0e0e0;
                    color: #000;
                    padding: 2px 1px;
                    border: 1px solid #000;
                    font-weight: bold;
                    text-align: center;
                    font-size: 10px;
                }

                .payment-table td {
                    border: 1px solid #000;
                    padding: 2px 1px;
                    text-align: center;
                    font-size: 10px;
                }

                .recap-section {
                    margin: 3px 0;
                }

                .recap-section h4 {
                    color: #000;
                    font-size: 12px;
                    margin-bottom: 3px;
                    text-align: center;
                    background: #f0f0f0;
                    padding: 2px;
                }

                .recap-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8px;
                }

                .recap-table th {
                    background: #e0e0e0;
                    color: #000;
                    padding: 1px;
                    border: 1px solid #000;
                    font-weight: bold;
                    text-align: center;
                    font-size: 8px;
                }

                .recap-table td {
                    border: 1px solid #000;
                    padding: 1px;
                    text-align: center;
                    font-size: 8px;
                }

                .footer-info {
                    margin-top: 3px;
                    font-size: 10px;
                    line-height: 1.1;
                    text-align: justify;
                    background: #f9f9f9;
                    padding: 3px;
                    border-left: 1px solid #000;
                }

                .footer-info > div {
                    margin: 4px 0;
                }

                .contact-info {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 3px;
                    background: white;
                    padding: 4px;
                    border: 1px solid #ccc;
                }

                .contact-left, .contact-right {
                    flex: 1;
                }

                .contact-left div, .contact-right div {
                    margin: 4px 0;
                    font-size: 8px;
                }

                .signature-section {
                    margin-top: 5px;
                    text-align: right;
                    font-size: 10px;
                    background: #f0f0f0;
                    padding: 4px;
                }

                .signature-line {
                    margin-top: 3px;
                    font-size: 10px;
                    font-weight: bold;
                    color: #000;
                }

                .amount-highlight {
                    background: #fff3cd;
                    padding: 0px 2px;
                    border-radius: 2px;
                    font-weight: bold;
                    color: #856404;
                }
            </style>
        </head>
        <body>
            <div class='receipt-main-container'>
                <!-- Exemplaire Parents -->
                {$receiptContent}

                <!-- Exemplaire Collège -->
                <div class='receipt-copy' style='border-left: 2px dashed #000;'>
                    <div class='copy-label'>EXEMPLAIRE COLLÈGE</div>
                    <div class='date-time'>
                        Généré le " . now()->format('d/m/Y à H:i:s') . "
                    </div>

                    <div class='header'>
                        " . ($logoBase64 ? "<img src='" . $logoBase64 . "' alt='Logo école' class='logo'>" : "") . "
                        <div class='school-name'>{$schoolSettings->school_name}</div>
                        <div class='academic-year'>Année académique : " . $workingYear->name . "</div>
                        <div class='receipt-title'>REÇU DE PAIEMENT - N° {$payment->receipt_number}</div>
                    </div>

                    <div class='student-info'>
                        <h4>Informations de l'Eleve</h4>
                        <div><strong>Matricule :</strong> " . ($student->student_number ?? 'N/A') . "</div>
                        <div><strong>Nom :</strong> {$student->last_name} {$student->first_name}</div>
                        <div><strong>Classe :</strong> " . ($schoolClass ? $schoolClass->name : 'Non défini') . "</div>
                        <div><strong>Date validation :</strong> " . \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') . "</div>
                        <div><strong>Banque :</strong> " . ($schoolSettings->bank_name ?? 'N/A') . "</div>
                        " . ($benefitInfo ? "<div><strong>Avantage :</strong> <span class='amount-highlight'>{$benefitInfo}</span></div>" : "") . "
                    </div>

                    <div class='payment-details'>
                        <h4>Détails du Paiement</h4>
                        <table class='payment-table'>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Banque</th>
                                    <th>Date versement</th>
                                    <th>Date de validation</th>
                                    <th>Tranche</th>
                                    <th>Montant (FCFA)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$paymentDetailsRows}
                            </tbody>
                        </table>
                    </div>

                    <div class='recap-section'>
                        <h4>Récapitulatif par Tranche</h4>
                        <table class='recap-table'>
                            <thead>
                                <tr>
                                    <th>Tranche</th>
                                    <th>Normal</th>
                                    <th>Payé</th>
                                    <th>Réduc.</th>
                                    <th>Bourse</th>
                                    <th>Reste</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$recapRows}
                            </tbody>
                        </table>
                    </div>

                    <div class='footer-info'>
                    <div><strong>N.B :</strong> Vos dossiers d'examen ne seront transmis qu'après paiement de la totalite des frais de scolarite.</div>
                    <div>Les frais d'inscription et d'etude de dossier ne sont pas remboursables, ni substituables.</div>
                    <div>Registration and studying documents fees are not refundable or transferable</div>

                        <div class='contact-info'>
                            <div class='contact-left'>
                                <div><strong></strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[0] : '4100') . "</div>
                                <div><strong></strong> " . ($schoolSettings->school_phone ?? '233 43 25 47') . "</div>
                                <div><strong></strong> " . ($schoolSettings->website ?? 'www.cpdyassa.com') . "</div>
                            </div>
                            <div class='contact-right'>
                                <div><strong></strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[1] ?? 'Douala' : 'Douala') . "</div>
                                <div><strong></strong> " . ($schoolSettings->school_email ?? 'contact@cpdyassa.com') . "</div>
                            </div>
                        </div>
                    </div>

                    <div class='signature-section'>
                        <div>Validé par : " . ($payment->createdByUser ? $payment->createdByUser->name : 'Comptable') . "</div>
                        <div class='signature-line'>Signature : _____________</div>
                    </div>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Générer un listing des paiements de scolarité par période avec montant total par élève
     */
    public function generatePaymentListingReport(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'class_id' => 'nullable|exists:school_classes,id',
                'format' => 'string|in:html,pdf'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $classId = $request->class_id;
            $format = $request->get('format', 'html');

            // Récupérer les paiements de la période
            $paymentsQuery = Payment::with([
                'student.classSeries.schoolClass',
                'paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_rame_physical', false)
            ->whereBetween('payment_date', [$startDate, $endDate]);

            // Filtrer par classe si spécifié
            if ($classId) {
                $paymentsQuery->whereHas('student.classSeries.schoolClass', function($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

            // Grouper par étudiant et calculer les totaux
            $studentPayments = [];
            foreach ($payments as $payment) {
                $student = $payment->student;
                $studentId = $student->id;

                if (!isset($studentPayments[$studentId])) {
                    $studentPayments[$studentId] = [
                        'student' => $student,
                        'total_amount' => 0,
                        'payment_count' => 0,
                        'payments' => []
                    ];
                }

                $studentPayments[$studentId]['total_amount'] += $payment->total_amount;
                $studentPayments[$studentId]['payment_count']++;
                $studentPayments[$studentId]['payments'][] = $payment;
            }

            // Trier par montant total décroissant
            uasort($studentPayments, function($a, $b) {
                return $b['total_amount'] <=> $a['total_amount'];
            });

            $schoolSettings = \App\Models\SchoolSetting::getSettings();
            $html = $this->generatePaymentListingHtml($studentPayments, $startDate, $endDate, $classId, $schoolSettings, $workingYear);

            if ($format === 'pdf') {
                $pdf = Pdf::loadHtml($html);
                $pdf->setPaper('A4', 'portrait');

                $filename = 'listing_paiements_' . date('Y-m-d_H-i-s') . '.pdf';

                return $pdf->download($filename);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html,
                    'summary' => [
                        'total_students' => count($studentPayments),
                        'total_amount' => array_sum(array_column($studentPayments, 'total_amount')),
                        'period' => ['start' => $startDate, 'end' => $endDate]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@generatePaymentListingReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le listing des paiements
     */
    private function generatePaymentListingHtml($studentPayments, $startDate, $endDate, $classId, $schoolSettings, $workingYear)
    {
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';
        if ($schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
            }
        }

        $formatAmount = function ($amount) {
            return number_format($amount, 0, ',', ' ');
        };

        $totalAmount = array_sum(array_column($studentPayments, 'total_amount'));
        $totalStudents = count($studentPayments);

        // Nom de la classe si filtrée
        $className = 'Toutes les classes';
        if ($classId) {
            $class = \App\Models\SchoolClass::find($classId);
            $className = $class ? $class->name : 'Classe inconnue';
        }

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Listing des Paiements de Scolarité</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 15px; font-size: 12px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
                .logo { max-height: 60px; margin-bottom: 10px; }
                .school-info { margin-bottom: 5px; font-weight: bold; }
                .title { font-size: 16px; font-weight: bold; margin: 15px 0; text-decoration: underline; }
                .period-info { margin: 10px 0; font-weight: bold; }
                .summary { background-color: #f5f5f5; padding: 10px; margin: 15px 0; border: 1px solid #ddd; }
                .payment-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .payment-table th, .payment-table td { border: 1px solid #000; padding: 6px; text-align: left; }
                .payment-table th { background-color: #e0e0e0; font-weight: bold; text-align: center; }
                .amount { text-align: right; font-weight: bold; }
                .total-row { background-color: #f0f0f0; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 10px; }
                .no-data { text-align: center; padding: 30px; font-style: italic; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo' class='logo'>" : "") . "
                <div class='school-info'>" . ($schoolSettings->school_name ?? 'École') . "</div>
                <div>" . ($schoolSettings->school_address ?? '') . "</div>
                <div>Tél: " . ($schoolSettings->school_phone ?? '') . " | Email: " . ($schoolSettings->school_email ?? '') . "</div>
            </div>

            <div class='title'>LISTING DES PAIEMENTS DE SCOLARITÉ</div>

            <div class='period-info'>
                <div>Période: du " . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . " au " . \Carbon\Carbon::parse($endDate)->format('d/m/Y') . "</div>
                <div>Classe: {$className}</div>
                <div>Année scolaire: {$workingYear->name}</div>
            </div>

            <div class='summary'>
                <strong>Résumé:</strong> {$totalStudents} étudiant(s) | Montant total: " . $formatAmount($totalAmount) . " FCFA
            </div>";

        if (empty($studentPayments)) {
            $html .= "<div class='no-data'>Aucun paiement trouvé pour cette période</div>";
        } else {
            $html .= "
            <table class='payment-table'>
                <thead>
                    <tr>
                        <th style='width: 5%;'>N°</th>
                        <th style='width: 25%;'>Nom et Prénom</th>
                        <th style='width: 20%;'>Classe</th>
                        <th style='width: 15%;'>Nb Paiements</th>
                        <th style='width: 20%;'>Montant Total</th>
                        <th style='width: 15%;'>Dernière Date</th>
                    </tr>
                </thead>
                <tbody>";

            $counter = 1;
            foreach ($studentPayments as $data) {
                $student = $data['student'];
                $studentName = ($student->last_name ?? '') . ' ' . ($student->first_name ?? '');
                $className = 'N/A';
                if ($student->classSeries && $student->classSeries->schoolClass) {
                    $className = $student->classSeries->schoolClass->name;
                }

                // Dernière date de paiement
                $lastPaymentDate = 'N/A';
                if (!empty($data['payments'])) {
                    $lastPayment = collect($data['payments'])->sortByDesc('payment_date')->first();
                    $lastPaymentDate = \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y');
                }

                $html .= "
                    <tr>
                        <td style='text-align: center;'>{$counter}</td>
                        <td>{$studentName}</td>
                        <td>{$className}</td>
                        <td style='text-align: center;'>{$data['payment_count']}</td>
                        <td class='amount'>" . $formatAmount($data['total_amount']) . " FCFA</td>
                        <td style='text-align: center;'>{$lastPaymentDate}</td>
                    </tr>";
                $counter++;
            }

            $html .= "
                    <tr class='total-row'>
                        <td colspan='4' style='text-align: right; font-weight: bold;'>TOTAL GÉNÉRAL:</td>
                        <td class='amount'>" . $formatAmount($totalAmount) . " FCFA</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>";
        }

        $html .= "
            <div class='footer'>
                <div>Document généré le " . now()->format('d/m/Y à H:i') . "</div>
                <div>Système de Gestion Scolaire - " . ($schoolSettings->school_name ?? 'École') . "</div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Générer le rapport des listes de tranches par période
     */
    public function generateTrancheListsReport(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'class_id' => 'nullable|exists:school_classes,id',
                'series_id' => 'nullable|exists:class_series,id',
                'tranche_ids' => 'required|array|min:1',
                'tranche_ids.*' => 'exists:payment_tranches,id',
                'format' => 'string|in:html,pdf'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $classId = $request->class_id;
            $seriesId = $request->series_id;
            $trancheIds = $request->tranche_ids;
            $format = $request->get('format', 'html');

            // Récupérer les tranches sélectionnées
            $selectedTranches = PaymentTranche::whereIn('id', $trancheIds)
                ->orderBy('order')
                ->get();

            // Construire la requête des étudiants - seulement ceux qui ont payé dans la période
            $studentsQuery = Student::with([
                'classSeries.schoolClass',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true)
            // Filtrer uniquement les étudiants qui ont payé des tranches sélectionnées dans la période
            ->whereHas('payments', function($query) use ($startDate, $endDate, $trancheIds) {
                $query->whereBetween('payment_date', [$startDate, $endDate])
                      ->where(function($subQuery) use ($trancheIds) {
                          // Inclure les paiements normaux ET les paiements avec réduction
                          $subQuery->whereHas('paymentDetails', function($detailQuery) use ($trancheIds) {
                              $detailQuery->whereIn('payment_tranche_id', $trancheIds);
                          })
                          ->orWhere(function($reductionQuery) use ($trancheIds) {
                              // Inclure les paiements avec réduction qui touchent les tranches sélectionnées
                              $reductionQuery->where('has_reduction', true)
                                           ->whereHas('paymentDetails', function($detailQuery) use ($trancheIds) {
                                               $detailQuery->whereIn('payment_tranche_id', $trancheIds);
                                           });
                          });
                      });
            });

            // Filtrer par classe si spécifié
            if ($classId) {
                $studentsQuery->whereHas('classSeries.schoolClass', function($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            // Filtrer par série si spécifié
            if ($seriesId) {
                $studentsQuery->whereHas('classSeries', function($query) use ($seriesId) {
                    $query->where('id', $seriesId);
                });
            }

            // Debug - voir la requête SQL générée
            \Illuminate\Support\Facades\Log::info("SQL Query for students: " . $studentsQuery->toSql());
            \Illuminate\Support\Facades\Log::info("Query bindings: " . json_encode($studentsQuery->getBindings()));

            $students = $studentsQuery->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            // Debug - voir quels étudiants sont récupérés
            \Illuminate\Support\Facades\Log::info("Students found for tranche report: " . $students->pluck('id')->implode(', '));

            // Debug spécial pour l'étudiant 40
            $student40 = Student::with('payments.paymentDetails.paymentTranche')->find(40);
            if ($student40) {
                \Illuminate\Support\Facades\Log::info("Student 40 exists - checking payments...");
                foreach ($student40->payments as $payment) {
                    $paymentDate = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
                    $startDateFormatted = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
                    $endDateFormatted = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
                    $inPeriod = $paymentDate >= $startDateFormatted && $paymentDate <= $endDateFormatted;
                    \Illuminate\Support\Facades\Log::info("Payment {$payment->id} - Date: {$payment->payment_date} ({$paymentDate}) - Period: {$startDateFormatted} to {$endDateFormatted} - In period: " . ($inPeriod ? 'Yes' : 'No') . " - Has reduction: " . ($payment->has_reduction ? 'Yes' : 'No'));
                    if ($inPeriod) {
                        foreach ($payment->paymentDetails as $detail) {
                            $inSelectedTranches = in_array($detail->payment_tranche_id, $trancheIds);
                            \Illuminate\Support\Facades\Log::info("  Detail - Tranche: {$detail->paymentTranche->name} - In selected: " . ($inSelectedTranches ? 'Yes' : 'No') . " - Amount: {$detail->amount_allocated}");
                        }
                    }
                }
            } else {
                \Illuminate\Support\Facades\Log::info("Student 40 not found");
            }

            // Préparer les données pour chaque étudiant
            $studentData = [];
            foreach ($students as $student) {
                $studentInfo = [
                    'student' => $student,
                    'payments_by_tranche' => []
                ];

                // Initialiser TOUTES les tranches sélectionnées avec des valeurs par défaut
                foreach ($selectedTranches as $tranche) {
                    $studentInfo['payments_by_tranche'][$tranche->id] = [
                        'tranche' => $tranche,
                        'amount_paid' => 0,
                        'payment_count' => 0,
                        'last_payment_date' => null,
                        'validation_date' => null,
                        'has_payment' => false,
                        'was_reduced' => false
                    ];
                }

                // Calculer les paiements par tranche - SEULEMENT les paiements de la période
                foreach ($student->payments as $payment) {
                    // Vérifier si le paiement est dans la période (comparison par date uniquement)
                    $paymentDate = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
                    $startDateFormatted = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
                    $endDateFormatted = \Carbon\Carbon::parse($endDate)->format('Y-m-d');

                    if ($paymentDate >= $startDateFormatted && $paymentDate <= $endDateFormatted) {
                        foreach ($payment->paymentDetails as $detail) {
                            $trancheId = $detail->paymentTranche->id;
                            if (in_array($trancheId, $trancheIds)) {
                                // Utiliser amount_allocated qui contient le montant effectivement payé
                                $amountPaid = $detail->amount_allocated;

                                $studentInfo['payments_by_tranche'][$trancheId]['amount_paid'] += $amountPaid;
                                $studentInfo['payments_by_tranche'][$trancheId]['payment_count']++;

                                // Marquer si cette tranche a été payée (même avec réduction)
                                if ($detail->was_reduced || $amountPaid > 0 || $payment->has_reduction) {
                                    $studentInfo['payments_by_tranche'][$trancheId]['has_payment'] = true;
                                    // Marquer comme réduit si le detail était réduit OU si le paiement global avait une réduction
                                    $studentInfo['payments_by_tranche'][$trancheId]['was_reduced'] = $detail->was_reduced || $payment->has_reduction;
                                }

                                $currentDate = $payment->payment_date;
                                $validationDate = $payment->validation_date;
                                if (!$studentInfo['payments_by_tranche'][$trancheId]['last_payment_date'] ||
                                    $currentDate > $studentInfo['payments_by_tranche'][$trancheId]['last_payment_date']) {
                                    $studentInfo['payments_by_tranche'][$trancheId]['last_payment_date'] = $currentDate;
                                    $studentInfo['payments_by_tranche'][$trancheId]['validation_date'] = $validationDate;
                                }

                                // Debug - pour voir tous les paiements
                                \Illuminate\Support\Facades\Log::info("Student {$student->id} - Tranche {$detail->paymentTranche->name} - Amount: {$amountPaid} - Detail reduced: " . ($detail->was_reduced ? 'Yes' : 'No') . " - Payment has_reduction: " . ($payment->has_reduction ? 'Yes' : 'No') . " - Context: {$detail->reduction_context}");
                            }
                        }
                    }
                }

                // Debug final pour chaque étudiant
                if ($student->id == 40) {
                    \Illuminate\Support\Facades\Log::info("Final data for student 40:");
                    foreach ($studentInfo['payments_by_tranche'] as $trancheId => $data) {
                        \Illuminate\Support\Facades\Log::info("  Tranche {$trancheId}: amount={$data['amount_paid']}, has_payment={$data['has_payment']}, was_reduced={$data['was_reduced']}");
                    }
                }

                $studentData[] = $studentInfo;
            }

            $schoolSettings = \App\Models\SchoolSetting::getSettings();
            $html = $this->generateTrancheListsHtml($studentData, $selectedTranches, $startDate, $endDate, $classId, $schoolSettings, $workingYear);

            if ($format === 'pdf') {
                $pdf = Pdf::loadHtml($html);
                $pdf->setPaper('A4', 'landscape'); // Paysage pour accommoder les colonnes

                $filename = 'liste_tranches_' . date('Y-m-d_H-i-s') . '.pdf';

                return $pdf->download($filename);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html,
                    'summary' => [
                        'total_students' => count($studentData),
                        'selected_tranches' => $selectedTranches->pluck('name')->toArray(),
                        'period' => ['start' => $startDate, 'end' => $endDate]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@generateTrancheListsReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du listing des tranches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le listing des tranches
     */
    private function generateTrancheListsHtml($studentData, $selectedTranches, $startDate, $endDate, $classId, $schoolSettings, $workingYear)
    {
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';
        if ($schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
            }
        }

        $formatAmount = function ($amount) {
            return number_format($amount, 0, ',', ' ');
        };

        $totalStudents = count($studentData);

        // Nom de la classe si filtrée
        $className = 'Toutes les classes';
        if ($classId) {
            $class = \App\Models\SchoolClass::find($classId);
            $className = $class ? $class->name : 'Classe inconnue';
        }

        // Largeur des colonnes dynamique selon le nombre de tranches
        $baseColumns = 3; // N°, Nom, Classe
        $trancheColumns = count($selectedTranches);
        $totalColumns = $baseColumns + $trancheColumns;
        $trancheWidth = round(70 / $trancheColumns, 1); // 70% répartis entre les tranches

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Liste des Paiements par Tranche</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 10px; font-size: 10px; }
                .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .logo { max-height: 50px; margin-bottom: 8px; }
                .school-info { margin-bottom: 3px; font-weight: bold; font-size: 11px; }
                .title { font-size: 14px; font-weight: bold; margin: 10px 0; text-decoration: underline; }
                .period-info { margin: 8px 0; font-weight: bold; font-size: 10px; }
                .summary { background-color: #f5f5f5; padding: 8px; margin: 10px 0; border: 1px solid #ddd; font-size: 10px; }
                .payment-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9px; }
                .payment-table th, .payment-table td { border: 1px solid #000; padding: 3px; text-align: left; }
                .payment-table th { background-color: #e0e0e0; font-weight: bold; text-align: center; font-size: 8px; }
                .amount { text-align: right; font-weight: bold; }
                .center { text-align: center; }
                .footer { margin-top: 20px; text-align: center; font-size: 8px; }
                .no-data { text-align: center; padding: 20px; font-style: italic; color: #666; }
                .tranche-header { background-color: #d0d0d0; font-size: 7px; font-weight: bold; }
                .student-row { font-size: 8px; }
            </style>
        </head>
        <body>
            <div class='header'>
                " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo' class='logo'>" : "") . "
                <div class='school-info'>" . ($schoolSettings->school_name ?? 'École') . "</div>
                <div>" . ($schoolSettings->school_address ?? '') . "</div>
                <div>Tél: " . ($schoolSettings->school_phone ?? '') . " | Email: " . ($schoolSettings->school_email ?? '') . "</div>
            </div>

            <div class='title'>LISTE DES PAIEMENTS PAR TRANCHE</div>

            <div class='period-info'>
                <div>Période: du " . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . " au " . \Carbon\Carbon::parse($endDate)->format('d/m/Y') . "</div>
                <div>Classe: {$className}</div>
                <div>Année scolaire: {$workingYear->name}</div>
                <div>Tranches sélectionnées: " . $selectedTranches->pluck('name')->implode(', ') . "</div>
            </div>

            <div class='summary'>
                <strong>Résumé:</strong> {$totalStudents} étudiant(s) | " . count($selectedTranches) . " tranche(s) sélectionnée(s)
            </div>";

        if (empty($studentData)) {
            $html .= "<div class='no-data'>Aucun étudiant trouvé pour cette période</div>";
        } else {
            $html .= "
            <table class='payment-table'>
                <thead>
                    <tr>
                        <th style='width: 5%;'>N°</th>
                        <th style='width: 20%;'>Nom et Prénom</th>
                        <th style='width: 15%;'>Classe</th>";

            // Colonnes pour chaque tranche
            foreach ($selectedTranches as $tranche) {
                $html .= "<th style='width: {$trancheWidth}%;' class='tranche-header'>{$tranche->name}<br><small>(Montant / Date)</small></th>";
            }

            $html .= "
                    </tr>
                </thead>
                <tbody>";

            $counter = 1;
            foreach ($studentData as $data) {
                $student = $data['student'];
                $studentName = ($student->last_name ?? '') . ' ' . ($student->first_name ?? '');
                $className = 'N/A';
                if ($student->classSeries && $student->classSeries->schoolClass) {
                    $className = $student->classSeries->schoolClass->name;
                    if ($student->classSeries->name) {
                        $className .= ' - ' . $student->classSeries->name;
                    }
                }

                $html .= "
                    <tr class='student-row'>
                        <td class='center'>{$counter}</td>
                        <td>{$studentName}</td>
                        <td>{$className}</td>";

                // Données pour chaque tranche
                foreach ($selectedTranches as $tranche) {
                    $trancheData = $data['payments_by_tranche'][$tranche->id];
                    $amount = $trancheData['amount_paid'];
                    $hasPayment = $trancheData['has_payment'] ?? false;
                    $wasReduced = $trancheData['was_reduced'] ?? false;
                    $lastPaymentDate = $trancheData['last_payment_date'];
                    $validationDate = $trancheData['validation_date'] ?? null;

                    if ($amount > 0) {
                        // Montant payé normalement
                        $dateStr = $validationDate
                            ? \Carbon\Carbon::parse($validationDate)->format('d/m/Y')
                            : ($lastPaymentDate ? \Carbon\Carbon::parse($lastPaymentDate)->format('d/m/Y') : '');
                        $html .= "<td class='center'>" . $formatAmount($amount) . " FCFA<br><small>{$dateStr}</small></td>";
                    } elseif ($hasPayment && $wasReduced) {
                        // Payé avec réduction complète (montant = 0)
                        $dateStr = $validationDate
                            ? \Carbon\Carbon::parse($validationDate)->format('d/m/Y')
                            : ($lastPaymentDate ? \Carbon\Carbon::parse($lastPaymentDate)->format('d/m/Y') : '');
                        $html .= "<td class='center'><small>Payé (réduction)<br>{$dateStr}</small></td>";
                    } else {
                        // Pas de paiement pour cette tranche
                        $html .= "<td class='center'>-</td>";
                    }
                }

                $html .= "</tr>";
                $counter++;
            }

            $html .= "
                </tbody>
            </table>";
        }

        $html .= "
            <div class='footer'>
                <div>Document généré le " . now()->format('d/m/Y à H:i') . "</div>
                <div>Système de Gestion Scolaire - " . ($schoolSettings->school_name ?? 'École') . "</div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Générer le rapport d'état des RAMEs par série
     */
    public function generateRameSeriesReport(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'series_id' => 'required|exists:class_series,id',
                'format' => 'string|in:html,pdf'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $seriesId = $request->series_id;
            $format = $request->get('format', 'html');

            // Récupérer la série avec ses informations
            $series = \App\Models\ClassSeries::with(['schoolClass.level.section'])
                ->find($seriesId);

            if (!$series) {
                return response()->json(['success' => false, 'message' => 'Série non trouvée'], 404);
            }

            // Récupérer tous les étudiants de la série
            $students = Student::with([
                'classSeries.schoolClass',
                'rameStatus' => function($query) use ($workingYear) {
                    $query->where('school_year_id', $workingYear->id);
                }
            ])
            ->where('class_series_id', $seriesId)
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

            // Préparer les données pour chaque étudiant
            $studentData = [];
            $summary = [
                'total_students' => $students->count(),
                'rame_brought' => 0,
                'rame_not_brought' => 0,
                'serie_info' => [
                    'name' => $series->name,
                    'class_name' => $series->schoolClass->name,
                    'level_name' => $series->schoolClass->level->name,
                    'section_name' => $series->schoolClass->level->section->name
                ]
            ];

            foreach ($students as $student) {
                $rameStatus = $student->rameStatus->first();
                $hasRameBrought = $rameStatus && $rameStatus->has_brought_rame;

                $status = $hasRameBrought ? 'brought' : 'not_brought';

                if ($hasRameBrought) {
                    $summary['rame_brought']++;
                } else {
                    $summary['rame_not_brought']++;
                }

                $studentData[] = [
                    'student' => $student,
                    'rame_status' => $rameStatus,
                    'has_rame_brought' => $hasRameBrought,
                    'status' => $status,
                    'marked_date' => $rameStatus ? $rameStatus->marked_date : null,
                    'deposit_date' => $rameStatus ? $rameStatus->deposit_date : null
                ];
            }

            $schoolSettings = \App\Models\SchoolSetting::getSettings();
            $html = $this->generateRameSeriesHtml($studentData, $series, $summary, $schoolSettings, $workingYear);

            if ($format === 'pdf') {
                $pdf = Pdf::loadHtml($html);
                $pdf->setPaper('A4', 'portrait');

                $filename = 'etat_rame_' . \Str::slug($series->name) . '_' . date('Y-m-d_H-i-s') . '.pdf';

                return $pdf->download($filename);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html,
                    'summary' => $summary,
                    'series' => $series
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PaymentController@generateRameSeriesReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport RAME',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le rapport d'état des RAMEs par série
     */
    private function generateRameSeriesHtml($studentData, $series, $summary, $schoolSettings, $workingYear)
    {
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';
        if ($schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
            }
        }

        $serieInfo = $summary['serie_info'];
        $serieFullName = "{$serieInfo['section_name']} - {$serieInfo['level_name']} - {$serieInfo['class_name']} - {$serieInfo['name']}";

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>État des RAMEs - {$series->name}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 15px; font-size: 11px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
                .logo { max-height: 60px; margin-bottom: 10px; }
                .school-info { margin-bottom: 5px; font-weight: bold; }
                .title { font-size: 16px; font-weight: bold; margin: 15px 0; text-decoration: underline; }
                .series-info { margin: 10px 0; font-weight: bold; }
                .summary { background-color: #f5f5f5; padding: 10px; margin: 15px 0; border: 1px solid #ddd; }
                .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }
                .summary-item { text-align: center; padding: 8px; border: 1px solid #ccc; }
                .status-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .status-table th, .status-table td { border: 1px solid #000; padding: 4px; text-align: left; }
                .status-table th { background-color: #e0e0e0; font-weight: bold; text-align: center; font-size: 10px; }
                .center { text-align: center; }
                .status-physical { background-color: #d4edda; color: #155724; font-weight: bold; }
                .status-paid { background-color: #cce5ff; color: #004085; font-weight: bold; }
                .status-not-paid { background-color: #f8d7da; color: #721c24; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 10px; }
                .no-data { text-align: center; padding: 30px; font-style: italic; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo' class='logo'>" : "") . "
                <div class='school-info'>" . ($schoolSettings->school_name ?? 'École') . "</div>
                <div>" . ($schoolSettings->school_address ?? '') . "</div>
                <div>Tél: " . ($schoolSettings->school_phone ?? '') . " | Email: " . ($schoolSettings->school_email ?? '') . "</div>
            </div>

            <div class='title'>ÉTAT DES RAMES PAR SÉRIE</div>

            <div class='series-info'>
                <div>Série: {$serieFullName}</div>
                <div>Année scolaire: {$workingYear->name}</div>
            </div>

            <div class='summary'>
                <strong>Résumé:</strong>
                <div class='summary-grid'>
                    <div class='summary-item'>
                        <div style='font-size: 18px; color: #007bff;'>{$summary['total_students']}</div>
                        <div>Total Étudiants</div>
                    </div>
                    <div class='summary-item'>
                        <div style='font-size: 18px; color: #28a745;'>{$summary['rame_physical']}</div>
                        <div>RAME Physique</div>
                    </div>
                    <div class='summary-item'>
                        <div style='font-size: 18px; color: #17a2b8;'>{$summary['rame_paid']}</div>
                        <div>RAME Payée</div>
                    </div>
                    <div class='summary-item'>
                        <div style='font-size: 18px; color: #dc3545;'>{$summary['rame_not_paid']}</div>
                        <div>Non Payée</div>
                    </div>
                </div>
            </div>";

        if (empty($studentData)) {
            $html .= "<div class='no-data'>Aucun étudiant trouvé pour cette série</div>";
        } else {
            $html .= "
            <table class='status-table'>
                <thead>
                    <tr>
                        <th style='width: 8%;'>N°</th>
                        <th style='width: 35%;'>Nom et Prénom</th>
                        <th style='width: 20%;'>Statut RAME</th>
                        <th style='width: 18%;'>Date Marquée</th>
                        <th style='width: 19%;'>Date Dépôt</th>
                    </tr>
                </thead>
                <tbody>";

            $counter = 1;
            foreach ($studentData as $data) {
                $student = $data['student'];
                $studentName = ($student->last_name ?? '') . ' ' . ($student->first_name ?? '');

                $statusText = '';
                $statusClass = '';
                switch ($data['status']) {
                    case 'physical':
                        $statusText = 'RAME Physique';
                        $statusClass = 'status-physical';
                        break;
                    case 'paid':
                        $statusText = 'RAME Payée';
                        $statusClass = 'status-paid';
                        break;
                    default:
                        $statusText = 'Non Payée';
                        $statusClass = 'status-not-paid';
                        break;
                }

                $markedDate = $data['marked_date'] ? \Carbon\Carbon::parse($data['marked_date'])->format('d/m/Y') : '-';
                $depositDate = $data['deposit_date'] ? \Carbon\Carbon::parse($data['deposit_date'])->format('d/m/Y') : '-';

                $html .= "
                    <tr>
                        <td class='center'>{$counter}</td>
                        <td>{$studentName}</td>
                        <td class='{$statusClass} center'>{$statusText}</td>
                        <td class='center'>{$markedDate}</td>
                        <td class='center'>{$depositDate}</td>
                    </tr>";
                $counter++;
            }

            $html .= "
                </tbody>
            </table>";
        }

        $html .= "
            <div class='footer'>
                <div>Document généré le " . now()->format('d/m/Y à H:i') . "</div>
                <div>Système de Gestion Scolaire - " . ($schoolSettings->school_name ?? 'École') . "</div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Récupérer toutes les tranches de paiement disponibles
     */
    public function getPaymentTranches()
    {
        try {
            $tranches = PaymentTranche::where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'name', 'order']);

            return response()->json([
                'success' => true,
                'data' => $tranches
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@getPaymentTranches: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tranches',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
