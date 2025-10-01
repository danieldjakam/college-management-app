<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\StaffAttendance;

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
            if ($workingYear && $workingYear->is_active) {
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
            $staffQuery = User::whereIn('role', [
                'teacher', 'accountant', 'admin', 'secretaire',
                'surveillant_general', 'comptable_superieur',
                'vacataire', 'semi_permanent'  // AJOUT DES VACATAIRES
            ]);

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
                    ->where('staff_type', 'vacataire')
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

            $html = $this->generateVacataireAttendancePdfHtml(
                $reportContent->data->vacataires,
                $reportContent->data->statistics,
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
}