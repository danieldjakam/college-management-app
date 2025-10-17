<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BusSetting;
use App\Models\BusSubscription;
use App\Models\BusPayment;
use App\Models\BusTicket;
use App\Models\Student;
use App\Models\SchoolYear;
use Carbon\Carbon;
use PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BusController extends Controller
{
    /**
     * Obtenir le tarif actuel du bus
     */
    public function getSettings()
    {
        try {
            $setting = BusSetting::first();

            if (!$setting) {
                $setting = BusSetting::create([
                    'monthly_price' => 0,
                    'is_active' => true,
                    'description' => 'Service de transport scolaire'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le tarif du bus
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'monthly_price' => 'required|numeric|min:0',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $setting = BusSetting::updatePrice(
                $request->monthly_price,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Tarif mis à jour avec succès',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du tarif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel abonnement (achat de tickets)
     */
    public function createSubscription(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:students,id',
                'school_year_id' => 'required|exists:school_years,id',
                'start_month' => 'required|integer|min:1|max:12',
                'start_year' => 'required|integer|min:2020',
                'months_count' => 'required|integer|min:1|max:12',
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money,check,other',
                'transaction_reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Récupérer le tarif actuel
            $monthlyPrice = BusSetting::getCurrentPrice();

            if ($monthlyPrice <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le tarif du bus n\'est pas encore configuré. Veuillez configurer le tarif avant de créer un abonnement.'
                ], 422);
            }

            // Calculer le montant total
            $totalAmount = $monthlyPrice * $request->months_count;

            DB::beginTransaction();

            // Créer l'abonnement
            $subscription = BusSubscription::create([
                'student_id' => $request->student_id,
                'school_year_id' => $request->school_year_id,
                'start_month' => $request->start_month,
                'start_year' => $request->start_year,
                'months_count' => $request->months_count,
                'monthly_price' => $monthlyPrice,
                'total_amount' => $totalAmount,
                'payment_status' => 'paid', // Payé immédiatement
                'purchase_date' => now()->toDateString(),
                'purchased_by' => auth()->id(),
                'notes' => $request->notes
            ]);

            // Enregistrer le paiement
            $payment = BusPayment::create([
                'bus_subscription_id' => $subscription->id,
                'amount' => $totalAmount,
                'payment_date' => now()->toDateString(),
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference,
                'received_by' => auth()->id(),
                'notes' => $request->notes
            ]);

            // Générer les tickets pour tous les mois couverts
            $this->generateTicketsForSubscription($subscription);

            DB::commit();

            // Charger les relations
            $subscription->load(['student', 'schoolYear', 'payments', 'tickets']);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'data' => $subscription
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bus subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'abonnement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer les tickets pour un abonnement
     */
    private function generateTicketsForSubscription($subscription)
    {
        $student = Student::find($subscription->student_id);
        $coveredMonths = $subscription->getCoveredMonths();

        foreach ($coveredMonths as $monthData) {
            $month = $monthData['month'];
            $year = $monthData['year'];

            // Nombre de jours dans ce mois
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Couleur du mois
            $monthColor = BusTicket::getMonthColor($month);

            // Créer un ticket pour chaque jour du mois
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $ticketDate = Carbon::createFromDate($year, $month, $day);

                // Données du QR code
                $qrCodeData = json_encode([
                    'student_name' => $student->last_name . ' ' . $student->first_name,
                    'matricule' => $student->matricule,
                    'date' => $ticketDate->format('Y-m-d'),
                    'month' => $month,
                    'year' => $year,
                    'day' => $day
                ]);

                BusTicket::create([
                    'bus_subscription_id' => $subscription->id,
                    'student_id' => $subscription->student_id,
                    'month' => $month,
                    'year' => $year,
                    'day_number' => $day,
                    'ticket_date' => $ticketDate,
                    'qr_code_data' => $qrCodeData,
                    'ticket_color' => $monthColor,
                    'is_used' => false
                ]);
            }
        }
    }

    /**
     * Liste des abonnements (avec filtres)
     */
    public function listSubscriptions(Request $request)
    {
        try {
            $query = BusSubscription::with([
                'student.classSeries.schoolClass',
                'schoolYear',
                'purchasedBy',
                'payments'
            ]);

            // Filtrer par élève
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filtrer par année scolaire
            if ($request->has('school_year_id')) {
                $query->where('school_year_id', $request->school_year_id);
            }

            // Filtrer par mois/année
            if ($request->has('month') && $request->has('year')) {
                $month = $request->month;
                $year = $request->year;

                // Trouver les abonnements qui couvrent ce mois
                $query->where(function($q) use ($month, $year) {
                    $q->whereRaw('
                        (start_year < ? OR (start_year = ? AND start_month <= ?))
                        AND
                        (
                            (start_year = ? AND start_month + months_count - 1 >= ?)
                            OR
                            (start_year < ? AND start_month + months_count - 1 >= 12)
                        )
                    ', [$year, $year, $month, $year, $month, $year]);
                });
            }

            // Filtrer par statut de paiement
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            $subscriptions = $query->orderBy('created_at', 'desc')->get();

            // Ajouter les infos de mois couverts
            $subscriptions->transform(function($sub) {
                $sub->covered_months = $sub->getCoveredMonths();
                return $sub;
            });

            return response()->json([
                'success' => true,
                'data' => $subscriptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des abonnements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger les carnets de tickets PDF pour un abonnement
     */
    public function downloadTickets($subscriptionId)
    {
        try {
            $subscription = BusSubscription::with(['student', 'tickets'])->findOrFail($subscriptionId);
            $student = $subscription->student;

            // Grouper les tickets par mois
            $ticketsByMonth = $subscription->tickets->groupBy(function($ticket) {
                return $ticket->year . '-' . str_pad($ticket->month, 2, '0', STR_PAD_LEFT);
            });

            // Générer un PDF pour chaque mois
            $pdfs = [];
            foreach ($ticketsByMonth as $yearMonth => $monthTickets) {
                $firstTicket = $monthTickets->first();
                $monthName = BusTicket::getMonthName($firstTicket->month);
                $year = $firstTicket->year;

                $html = $this->generateTicketPdf($student, $monthTickets, $monthName, $year);

                $pdf = PDF::loadHTML($html);
                $pdf->setPaper('A4', 'portrait');

                $filename = "Bus_Tickets_{$student->matricule}_{$monthName}_{$year}.pdf";

                // Si un seul mois, télécharger directement
                if ($ticketsByMonth->count() == 1) {
                    return $pdf->download($filename);
                }

                $pdfs[] = [
                    'filename' => $filename,
                    'pdf' => $pdf
                ];
            }

            // Si plusieurs mois, retourner une liste (ou créer un ZIP - à implémenter plus tard)
            // Pour l'instant, on télécharge le premier mois
            if (count($pdfs) > 0) {
                return $pdfs[0]['pdf']->download($pdfs[0]['filename']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Aucun ticket trouvé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error downloading bus tickets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement des tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML du PDF de tickets
     */
    private function generateTicketPdf($student, $tickets, $monthName, $year)
    {
        $ticketColor = $tickets->first()->ticket_color;
        $schoolSetting = \App\Models\SchoolSetting::first();

        // Générer une couleur unique pour chaque élève basée sur son matricule
        $studentColor = $this->generateStudentColor($student->matricule);

        // Organiser les tickets en grille 5x6 (30 tickets)
        $ticketsArray = $tickets->sortBy('day_number')->values()->all();

        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .student-info {
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .student-info strong {
            font-weight: bold;
        }
        .tickets-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .ticket-row {
            display: table-row;
        }
        .ticket {
            display: table-cell;
            width: 20%;
            height: 150px;
            border: 3px dashed ' . $ticketColor . ';
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            position: relative;
            background: linear-gradient(135deg, rgba(' . $this->hexToRgb($ticketColor) . ', 0.15), rgba(' . $this->hexToRgb($studentColor) . ', 0.15));
            border-left: 5px solid ' . $studentColor . ';
        }
        .ticket-day {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: ' . $ticketColor . ';
        }
        .ticket-date {
            font-size: 10px;
            margin-bottom: 5px;
        }
        .ticket-qr {
            width: 60px;
            height: 60px;
            margin: 5px auto;
        }
        .ticket-student {
            font-size: 8px;
            margin-top: 3px;
        }
        .month-indicator {
            position: absolute;
            top: 2px;
            right: 2px;
            background: ' . $ticketColor . ';
            color: white;
            padding: 2px 5px;
            font-size: 8px;
            border-radius: 3px;
        }
        .instructions {
            margin-top: 20px;
            padding: 10px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ($schoolSetting->school_name ?? 'ÉCOLE') . '</h1>
        <p>TICKETS DE BUS - ' . strtoupper($monthName) . ' ' . $year . '</p>
    </div>

    <div class="student-info" style="border-left: 5px solid ' . $studentColor . ';">
        <p><strong>Élève :</strong> ' . $student->last_name . ' ' . $student->first_name . '</p>
        <p><strong>Matricule :</strong> ' . $student->matricule . '</p>
        <p><strong>Classe :</strong> ' . ($student->classSeries->schoolClass->name ?? 'N/A') . ' - ' . ($student->classSeries->name ?? 'N/A') . '</p>
        <p><strong>Couleur du mois :</strong> <span style="display:inline-block; width:15px; height:15px; background:' . $ticketColor . '; border:1px solid #000;"></span> ' . strtoupper($monthName) . '&nbsp;&nbsp;&nbsp;<strong>Code couleur élève :</strong> <span style="display:inline-block; width:15px; height:15px; background:' . $studentColor . '; border:1px solid #000;"></span></p>
    </div>

    <div class="tickets-grid">';

        // Créer la grille de tickets
        $row = 0;
        $col = 0;
        $html .= '<div class="ticket-row">';

        foreach ($ticketsArray as $ticket) {
            if ($col >= 5) {
                $html .= '</div><div class="ticket-row">';
                $col = 0;
                $row++;
            }

            // Générer le QR code en SVG pour éviter les problèmes avec imagick/GD
            $qrCodeSvg = QrCode::size(120)->generate($ticket->qr_code_data);

            // Encoder le SVG en base64 pour DOMPDF
            $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

            $html .= '
            <div class="ticket">
                <span class="month-indicator">' . strtoupper(substr($monthName, 0, 3)) . '</span>
                <div class="ticket-day">JOUR ' . $ticket->day_number . '</div>
                <div class="ticket-date">' . Carbon::parse($ticket->ticket_date)->format('d/m/Y') . '</div>
                <img class="ticket-qr" src="' . $qrCodeDataUri . '" style="width:60px;height:60px;" />
                <div class="ticket-student">' . strtoupper(substr($student->last_name, 0, 10)) . '</div>
            </div>';

            $col++;
        }

        $html .= '</div></div>

    <div class="instructions">
        <strong>Instructions :</strong><br/>
        • Découper chaque ticket le long des pointillés<br/>
        • Présenter UN ticket par jour au chauffeur avant de monter dans le bus<br/>
        • Ces tickets sont valables UNIQUEMENT pour le mois de <strong>' . $monthName . ' ' . $year . '</strong><br/>
        • En cas de perte, contacter l\'administration
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Convertir hex en rgb
     */
    private function hexToRgb($hex) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }

    /**
     * Générer une couleur unique pour chaque élève basée sur son matricule
     */
    private function generateStudentColor($matricule) {
        // Créer un hash du matricule
        $hash = md5($matricule);

        // Extraire les valeurs RGB du hash (utiliser des valeurs plus saturées)
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        // Ajuster pour des couleurs plus vives et visibles
        $r = max(80, min(220, $r));
        $g = max(80, min(220, $g));
        $b = max(80, min(220, $b));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Statistiques et rapport transport
     */
    public function getTransportReport(Request $request)
    {
        try {
            $month = $request->get('month', date('n'));
            $year = $request->get('year', date('Y'));
            $schoolYearId = $request->get('school_year_id');

            $query = BusSubscription::with(['student', 'payments']);

            // Filtrer par année scolaire si fournie
            if ($schoolYearId) {
                $query->where('school_year_id', $schoolYearId);
            }

            // Filtrer les abonnements qui couvrent ce mois
            $subscriptions = $query->get()->filter(function($sub) use ($month, $year) {
                return $sub->coversMonth($month, $year);
            });

            // Calculer les statistiques
            $totalSubscribers = $subscriptions->count();
            $totalRevenue = $subscriptions->sum('total_amount');
            $monthlyRevenue = $subscriptions->sum('monthly_price');

            // Répartition par classe
            $byClass = $subscriptions->groupBy(function($sub) {
                return $sub->student->classSeries->schoolClass->name ?? 'N/A';
            })->map(function($subs) {
                return [
                    'count' => $subs->count(),
                    'revenue' => $subs->sum('monthly_price')
                ];
            });

            // Répartition par méthode de paiement
            $allPayments = BusPayment::whereIn('bus_subscription_id', $subscriptions->pluck('id'))
                ->get();

            $byPaymentMethod = $allPayments->groupBy('payment_method')->map(function($payments) {
                return [
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_subscribers' => $totalSubscribers,
                        'total_revenue' => $totalRevenue,
                        'monthly_revenue' => $monthlyRevenue,
                        'month' => $month,
                        'year' => $year,
                        'month_label' => BusTicket::getMonthName($month)
                    ],
                    'by_class' => $byClass,
                    'by_payment_method' => $byPaymentMethod,
                    'subscriptions' => $subscriptions->values()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scanner un QR code de ticket
     */
    public function scanTicket(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'qr_code_data' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Décoder les données du QR code
            $qrData = json_decode($request->qr_code_data, true);

            if (!$qrData) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code invalide'
                ], 422);
            }

            // Trouver le ticket correspondant
            $ticket = BusTicket::where('qr_code_data', $request->qr_code_data)
                ->with(['student', 'subscription'])
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            // Vérifier si le ticket est pour aujourd'hui
            $today = Carbon::today();
            $ticketDate = Carbon::parse($ticket->ticket_date);

            if (!$ticketDate->isSameDay($today)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket n\'est pas valable aujourd\'hui. Date du ticket: ' . $ticketDate->format('d/m/Y'),
                    'data' => [
                        'ticket' => $ticket,
                        'valid_date' => $ticketDate->format('Y-m-d')
                    ]
                ], 422);
            }

            // Vérifier si déjà utilisé
            if ($ticket->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket a déjà été utilisé le ' . Carbon::parse($ticket->used_at)->format('d/m/Y à H:i'),
                    'data' => [
                        'ticket' => $ticket,
                        'used_at' => $ticket->used_at
                    ]
                ], 422);
            }

            // Marquer comme utilisé
            $ticket->markAsUsed();

            return response()->json([
                'success' => true,
                'message' => 'Ticket validé avec succès',
                'data' => [
                    'student' => $ticket->student,
                    'ticket' => $ticket,
                    'validated_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
