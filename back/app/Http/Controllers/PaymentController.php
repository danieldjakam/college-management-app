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
use App\Models\SchoolSetting;

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
                    'deadline' => $paymentStatus->discount_deadline ? $paymentStatus->discount_deadline->format('d/m/Y') : null,
                    'percentage' => $paymentStatus->discount_percentage,
                    'amount' => $paymentStatus->discount_amount,
                    'amount_to_pay_with_discount' => $paymentStatus->amount_to_pay_with_discount,
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
            
            // Si le frontend demande explicitement une réduction globale
            if ($request->apply_global_discount === true) {
                \Log::info('Frontend requests global discount');
                
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

            $receiptNumber = Payment::generateReceiptNumber($workingYear, $request->payment_date);

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
                $this->allocatePaymentToTranchesWithGlobalDiscount($payment, $student, $workingYear, $paymentStatus->payment_tranches);
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
     * Marquer la RAME comme payée physiquement
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

            // Récupérer la tranche RAME
            $rameTranche = PaymentTranche::where('name', 'RAME')->first();
            if (!$rameTranche) {
                return response()->json(['success' => false, 'message' => 'Tranche RAME non trouvée'], 404);
            }

            // Vérifier si la RAME n'a pas déjà été payée
            $existingRamePayment = Payment::where('student_id', $studentId)
                ->where('school_year_id', $workingYear->id)
                ->where('is_rame_physical', true)
                ->first();
            
            if ($existingRamePayment) {
                return response()->json([
                    'success' => false, 
                    'message' => 'La RAME a déjà été payée physiquement pour cet étudiant'
                ], 422);
            }

            // Vérifier si la RAME n'a pas été payée électroniquement
            $existingElectronicRame = PaymentDetail::whereHas('payment', function($query) use ($studentId, $workingYear) {
                $query->where('student_id', $studentId)
                      ->where('school_year_id', $workingYear->id)
                      ->where('is_rame_physical', false);
            })->where('payment_tranche_id', $rameTranche->id)
              ->where('amount_allocated', '>', 0)
              ->first();

            if ($existingElectronicRame) {
                return response()->json([
                    'success' => false, 
                    'message' => 'La RAME a déjà été payée électroniquement pour cet étudiant'
                ], 422);
            }

            DB::beginTransaction();

            $receiptNumber = Payment::generateReceiptNumber($workingYear, $request->payment_date);

            // Créer le paiement RAME physique
            $payment = Payment::create([
                'student_id' => $studentId,
                'school_year_id' => $workingYear->id,
                'total_amount' => $rameTranche->default_amount,
                'payment_date' => $request->payment_date,
                'versement_date' => $request->versement_date,
                'validation_date' => now(),
                'payment_method' => 'rame_physical',
                'reference_number' => null,
                'notes' => $request->notes ?? 'Paiement RAME physique',
                'created_by_user_id' => Auth::id(),
                'receipt_number' => $receiptNumber,
                'is_rame_physical' => true,
                'has_scholarship' => false,
                'scholarship_amount' => 0,
                'has_reduction' => false,
                'reduction_amount' => 0,
                'discount_reason' => null
            ]);

            // Créer le détail de paiement pour la tranche RAME
            PaymentDetail::create([
                'payment_id' => $payment->id,
                'payment_tranche_id' => $rameTranche->id,
                'amount_allocated' => $rameTranche->default_amount,
                'previous_amount' => 0,
                'new_total_amount' => $rameTranche->default_amount,
                'is_fully_paid' => true,
                'required_amount_at_time' => $rameTranche->default_amount,
                'was_reduced' => false,
                'reduction_context' => null
            ]);

            DB::commit();

            // Envoyer la notification WhatsApp
            try {
                $whatsAppService = new \App\Services\WhatsAppService();
                $whatsAppService->sendPaymentNotification($payment);
            } catch (\Exception $e) {
                Log::warning('Erreur lors de l\'envoi de la notification WhatsApp pour paiement RAME physique', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
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

            // Récupérer la tranche RAME
            $rameTranche = PaymentTranche::where('name', 'RAME')->first();
            if (!$rameTranche) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'rame_available' => false,
                        'message' => 'La tranche RAME n\'est pas configurée'
                    ]
                ]);
            }

            // Vérifier si la RAME a été payée physiquement
            $physicalRamePayment = Payment::where('student_id', $studentId)
                ->where('school_year_id', $workingYear->id)
                ->where('is_rame_physical', true)
                ->first();

            // Vérifier si la RAME a été payée électroniquement
            $electronicRamePayment = PaymentDetail::whereHas('payment', function($query) use ($studentId, $workingYear) {
                $query->where('student_id', $studentId)
                      ->where('school_year_id', $workingYear->id)
                      ->where('is_rame_physical', false);
            })->where('payment_tranche_id', $rameTranche->id)
              ->where('amount_allocated', '>', 0)
              ->first();

            $status = [
                'rame_available' => true,
                'amount' => $rameTranche->default_amount,
                'is_paid' => false,
                'payment_type' => null,
                'can_pay_physically' => false,
                'can_pay_electronically' => false,
                'payment_details' => null
            ];

            if ($physicalRamePayment) {
                $status['is_paid'] = true;
                $status['payment_type'] = 'physical';
                $status['payment_details'] = [
                    'payment_id' => $physicalRamePayment->id,
                    'payment_date' => $physicalRamePayment->payment_date,
                    'receipt_number' => $physicalRamePayment->receipt_number,
                    'amount' => $physicalRamePayment->total_amount
                ];
            } elseif ($electronicRamePayment) {
                $status['is_paid'] = true;
                $status['payment_type'] = 'electronic';
                $status['payment_details'] = [
                    'payment_id' => $electronicRamePayment->payment_id,
                    'amount_allocated' => $electronicRamePayment->amount_allocated,
                    'payment_date' => $electronicRamePayment->payment->payment_date ?? null
                ];
            } else {
                // RAME non payée - les deux options sont disponibles
                $status['can_pay_physically'] = true;
                $status['can_pay_electronically'] = true;
            }

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
     * Allouer le paiement aux tranches avec réduction globale
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
            $receiptHtml = $this->generateReceiptHtml($payment, $schoolSettings);

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $receiptHtml,
                    'payment' => $payment
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
     * Générer le HTML du reçu de paiement
     */
    private function generateReceiptHtml($payment, $schoolSettings)
    {
        $student = $payment->student;
        $schoolClass = $student->classSeries->schoolClass ?? null;
        
        // Formatage des montants
        $formatAmount = function($amount) {
            return number_format($amount, 0, ',', ' ');
        };

        // Obtenir le statut récapitulatif des paiements AU MOMENT de ce paiement
        $workingYear = $payment->schoolYear;
        $paymentStatus = $this->getPaymentStatusAtTime($student, $payment);

        // Générer le tableau des détails de paiement
        $paymentDetailsRows = '';
        $operationNumber = 1;
        foreach ($payment->paymentDetails as $detail) {
            $trancheName = $detail->paymentTranche->name;
            $bankName = $schoolSettings->bank_name ?? 'N/A';
            $validationDate = \Carbon\Carbon::parse($payment->versement_date)->format('d/m/Y');
            $paymentType = $trancheName; // Afficher la tranche affectée
            $amount = $formatAmount($detail->amount_allocated);
            
            $paymentDetailsRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$operationNumber}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$bankName}</td>
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
            
            $recapRows .= "
                <tr>
                    <td style='border: 1px solid #000; padding: 4px; text-align: center;'>{$tranche['tranche']->name}</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($trancheRequired) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($tranchePaid) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($discountAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($scholarshipAmount) . "</td>
                    <td style='border: 1px solid #000; padding: 4px; text-align: right;'>" . $formatAmount($effectiveRemaining) . "</td>
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
            </tr>
        ";

        // Informations sur les avantages
        $benefitInfo = '';
        if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
            $benefitInfo = "Bourse: " . $formatAmount($payment->scholarship_amount) . " FCFA";
        } elseif ($payment->has_reduction && $payment->reduction_amount > 0) {
            $benefitInfo = "Réduction: " . $formatAmount($payment->reduction_amount) . " FCFA";
        }

        // HTML du reçu selon le format de l'exemple
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Reçu de Paiement - {$payment->receipt_number}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    font-size: 12px;
                    line-height: 1.2;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    position: relative;
                }
                .logo {
                    position: absolute;
                    left: 20px;
                    top: 0;
                    width: 60px;
                    height: 60px;
                    object-fit: contain;
                }
                .school-name { 
                    font-size: 16px; 
                    font-weight: bold; 
                    margin-bottom: 5px;
                }
                .academic-year {
                    font-size: 12px;
                    margin-bottom: 10px;
                }
                .receipt-title { 
                    font-size: 14px; 
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 15px 0;
                }
                .student-info {
                    margin: 15px 0;
                    text-align: left;
                }
                .student-info div {
                    margin: 3px 0;
                }
                .payment-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    font-size: 11px;
                }
                .payment-table th {
                    border: 1px solid #000;
                    padding: 4px;
                    background-color: #f0f0f0;
                    text-align: center;
                    font-weight: bold;
                }
                .recap-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    font-size: 11px;
                }
                .recap-table th {
                    border: 1px solid #000;
                    padding: 4px;
                    background-color: #f0f0f0;
                    text-align: center;
                    font-weight: bold;
                }
                .footer-info {
                    margin-top: 20px;
                    font-size: 10px;
                    line-height: 1.4;
                }
                .signature-section {
                    margin-top: 30px;
                    text-align: right;
                    font-size: 11px;
                }
                .date-time {
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    font-size: 10px;
                }
                .float-right {
                    float: right;
                }
                .clearfix {
                    clear: both;
                }
            </style>
        </head>
        <body>
            <div class='date-time'>
                Le " . now()->format('d/m/Y H:i:s') . "
            </div>
            
            <div class='header'>
                " . ($schoolSettings->school_logo ? "<img src='" . url('storage/' . $schoolSettings->school_logo) . "' alt='Logo école' class='logo'>" : "") . "
                <div class='school-name'>{$schoolSettings->school_name}</div>
                <div class='academic-year'>Année académique : " . $workingYear->name . "</div>
                <div class='receipt-title'>REÇU PENSION/FRAIS DIVERS</div>
            </div>

            <div class='student-info'>
                <div><strong>Matricule :</strong> " . ($student->student_number ?? 'N/A') . "</div>
                <div><strong>Nom :</strong> {$student->last_name} {$student->first_name} <span class='float-right'><strong>Prénom :</strong></span></div>
                <div><strong>Classe :</strong> " . ($schoolClass ? $schoolClass->name : 'Non défini') . "</div>
                <div><strong>Inscription :</strong> " . $formatAmount($paymentStatus->tranche_status[0]['required_amount'] ?? 0) . " <span style='margin-left: 50px;'>Le " . \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') . "</span> <span class='float-right'><strong>Banque :</strong> " . ($schoolSettings->bank_name ?? 'N/A') . "</span></div>
                " . ($benefitInfo ? "<div><strong>Motif ou rabais :</strong> {$benefitInfo}</div>" : "") . "
            </div>

            <table class='payment-table'>
                <thead>
                    <tr>
                        <th>N° Op</th>
                        <th>Banque</th>
                        <th>Date validation</th>
                        <th>Tranche affectée</th>
                        <th>Montant payé</th>
                    </tr>
                </thead>
                <tbody>
                    {$paymentDetailsRows}
                </tbody>
            </table>

            <div style='margin: 20px 0;'>
                <strong>Reste à payer par tranche</strong>
                <table class='recap-table' style='width: 100%;'>
                    <thead>
                        <tr>
                            <th>Tranche</th>
                            <th>Montant normal</th>
                            <th>Montant payé</th>
                            <th>Réduction</th>
                            <th>Bourse</th>
                            <th>Reste à payer</th>
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
                <br>
                <div style='display: flex; justify-content: space-between;'>
                    <div>
                        <div><strong>B.P :</strong> " . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[0] : '4100') . "</div>
                        <div><strong>Tél :</strong> " . ($schoolSettings->school_phone ?? '233 43 25 47') . "</div>
                        <div><strong>Site web :</strong> " . ($schoolSettings->website ?? 'www.cpdyassa.com') . "</div>
                    </div>
                    <div>
                        <div>" . ($schoolSettings->school_address ? explode(',', $schoolSettings->school_address)[1] ?? 'Douala' : 'Douala') . "</div>
                        <div><strong>Email :</strong> " . ($schoolSettings->school_email ?? 'contact@cpdyassa.com') . "</div>
                    </div>
                </div>
            </div>

            <div class='signature-section'>
                <div>Validé par " . ($payment->createdByUser ? $payment->createdByUser->name : 'Comptable') . "</div>
                <div style='margin-top: 30px;'>_____________________</div>
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
        
        // Récupérer toutes les tranches
        $paymentTranches = \App\Models\PaymentTranche::active()
            ->ordered()
            ->with(['classPaymentAmounts' => function ($query) use ($student) {
                if ($student->classSeries && $student->classSeries->schoolClass) {
                    $query->where('class_id', $student->classSeries->schoolClass->id);
                }
            }])
            ->get();

        // Récupérer tous les paiements JUSQU'À et Y COMPRIS ce paiement spécifique
        $paymentsUpToThis = \App\Models\Payment::forStudent($student->id)
            ->forYear($workingYear->id)
            ->where('is_rame_physical', false)
            ->where('created_at', '<=', $specificPayment->created_at)
            ->with(['paymentDetails.paymentTranche'])
            ->orderBy('payment_date', 'asc')
            ->get();

        // Calculer les montants payés par tranche jusqu'à ce moment
        $paidPerTranche = [];
        $discountPerTranche = [];
        foreach ($paymentsUpToThis as $payment) {
            foreach ($payment->paymentDetails as $detail) {
                if (!isset($paidPerTranche[$detail->payment_tranche_id])) {
                    $paidPerTranche[$detail->payment_tranche_id] = 0;
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        'has_discount' => false,
                        'discount_amount' => 0
                    ];
                }
                $paidPerTranche[$detail->payment_tranche_id] += $detail->amount_allocated;
                
                // Vérifier si ce détail a une réduction globale
                if ($detail->was_reduced && strpos($detail->reduction_context, 'Réduction globale') !== false) {
                    $schoolSettings = \App\Models\SchoolSetting::getSettings();
                    $discountPercentage = $schoolSettings->reduction_percentage ?? 0;
                    
                    $reducedAmount = $detail->required_amount_at_time;
                    $normalAmount = round($reducedAmount / (1 - $discountPercentage / 100), 0);
                    $discountAmount = $normalAmount - $reducedAmount;
                    
                    $discountPerTranche[$detail->payment_tranche_id] = [
                        'has_discount' => true,
                        'discount_amount' => $discountAmount
                    ];
                }
            }
        }

        // Récupérer les informations de bourse (elles sont statiques par classe)
        $discountCalculator = new \App\Services\DiscountCalculatorService();
        $scholarship = $discountCalculator->getClassScholarship($student);

        // Construire le statut des tranches au moment du paiement
        $trancheStatus = [];
        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false);
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;
            $remainingAmount = max(0, $requiredAmount - $paidAmount);

            // Vérifier si cette tranche bénéficie d'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            
            if ($scholarship && $scholarship->payment_tranche_id == $tranche->id) {
                $scholarshipAmount = $scholarship->amount;
                $hasScholarship = true;
            } else {
                $discountInfo = $discountPerTranche[$tranche->id] ?? ['has_discount' => false, 'discount_amount' => 0];
                if ($discountInfo['has_discount']) {
                    $hasGlobalDiscount = true;
                    $globalDiscountAmount = $discountInfo['discount_amount'];
                }
            }

            $trancheStatus[] = [
                'tranche' => $tranche,
                'required_amount' => $requiredAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'has_scholarship' => $hasScholarship,
                'scholarship_amount' => $scholarshipAmount,
                'has_global_discount' => $hasGlobalDiscount,
                'global_discount_amount' => $globalDiscountAmount,
            ];
        }

        // Retourner un objet similaire à PaymentStatusService
        return (object) [
            'tranche_status' => $trancheStatus
        ];
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
                return 'ESP'; // Espèces
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
}