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

            $filterType = $request->get('filterType', 'section'); // section, class, series
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

            // Récupérer tous les étudiants avec leurs informations de paiement
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true);

            // Appliquer les filtres
            if (!empty($sectionId)) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $studentsQuery->where('class_series_id', $seriesId);
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

            $filterType = $request->get('filterType', 'section'); // section, class, series
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

            // Récupérer tous les étudiants avec leurs informations de paiement
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true);

            // Appliquer les filtres
            if (!empty($sectionId)) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $studentsQuery->where('class_series_id', $seriesId);
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

            $filterType = $request->get('filterType', 'section'); // section, class, series
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

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
            if (!empty($sectionId)) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $studentsQuery->where('class_series_id', $seriesId);
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
     * Rapport récapitulatif des encaissements
     */
    public function getCollectionSummaryReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $filterType = $request->get('filterType', 'section');
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

            // Récupérer les classes/séries selon les filtres
            $classSeriesQuery = ClassSeries::with(['schoolClass.level.section'])
                ->whereHas('schoolClass', function ($query) use ($workingYear) {
                    $query->where('school_year_id', $workingYear->id);
                });

            if (!empty($sectionId)) {
                $classSeriesQuery->whereHas('schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $classSeriesQuery->whereHas('schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $classSeriesQuery->where('id', $seriesId);
            }

            $classSeries = $classSeriesQuery->get();
            $paymentTranches = PaymentTranche::active()->ordered()->get();

            $classesData = [];
            $globalTotalDue = 0;
            $globalTotalPaid = 0;
            $globalTotalRemaining = 0;

            foreach ($classSeries as $series) {
                $students = Student::where('class_series_id', $series->id)
                    ->where('school_year_id', $workingYear->id)
                    ->where('is_active', true)
                    ->with(['payments.paymentDetails'])
                    ->get();

                $classTotalDue = 0;
                $classTotalPaid = 0;

                foreach ($students as $student) {
                    foreach ($paymentTranches as $tranche) {
                        try {
                            $requiredAmount = $tranche->getAmountForStudent($student, true, true, true) ?? 0;
                        } catch (\Exception $e) {
                            $requiredAmount = 0;
                        }
                        $classTotalDue += $requiredAmount;

                        // Calculer le montant payé
                        foreach ($student->payments as $payment) {
                            foreach ($payment->paymentDetails as $detail) {
                                if ($detail->payment_tranche_id == $tranche->id) {
                                    $classTotalPaid += $detail->amount_allocated;
                                }
                            }
                        }
                    }
                }

                $classTotalRemaining = max(0, $classTotalDue - $classTotalPaid);
                $collectionRate = $classTotalDue > 0 ? ($classTotalPaid / $classTotalDue) * 100 : 0;

                $classesData[] = [
                    'class_name' => $series->schoolClass->name,
                    'series_name' => $series->name,
                    'student_count' => $students->count(),
                    'total_due' => $classTotalDue,
                    'total_paid' => $classTotalPaid,
                    'total_remaining' => $classTotalRemaining,
                    'collection_rate' => $collectionRate
                ];

                $globalTotalDue += $classTotalDue;
                $globalTotalPaid += $classTotalPaid;
                $globalTotalRemaining += $classTotalRemaining;
            }

            $globalCollectionRate = $globalTotalDue > 0 ? ($globalTotalPaid / $globalTotalDue) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_due' => $globalTotalDue,
                        'total_paid' => $globalTotalPaid,
                        'total_remaining' => $globalTotalRemaining,
                        'collection_rate' => $globalCollectionRate
                    ],
                    'classes' => $classesData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getCollectionSummaryReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport des détails de paiements
     */
    public function getPaymentDetailsReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $filterType = $request->get('filterType', 'section');
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

            // Récupérer les paiements selon les filtres
            $paymentsQuery = Payment::with([
                'student.classSeries.schoolClass.level.section'
            ])
            ->where('school_year_id', $workingYear->id);

            if (!empty($sectionId)) {
                $paymentsQuery->whereHas('student.classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $paymentsQuery->whereHas('student.classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $paymentsQuery->whereHas('student', function ($query) use ($seriesId) {
                    $query->where('class_series_id', $seriesId);
                });
            }

            $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();

            // Grouper par classe/série
            $classesData = [];
            $totalAmount = 0;

            foreach ($payments as $payment) {
                $student = $payment->student;
                $classSeries = $student->classSeries;
                $classKey = $classSeries->schoolClass->name . ' - ' . $classSeries->name;

                if (!isset($classesData[$classKey])) {
                    $classesData[$classKey] = [
                        'class_name' => $classSeries->schoolClass->name,
                        'series_name' => $classSeries->name,
                        'payments' => [],
                        'total_amount' => 0
                    ];
                }

                $paymentData = [
                    'student_matricule' => $student->matricule,
                    'student_lastname' => $student->last_name,
                    'student_firstname' => $student->first_name,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'amount' => $payment->total_amount
                ];

                $classesData[$classKey]['payments'][] = $paymentData;
                $classesData[$classKey]['total_amount'] += $payment->total_amount;
                $totalAmount += $payment->total_amount;
            }

            // Convertir en array indexé
            $classes = array_values($classesData);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_amount' => $totalAmount,
                        'total_payments' => $payments->count()
                    ],
                    'classes' => $classes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getPaymentDetailsReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport des bourses et rabais
     */
    public function getScholarshipsDiscountsReport(Request $request)
    {
        try {
            $workingYear = $this->getUserWorkingYear();
            
            if (!$workingYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $filterType = $request->get('filterType', 'section');
            $sectionId = $request->get('sectionId');
            $classId = $request->get('classId');
            $seriesId = $request->get('seriesId');

            // Récupérer les étudiants avec bourses/rabais
            $studentsQuery = Student::with([
                'classSeries.schoolClass.level.section',
                'classScholarship',
                'payments.paymentDetails.paymentTranche'
            ])
            ->where('school_year_id', $workingYear->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('class_scholarship_id')
                      ->orWhereExists(function ($subQuery) {
                          $subQuery->select(DB::raw(1))
                                   ->from('payments')
                                   ->whereColumn('payments.student_id', 'students.id')
                                   ->where('payments.has_reduction', true);
                      });
            });

            if (!empty($sectionId)) {
                $studentsQuery->whereHas('classSeries.schoolClass.level.section', function ($query) use ($sectionId) {
                    $query->where('id', $sectionId);
                });
            }

            if (!empty($classId)) {
                $studentsQuery->whereHas('classSeries.schoolClass', function ($query) use ($classId) {
                    $query->where('id', $classId);
                });
            }

            if (!empty($seriesId)) {
                $studentsQuery->where('class_series_id', $seriesId);
            }

            $students = $studentsQuery->get();
            $paymentTranches = PaymentTranche::active()->ordered()->get();

            $studentsData = [];
            $totalScholarships = 0;
            $totalDiscounts = 0;
            $classSummary = [];

            foreach ($students as $student) {
                $classSeries = $student->classSeries;
                $classKey = $classSeries->schoolClass->name . ' - ' . $classSeries->name;

                // Calculer le montant total de scolarité (sans réductions)
                $tuitionAmount = 0;
                foreach ($paymentTranches as $tranche) {
                    try {
                        $baseAmount = $tranche->getAmountForStudent($student, true, false, false) ?? 0;
                        $tuitionAmount += $baseAmount;
                    } catch (\Exception $e) {
                        // Ignorer les erreurs
                    }
                }

                // Calculer les bourses
                $scholarshipAmount = 0;
                $scholarshipReason = '';
                if ($student->classScholarship) {
                    $scholarshipReason = $student->classScholarship->reason;
                    foreach ($paymentTranches as $tranche) {
                        try {
                            $baseAmount = $tranche->getAmountForStudent($student, true, false, false) ?? 0;
                            $withScholarshipAmount = $tranche->getAmountForStudent($student, true, false, true) ?? 0;
                            $scholarshipAmount += max(0, $baseAmount - $withScholarshipAmount);
                        } catch (\Exception $e) {
                            // Ignorer les erreurs
                        }
                    }
                }

                // Calculer les rabais globaux
                $discountAmount = 0;
                $discountReason = '';
                foreach ($student->payments as $payment) {
                    if ($payment->has_reduction && $payment->reduction_amount > 0) {
                        $discountAmount += $payment->reduction_amount;
                        $discountReason = 'Paiement cash avant 15 août';
                        break; // Une seule raison pour simplifier
                    }
                }

                $totalBenefitAmount = $scholarshipAmount + $discountAmount;

                if ($totalBenefitAmount > 0) {
                    $studentsData[] = [
                        'class_name' => $classSeries->schoolClass->name,
                        'student_name' => $student->last_name . ' ' . $student->first_name,
                        'tuition_amount' => $tuitionAmount,
                        'scholarship_reason' => $scholarshipReason,
                        'discount_reason' => $discountReason,
                        'total_benefit_amount' => $totalBenefitAmount,
                        'observation' => ''
                    ];

                    $totalScholarships += $scholarshipAmount;
                    $totalDiscounts += $discountAmount;

                    // Récapitulatif par classe
                    if (!isset($classSummary[$classKey])) {
                        $classSummary[$classKey] = [
                            'class_name' => $classSeries->schoolClass->name,
                            'series_name' => $classSeries->name,
                            'beneficiary_count' => 0,
                            'total_scholarships' => 0,
                            'total_discounts' => 0,
                            'total_benefits' => 0
                        ];
                    }

                    $classSummary[$classKey]['beneficiary_count']++;
                    $classSummary[$classKey]['total_scholarships'] += $scholarshipAmount;
                    $classSummary[$classKey]['total_discounts'] += $discountAmount;
                    $classSummary[$classKey]['total_benefits'] += $totalBenefitAmount;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_scholarships' => $totalScholarships,
                        'total_discounts' => $totalDiscounts,
                        'beneficiary_count' => count($studentsData)
                    ],
                    'students' => $studentsData,
                    'class_summary' => array_values($classSummary)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReportsController@getScholarshipsDiscountsReport: ' . $e->getMessage());
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
            
            // Retourner le HTML optimisé pour impression/PDF (comme dans StudentController)
            $printOptimizedHtml = $this->generatePdfFromHtml($html);
            
            $filename = "rapport_{$reportType}_" . date('Y-m-d_H-i-s') . ".pdf";
            
            // Retourner le HTML formaté que le navigateur peut imprimer en PDF
            return response($printOptimizedHtml, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);

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
     * Générer un PDF à partir du HTML (méthode simple mais robuste)
     */
    private function generatePdfFromHtml($html)
    {
        // Approche simple et robuste : retourner directement le HTML formaté pour impression
        // Le navigateur peut ensuite imprimer en PDF si nécessaire
        
        // Ajouter des styles optimisés pour l'impression/PDF
        $printOptimizedHtml = str_replace(
            '<style>',
            '<style>
                @page { 
                    size: A4; 
                    margin: 10mm; 
                }
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 10pt; 
                    line-height: 1.3;
                    margin: 0;
                    padding: 0;
                }
                .no-print { display: none !important; }
                table { 
                    page-break-inside: avoid; 
                    width: 100%;
                    font-size: 9pt;
                }
                thead { 
                    display: table-header-group; 
                }
                tr { 
                    page-break-inside: avoid; 
                }
            ',
            $html
        );
        
        // Retourner le HTML optimisé - le navigateur se chargera de la conversion PDF
        return $printOptimizedHtml;
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

        $html .= "<table>
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Classe/Série</th>
                    <th class='text-right'>Total Requis</th>
                    <th class='text-right'>Total Payé</th>
                    <th class='text-right'>Reste à Payer</th>
                    <th>Tranches Incomplètes</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($reportData['students'] as $studentData) {
            $incompleteTranches = '';
            if (isset($studentData['incomplete_tranches'])) {
                foreach ($studentData['incomplete_tranches'] as $tranche) {
                    $incompleteTranches .= $tranche['tranche_name'] . ': ' . number_format($tranche['paid_amount'], 0, ',', ' ') . '/' . number_format($tranche['required_amount'], 0, ',', ' ') . ' FCFA<br>';
                }
            }
            
            $html .= "<tr>
                <td>{$studentData['student']['full_name']}</td>
                <td>{$studentData['student']['class_series']}</td>
                <td class='text-right'>" . number_format($studentData['total_required'], 0, ',', ' ') . " FCFA</td>
                <td class='text-right'>" . number_format($studentData['total_paid'], 0, ',', ' ') . " FCFA</td>
                <td class='text-right'>" . number_format($studentData['total_remaining'], 0, ',', ' ') . " FCFA</td>
                <td>{$incompleteTranches}</td>
            </tr>";
        }
        
        $html .= "</tbody></table>";

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

        foreach ($reportData['students'] as $studentData) {
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

        $html .= "<table>
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Classe/Série</th>
                    <th class='text-right'>Montant RAME</th>
                    <th class='text-right'>Montant Payé</th>
                    <th class='text-center'>Type</th>
                    <th class='text-center'>Statut</th>
                    <th class='text-center'>Date Paiement</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($reportData['students'] as $studentData) {
            $type = $studentData['rame_details']['payment_type'] === 'physical' ? 'Physique' : 
                   ($studentData['rame_details']['payment_type'] === 'cash' ? 'Espèces' : 'Non payé');
            $status = $studentData['rame_details']['payment_status'] === 'paid' ? 'Payé' : 'En attente';
            $paymentDate = isset($studentData['rame_details']['payment_date']) && $studentData['rame_details']['payment_date'] 
                          ? date('d/m/Y', strtotime($studentData['rame_details']['payment_date'])) 
                          : '-';
            
            $html .= "<tr>
                <td>{$studentData['student']['full_name']}</td>
                <td>{$studentData['student']['class_series']}</td>
                <td class='text-right'>" . number_format($studentData['rame_details']['required_amount'], 0, ',', ' ') . " FCFA</td>
                <td class='text-right'>" . number_format($studentData['rame_details']['paid_amount'], 0, ',', ' ') . " FCFA</td>
                <td class='text-center'>{$type}</td>
                <td class='text-center'>{$status}</td>
                <td class='text-center'>{$paymentDate}</td>
            </tr>";
        }
        
        $html .= "</tbody></table>";

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