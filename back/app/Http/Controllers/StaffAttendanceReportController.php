<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class StaffAttendanceReportController extends Controller
{
    /**
     * Obtenir l'année scolaire de travail de l'utilisateur connecté
     */
    private function getUserWorkingYear()
    {
        $user = Auth::user();
        
        if ($user && $user->working_school_year_id) {
            $workingYear = \App\Models\SchoolYear::find($user->working_school_year_id);
            if ($workingYear) {
                return $workingYear;
            }
        }

        $workingYear = \App\Models\SchoolYear::where('is_current', true)->first();
        
        if (!$workingYear) {
            $workingYear = \App\Models\SchoolYear::where('is_active', true)->first();
        }
        
        return $workingYear;
    }

    /**
     * Rapport mensuel de présence du personnel
     */
    public function getStaffAttendanceMonthlyReport(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $role = $request->get('role');
            $department = $request->get('department');
            $status = $request->get('status');

            // Construire la requête de base pour les utilisateurs personnel
            // Inclure tous les rôles du personnel (pas les parents/élèves)
            $staffQuery = User::whereNotIn('role', ['parent', 'student'])
                ->where('is_active', true);

            if ($role) {
                $staffQuery->where('role', $role);
            }

            $staff = $staffQuery->get();

            // Calculer les statistiques de présence pour chaque personnel
            $attendanceData = [];
            $monthStart = $month . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Calculer le nombre de jours ouvrables du mois
            $workingDays = $this->getWorkingDaysInMonth($month);

            foreach ($staff as $staffMember) {
                $attendanceStats = $this->calculateStaffMonthlyStats($staffMember, $monthStart, $monthEnd);
                
                // Filtrer par statut de présence si spécifié
                $attendanceRate = $attendanceStats['total_days'] > 0 ? 
                    ($attendanceStats['present_days'] / $attendanceStats['total_days']) * 100 : 0;

                $shouldInclude = true;
                if ($status && $status !== 'all') {
                    switch ($status) {
                        case 'excellent':
                            $shouldInclude = $attendanceRate >= 95;
                            break;
                        case 'good':
                            $shouldInclude = $attendanceRate >= 85 && $attendanceRate < 95;
                            break;
                        case 'average':
                            $shouldInclude = $attendanceRate >= 70 && $attendanceRate < 85;
                            break;
                        case 'poor':
                            $shouldInclude = $attendanceRate < 70;
                            break;
                    }
                }

                if ($shouldInclude) {
                    $attendanceData[] = [
                        'id' => $staffMember->id,
                        'name' => $staffMember->name,
                        'email' => $staffMember->email,
                        'role' => $staffMember->role,
                        'stats' => $attendanceStats
                    ];
                }
            }

            // Calculer les statistiques globales du mois
            $monthlyStats = $this->calculateMonthlyGlobalStats($attendanceData, $workingDays);

            return response()->json([
                'success' => true,
                'data' => [
                    'staff_attendance' => $attendanceData,
                    'monthly_stats' => $monthlyStats,
                    'working_days' => $workingDays
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in StaffAttendanceReportController@getStaffAttendanceMonthlyReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF du rapport mensuel de présence du personnel
     */
    public function exportStaffAttendanceMonthlyPdf(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            
            // Récupérer les données du rapport
            $reportData = $this->getStaffAttendanceMonthlyReport($request);
            $reportContent = $reportData->getData();
            
            if (!$reportContent->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des données'
                ], 400);
            }

            $html = $this->generateStaffAttendanceMonthlyPdfHtml(
                $reportContent->data->staff_attendance,
                $reportContent->data->monthly_stats,
                $month
            );

            // Générer le PDF avec DomPDF
            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream("rapport_presence_personnel_{$month}.pdf");

        } catch (\Exception $e) {
            Log::error('Error in StaffAttendanceReportController@exportStaffAttendanceMonthlyPdf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Excel du rapport mensuel de présence du personnel
     */
    public function exportStaffAttendanceMonthlyExcel(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            
            // Récupérer les données du rapport
            $reportData = $this->getStaffAttendanceMonthlyReport($request);
            $reportContent = $reportData->getData();
            
            if (!$reportContent->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des données'
                ], 400);
            }

            // Créer le contenu CSV/Excel
            $csvData = $this->generateStaffAttendanceMonthlyCSV(
                $reportContent->data->staff_attendance,
                $reportContent->data->monthly_stats,
                $month
            );

            $filename = "rapport_presence_personnel_{$month}.csv";
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename={$filename}")
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Expires', '0')
                ->header('Pragma', 'public');

        } catch (\Exception $e) {
            Log::error('Error in StaffAttendanceReportController@exportStaffAttendanceMonthlyExcel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer les statistiques mensuelles d'un membre du personnel
     */
    private function calculateStaffMonthlyStats($staffMember, $monthStart, $monthEnd)
    {
        // Récupérer les présences du mois
        $attendances = StaffAttendance::where('user_id', $staffMember->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->get();

        $totalDays = count($attendances);
        $presentDays = $attendances->where('is_present', true)->count();
        
        // Calculer le temps de travail total en minutes
        $totalWorkingMinutes = 0;
        foreach ($attendances as $attendance) {
            if ($attendance->work_hours) {
                $totalWorkingMinutes += $attendance->work_hours * 60; // Convertir les heures en minutes
            }
        }
        
        $avgWorkingMinutesPerDay = $totalDays > 0 ? $totalWorkingMinutes / $totalDays : 0;

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $totalDays - $presentDays,
            'total_working_minutes' => round($totalWorkingMinutes, 0),
            'avg_working_minutes_per_day' => round($avgWorkingMinutesPerDay, 0)
        ];
    }

    /**
     * Calculer les statistiques globales du mois
     */
    private function calculateMonthlyGlobalStats($attendanceData, $workingDays)
    {
        $totalStaff = count($attendanceData);
        
        if ($totalStaff === 0) {
            return [
                'total_staff' => 0,
                'total_working_days' => $workingDays,
                'avg_attendance_rate' => 0,
                'avg_working_hours' => 0,
                'excellent_attendance' => 0,
                'poor_attendance' => 0
            ];
        }

        $totalAttendanceRate = 0;
        $totalWorkingMinutes = 0;
        $excellentCount = 0;
        $poorCount = 0;

        foreach ($attendanceData as $staff) {
            $attendanceRate = $staff['stats']['total_days'] > 0 ? 
                ($staff['stats']['present_days'] / $staff['stats']['total_days']) * 100 : 0;
            
            $totalAttendanceRate += $attendanceRate;
            $totalWorkingMinutes += $staff['stats']['total_working_minutes'];
            
            if ($attendanceRate >= 95) {
                $excellentCount++;
            } elseif ($attendanceRate < 70) {
                $poorCount++;
            }
        }

        return [
            'total_staff' => $totalStaff,
            'total_working_days' => $workingDays,
            'avg_attendance_rate' => round($totalAttendanceRate / $totalStaff, 1),
            'avg_working_hours' => round(($totalWorkingMinutes / $totalStaff), 0),
            'excellent_attendance' => $excellentCount,
            'poor_attendance' => $poorCount
        ];
    }

    /**
     * Calculer le nombre de jours ouvrables dans un mois
     */
    private function getWorkingDaysInMonth($month)
    {
        $startDate = new \DateTime($month . '-01');
        $endDate = new \DateTime($startDate->format('Y-m-t'));
        
        $workingDays = 0;
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            // Compter tous les jours sauf samedi et dimanche
            $dayOfWeek = (int) $currentDate->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) { // 0 = Dimanche, 6 = Samedi
                $workingDays++;
            }
            $currentDate->add(new \DateInterval('P1D'));
        }
        
        return $workingDays;
    }

    /**
     * Générer le HTML pour l'export PDF du rapport mensuel de présence
     */
    private function generateStaffAttendanceMonthlyPdfHtml($attendanceData, $monthlyStats, $month)
    {
        $schoolSettings = \App\Models\SchoolSetting::getSettings();
        
        // Obtenir le logo en base64
        $logoBase64 = '';
        if ($schoolSettings->logo) {
            $logoPath = storage_path('app/public/logos/' . $schoolSettings->logo);
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            }
        }

        $monthName = date('F Y', strtotime($month . '-01'));

        $html = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Rapport Présence Personnel - {$monthName}</title>
            <style>
                @page { size: A4 landscape; margin: 0.8cm; }
                body { font-family: 'Times New Roman', serif; font-size: 9px; line-height: 1.2; margin: 0; padding: 8px; }
                .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .header h1 { font-size: 16px; margin: 8px 0; }
                .header .logo { float: left; width: 60px; height: 60px; margin-right: 10px; }
                .stats-section { margin-bottom: 15px; background: #f9f9f9; padding: 10px; }
                table { width: 100%; border-collapse: collapse; font-size: 8px; }
                th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .clear-both { clear: both; }
            </style>
        </head>
        <body>
            <div class='header'>
                " . ($logoBase64 ? "<img class='logo' src='{$logoBase64}' alt='Logo'>" : '') . "
                <h1>{$schoolSettings->school_name}</h1>
                <div class='clear-both'></div>
                <h2>RAPPORT DE PRÉSENCE DU PERSONNEL</h2>
                <p><strong>Période:</strong> {$monthName} | <strong>Date d'édition:</strong> " . now()->format('d/m/Y à H:i') . "</p>
            </div>

            <div class='stats-section'>
                <h3>STATISTIQUES GLOBALES</h3>
                <table>
                    <tr>
                        <th>Personnel Total</th>
                        <th>Jours Ouvrables</th>
                        <th>Taux Présence Moyen</th>
                        <th>Personnel Excellent (≥95%)</th>
                        <th>Personnel Faible (&lt;70%)</th>
                    </tr>
                    <tr>
                        <td class='text-center'>{$monthlyStats->total_staff}</td>
                        <td class='text-center'>{$monthlyStats->total_working_days}</td>
                        <td class='text-center'>{$monthlyStats->avg_attendance_rate}%</td>
                        <td class='text-center'>{$monthlyStats->excellent_attendance}</td>
                        <td class='text-center'>{$monthlyStats->poor_attendance}</td>
                    </tr>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Personnel</th>
                        <th>Rôle</th>
                        <th>Jours Travaillés</th>
                        <th>Jours Présents</th>
                        <th>Taux Présence</th>
                        <th>Temps Total (min)</th>
                        <th>Temps Moyen/Jour</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($attendanceData as $index => $staff) {
            // Convertir l'objet en tableau si nécessaire
            $staffArray = is_array($staff) ? $staff : (array)$staff;
            $statsArray = is_array($staffArray['stats']) ? $staffArray['stats'] : (array)$staffArray['stats'];

            $attendanceRate = $statsArray['total_days'] > 0 ?
                ($statsArray['present_days'] / $statsArray['total_days']) * 100 : 0;

            $status = $attendanceRate >= 95 ? 'Excellent' :
                     ($attendanceRate >= 85 ? 'Bon' :
                     ($attendanceRate >= 70 ? 'Moyen' : 'Faible'));

            $html .= "<tr>
                        <td class='text-center'>" . ($index + 1) . "</td>
                        <td>{$staffArray['name']}</td>
                        <td>{$staffArray['role']}</td>
                        <td class='text-center'>{$statsArray['total_days']}</td>
                        <td class='text-center'>{$statsArray['present_days']}</td>
                        <td class='text-center'>" . number_format($attendanceRate, 1) . "%</td>
                        <td class='text-center'>{$statsArray['total_working_minutes']}</td>
                        <td class='text-center'>{$statsArray['avg_working_minutes_per_day']}</td>
                        <td class='text-center'>{$status}</td>
                    </tr>";
        }

        $html .= "</tbody>
            </table>
            
            <div style='margin-top: 20px; font-size: 8px; text-align: right;'>
                <em>Document généré automatiquement le " . now()->format('d/m/Y à H:i:s') . "</em>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Générer le CSV pour l'export Excel du rapport mensuel de présence
     */
    private function generateStaffAttendanceMonthlyCSV($attendanceData, $monthlyStats, $month)
    {
        $monthName = date('F Y', strtotime($month . '-01'));
        
        $csv = "Rapport de Présence du Personnel - {$monthName}\n";
        $csv .= "Généré le " . now()->format('d/m/Y à H:i') . "\n\n";
        
        $csv .= "STATISTIQUES GLOBALES\n";
        $csv .= "Personnel Total,Jours Ouvrables,Taux Présence Moyen,Personnel Excellent,Personnel Faible\n";
        $csv .= "{$monthlyStats['total_staff']},{$monthlyStats['total_working_days']},{$monthlyStats['avg_attendance_rate']}%,{$monthlyStats['excellent_attendance']},{$monthlyStats['poor_attendance']}\n\n";
        
        $csv .= "DÉTAIL PAR PERSONNEL\n";
        $csv .= "N°,Personnel,Email,Rôle,Jours Travaillés,Jours Présents,Taux Présence,Temps Total (min),Temps Moyen/Jour,Statut\n";
        
        foreach ($attendanceData as $index => $staff) {
            $attendanceRate = $staff['stats']['total_days'] > 0 ? 
                ($staff['stats']['present_days'] / $staff['stats']['total_days']) * 100 : 0;
            
            $status = $attendanceRate >= 95 ? 'Excellent' :
                     ($attendanceRate >= 85 ? 'Bon' :
                     ($attendanceRate >= 70 ? 'Moyen' : 'Faible'));

            $csv .= ($index + 1) . ",\"{$staff['name']}\",{$staff['email']},{$staff['role']},{$staff['stats']['total_days']},{$staff['stats']['present_days']}," . number_format($attendanceRate, 1) . "%,{$staff['stats']['total_working_minutes']},{$staff['stats']['avg_working_minutes_per_day']},{$status}\n";
        }
        
        return $csv;
    }

    /**
     * Rapport spécifique pour les enseignants vacataires avec détail des classes
     */
    public function getVacataireAttendanceReport(Request $request)
    {
        try {
            // Gestion des filtres de date
            $month = $request->get('month', now()->format('Y-m'));
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Si start_date et end_date sont fournis, on les utilise
            if ($startDate && $endDate) {
                $monthStart = $startDate;
                $monthEnd = $endDate;
            } else {
                // Sinon, on utilise le mois entier
                $monthStart = $month . '-01';
                $monthEnd = date('Y-m-t', strtotime($monthStart));
            }

            // Filtre par personne (user_id)
            $userId = $request->get('user_id');

            // Récupérer tous les vacataires et semi-permanents via la table teachers
            $vacatairesQuery = User::whereHas('teacher', function($query) {
                    $query->whereIn('type_personnel', ['V', 'SP']); // V = Vacataire, SP = Semi-Permanent
                })
                ->where('is_active', true)
                ->with('teacher');

            // Si un user_id est fourni, filtrer par cette personne
            if ($userId) {
                $vacatairesQuery->where('id', $userId);
            }

            $vacataires = $vacatairesQuery->get();

            $vacataireData = [];

            foreach ($vacataires as $vacataire) {
                // Récupérer toutes les présences du mois avec les classes
                $attendances = StaffAttendance::where('user_id', $vacataire->id)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->whereIn('staff_type', ['vacataire', 'semi_permanent'])
                    ->with(['attendanceClasses.schoolClass'])
                    ->get();

                // Compter les jours travaillés et les classes enseignées
                $totalDays = $attendances->count();
                $presentDays = $attendances->where('is_present', true)->count();

                // Regrouper par classe
                $classesTaught = [];
                $totalWorkingMinutes = 0;

                foreach ($attendances as $attendance) {
                    // Temps de travail
                    if ($attendance->work_hours) {
                        $totalWorkingMinutes += $attendance->work_hours * 60;
                    }

                    // Classes enseignées
                    if ($attendance->attendanceClasses) {
                        foreach ($attendance->attendanceClasses as $attendanceClass) {
                            $className = $attendanceClass->schoolClass->name ?? 'Classe inconnue';

                            if (!isset($classesTaught[$className])) {
                                $classesTaught[$className] = [
                                    'class_id' => $attendanceClass->school_class_id,
                                    'class_name' => $className,
                                    'days_count' => 0,
                                    'total_hours' => 0
                                ];
                            }

                            $classesTaught[$className]['days_count']++;
                            if ($attendance->work_hours) {
                                $classesTaught[$className]['total_hours'] += $attendance->work_hours;
                            }
                        }
                    }
                }

                // Calculer le taux de présence
                $attendanceRate = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;

                // Calculer les heures totales et moyennes
                $totalHours = round($totalWorkingMinutes / 60, 2);
                $avgHoursPerDay = $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0;

                // Déterminer le type de personnel depuis la table teacher
                $typePersonnel = $vacataire->teacher ? $vacataire->teacher->type_personnel : 'V';
                $roleLabel = $typePersonnel === 'V' ? 'vacataire' : 'semi_permanent';

                $vacataireData[] = [
                    'id' => $vacataire->id,
                    'name' => $vacataire->name,
                    'email' => $vacataire->email,
                    'role' => $roleLabel,
                    'total_days' => $totalDays,
                    'present_days' => $presentDays,
                    'attendance_rate' => round($attendanceRate, 1),
                    'total_hours' => $totalHours,
                    'avg_hours_per_day' => $avgHoursPerDay,
                    'classes_taught' => array_values($classesTaught),
                    'total_classes' => count($classesTaught)
                ];
            }

            // Statistiques globales
            $totalVacataires = count($vacataireData);
            $totalDaysTaught = array_sum(array_column($vacataireData, 'total_days'));
            $totalHoursTaught = array_sum(array_column($vacataireData, 'total_hours'));
            $avgAttendanceRate = $totalVacataires > 0 ?
                array_sum(array_column($vacataireData, 'attendance_rate')) / $totalVacataires : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'vacataires' => $vacataireData,
                    'statistics' => [
                        'total_vacataires' => $totalVacataires,
                        'total_days_taught' => $totalDaysTaught,
                        'total_hours_taught' => round($totalHoursTaught, 2),
                        'avg_attendance_rate' => round($avgAttendanceRate, 1),
                        'avg_hours_per_vacataire' => $totalVacataires > 0 ?
                            round($totalHoursTaught / $totalVacataires, 2) : 0
                    ],
                    'period' => [
                        'month' => $month,
                        'start_date' => $monthStart,
                        'end_date' => $monthEnd
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getVacataireAttendanceReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport vacataires',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF du rapport vacataires
     */
    public function exportVacataireAttendancePdf(Request $request)
    {
        try {
            // Récupérer les paramètres de filtre
            $month = $request->get('month', now()->format('Y-m'));
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $userId = $request->get('user_id');

            // Récupérer les données avec les filtres
            $reportData = $this->getVacataireAttendanceReport($request);
            $reportContent = $reportData->getData();

            if (!$reportContent->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des données'
                ], 400);
            }

            // Déterminer la période pour le nom de fichier et le titre
            $periodLabel = $month;
            $fileName = "rapport_vacataires_{$month}.pdf";

            if ($startDate && $endDate) {
                $periodLabel = "du {$startDate} au {$endDate}";
                $fileName = "rapport_vacataires_{$startDate}_{$endDate}.pdf";
            }

            // Si filtre par personne, ajouter au nom
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $fileName = "rapport_vacataire_{$user->name}_{$month}.pdf";
                }
            }

            // Convertir les objets en tableaux pour le HTML
            $vacataires = json_decode(json_encode($reportContent->data->vacataires), true);
            $statistics = json_decode(json_encode($reportContent->data->statistics), true);

            $html = $this->generateVacataireAttendancePdfHtml(
                $vacataires,
                $statistics,
                $periodLabel
            );

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('Error in exportVacataireAttendancePdf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF vacataires',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le PDF des vacataires
     */
    private function generateVacataireAttendancePdfHtml($vacataireData, $statistics, $month)
    {
        $schoolSettings = \App\Models\SchoolSetting::getSettings();

        // Logo en base64
        $logoBase64 = '';
        if ($schoolSettings->logo) {
            $logoPath = storage_path('app/public/logos/' . $schoolSettings->logo);
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            }
        }

        $monthName = date('F Y', strtotime($month . '-01'));

        $html = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Rapport Vacataires - {$monthName}</title>
            <style>
                @page { size: A4 landscape; margin: 0.8cm; }
                body { font-family: 'Times New Roman', serif; font-size: 9px; line-height: 1.2; margin: 0; padding: 8px; }
                .header { text-align: center; margin-bottom: 15px; border-bottom: 3px solid #9333ea; padding-bottom: 10px; }
                .header h1 { font-size: 18px; margin: 8px 0; color: #9333ea; }
                .header .logo { float: left; width: 60px; height: 60px; margin-right: 10px; }
                .stats-section { margin-bottom: 15px; background: #f3f4f6; padding: 12px; border-left: 4px solid #9333ea; }
                .stats-grid { display: flex; justify-content: space-around; margin-top: 10px; }
                .stat-item { text-align: center; }
                .stat-value { font-size: 20px; font-weight: bold; color: #9333ea; }
                .stat-label { font-size: 9px; color: #666; }
                table { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #9333ea; color: white; font-weight: bold; text-align: center; }
                .classes-list { font-size: 7px; color: #059669; }
                .text-center { text-align: center; }
                .vacataire-row { background: #fef3c7; }
                .semi-permanent-row { background: #dbeafe; }
                .clear-both { clear: both; }
                .footer { margin-top: 20px; font-size: 7px; text-align: center; color: #999; }
            </style>
        </head>
        <body>
            <div class='header'>
                " . ($logoBase64 ? "<img class='logo' src='{$logoBase64}' alt='Logo'>" : '') . "
                <h1>{$schoolSettings->school_name}</h1>
                <div class='clear-both'></div>
                <h2 style='color: #9333ea;'>📊 RAPPORT ENSEIGNANTS VACATAIRES & SEMI-PERMANENTS</h2>
                <p><strong>Période:</strong> {$monthName} | <strong>Date:</strong> " . now()->format('d/m/Y à H:i') . "</p>
            </div>

            <div class='stats-section'>
                <h3 style='margin-bottom: 10px;'>📈 STATISTIQUES GLOBALES</h3>
                <div class='stats-grid'>
                    <div class='stat-item'>
                        <div class='stat-value'>{$statistics['total_vacataires']}</div>
                        <div class='stat-label'>Vacataires/Semi-Perm.</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-value'>{$statistics['total_days_taught']}</div>
                        <div class='stat-label'>Jours Enseignés</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-value'>{$statistics['total_hours_taught']}h</div>
                        <div class='stat-label'>Heures Totales</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-value'>{$statistics['avg_attendance_rate']}%</div>
                        <div class='stat-label'>Taux Présence Moyen</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-value'>{$statistics['avg_hours_per_vacataire']}h</div>
                        <div class='stat-label'>Moyenne Heures/Personne</div>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style='width: 25px;'>N°</th>
                        <th style='width: 180px;'>Nom & Prénom</th>
                        <th style='width: 70px;'>Type</th>
                        <th style='width: 50px;'>Jours</th>
                        <th style='width: 50px;'>Présent</th>
                        <th style='width: 55px;'>Taux %</th>
                        <th style='width: 50px;'>Heures</th>
                        <th style='width: 50px;'>H/Jour</th>
                        <th style='width: 40px;'>Classes</th>
                        <th>Classes Enseignées (Détail)</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($vacataireData as $index => $vacataire) {
            $rowClass = $vacataire['role'] === 'vacataire' ? 'vacataire-row' : 'semi-permanent-row';
            $typeLabel = $vacataire['role'] === 'vacataire' ? 'Vacataire' : 'Semi-Perm.';

            // Générer la liste des classes
            $classesDetails = [];
            foreach ($vacataire['classes_taught'] as $class) {
                $classesDetails[] = "{$class['class_name']} ({$class['days_count']}j, {$class['total_hours']}h)";
            }
            $classesText = !empty($classesDetails) ? implode(' • ', $classesDetails) : 'Aucune';

            $html .= "<tr class='{$rowClass}'>
                        <td class='text-center'>" . ($index + 1) . "</td>
                        <td><strong>{$vacataire['name']}</strong></td>
                        <td class='text-center'>{$typeLabel}</td>
                        <td class='text-center'>{$vacataire['total_days']}</td>
                        <td class='text-center'>{$vacataire['present_days']}</td>
                        <td class='text-center'><strong>{$vacataire['attendance_rate']}%</strong></td>
                        <td class='text-center'>{$vacataire['total_hours']}</td>
                        <td class='text-center'>{$vacataire['avg_hours_per_day']}</td>
                        <td class='text-center'><strong>{$vacataire['total_classes']}</strong></td>
                        <td class='classes-list'>{$classesText}</td>
                    </tr>";
        }

        $html .= "</tbody>
            </table>

            <div class='footer'>
                <p><strong>Légende:</strong> 🟡 Vacataire | 🔵 Semi-Permanent | Format classe: Nom (jours enseignés, heures totales)</p>
                <p>Document généré automatiquement le " . now()->format('d/m/Y à H:i:s') . " par le système de gestion CPBD</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Récupérer la liste des vacataires pour les filtres
     */
    public function getVacatairesList()
    {
        try {
            $vacataires = User::whereHas('teacher', function($query) {
                    $query->whereIn('type_personnel', ['V', 'SP']); // V = Vacataire, SP = Semi-Permanent
                })
                ->where('is_active', true)
                ->with('teacher')
                ->get()
                ->map(function($user) {
                    $typePersonnel = $user->teacher ? $user->teacher->type_personnel : 'V';
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $typePersonnel === 'V' ? 'vacataire' : 'semi_permanent'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $vacataires
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getVacatairesList: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la liste des vacataires',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Excel du rapport vacataires
     */
    public function exportVacataireAttendanceExcel(Request $request)
    {
        try {
            // Récupérer les données
            $reportData = $this->getVacataireAttendanceReport($request);
            $reportContent = $reportData->getData();

            if (!$reportContent->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des données'
                ], 400);
            }

            $vacataireData = $reportContent->data->vacataires;
            $statistics = $reportContent->data->statistics;
            $period = $reportContent->data->period;

            // Créer le fichier CSV
            $filename = "rapport_vacataires_" . $period->month . ".csv";

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($vacataireData, $statistics, $period) {
                $file = fopen('php://output', 'w');

                // BOM UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // En-têtes du rapport
                fputcsv($file, ['RAPPORT ENSEIGNANTS VACATAIRES & SEMI-PERMANENTS'], ';');
                fputcsv($file, ['Période: ' . $period->month], ';');
                fputcsv($file, [''], ';');

                // Statistiques globales
                fputcsv($file, ['STATISTIQUES GLOBALES'], ';');
                fputcsv($file, ['Total Vacataires/Semi-Perm.', $statistics->total_vacataires], ';');
                fputcsv($file, ['Jours Enseignés', $statistics->total_days_taught], ';');
                fputcsv($file, ['Heures Totales', $statistics->total_hours_taught . 'h'], ';');
                fputcsv($file, ['Taux Présence Moyen', $statistics->avg_attendance_rate . '%'], ';');
                fputcsv($file, ['Moyenne Heures/Personne', $statistics->avg_hours_per_vacataire . 'h'], ';');
                fputcsv($file, [''], ';');

                // En-têtes des colonnes
                fputcsv($file, [
                    'N°',
                    'Nom & Prénom',
                    'Type',
                    'Total Jours',
                    'Jours Présents',
                    'Taux Présence (%)',
                    'Heures Totales',
                    'Heures/Jour',
                    'Nb Classes',
                    'Classes Enseignées (Détail)'
                ], ';');

                // Données des vacataires
                foreach ($vacataireData as $index => $vacataire) {
                    $typeLabel = $vacataire->role === 'vacataire' ? 'Vacataire' : 'Semi-Permanent';

                    // Générer la liste des classes
                    $classesDetails = [];
                    foreach ($vacataire->classes_taught as $class) {
                        $classesDetails[] = "{$class->class_name} ({$class->days_count}j, {$class->total_hours}h)";
                    }
                    $classesText = !empty($classesDetails) ? implode(' | ', $classesDetails) : 'Aucune';

                    fputcsv($file, [
                        $index + 1,
                        $vacataire->name,
                        $typeLabel,
                        $vacataire->total_days,
                        $vacataire->present_days,
                        $vacataire->attendance_rate . '%',
                        $vacataire->total_hours . 'h',
                        $vacataire->avg_hours_per_day . 'h',
                        $vacataire->total_classes,
                        $classesText
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error in exportVacataireAttendanceExcel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport vacataires au format calendrier mensuel
     * Format: Un vacataire par ligne, un jour par colonne avec In/Out
     */
    public function getVacataireMonthlyCalendarReport(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $userId = $request->get('user_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Si des dates personnalisées sont fournies, les utiliser
            if ($startDate && $endDate) {
                $monthStart = $startDate;
                $monthEnd = $endDate;

                // Calculer le nombre de jours dans la période
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $daysInMonth = $start->diffInDays($end) + 1;
            } else {
                // Sinon, utiliser le mois entier
                $monthStart = $month . '-01';
                $monthEnd = date('Y-m-t', strtotime($monthStart));
                $daysInMonth = date('t', strtotime($monthStart));
            }

            // Générer la liste des jours de la période avec leur nom
            $daysHeader = [];
            if ($startDate && $endDate) {
                // Mode dates personnalisées : itérer jour par jour
                $currentDate = \Carbon\Carbon::parse($startDate);
                $endDateCarbon = \Carbon\Carbon::parse($endDate);

                while ($currentDate <= $endDateCarbon) {
                    $daysHeader[] = [
                        'day' => (int) $currentDate->format('d'),
                        'day_name' => $currentDate->format('D'),
                        'date' => $currentDate->format('Y-m-d')
                    ];
                    $currentDate->addDay();
                }
            } else {
                // Mode mois : afficher tous les jours du mois
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $dayName = \Carbon\Carbon::parse($date)->locale('en')->format('D'); // Mon, Tue, Wed...
                    $daysHeader[] = [
                        'day' => $day,
                        'day_name' => $dayName,
                        'date' => $date
                    ];
                }
            }

            // Récupérer tous les vacataires
            $vacatairesQuery = User::whereHas('teacher', function($query) {
                    $query->whereIn('type_personnel', ['V', 'SP']);
                })
                ->where('is_active', true)
                ->with('teacher')
                ->orderBy('name');

            if ($userId) {
                $vacatairesQuery->where('id', $userId);
            }

            $vacataires = $vacatairesQuery->get();
            $vacataireData = [];

            foreach ($vacataires as $vacataire) {
                // Récupérer toutes les présences du mois
                $attendances = StaffAttendance::where('user_id', $vacataire->id)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->whereIn('staff_type', ['vacataire', 'semi_permanent'])
                    ->orderBy('scanned_at', 'asc')
                    ->get();

                // Organiser les présences par jour
                $dailyAttendance = [];
                $totalSeconds = 0;
                $workingDays = 0;
                $presentDays = 0;
                $absentDays = 0;
                $lateDays = 0;
                $halfDays = 0;

                foreach ($attendances as $att) {
                    // Convertir la date en string si c'est un objet Carbon
                    $date = is_string($att->attendance_date) ? $att->attendance_date : $att->attendance_date->format('Y-m-d');

                    if (!isset($dailyAttendance[$date])) {
                        $dailyAttendance[$date] = [
                            'entries' => [],
                            'exits' => []
                        ];
                    }

                    if ($att->event_type === 'entry') {
                        $dailyAttendance[$date]['entries'][] = [
                            'time' => $att->scanned_at->format('H:i:s'),
                            'scanned_at' => $att->scanned_at,
                            'late_minutes' => $att->late_minutes ?? 0
                        ];
                        $presentDays++;
                        if ($att->late_minutes > 0) {
                            $lateDays++;
                        }
                    } elseif ($att->event_type === 'exit') {
                        $dailyAttendance[$date]['exits'][] = [
                            'time' => $att->scanned_at->format('H:i:s'),
                            'scanned_at' => $att->scanned_at
                        ];
                    }
                }

                // Calculer les heures travaillées en associant chaque Entry avec sa Exit correspondante
                foreach ($dailyAttendance as $date => $dayData) {
                    $entries = $dayData['entries'];
                    $exits = $dayData['exits'];

                    // Trier les entrées et sorties par heure
                    usort($entries, function($a, $b) {
                        return $a['scanned_at'] <=> $b['scanned_at'];
                    });
                    usort($exits, function($a, $b) {
                        return $a['scanned_at'] <=> $b['scanned_at'];
                    });

                    // Sauvegarder les tableaux triés dans dailyAttendance
                    $dailyAttendance[$date]['entries'] = $entries;
                    $dailyAttendance[$date]['exits'] = $exits;

                    // Associer chaque entrée avec la première sortie qui vient après
                    $entryCount = count($entries);
                    $exitIndex = 0;

                    for ($i = 0; $i < $entryCount; $i++) {
                        $entryTime = $entries[$i]['scanned_at'];

                        // Chercher la prochaine sortie qui vient après cette entrée
                        while ($exitIndex < count($exits)) {
                            $exitTime = $exits[$exitIndex]['scanned_at'];

                            if ($exitTime > $entryTime) {
                                // Trouvé une sortie valide après l'entrée
                                // Utiliser diffInSeconds avec false pour avoir une valeur signée
                                $workedSeconds = $entryTime->diffInSeconds($exitTime, false);

                                // Ne prendre que les valeurs positives
                                if ($workedSeconds > 0) {
                                    $totalSeconds += $workedSeconds;
                                    \Log::info("Added worked time for {$vacataire->name} on {$date}: {$workedSeconds} seconds (Entry: {$entryTime->format('Y-m-d H:i:s')}, Exit: {$exitTime->format('Y-m-d H:i:s')})");
                                } else {
                                    \Log::warning("Negative worked time for {$vacataire->name} on {$date}: {$workedSeconds} seconds (Entry: {$entryTime->format('Y-m-d H:i:s')}, Exit: {$exitTime->format('Y-m-d H:i:s')})");
                                }
                                $exitIndex++; // Passer à la sortie suivante
                                break;
                            } else {
                                // Cette sortie est avant l'entrée, passer à la suivante
                                $exitIndex++;
                            }
                        }
                    }
                }

                // Créer le tableau des jours avec toutes les paires In/Out pour chaque jour
                $days = [];
                foreach ($daysHeader as $dayInfo) {
                    $date = $dayInfo['date'];

                    if (isset($dailyAttendance[$date])) {
                        $entries = $dailyAttendance[$date]['entries'];
                        $exits = $dailyAttendance[$date]['exits'];

                        // Créer un tableau de paires [In/Out]
                        $pairs = [];
                        $entryCount = count($entries);

                        for ($i = 0; $i < $entryCount; $i++) {
                            $pairs[] = [
                                'in' => $entries[$i]['time'],
                                'out' => isset($exits[$i]) ? $exits[$i]['time'] : null,
                                'late_minutes' => $entries[$i]['late_minutes']
                            ];
                        }

                        $days[] = [
                            'pairs' => $pairs,  // Toutes les paires [In/Out]
                            'is_weekend' => \Carbon\Carbon::parse($date)->dayOfWeek == 0 || \Carbon\Carbon::parse($date)->dayOfWeek == 6
                        ];
                    } else {
                        $days[] = null; // Jour non travaillé
                    }
                }

                // Calculer le taux de présence (jours travaillés / jours ouvrables de la période)
                // Pour simplifier, on considère tous les jours sauf dimanche
                $workingDaysInMonth = 0;
                foreach ($daysHeader as $dayInfo) {
                    $dayOfWeek = \Carbon\Carbon::parse($dayInfo['date'])->dayOfWeek;
                    if ($dayOfWeek != 0) { // 0 = Dimanche
                        $workingDaysInMonth++;
                    }
                }

                $attendanceRate = $workingDaysInMonth > 0 ? ($presentDays / $workingDaysInMonth) * 100 : 0;

                // Formater le temps total en HH:MM:SS
                // S'assurer que totalSeconds n'est pas négatif
                $totalSeconds = max(0, $totalSeconds);
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                $totalTimeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                $typePersonnel = $vacataire->teacher && $vacataire->teacher->type_personnel === 'V' ? 'Vacataire' : 'Semi-Permanent';

                $vacataireData[] = [
                    'id' => $vacataire->id,
                    'name' => $vacataire->teacher ?
                        ($vacataire->teacher->first_name . ' ' . $vacataire->teacher->last_name) :
                        $vacataire->name,
                    'type' => $typePersonnel,
                    'days' => $days,
                    'attendance_rate' => round($attendanceRate, 2),
                    'total_hours' => $totalTimeFormatted,
                    'working_days' => $workingDaysInMonth,
                    'present_days' => $presentDays,
                    'absent_days' => $workingDaysInMonth - $presentDays,
                    'late_days' => $lateDays,
                    'half_days' => $halfDays
                ];
            }

            // Préparer le nom de la période
            $periodName = ($startDate && $endDate)
                ? \Carbon\Carbon::parse($startDate)->locale('fr')->isoFormat('D MMM') . ' - ' .
                  \Carbon\Carbon::parse($endDate)->locale('fr')->isoFormat('D MMM YYYY')
                : \Carbon\Carbon::parse($monthStart)->locale('fr')->isoFormat('MMMM YYYY');

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'monthName' => $periodName,
                    'year' => \Carbon\Carbon::parse($monthStart)->year,
                    'daysHeader' => $daysHeader,
                    'vacataires' => $vacataireData,
                    'workingDaysInMonth' => $workingDaysInMonth
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getVacataireMonthlyCalendarReport: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport calendrier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF du rapport calendrier mensuel vacataires
     */
    public function exportVacataireCalendarPdf(Request $request)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            $vacataireId = $request->input('vacataire_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Utiliser la même méthode que l'API JSON
            $request->merge(['user_id' => $vacataireId]);
            $apiResponse = $this->getVacataireMonthlyCalendarReport($request);
            $responseData = json_decode($apiResponse->getContent(), true);

            if (!$responseData['success']) {
                throw new \Exception($responseData['message'] ?? 'Erreur lors de la génération des données');
            }

            $reportData = $responseData['data'];

            // Augmenter la limite de mémoire pour les gros rapports
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);

            // Ajouter les informations supplémentaires pour le PDF
            $reportData['generatedAt'] = now()->locale('fr')->isoFormat('DD MMMM YYYY à HH:mm');
            $reportData['schoolYear'] = SchoolYear::where('is_current', true)->first()->name ?? 'N/A';

            // Générer le PDF
            $pdf = \PDF::loadView('reports.vacataire-attendance-calendar', $reportData);
            $pdf->setPaper('A3', 'landscape');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);

            // Nom du fichier
            $fileName = 'Rapport_Vacataires_Calendrier_' . $reportData['monthName'] . '_' . $reportData['year'] . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export PDF calendrier vacataires', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Excel du rapport calendrier mensuel vacataires
     */
    public function exportVacataireCalendarExcel(Request $request)
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            $vacataireId = $request->input('vacataire_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Utiliser la même méthode que l'API JSON
            $request->merge(['user_id' => $vacataireId]);
            $apiResponse = $this->getVacataireMonthlyCalendarReport($request);
            $responseData = json_decode($apiResponse->getContent(), true);

            if (!$responseData['success']) {
                throw new \Exception($responseData['message'] ?? 'Erreur lors de la génération des données');
            }

            $reportData = $responseData['data'];

            // Augmenter la limite de mémoire pour les gros rapports
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300);

            // Créer un nouveau fichier Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // En-tête
            $sheet->setCellValue('A1', 'COLLÈGE POLYVALENT BILINGUE DE DOUALA');
            $sheet->mergeCells('A1:' . chr(65 + count($reportData['daysHeader']) + 7) . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'Rapport Mensuel de Présences - Vacataires et Semi-Permanents');
            $sheet->mergeCells('A2:' . chr(65 + count($reportData['daysHeader']) + 7) . '2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Informations du mois
            $row = 4;
            $sheet->setCellValue('A' . $row, 'Mois: ' . $reportData['monthName'] . ' ' . $reportData['year']);
            $sheet->setCellValue('C' . $row, 'Total Vacataires: ' . count($reportData['vacataires']));
            $sheet->setCellValue('E' . $row, 'Jours ouvrables: ' . ($reportData['workingDaysInMonth'] ?? 0));

            // En-têtes de colonnes
            $row = 6;
            $col = 0;
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'Employé');

            // Jours du mois
            foreach ($reportData['daysHeader'] as $dayInfo) {
                $sheet->setCellValueByColumnAndRow(++$col, $row, $dayInfo['day_name'] . ' ' . str_pad($dayInfo['day'], 2, '0', STR_PAD_LEFT));
            }

            // Colonnes statistiques
            $sheet->setCellValueByColumnAndRow(++$col, $row, '(%)');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'Total Heures');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'W');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'P');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'A');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'L');
            $sheet->setCellValueByColumnAndRow(++$col, $row, 'HD');

            // Style des en-têtes
            $headerRange = 'A' . $row . ':' . chr(64 + $col) . $row;
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF007BFF');
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Données des vacataires
            foreach ($reportData['vacataires'] as $vacataire) {
                $row++;
                $col = 0;

                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['name']);

                // Jours de présence
                foreach ($vacataire['days'] as $dayData) {
                    $cellValue = '-';
                    if ($dayData && isset($dayData['in'])) {
                        $cellValue = 'In: ' . $dayData['in'] . "\n";
                        $cellValue .= 'Out: ' . ($dayData['out'] ?? '--:--');
                        if (isset($dayData['late_minutes']) && $dayData['late_minutes'] > 0) {
                            $cellValue .= "\n(" . $dayData['late_minutes'] . 'min retard)';
                        }
                    }
                    $sheet->setCellValueByColumnAndRow(++$col, $row, $cellValue);
                    $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setWrapText(true);
                }

                // Statistiques
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['attendance_rate'] . '%');
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['total_hours']);
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['working_days']);
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['present_days']);
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['absent_days']);
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['late_days']);
                $sheet->setCellValueByColumnAndRow(++$col, $row, $vacataire['half_days']);
            }

            // Auto-size des colonnes
            foreach (range('A', chr(64 + $col)) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Hauteur des lignes de données
            for ($i = 7; $i <= $row; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(30);
            }

            // Bordures
            $dataRange = 'A6:' . chr(64 + $col) . $row;
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Télécharger le fichier
            $fileName = 'Rapport_Vacataires_Calendrier_' . $reportData['monthName'] . '_' . $reportData['year'] . '.xlsx';

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export Excel calendrier vacataires', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de l\'Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DEPRECATED: Utiliser getVacataireMonthlyCalendarReport() à la place
     * Générer les données du rapport calendrier (méthode commune)
     */
    private function generateCalendarReportData($month, $vacataireId = null)
    {
        $date = \Carbon\Carbon::parse($month . '-01');
        $year = $date->year;
        $monthNumber = $date->month;
        $daysInMonth = $date->daysInMonth;

        // Nom du mois en français
        $monthName = $date->locale('fr')->translatedFormat('F');

        // Générer l'en-tête des jours
        $daysHeader = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $dayName = \Carbon\Carbon::parse($currentDate)->locale('en')->format('D');
            $daysHeader[] = [
                'day' => $day,
                'day_name' => $dayName,
                'date' => $currentDate
            ];
        }

        // Récupérer les vacataires
        $vacatairesQuery = User::whereIn('staff_type', ['vacataire', 'semi_permanent'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($vacataireId) {
            $vacatairesQuery->where('id', $vacataireId);
        }

        $vacataires = $vacatairesQuery->get();

        // Compter les jours ouvrables (lundi-samedi)
        $workingDaysInMonth = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = \Carbon\Carbon::parse($currentDate)->dayOfWeek;
            if ($dayOfWeek >= 1 && $dayOfWeek <= 6) {
                $workingDaysInMonth++;
            }
        }

        // Préparer les données pour chaque vacataire
        $vacataireData = [];

        foreach ($vacataires as $vacataire) {
            // Récupérer toutes les présences du mois
            $attendances = StaffAttendance::where('user_id', $vacataire->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $monthNumber)
                ->orderBy('attendance_date')
                ->orderBy('scanned_at')
                ->get();

            // Grouper par jour
            $dailyAttendance = [];
            foreach ($attendances as $attendance) {
                $date = $attendance->attendance_date;
                if (!isset($dailyAttendance[$date])) {
                    $dailyAttendance[$date] = [
                        'entries' => [],
                        'exits' => []
                    ];
                }

                if ($attendance->event_type === 'entry') {
                    $dailyAttendance[$date]['entries'][] = $attendance;
                } else {
                    $dailyAttendance[$date]['exits'][] = $attendance;
                }
            }

            // Construire le tableau des jours
            $days = [];
            $totalSeconds = 0;
            $presentDays = 0;
            $lateDays = 0;
            $halfDays = 0;

            foreach ($daysHeader as $dayInfo) {
                $date = $dayInfo['date'];
                $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
                $isWeekend = ($dayOfWeek == 0); // Dimanche

                if (isset($dailyAttendance[$date]) && !empty($dailyAttendance[$date]['entries'])) {
                    $entry = $dailyAttendance[$date]['entries'][0];
                    $exit = !empty($dailyAttendance[$date]['exits']) ? $dailyAttendance[$date]['exits'][0] : null;

                    $inTime = \Carbon\Carbon::parse($entry->scanned_at)->format('H:i:s');
                    $outTime = $exit ? \Carbon\Carbon::parse($exit->scanned_at)->format('H:i:s') : null;

                    $lateMinutes = $entry->late_minutes ?? 0;

                    // Calculer les heures travaillées
                    if ($exit && $entry->work_hours > 0) {
                        $totalSeconds += $entry->work_hours * 3600;
                    }

                    $days[] = [
                        'in' => $inTime,
                        'out' => $outTime,
                        'late_minutes' => $lateMinutes,
                        'is_weekend' => $isWeekend
                    ];

                    $presentDays++;
                    if ($lateMinutes > 0) {
                        $lateDays++;
                    }
                } else {
                    $days[] = [
                        'in' => null,
                        'out' => null,
                        'late_minutes' => 0,
                        'is_weekend' => $isWeekend
                    ];
                }
            }

            // Formater le total des heures en HH:MM:SS
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;
            $totalTimeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            // Calculer le taux de présence
            $attendanceRate = $workingDaysInMonth > 0 ? round(($presentDays / $workingDaysInMonth) * 100, 2) : 0;

            $vacataireData[] = [
                'id' => $vacataire->id,
                'name' => trim($vacataire->last_name . ' ' . $vacataire->first_name),
                'days' => $days,
                'attendance_rate' => $attendanceRate,
                'total_hours' => $totalTimeFormatted,
                'working_days' => $workingDaysInMonth,
                'present_days' => $presentDays,
                'absent_days' => $workingDaysInMonth - $presentDays,
                'late_days' => $lateDays,
                'half_days' => $halfDays
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'monthName' => $monthName,
            'daysInMonth' => $daysInMonth,
            'daysHeader' => $daysHeader,
            'vacataires' => $vacataireData,
            'workingDaysInMonth' => $workingDaysInMonth
        ];
    }

    /**
     * Rapport calendrier mensuel pour le personnel permanent (format identique aux vacataires)
     */
    public function getStaffMonthlyCalendarReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $format = $request->input('format', 'json');

        // Récupérer toutes les présences du mois (sans filtre de rôle)
        $attendances = StaffAttendance::whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->orderBy('scanned_at')
            ->get();

        // Récupérer tous les utilisateurs actifs qui ont des enregistrements de présence ce mois
        $staffIdsWithAttendance = $attendances->pluck('user_id')->unique()->toArray();

        $staffUsers = User::where('is_active', true)
            ->where(function ($query) use ($staffIdsWithAttendance) {
                // Inclure les utilisateurs avec des présences ce mois
                $query->whereIn('id', $staffIdsWithAttendance)
                    // Ou les utilisateurs avec un rôle de personnel (pour afficher aussi ceux à 0%)
                    ->orWhereIn('role', ['teacher', 'accountant', 'admin', 'secretaire', 'surveillant_general', 'comptable_superieur', 'principal', 'censeur', 'bibliothecaire', 'reprographe', 'surveillant_secteur', 'supervisor']);
            })
            ->orderBy('name')
            ->get();

        $staffIds = $staffUsers->pluck('id')->toArray();

        // Calculer les jours ouvrables du mois
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDaysInMonth = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (!in_array($date->dayOfWeek, [0, 6])) {
                $workingDaysInMonth++;
            }
        }

        // Créer l'en-tête des jours
        $daysHeader = [];
        for ($day = 1; $day <= $endDate->day; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $daysHeader[] = [
                'day' => $day,
                'day_name' => $currentDate->locale('fr')->isoFormat('ddd')
            ];
        }

        // Traiter les données pour chaque employé
        $staffData = [];

        foreach ($staffUsers as $staff) {
            $staffAttendances = $attendances->where('user_id', $staff->id);

            // Grouper par jour
            $dailyAttendance = [];
            foreach ($staffAttendances as $att) {
                // Convertir la date en string si c'est un objet Carbon
                $date = is_string($att->attendance_date) ? $att->attendance_date : $att->attendance_date->format('Y-m-d');

                if (!isset($dailyAttendance[$date])) {
                    $dailyAttendance[$date] = [
                        'entries' => [],
                        'exits' => []
                    ];
                }

                if ($att->event_type === 'entry') {
                    $dailyAttendance[$date]['entries'][] = [
                        'scanned_at' => Carbon::parse($att->scanned_at),
                        'time' => Carbon::parse($att->scanned_at)->format('H:i:s'),
                        'late_minutes' => $att->late_minutes ?? 0
                    ];
                } else {
                    $dailyAttendance[$date]['exits'][] = [
                        'scanned_at' => Carbon::parse($att->scanned_at),
                        'time' => Carbon::parse($att->scanned_at)->format('H:i:s')
                    ];
                }
            }

            // Calculer les statistiques
            $presentDays = 0;
            $lateDays = 0;
            $halfDays = 0;
            $totalSeconds = 0;
            $totalLateMinutes = 0; // Total des minutes de retard du mois

            // Calculer les heures travaillées en associant chaque Entry avec sa Exit correspondante
            foreach ($dailyAttendance as $date => $dayData) {
                $entries = $dayData['entries'];
                $exits = $dayData['exits'];

                // Associer chaque entrée avec sa sortie suivante
                $entryCount = count($entries);
                $exitCount = count($exits);

                for ($i = 0; $i < $entryCount; $i++) {
                    if (isset($exits[$i])) {
                        // Calculer la différence entre cette entrée et cette sortie
                        $entryTime = $entries[$i]['scanned_at'];
                        $exitTime = $exits[$i]['scanned_at'];

                        $workedSeconds = $exitTime->diffInSeconds($entryTime);
                        $totalSeconds += $workedSeconds;
                    }
                }

                // Statistiques de présence
                if (count($entries) > 0) {
                    $presentDays++;

                    // Calculer le total des minutes de retard
                    foreach ($entries as $entry) {
                        if ($entry['late_minutes'] > 0) {
                            $totalLateMinutes += $entry['late_minutes'];
                        }
                    }

                    if ($entries[0]['late_minutes'] > 0) {
                        $lateDays++;
                    }

                    // Demi-journée si moins de 4h travaillées
                    $daySeconds = 0;
                    for ($i = 0; $i < $entryCount; $i++) {
                        if (isset($exits[$i])) {
                            $daySeconds += $exits[$i]['scanned_at']->diffInSeconds($entries[$i]['scanned_at']);
                        }
                    }

                    if ($daySeconds < 4 * 3600) {
                        $halfDays++;
                    }
                }
            }

            // Convertir les secondes en format H:i:s
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;
            $totalHours = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            // Créer le tableau des jours pour l'affichage
            $days = [];
            for ($day = 1; $day <= $endDate->day; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $dayData = $dailyAttendance[$dateStr] ?? null;

                if ($dayData) {
                    $entries = $dayData['entries'];
                    $exits = $dayData['exits'];

                    // Créer un tableau de paires [In/Out]
                    $pairs = [];
                    $entryCount = count($entries);

                    for ($i = 0; $i < $entryCount; $i++) {
                        $pairs[] = [
                            'in' => $entries[$i]['time'],
                            'out' => isset($exits[$i]) ? $exits[$i]['time'] : null,
                            'late_minutes' => $entries[$i]['late_minutes']
                        ];
                    }

                    $days[] = [
                        'pairs' => $pairs,  // Toutes les paires [In/Out]
                        'is_weekend' => Carbon::parse($dateStr)->dayOfWeek == 0 || Carbon::parse($dateStr)->dayOfWeek == 6
                    ];
                } else {
                    $days[] = [
                        'pairs' => [],
                        'is_weekend' => Carbon::parse($dateStr)->dayOfWeek == 0 || Carbon::parse($dateStr)->dayOfWeek == 6
                    ];
                }
            }

            $absentDays = $workingDaysInMonth - $presentDays;
            $attendanceRate = $workingDaysInMonth > 0 ? round(($presentDays / $workingDaysInMonth) * 100, 2) : 0;

            // Formater le temps de retard total en HH:mm
            $lateHours = floor($totalLateMinutes / 60);
            $lateMinutes = $totalLateMinutes % 60;
            $totalLateFormatted = sprintf('%02d:%02d', $lateHours, $lateMinutes);

            $staffData[] = [
                'id' => $staff->id,
                'name' => $staff->name,
                'role' => $staff->role,
                'days' => $days,
                'attendance_rate' => $attendanceRate,
                'total_hours' => $totalHours,
                'working_days' => $workingDaysInMonth,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'half_days' => $halfDays,
                'total_late_minutes' => $totalLateMinutes,
                'total_late_formatted' => $totalLateFormatted
            ];
        }

        $monthName = Carbon::create($year, $month, 1)->locale('fr')->isoFormat('MMMM');

        // Déterminer l'année scolaire
        $currentYear = SchoolYear::where('is_current', true)->first();
        $schoolYear = $currentYear ? $currentYear->name : 'N/A';

        $data = [
            'month' => $month,
            'year' => $year,
            'monthName' => ucfirst($monthName),
            'schoolYear' => $schoolYear,
            'generatedAt' => now()->locale('fr')->isoFormat('DD MMMM YYYY à HH:mm'),
            'daysHeader' => $daysHeader,
            'staff' => $staffData,
            'workingDaysInMonth' => $workingDaysInMonth
        ];

        if ($format === 'pdf') {
            // Augmenter la limite de mémoire pour la génération du PDF
            ini_set('memory_limit', '512M');

            $pdf = PDF::loadView('reports.staff-attendance-calendar', $data);
            $pdf->setPaper('a3', 'landscape');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            return $pdf->download("rapport_calendrier_personnel_{$year}_{$month}.pdf");
        }

        if ($format === 'excel') {
            return $this->exportStaffCalendarToExcel($data);
        }

        return response()->json($data);
    }

    /**
     * Export du calendrier personnel vers Excel
     */
    private function exportStaffCalendarToExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // En-tête
        $sheet->setCellValue('A1', 'COLLÈGE POLYVALENT BILINGUE DE DOUALA');
        $sheet->mergeCells('A1:AJ1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Rapport Mensuel de Présences - Personnel Permanent');
        $sheet->mergeCells('A2:AJ2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Informations
        $row = 4;
        $sheet->setCellValue('A'.$row, "Mois: {$data['monthName']} {$data['year']}");
        $sheet->setCellValue('E'.$row, "Total Personnel: " . count($data['staff']));
        $sheet->setCellValue('I'.$row, "Jours ouvrables: {$data['workingDaysInMonth']}");
        $sheet->setCellValue('M'.$row, "Année scolaire: {$data['schoolYear']}");

        // En-têtes des colonnes
        $row = 6;
        $col = 'A';
        $sheet->setCellValue($col.$row, 'Employé');
        $col++;

        // Jours du mois
        foreach ($data['daysHeader'] as $dayInfo) {
            $sheet->setCellValue($col.$row, $dayInfo['day_name'] . "\n" . str_pad($dayInfo['day'], 2, '0', STR_PAD_LEFT));
            $sheet->getStyle($col.$row)->getAlignment()->setWrapText(true);
            $col++;
        }

        // Statistiques
        $sheet->setCellValue($col.$row, '%');
        $col++;
        $sheet->setCellValue($col.$row, 'Total Heures');
        $col++;
        $sheet->setCellValue($col.$row, 'W');
        $col++;
        $sheet->setCellValue($col.$row, 'P');
        $col++;
        $sheet->setCellValue($col.$row, 'A');
        $col++;
        $sheet->setCellValue($col.$row, 'L');
        $col++;
        $sheet->setCellValue($col.$row, 'HD');
        $col++;
        $sheet->setCellValue($col.$row, 'Total Retard');

        // Style des en-têtes
        $sheet->getStyle('A'.$row.':'.$col.$row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // Données
        $row++;
        foreach ($data['staff'] as $staff) {
            $col = 'A';
            $sheet->setCellValue($col.$row, $staff['name']);
            $col++;

            // Jours
            foreach ($staff['days'] as $dayData) {
                $cellValue = '';
                if (isset($dayData['pairs']) && count($dayData['pairs']) > 0) {
                    $pairsText = [];
                    foreach ($dayData['pairs'] as $pair) {
                        $inOut = "In: {$pair['in']}\nOut: " . ($pair['out'] ?? '--:--');
                        if (isset($pair['late_minutes']) && $pair['late_minutes'] > 0) {
                            $inOut .= "\n(+{$pair['late_minutes']}min)";
                        }
                        $pairsText[] = $inOut;
                    }
                    $cellValue = implode("\n---\n", $pairsText);
                } else {
                    $cellValue = '-';
                }

                $sheet->setCellValue($col.$row, $cellValue);
                $sheet->getStyle($col.$row)->getAlignment()->setWrapText(true);

                if ($dayData['is_weekend']) {
                    $sheet->getStyle($col.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('ffe5e5');
                }

                $col++;
            }

            // Statistiques
            $sheet->setCellValue($col.$row, $staff['attendance_rate'] . '%');
            $col++;
            $sheet->setCellValue($col.$row, $staff['total_hours']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['working_days']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['present_days']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['absent_days']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['late_days']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['half_days']);
            $col++;
            $sheet->setCellValue($col.$row, $staff['total_late_formatted']);
            // Style rouge pour la colonne Total Retard
            $sheet->getStyle($col.$row)->getFont()->getColor()->setRGB('dc3545');
            $sheet->getStyle($col.$row)->getFont()->setBold(true);

            $row++;
        }

        // Légende
        $row += 2;
        $sheet->setCellValue('A'.$row, '(%) : Taux de présence | W : Working days | P : Present | A : Absent | L : Late days | HD : Half Day | Total Retard : Cumul retard mensuel (HH:MM)');
        $sheet->mergeCells('A'.$row.':K'.$row);

        // Footer
        $row += 2;
        $sheet->setCellValue('A'.$row, "Document généré le {$data['generatedAt']}");
        $sheet->mergeCells('A'.$row.':J'.$row);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Téléchargement
        $writer = new Xlsx($spreadsheet);
        $filename = "rapport_calendrier_personnel_{$data['year']}_{$data['month']}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $writer->save('php://output');
        exit;
    }
}