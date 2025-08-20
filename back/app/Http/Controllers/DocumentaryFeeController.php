<?php

namespace App\Http\Controllers;

use App\Models\DocumentaryFee;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentaryFeeController extends Controller
{
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

    /**
     * Afficher la liste des frais de dossiers
     */
    public function index(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $query = DocumentaryFee::with(['student', 'schoolYear', 'createdByUser', 'validatedByUser'])
                ->forYear($workingYear->id);

            // Filtres
            if ($request->has('student_id') && $request->student_id) {
                $query->forStudent($request->student_id);
            }

            if ($request->has('has_penalty') && $request->has_penalty !== '') {
                if ($request->boolean('has_penalty')) {
                    $query->withPenalty();
                } else {
                    $query->withoutPenalty();
                }
            }

            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->betweenDates($request->start_date, $request->end_date);
            }

            $documentaryFees = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $documentaryFees,
                'working_year' => $workingYear
            ]);
        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des frais de dossiers'
            ], 500);
        }
    }

    /**
     * Créer un nouveau frais de dossier
     */
    public function store(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:students,id',
                'description' => 'nullable|string|max:255',
                'fee_amount' => 'required|numeric|min:0',
                'penalty_amount' => 'nullable|numeric|min:0',
                'payment_date' => 'required|date',
                'versement_date' => 'nullable|date',
                'payment_method' => 'required|in:cash,cheque,transfer,mobile_money',
                'reference_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que l'étudiant existe
            $student = Student::find($request->student_id);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
            }

            DB::beginTransaction();

            // Calculer les montants
            $feeAmount = (float) $request->fee_amount;
            $penaltyAmount = (float) ($request->penalty_amount ?? 0);
            $totalAmount = $feeAmount + $penaltyAmount;

            // Générer le numéro de reçu
            $hasPenalty = $penaltyAmount > 0;
            $receiptNumber = DocumentaryFee::generateReceiptNumber($workingYear, $hasPenalty, $request->payment_date);

            // Créer le frais de dossier
            $documentaryFee = DocumentaryFee::create([
                'student_id' => $request->student_id,
                'school_year_id' => $workingYear->id,
                'fee_type' => 'frais_dossier',
                'description' => $request->description,
                'fee_amount' => $feeAmount,
                'penalty_amount' => $penaltyAmount,
                'total_amount' => $totalAmount,
                'payment_date' => $request->payment_date,
                'versement_date' => $request->versement_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'receipt_number' => $receiptNumber,
                'notes' => $request->notes,
                'status' => 'validated',
                'validation_date' => now(),
                'validated_by_user_id' => Auth::id(),
                'created_by_user_id' => Auth::id()
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $documentaryFee->load(['student', 'schoolYear', 'createdByUser']);

            return response()->json([
                'success' => true,
                'message' => 'Frais de dossier enregistré avec succès',
                'data' => $documentaryFee
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in DocumentaryFeeController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du frais de dossier'
            ], 500);
        }
    }

    /**
     * Afficher un frais de dossier spécifique
     */
    public function show($id)
    {
        try {
            $documentaryFee = DocumentaryFee::with(['student', 'schoolYear', 'createdByUser', 'validatedByUser'])
                ->find($id);

            if (!$documentaryFee) {
                return response()->json(['success' => false, 'message' => 'Frais de dossier non trouvé'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $documentaryFee
            ]);
        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du frais de dossier'
            ], 500);
        }
    }

    /**
     * Mettre à jour un frais de dossier
     */
    public function update(Request $request, $id)
    {
        try {
            $documentaryFee = DocumentaryFee::find($id);
            if (!$documentaryFee) {
                return response()->json(['success' => false, 'message' => 'Frais de dossier non trouvé'], 404);
            }

            // Vérifier que le frais n'est pas déjà validé
            if ($documentaryFee->status === 'validated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de modifier un frais déjà validé'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'description' => 'nullable|string|max:255',
                'fee_amount' => 'sometimes|numeric|min:0',
                'penalty_amount' => 'nullable|numeric|min:0',
                'payment_date' => 'sometimes|date',
                'versement_date' => 'nullable|date',
                'payment_method' => 'sometimes|in:cash,cheque,transfer,mobile_money',
                'reference_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Préparer les données de mise à jour
            $updateData = $request->only([
                'description', 'payment_date', 'versement_date',
                'payment_method', 'reference_number', 'notes'
            ]);

            // Recalculer le montant total si les montants sont modifiés
            if ($request->has('fee_amount') || $request->has('penalty_amount')) {
                $feeAmount = $request->has('fee_amount') ? (float) $request->fee_amount : $documentaryFee->fee_amount;
                $penaltyAmount = $request->has('penalty_amount') ? (float) ($request->penalty_amount ?? 0) : $documentaryFee->penalty_amount;
                $totalAmount = $feeAmount + $penaltyAmount;

                $updateData['fee_amount'] = $feeAmount;
                $updateData['penalty_amount'] = $penaltyAmount;
                $updateData['total_amount'] = $totalAmount;
            }

            $documentaryFee->update($updateData);

            $documentaryFee->load(['student', 'schoolYear', 'createdByUser', 'validatedByUser']);

            return response()->json([
                'success' => true,
                'message' => 'Frais de dossier mis à jour avec succès',
                'data' => $documentaryFee
            ]);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du frais de dossier'
            ], 500);
        }
    }


    /**
     * Supprimer un frais de dossier
     */
    public function destroy($id)
    {
        try {
            $documentaryFee = DocumentaryFee::find($id);
            if (!$documentaryFee) {
                return response()->json(['success' => false, 'message' => 'Frais de dossier non trouvé'], 404);
            }

            // Seuls les frais en attente peuvent être supprimés
            if ($documentaryFee->status === 'validated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un frais validé'
                ], 422);
            }

            $documentaryFee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Frais de dossier supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du frais de dossier'
            ], 500);
        }
    }

    /**
     * Générer un reçu PDF pour un frais de dossier
     */
    public function generateReceipt($id)
    {
        try {
            $documentaryFee = DocumentaryFee::with(['student.classSeries.schoolClass', 'schoolYear', 'createdByUser'])
                ->find($id);

            if (!$documentaryFee) {
                return response()->json(['success' => false, 'message' => 'Frais de dossier non trouvé'], 404);
            }

            // Obtenir les paramètres de l'école
            $schoolSettings = SchoolSetting::all()->pluck('value', 'key');

            $data = [
                'documentary_fee' => $documentaryFee,
                'school_settings' => $schoolSettings,
                'generated_at' => now()->format('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('receipts.documentary_fee', $data);
            $filename = "recu_frais_dossier_{$documentaryFee->receipt_number}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@generateReceipt: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du reçu'
            ], 500);
        }
    }

    /**
     * Rechercher des étudiants pour le formulaire
     */
    public function searchStudents(Request $request)
    {
        try {
            $query = $request->get('q', '');

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $students = Student::with(['classSeries.schoolClass'])
                ->where('school_year_id', $workingYear->id)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('first_name', 'like', '%' . $query . '%')
                      ->orWhere('last_name', 'like', '%' . $query . '%')
                      ->orWhere('student_number', 'like', '%' . $query . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $query . '%']);
                })
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students
            ]);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@searchStudents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Générer un rapport PDF de tous les versements sur une période
     */
    public function generatePeriodReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'has_penalty' => 'nullable|boolean'
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

            // Requête avec filtres
            $query = DocumentaryFee::with(['student.classSeries.schoolClass', 'schoolYear', 'createdByUser'])
                ->forYear($workingYear->id)
                ->betweenDates($request->start_date, $request->end_date)
                ->where('status', 'validated') // Seulement les validés
                ->orderBy('payment_date', 'asc');

            // Filtre pénalité si spécifié
            if ($request->has('has_penalty') && $request->has_penalty !== null) {
                if ($request->boolean('has_penalty')) {
                    $query->where('penalty_amount', '>', 0);
                } else {
                    $query->where('penalty_amount', '=', 0);
                }
            }

            $documentaryFees = $query->get();

            if ($documentaryFees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun versement trouvé pour cette période'
                ], 404);
            }

            // Calculs statistiques
            $totalAmount = $documentaryFees->sum('total_amount');
            $totalFeeAmount = $documentaryFees->sum('fee_amount');
            $totalPenaltyAmount = $documentaryFees->sum('penalty_amount');
            $feesWithPenalty = $documentaryFees->where('penalty_amount', '>', 0)->count();
            $feesWithoutPenalty = $documentaryFees->where('penalty_amount', '=', 0)->count();

            // Obtenir les paramètres de l'école
            $schoolSettings = SchoolSetting::all()->pluck('value', 'key');

            $data = [
                'documentary_fees' => $documentaryFees,
                'school_settings' => $schoolSettings,
                'working_year' => $workingYear,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'statistics' => [
                    'total_count' => $documentaryFees->count(),
                    'total_amount' => $totalAmount,
                    'total_fee_amount' => $totalFeeAmount,
                    'total_penalty_amount' => $totalPenaltyAmount,
                    'fees_with_penalty' => $feesWithPenalty,
                    'fees_without_penalty' => $feesWithoutPenalty,
                ]
            ];

            $pdf = Pdf::loadView('reports.documentary_fees_period', $data);
            $filename = "rapport_frais_dossiers_" . $request->start_date . "_au_" . $request->end_date . ".pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@generatePeriodReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des frais de dossiers
     */
    public function getStatistics(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            if (!$workingYear) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire définie'], 400);
            }

            $query = DocumentaryFee::forYear($workingYear->id);

            // Filtres de date
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->betweenDates($request->start_date, $request->end_date);
            }

            $totalFees = $query->count();
            $totalAmount = $query->sum('total_amount');
            $validatedFees = $query->validated()->count();
            $validatedAmount = $query->validated()->sum('total_amount');

            // Répartition par type (toujours frais_dossier, mais avec/sans pénalité)
            $totalAmount = $query->sum('total_amount');
            $totalFeeAmount = $query->sum('fee_amount');
            $totalPenaltyAmount = $query->sum('penalty_amount');
            $feesWithPenalty = $query->where('penalty_amount', '>', 0)->count();
            $feesWithoutPenalty = $query->where('penalty_amount', '=', 0)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_fees' => $totalFees,
                    'total_amount' => $totalAmount,
                    'validated_fees' => $validatedFees,
                    'validated_amount' => $validatedAmount,
                    'fees_with_penalty' => $feesWithPenalty,
                    'fees_without_penalty' => $feesWithoutPenalty,
                    'total_fee_amount' => $totalFeeAmount,
                    'total_penalty_amount' => $totalPenaltyAmount,
                    'working_year' => $workingYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in DocumentaryFeeController@getStatistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
