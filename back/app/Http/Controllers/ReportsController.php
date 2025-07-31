<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentTranche;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Section;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Obtenir l'année scolaire de travail de l'utilisateur connecté
     */
    private function getUserWorkingYear()
    {
        $user = Auth::user();
        
        if ($user && $user->working_school_year_id) {
            $workingYear = SchoolYear::find($user->working_school_year_id);
            if ($workingYear && $workingYear->is_active) {
                return $workingYear;
            }
        }
        
        $currentYear = SchoolYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            $currentYear = SchoolYear::where('is_active', true)->first();
        }
        
        return $currentYear;
    }

    /**
     * Rapport d'état insolvable - Liste des élèves n'ayant pas fini de payer
     */
    public function getInsolvableReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $groupBy = $request->get('groupBy', 'series'); // series, class, section
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');

            // Récupérer tous les étudiants avec leurs informations de paiement
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true);

            // Appliquer les filtres
            if ($sectionId) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if ($classId) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            $students = $studentsQuery->get();
            $paymentTranches = PaymentTranche::active()->ordered()->get();

            // Filtrer seulement les élèves insolvables (qui n'ont pas fini de payer)
            $insolvableStudents = [];

            foreach ($students as $student) {
                $incompletesTranches = [];
                $studentTotalRequired = 0;
                $studentTotalPaid = 0;
                $hasIncompletePayments = false;

                foreach ($paymentTranches as $tranche) {
                    try {
                        // Montant requis pour cette tranche (avec bourses et réductions)
                        $requiredAmount = $tranche->getAmountForStudent($student, true, true, true) ?? 0;
                    } catch (\Exception $e) {
                        Log::warning("Erreur getAmountForStudent pour {$student->id}, tranche {$tranche->id}: " . $e->getMessage());
                        $requiredAmount = 0;
                    }
                    
                    // Montant payé pour cette tranche
                    $paidAmount = 0;
                    foreach ($student->payments as $payment) {
                        foreach ($payment->paymentDetails as $detail) {
                            if ($detail->payment_tranche_id == $tranche->id) {
                                $paidAmount += $detail->amount_allocated;
                            }
                        }
                    }

                    $remainingAmount = max(0, $requiredAmount - $paidAmount);
                    
                    if ($remainingAmount > 0) {
                        $hasIncompletePayments = true;
                        $incompletesTranches[] = [
                            'tranche_name' => $tranche->name,
                            'required_amount' => $requiredAmount,
                            'paid_amount' => $paidAmount,
                            'remaining_amount' => $remainingAmount
                        ];
                    }

                    $studentTotalRequired += $requiredAmount;
                    $studentTotalPaid += $paidAmount;
                }

                // Inclure seulement les élèves avec des paiements incomplets
                if ($hasIncompletePayments) {
                    $insolvableStudents[] = [
                        'student' => [
                            'id' => $student->id,
                            'first_name' => $student->first_name ?? '',
                            'last_name' => $student->last_name ?? '',
                            'full_name' => ($student->last_name ?? '') . ' ' . ($student->first_name ?? ''),
                            'class_series' => ($student->classSeries && $student->classSeries->schoolClass) 
                                ? $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name 
                                : 'Non défini'
                        ],
                        'total_required' => $studentTotalRequired,
                        'total_paid' => $studentTotalPaid,
                        'total_remaining' => max(0, $studentTotalRequired - $studentTotalPaid),
                        'incomplete_tranches' => $incompletesTranches
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $insolvableStudents,
                    'total_insolvable_students' => count($insolvableStudents)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getInsolvableReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la clé de groupement pour un étudiant
     */
    private function getGroupKey($student, $groupBy)
    {
        switch ($groupBy) {
            case 'series':
                return $student->classSeries->id;
            case 'class':
                return $student->classSeries->schoolClass->id;
            case 'section':
                return $student->classSeries->schoolClass->level->section->id;
            default:
                return $student->classSeries->id;
        }
    }

    /**
     * Obtenir le nom de groupement pour un étudiant
     */
    private function getGroupName($student, $groupBy)
    {
        switch ($groupBy) {
            case 'series':
                return $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name;
            case 'class':
                return $student->classSeries->schoolClass->name;
            case 'section':
                return $student->classSeries->schoolClass->level->section->name;
            default:
                return $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name;
        }
    }

    /**
     * Rapport des paiements - Liste de tous les élèves avec infos de toutes les tranches
     */
    public function getPaymentsReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $groupBy = $request->get('groupBy', 'series'); // series, class, section
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');

            // Récupérer tous les étudiants avec leurs informations de paiement
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true);

            // Appliquer les filtres
            if ($sectionId) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if ($classId) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            $students = $studentsQuery->get();
            
            // Récupérer seulement les tranches liées aux classes des étudiants
            $classIds = $students->pluck('classSeries.schoolClass.id')->unique();
            $paymentTranches = PaymentTranche::active()
                ->whereIn('class_id', $classIds)
                ->ordered()
                ->get();

            // Créer les détails pour tous les élèves
            $studentsData = [];
            
            foreach ($students as $student) {
                $tranchesDetails = [];
                
                // Filtrer les tranches pour cette classe spécifique
                $studentTranches = $paymentTranches->where('class_id', $student->classSeries->school_class_id);
                
                foreach ($studentTranches as $tranche) {
                    try {
                        // Montant requis pour cette tranche (avec bourses et réductions)  
                        $requiredAmount = $tranche->getAmountForStudent($student, true, true, true) ?? 0;
                    } catch (\Exception $e) {
                        Log::warning("Erreur getAmountForStudent pour {$student->id}, tranche {$tranche->id}: " . $e->getMessage());
                        $requiredAmount = 0;
                    }
                    
                    // Montant payé pour cette tranche
                    $paidAmount = 0;
                    foreach ($student->payments as $payment) {
                        foreach ($payment->paymentDetails as $detail) {
                            if ($detail->payment_tranche_id == $tranche->id) {
                                $paidAmount += $detail->amount_allocated;
                            }
                        }
                    }

                    $tranchesDetails[] = [
                        'tranche_name' => $tranche->name,
                        'required_amount' => $requiredAmount,
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => max(0, $requiredAmount - $paidAmount),
                        'status' => $paidAmount >= $requiredAmount ? 'complete' : 'incomplete'
                    ];
                }

                $studentsData[] = [
                    'student' => [
                        'id' => $student->id,
                        'first_name' => $student->first_name ?? '',
                        'last_name' => $student->last_name ?? '',
                        'full_name' => ($student->last_name ?? '') . ' ' . ($student->first_name ?? ''),
                        'class_series' => ($student->classSeries && $student->classSeries->schoolClass) 
                            ? $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name 
                            : 'Non défini'
                    ],
                    'tranches_details' => $tranchesDetails
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $studentsData,
                    'total_students' => count($studentsData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getPaymentsReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport d'état des RAME - Liste des élèves avec détails RAME (espèces/physique/pas payé)
     */
    public function getRameReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $groupBy = $request->get('groupBy', 'series'); // series, class, section
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');

            // Récupérer la tranche RAME
            $rameTranche = PaymentTranche::active()
                ->where(function ($query) {
                    $query->where('name', 'RAME')
                          ->orWhere('name', 'like', '%RAME%');
                })
                ->first();

            if (!$rameTranche) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tranche RAME non trouvée'
                ], 404);
            }

            // Récupérer tous les étudiants avec leurs paiements RAME
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true);

            // Appliquer les filtres
            if ($sectionId) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if ($classId) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            $students = $studentsQuery->get();

            $rameStudentsData = [];
            foreach ($students as $student) {
                try {
                    $rameAmount = $rameTranche->getAmountForStudent($student, true, true, true) ?? 0; // Avec bourses et réductions
                } catch (\Exception $e) {
                    Log::warning("Erreur getAmountForStudent RAME pour {$student->id}: " . $e->getMessage());
                    $rameAmount = 0;
                }
                $ramePaid = 0;
                $rameType = 'unpaid'; // unpaid, cash, physical
                $paymentDate = null;

                // Vérifier les paiements RAME
                foreach ($student->payments as $payment) {
                    foreach ($payment->paymentDetails as $detail) {
                        if ($detail->payment_tranche_id == $rameTranche->id) {
                            $ramePaid += $detail->amount_allocated;
                            $rameType = $payment->is_rame_physical ? 'physical' : 'cash';
                            $paymentDate = $payment->payment_date;
                        }
                    }
                }

                $rameStudentsData[] = [
                    'student' => [
                        'id' => $student->id,
                        'first_name' => $student->first_name ?? '',
                        'last_name' => $student->last_name ?? '',
                        'full_name' => ($student->last_name ?? '') . ' ' . ($student->first_name ?? ''),
                        'class_series' => ($student->classSeries && $student->classSeries->schoolClass) 
                            ? $student->classSeries->schoolClass->name . ' - ' . $student->classSeries->name 
                            : 'Non défini'
                    ],
                    'rame_details' => [
                        'required_amount' => $rameAmount,
                        'paid_amount' => $ramePaid,
                        'payment_type' => $rameType, // unpaid, cash, physical
                        'payment_status' => $ramePaid >= $rameAmount ? 'paid' : 'unpaid',
                        'payment_date' => $paymentDate
                    ]
                ];
            }

            // Calculer les statistiques générales
            $summary = [
                'total_students' => count($rameStudentsData),
                'paid_count' => count(array_filter($rameStudentsData, function ($item) { 
                    return $item['rame_details']['payment_status'] === 'paid'; 
                })),
                'physical_count' => count(array_filter($rameStudentsData, function ($item) { 
                    return $item['rame_details']['payment_type'] === 'physical'; 
                })),
                'cash_count' => count(array_filter($rameStudentsData, function ($item) { 
                    return $item['rame_details']['payment_type'] === 'cash'; 
                })),
                'unpaid_count' => count(array_filter($rameStudentsData, function ($item) { 
                    return $item['rame_details']['payment_status'] === 'unpaid'; 
                })),
                'total_amount_expected' => array_sum(array_column(array_column($rameStudentsData, 'rame_details'), 'required_amount')),
                'total_amount_collected' => array_sum(array_column(array_column($rameStudentsData, 'rame_details'), 'paid_amount'))
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $rameStudentsData,
                    'summary' => $summary
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getRameReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport de recouvrement
     */
    public function getRecoveryReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $startDate = $request->get('startDate', $workingYear->start_date);
            $endDate = $request->get('endDate', $workingYear->end_date ?: now()->toDateString());

            // Récupérer toutes les tranches (hors RAME)
            $paymentTranches = PaymentTranche::active()
                ->where(function ($query) {
                    $query->where('name', '!=', 'RAME')
                          ->where('name', 'not like', '%RAME%');
                })
                ->ordered()
                ->get();

            // Récupérer tous les étudiants
            $students = Student::with([
                'classSeries.schoolClass',
                'payments' => function ($query) use ($workingYear, $startDate, $endDate) {
                    $query->where('school_year_id', $workingYear->id)
                          ->whereBetween('payment_date', [$startDate, $endDate]);
                }
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true)
            ->get();

            // Calculer les totaux globaux
            $totalExpected = 0;
            $totalCollected = 0;
            $totalScholarships = 0;
            $totalReductions = 0;

            foreach ($students as $student) {
                foreach ($paymentTranches as $tranche) {
                    try {
                        // Montant attendu avec bourses et réductions
                        $expectedAmount = $tranche->getAmountForStudent($student, true, true, true) ?? 0;
                    } catch (\Exception $e) {
                        Log::warning("Erreur getAmountForStudent recovery pour {$student->id}, tranche {$tranche->id}: " . $e->getMessage());
                        $expectedAmount = 0;
                    }
                    $totalExpected += $expectedAmount;

                    // Calculer les bourses et réductions
                    try {
                        $baseAmount = $tranche->getAmountForStudent($student, true, false, false) ?? 0;
                        $withScholarshipAmount = $tranche->getAmountForStudent($student, true, false, true) ?? 0;
                        $scholarshipAmount = max(0, $baseAmount - $withScholarshipAmount);
                        $reductionAmount = max(0, $withScholarshipAmount - $expectedAmount);
                    } catch (\Exception $e) {
                        Log::warning("Erreur calcul bourses/réductions pour {$student->id}, tranche {$tranche->id}: " . $e->getMessage());
                        $scholarshipAmount = 0;
                        $reductionAmount = 0;
                    }
                    
                    $totalScholarships += $scholarshipAmount;
                    $totalReductions += $reductionAmount;
                }

                // Montant collecté
                foreach ($student->payments as $payment) {
                    foreach ($payment->paymentDetails as $detail) {
                        $tranche = $paymentTranches->find($detail->payment_tranche_id);
                        if ($tranche) {
                            $totalCollected += $detail->amount_allocated;
                        }
                    }
                }
            }

            $totalRemaining = max(0, $totalExpected - $totalCollected);
            $recoveryRate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

            // Données par période (mensuelle)
            $periods = [];
            $currentDate = Carbon::parse($startDate);
            $endDateCarbon = Carbon::parse($endDate);

            while ($currentDate->lte($endDateCarbon)) {
                $monthStart = $currentDate->copy()->startOfMonth();
                $monthEnd = $currentDate->copy()->endOfMonth();
                
                if ($monthEnd->gt($endDateCarbon)) {
                    $monthEnd = $endDateCarbon;
                }

                // Calculer les paiements du mois
                $monthlyCollected = Payment::where('school_year_id', $workingYear->id)
                    ->whereBetween('payment_date', [$monthStart, $monthEnd])
                    ->sum('total_amount');

                $periods[] = [
                    'period_name' => $monthStart->format('M Y'),
                    'expected' => $totalExpected / 12, // Répartition mensuelle approximative
                    'collected' => $monthlyCollected,
                    'remaining' => max(0, ($totalExpected / 12) - $monthlyCollected),
                    'rate' => ($totalExpected / 12) > 0 ? ($monthlyCollected / ($totalExpected / 12)) * 100 : 0,
                    'scholarships' => $totalScholarships / 12,
                    'reductions' => $totalReductions / 12
                ];

                $currentDate->addMonth();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_expected' => $totalExpected,
                        'total_collected' => $totalCollected,
                        'total_remaining' => $totalRemaining,
                        'recovery_rate' => $recoveryRate,
                        'total_scholarships' => $totalScholarships,
                        'total_reductions' => $totalReductions
                    ],
                    'periods' => $periods
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getRecoveryReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF des rapports
     */
    public function exportPdf(Request $request)
    {
        try {
            $reportType = $request->get('report_type', 'insolvable');
            
            // Générer les données du rapport selon le type
            $reportData = null;
            switch ($reportType) {
                case 'insolvable':
                    $response = $this->getInsolvableReport($request);
                    break;
                case 'payments':
                    $response = $this->getPaymentsReport($request);
                    break;
                case 'rame':
                    $response = $this->getRameReport($request);
                    break;
                case 'recovery':
                    $response = $this->getRecoveryReport($request);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Type de rapport non reconnu'
                    ], 400);
            }

            $responseData = $response->getData(true);
            
            if (!$responseData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Erreur lors de la génération du rapport'
                ], 500);
            }

            $reportData = $responseData['data'];
            
            // Générer le HTML pour le PDF
            $html = $this->generateReportHtml($reportType, $reportData, $request);
            
            // Générer le PDF
            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "rapport_{$reportType}_" . date('Y-m-d_H-i-s') . ".pdf";
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@exportPdf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le PDF
     */
    private function generateReportHtml($reportType, $reportData, $request)
    {
        $workingYear = $this->getUserWorkingYear();
        $schoolName = "Groupe Scolaire Bilingue Privé La Semence";
        $currentDate = now()->format('d/m/Y H:i');
        
        $titles = [
            'insolvable' => 'Rapport État Insolvable',
            'payments' => 'Rapport État des Paiements',
            'rame' => 'Rapport État des RAME',
            'recovery' => 'Rapport de Recouvrement'
        ];
        
        $title = $titles[$reportType] ?? 'Rapport';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 12px; 
                    margin: 20px;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333;
                    padding-bottom: 15px;
                }
                .school-name { 
                    font-size: 18px; 
                    font-weight: bold; 
                    color: #2c5aa0;
                }
                .report-title { 
                    font-size: 16px; 
                    font-weight: bold; 
                    margin: 10px 0; 
                }
                .meta-info { 
                    font-size: 10px; 
                    color: #666;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 15px 0;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left;
                }
                th { 
                    background-color: #f2f2f2; 
                    font-weight: bold;
                }
                .group-header {
                    background-color: #e8f4f8;
                    font-weight: bold;
                    padding: 10px;
                    margin: 15px 0 5px 0;
                }
                .summary {
                    background-color: #f9f9f9;
                    padding: 15px;
                    margin: 20px 0;
                    border-left: 4px solid #2c5aa0;
                }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='school-name'>{$schoolName}</div>
                <div class='report-title'>{$title}</div>
                <div class='meta-info'>
                    Année scolaire: {$workingYear->name} | 
                    Généré le: {$currentDate}
                </div>
            </div>
        ";
        
        // Générer le contenu selon le type de rapport
        $html .= $this->generateReportContent($reportType, $reportData);
        
        $html .= "
            <div class='footer'>
                Document généré automatiquement - {$schoolName}
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Générer le contenu spécifique du rapport
     */
    private function generateReportContent($reportType, $reportData)
    {
        switch ($reportType) {
            case 'insolvable':
                return $this->generateInsolvableContent($reportData);
            case 'payments':
                return $this->generatePaymentsContent($reportData);
            case 'rame':
                return $this->generateRameContent($reportData);
            case 'recovery':
                return $this->generateRecoveryContent($reportData);
            default:
                return '<p>Type de rapport non supporté</p>';
        }
    }

    /**
     * Générer le contenu du rapport insolvable
     */
    private function generateInsolvableContent($reportData)
    {
        $html = "<div class='summary'>";
        $html .= "<h3>Résumé</h3>";
        $html .= "<p><strong>Total élèves insolvables:</strong> {$reportData['total_insolvable_students']}</p>";
        $html .= "</div>";

        foreach ($reportData['grouped_data'] as $group) {
            $html .= "<div class='group-header'>{$group['group_name']}</div>";
            $html .= "<table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Classe/Série</th>
                        <th class='text-right'>Total Requis</th>
                        <th class='text-right'>Total Payé</th>
                        <th class='text-right'>Reste à Payer</th>
                    </tr>
                </thead>
                <tbody>";
            
            foreach ($group['students'] as $studentData) {
                $html .= "<tr>
                    <td>{$studentData['student']['full_name']}</td>
                    <td>{$studentData['student']['class_series']}</td>
                    <td class='text-right'>" . number_format($studentData['total_required'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($studentData['total_paid'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($studentData['total_remaining'], 0, ',', ' ') . " FCFA</td>
                </tr>";
            }
            
            $html .= "</tbody></table>";
        }

        return $html;
    }

    /**
     * Générer le contenu du rapport des paiements
     */
    private function generatePaymentsContent($reportData)
    {
        $html = "<div class='summary'>";
        $html .= "<h3>Résumé</h3>";
        $html .= "<p><strong>Total étudiants:</strong> {$reportData['total_students']}</p>";
        $html .= "</div>";

        foreach ($reportData['grouped_data'] as $group) {
            $html .= "<div class='group-header'>{$group['group_name']}</div>";
            
            foreach ($group['students'] as $studentData) {
                $html .= "<h4>{$studentData['student']['full_name']} - {$studentData['student']['class_series']}</h4>";
                $html .= "<table>
                    <thead>
                        <tr>
                            <th>Tranche</th>
                            <th class='text-right'>Montant Requis</th>
                            <th class='text-right'>Montant Payé</th>
                            <th class='text-right'>Reste à Payer</th>
                            <th class='text-center'>Statut</th>
                        </tr>
                    </thead>
                    <tbody>";
                
                foreach ($studentData['tranches_details'] as $tranche) {
                    $status = $tranche['status'] === 'complete' ? 'Complet' : 'Incomplet';
                    $html .= "<tr>
                        <td>{$tranche['tranche_name']}</td>
                        <td class='text-right'>" . number_format($tranche['required_amount'], 0, ',', ' ') . " FCFA</td>
                        <td class='text-right'>" . number_format($tranche['paid_amount'], 0, ',', ' ') . " FCFA</td>
                        <td class='text-right'>" . number_format($tranche['remaining_amount'], 0, ',', ' ') . " FCFA</td>
                        <td class='text-center'>{$status}</td>
                    </tr>";
                }
                
                $html .= "</tbody></table>";
            }
        }

        return $html;
    }

    /**
     * Générer le contenu du rapport RAME
     */
    private function generateRameContent($reportData)
    {
        $summary = $reportData['summary'];
        $html = "<div class='summary'>";
        $html .= "<h3>Résumé</h3>";
        $html .= "<p><strong>Total étudiants:</strong> {$summary['total_students']}</p>";
        $html .= "<p><strong>Payés:</strong> {$summary['paid_count']} | ";
        $html .= "<strong>Espèces:</strong> {$summary['cash_count']} | ";
        $html .= "<strong>Physique:</strong> {$summary['physical_count']} | ";
        $html .= "<strong>Non payés:</strong> {$summary['unpaid_count']}</p>";
        $html .= "</div>";

        foreach ($reportData['grouped_data'] as $group) {
            $html .= "<div class='group-header'>{$group['group_name']}</div>";
            $html .= "<table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Classe/Série</th>
                        <th class='text-right'>Montant RAME</th>
                        <th class='text-right'>Montant Payé</th>
                        <th class='text-center'>Type</th>
                        <th class='text-center'>Statut</th>
                    </tr>
                </thead>
                <tbody>";
            
            foreach ($group['students'] as $studentData) {
                $type = $studentData['rame_details']['payment_type'] === 'physical' ? 'Physique' : 
                       ($studentData['rame_details']['payment_type'] === 'cash' ? 'Espèces' : 'Non payé');
                $status = $studentData['rame_details']['payment_status'] === 'paid' ? 'Payé' : 'En attente';
                
                $html .= "<tr>
                    <td>{$studentData['student']['full_name']}</td>
                    <td>{$studentData['student']['class_series']}</td>
                    <td class='text-right'>" . number_format($studentData['rame_details']['required_amount'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($studentData['rame_details']['paid_amount'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-center'>{$type}</td>
                    <td class='text-center'>{$status}</td>
                </tr>";
            }
            
            $html .= "</tbody></table>";
        }

        return $html;
    }

    /**
     * Générer le contenu du rapport de recouvrement
     */
    private function generateRecoveryContent($reportData)
    {
        $summary = $reportData['summary'];
        $html = "<div class='summary'>";
        $html .= "<h3>Résumé Global</h3>";
        $html .= "<p><strong>Total attendu:</strong> " . number_format($summary['total_expected'], 0, ',', ' ') . " FCFA</p>";
        $html .= "<p><strong>Total collecté:</strong> " . number_format($summary['total_collected'], 0, ',', ' ') . " FCFA</p>";
        $html .= "<p><strong>Reste à collecter:</strong> " . number_format($summary['total_remaining'], 0, ',', ' ') . " FCFA</p>";
        $html .= "<p><strong>Taux de recouvrement:</strong> " . number_format($summary['recovery_rate'], 1) . "%</p>";
        $html .= "</div>";

        if (isset($reportData['periods'])) {
            $html .= "<h3>Évolution par période</h3>";
            $html .= "<table>
                <thead>
                    <tr>
                        <th>Période</th>
                        <th class='text-right'>Attendu</th>
                        <th class='text-right'>Collecté</th>
                        <th class='text-right'>Reste</th>
                        <th class='text-right'>Taux</th>
                    </tr>
                </thead>
                <tbody>";
            
            foreach ($reportData['periods'] as $period) {
                $html .= "<tr>
                    <td>{$period['period_name']}</td>
                    <td class='text-right'>" . number_format($period['expected'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($period['collected'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($period['remaining'], 0, ',', ' ') . " FCFA</td>
                    <td class='text-right'>" . number_format($period['rate'], 1) . "%</td>
                </tr>";
            }
            
            $html .= "</tbody></table>";
        }

        return $html;
    }

    /**
     * Obtenir le libellé du mode de paiement
     */
    private function getPaymentMethodLabel($method, $isRamePhysical = false)
    {
        if ($isRamePhysical) {
            return 'RAME Physique';
        }
        
        $methods = [
            'cash' => 'Espèces',
            'card' => 'Carte bancaire',
            'transfer' => 'Virement',
            'check' => 'Chèque'
        ];
        
        return $methods[$method] ?? $method;
    }
}