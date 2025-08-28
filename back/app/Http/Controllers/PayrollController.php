<?php

namespace App\Http\Controllers;

use App\Models\EmployeePayroll;
use App\Models\PayrollPeriod;
use App\Models\SalaryCut;
use App\Models\Payslip;
use App\Models\PayrollWhatsappNotification;
use App\Models\User;
use App\Services\PayrollWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayrollController extends Controller
{
    protected PayrollWhatsAppService $whatsappService;

    public function __construct(PayrollWhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    // ================================
    // GESTION DES EMPLOYÉS
    // ================================

    /**
     * Liste des employés en paie
     */
    public function getEmployees(Request $request): JsonResponse
    {
        try {
            $query = EmployeePayroll::with('user')
                ->orderBy('nom')
                ->orderBy('prenom');

            // Filtres
            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'LIKE', "%{$search}%")
                      ->orWhere('prenom', 'LIKE', "%{$search}%")
                      ->orWhere('matricule', 'LIKE', "%{$search}%")
                      ->orWhere('poste', 'LIKE', "%{$search}%");
                });
            }

            $employees = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des employés: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un employé
     */
    public function createEmployee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:employees_payroll,user_id',
            'matricule' => 'required|string|unique:employees_payroll,matricule',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'poste' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'salaire_base' => 'required|numeric|min:0',
            'primes_fixes' => 'nullable|numeric|min:0',
            'deductions_fixes' => 'nullable|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement',
            'telephone_whatsapp' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $employee = EmployeePayroll::create($request->all());
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employé ajouté avec succès',
                'data' => $employee->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifier un employé
     */
    public function updateEmployee(Request $request, $id): JsonResponse
    {
        $employee = EmployeePayroll::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'matricule' => 'required|string|unique:employees_payroll,matricule,' . $id,
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'poste' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'salaire_base' => 'required|numeric|min:0',
            'primes_fixes' => 'nullable|numeric|min:0',
            'deductions_fixes' => 'nullable|numeric|min:0',
            'mode_paiement' => 'required|in:especes,cheque,virement',
            'telephone_whatsapp' => 'nullable|string|max:20',
            'statut' => 'required|in:actif,suspendu,conge'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee->update($request->except(['user_id'])); // Ne pas modifier user_id

            return response()->json([
                'success' => true,
                'message' => 'Employé modifié avec succès',
                'data' => $employee->load('user')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs disponibles pour la paie
     */
    public function getAvailableUsers(): JsonResponse
    {
        try {
            $users = User::whereNotIn('id', EmployeePayroll::pluck('user_id'))
                ->where('role', '!=', 'admin')
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // GESTION DES PÉRIODES DE PAIE
    // ================================

    /**
     * Liste des périodes de paie
     */
    public function getPeriods(Request $request): JsonResponse
    {
        try {
            $query = PayrollPeriod::orderBy('annee', 'desc')
                ->orderBy('mois', 'desc');

            if ($request->has('annee') && $request->annee !== '') {
                $query->where('annee', $request->annee);
            }

            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            $periods = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $periods
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une période de paie
     */
    public function createPeriod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020|max:2050'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si la période existe déjà
            $exists = PayrollPeriod::where('mois', $request->mois)
                ->where('annee', $request->annee)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette période de paie existe déjà'
                ], 422);
            }

            $period = PayrollPeriod::createForMonth($request->mois, $request->annee);

            return response()->json([
                'success' => true,
                'message' => 'Période créée avec succès',
                'data' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une période avec statistiques
     */
    public function getPeriodDetails($id): JsonResponse
    {
        try {
            $period = PayrollPeriod::with(['payslips.employee', 'salaryCuts.employee'])
                ->findOrFail($id);

            $stats = [
                'total_employees' => EmployeePayroll::active()->count(),
                'payslips_count' => $period->payslips()->count(),
                'total_salaries' => $period->getTotalSalaries(),
                'total_by_mode' => [
                    'especes' => $period->getTotalBySalaryMode('especes'),
                    'cheque' => $period->getTotalBySalaryMode('cheque'),
                    'virement' => $period->getTotalBySalaryMode('virement')
                ],
                'salary_cuts' => [
                    'count' => $period->getSalaryCutsCount(),
                    'total_amount' => $period->getTotalSalaryCuts()
                ],
                'notifications' => $this->whatsappService->getNotificationStats($period)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // CALCUL DE PAIE
    // ================================

    /**
     * Calculer la paie pour une période
     */
    public function calculatePayroll(Request $request, $periodId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employees' => 'array',
            'employees.*.employee_id' => 'required|exists:employees_payroll,id',
            'employees.*.primes_mensuelles' => 'nullable|numeric|min:0',
            'employees.*.deductions_mensuelles' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $period = PayrollPeriod::findOrFail($periodId);

            if (!$period->canCalculate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette période ne peut pas être calculée'
                ], 422);
            }

            $employeesData = $request->get('employees', []);
            $results = [];

            // Si aucun employé spécifique, calculer pour tous les employés actifs
            if (empty($employeesData)) {
                $employees = EmployeePayroll::active()->get();
                foreach ($employees as $employee) {
                    $payslip = $this->createOrUpdatePayslip($employee, $period, 0, 0);
                    $results[] = $payslip;
                }
            } else {
                // Calculer pour les employés spécifiés
                foreach ($employeesData as $empData) {
                    $employee = EmployeePayroll::findOrFail($empData['employee_id']);
                    $primes = $empData['primes_mensuelles'] ?? 0;
                    $deductions = $empData['deductions_mensuelles'] ?? 0;
                    
                    $payslip = $this->createOrUpdatePayslip($employee, $period, $primes, $deductions);
                    $results[] = $payslip;
                }
            }

            $period->calculate();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paie calculée avec succès',
                'data' => [
                    'period' => $period,
                    'payslips' => $results
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createOrUpdatePayslip(EmployeePayroll $employee, PayrollPeriod $period, float $primes, float $deductions): Payslip
    {
        // Vérifier si le bulletin existe déjà
        $payslip = Payslip::where('employee_id', $employee->id)
            ->where('period_id', $period->id)
            ->first();

        $coupures = $employee->getTotalCoupures($period->id);

        if ($payslip) {
            // Mettre à jour
            $payslip->update([
                'primes_mensuelles' => $primes,
                'deductions_mensuelles' => $deductions + $employee->deductions_fixes,
                'montant_coupures' => $coupures,
            ]);
        } else {
            // Créer nouveau
            $payslip = Payslip::create([
                'employee_id' => $employee->id,
                'period_id' => $period->id,
                'salaire_base' => $employee->salaire_base,
                'primes_mensuelles' => $primes,
                'deductions_mensuelles' => $deductions + $employee->deductions_fixes,
                'montant_coupures' => $coupures,
                'mode_paiement' => $employee->mode_paiement,
                'statut' => 'brouillon'
            ]);
        }

        $payslip->calculateSalary();
        return $payslip->load('employee');
    }

    /**
     * Valider une période de paie
     */
    public function validatePeriod($periodId): JsonResponse
    {
        try {
            $period = PayrollPeriod::findOrFail($periodId);

            if (!$period->canValidate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette période ne peut pas être validée'
                ], 422);
            }

            DB::beginTransaction();

            // Valider tous les bulletins de la période
            $period->payslips()->update(['statut' => 'valide']);
            
            // Marquer la période comme validée
            $period->validate();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Période validée avec succès',
                'data' => $period
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer les salaires comme disponibles et envoyer les notifications
     */
    public function markSalariesAvailable(Request $request, $periodId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_paie' => 'nullable|date',
            'send_notifications' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $period = PayrollPeriod::findOrFail($periodId);

            if (!$period->canMarkAsPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette période ne peut pas être marquée comme payée'
                ], 422);
            }

            DB::beginTransaction();

            $datePaie = $request->date_paie ? Carbon::parse($request->date_paie) : Carbon::now();
            
            // Marquer la période comme payée
            $period->markAsPaid($datePaie);
            
            // Marquer tous les bulletins comme payés
            $period->payslips()->update(['statut' => 'paye']);

            $notificationResults = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

            // Envoyer les notifications si demandé
            if ($request->get('send_notifications', true)) {
                $notificationResults = $this->whatsappService->sendSalaryAvailableNotifications($period);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salaires marqués comme disponibles',
                'data' => [
                    'period' => $period,
                    'notifications' => $notificationResults
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // COUPURES DE SALAIRE
    // ================================

    /**
     * Créer une coupure de salaire
     */
    public function createSalaryCut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees_payroll,id',
            'period_id' => 'required|exists:payroll_periods,id',
            'montant_coupe' => 'required|numeric|min:0',
            'motif' => 'required|string|max:1000',
            'date_coupure' => 'nullable|date',
            'send_notification' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $salaryCut = SalaryCut::create([
                'employee_id' => $request->employee_id,
                'period_id' => $request->period_id,
                'montant_coupe' => $request->montant_coupe,
                'motif' => $request->motif,
                'date_coupure' => $request->date_coupure ?? Carbon::now(),
                'created_by' => auth()->id(),
                'statut' => 'active'
            ]);

            // Mettre à jour le bulletin de paie s'il existe
            $payslip = Payslip::where('employee_id', $request->employee_id)
                ->where('period_id', $request->period_id)
                ->first();

            if ($payslip) {
                $payslip->montant_coupures = $salaryCut->employee->getTotalCoupures($request->period_id);
                $payslip->calculateSalary();
            }

            DB::commit();

            // Envoyer la notification WhatsApp si demandé
            $notificationResult = null;
            if ($request->get('send_notification', true)) {
                $notificationResult = $this->whatsappService->sendSalaryCutNotification($salaryCut);
            }

            return response()->json([
                'success' => true,
                'message' => 'Coupure créée avec succès',
                'data' => [
                    'salary_cut' => $salaryCut->load(['employee', 'period']),
                    'notification' => $notificationResult
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des coupures
     */
    public function getSalaryCuts(Request $request): JsonResponse
    {
        try {
            $query = SalaryCut::with(['employee', 'period', 'createdBy'])
                ->orderBy('created_at', 'desc');

            // Filtres
            if ($request->has('employee_id') && $request->employee_id !== '') {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('period_id') && $request->period_id !== '') {
                $query->where('period_id', $request->period_id);
            }

            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            $cuts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $cuts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler une coupure
     */
    public function cancelSalaryCut($id): JsonResponse
    {
        try {
            $salaryCut = SalaryCut::findOrFail($id);

            if (!$salaryCut->canCancel()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette coupure ne peut pas être annulée'
                ], 422);
            }

            DB::beginTransaction();

            $salaryCut->cancel();

            // Mettre à jour le bulletin de paie
            $payslip = Payslip::where('employee_id', $salaryCut->employee_id)
                ->where('period_id', $salaryCut->period_id)
                ->first();

            if ($payslip) {
                $payslip->montant_coupures = $salaryCut->employee->getTotalCoupures($salaryCut->period_id);
                $payslip->calculateSalary();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Coupure annulée avec succès',
                'data' => $salaryCut
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // BULLETINS DE PAIE
    // ================================

    /**
     * Liste des bulletins pour une période
     */
    public function getPayslips(Request $request, $periodId): JsonResponse
    {
        try {
            $query = Payslip::with(['employee', 'period'])
                ->where('period_id', $periodId)
                ->orderBy('employee.nom');

            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            $payslips = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payslips
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulletins d'un employé
     */
    public function getEmployeePayslips($employeeId): JsonResponse
    {
        try {
            $payslips = Payslip::with(['period'])
                ->where('employee_id', $employeeId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payslips
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un salaire comme retiré
     */
    public function markSalaryAsRetired($payslipId): JsonResponse
    {
        try {
            $payslip = Payslip::findOrFail($payslipId);

            if (!$payslip->canMarkAsRetired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce salaire ne peut pas être marqué comme retiré'
                ], 422);
            }

            $payslip->markAsRetired();

            return response()->json([
                'success' => true,
                'message' => 'Salaire marqué comme retiré',
                'data' => $payslip
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // NOTIFICATIONS
    // ================================

    /**
     * Liste des notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $query = PayrollWhatsappNotification::with(['employee', 'payrollPeriod'])
                ->orderBy('created_at', 'desc');

            // Filtres
            if ($request->has('employee_id') && $request->employee_id !== '') {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('period_id') && $request->period_id !== '') {
                $query->where('payroll_period_id', $request->period_id);
            }

            if ($request->has('type') && $request->type !== '') {
                $query->where('type', $request->type);
            }

            if ($request->has('statut') && $request->statut !== '') {
                $query->where('statut', $request->statut);
            }

            $notifications = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renvoyer une notification
     */
    public function retryNotification($notificationId): JsonResponse
    {
        try {
            $notification = PayrollWhatsappNotification::findOrFail($notificationId);

            $result = $this->whatsappService->retryNotification($notification);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $notification->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du renvoi: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // STATISTIQUES & DASHBOARD
    // ================================

    /**
     * Dashboard principal de la paie
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $currentMonth = Carbon::now();
            
            // Période actuelle
            $currentPeriod = PayrollPeriod::where('mois', $currentMonth->month)
                ->where('annee', $currentMonth->year)
                ->first();

            // Statistiques générales
            $stats = [
                'total_employees' => EmployeePayroll::active()->count(),
                'current_period' => $currentPeriod,
                'periods_this_year' => PayrollPeriod::where('annee', $currentMonth->year)->count(),
                'total_notifications_sent' => PayrollWhatsappNotification::sent()->count(),
                'pending_notifications' => PayrollWhatsappNotification::pending()->count(),
                'active_salary_cuts' => SalaryCut::active()->count(),
            ];

            // Dernières notifications
            $recentNotifications = PayrollWhatsappNotification::with(['employee', 'payrollPeriod'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Dernières coupures
            $recentCuts = SalaryCut::with(['employee', 'period'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_notifications' => $recentNotifications,
                    'recent_cuts' => $recentCuts
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // GÉNÉRATION PDF
    // ================================

    /**
     * Générer un bulletin de paie PDF
     */
    public function generatePayslipPDF($payslipId): Response
    {
        try {
            $payslip = Payslip::with(['employee.user', 'period', 'salaryCuts'])
                ->findOrFail($payslipId);

            $html = view('pdf.payslip', compact('payslip'))->render();
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'bulletin_paie_' . $payslip->employee->user->nom . '_' . 
                       $payslip->period->libelle_periode . '.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer PDF de tous les bulletins d'une période
     */
    public function generatePeriodPayslipsPDF($periodId): Response
    {
        try {
            $period = PayrollPeriod::findOrFail($periodId);
            $payslips = Payslip::with(['employee.user', 'salaryCuts'])
                ->where('period_id', $periodId)
                ->orderBy('employee.nom')
                ->get();

            if ($payslips->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun bulletin trouvé pour cette période'
                ], 404);
            }

            $html = view('pdf.payslips_bulk', compact('payslips', 'period'))->render();
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'bulletins_paie_' . $period->libelle_periode . '.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un récapitulatif PDF d'une période
     */
    public function generatePeriodSummaryPDF($periodId): Response
    {
        try {
            $period = PayrollPeriod::with(['payslips.employee.user'])
                ->findOrFail($periodId);

            // Statistiques de la période
            $stats = [
                'total_employees' => $period->payslips->count(),
                'total_gross' => $period->payslips->sum('salaire_brut'),
                'total_deductions' => $period->payslips->sum('montant_coupures'),
                'total_net' => $period->payslips->sum('salaire_net'),
                'total_cuts' => $period->salaryCuts()->where('statut', 'active')->count(),
                'payment_modes' => $period->payslips->groupBy('mode_paiement')->map->count()
            ];

            $html = view('pdf.period_summary', compact('period', 'stats'))->render();
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'recapitulatif_paie_' . $period->libelle_periode . '.pdf';

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}