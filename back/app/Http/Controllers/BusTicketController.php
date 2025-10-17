<?php

namespace App\Http\Controllers;

use App\Models\BusTicketBatch;
use App\Models\BusTicketSale;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;

class BusTicketController extends Controller
{
    /**
     * Générer un nouveau lot de tickets
     */
    public function generateBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_date' => 'required|date',
            'ticket_type' => 'required|in:aller,retour',
            'quantity' => 'required|integer|min:1|max:100',
            'price_per_ticket' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Vérifier s'il existe déjà un lot actif pour ce type et cette date
            $existingBatch = BusTicketBatch::where('batch_date', $request->batch_date)
                ->where('ticket_type', $request->ticket_type)
                ->where('is_active', true)
                ->first();

            if ($existingBatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un lot actif existe déjà pour ce type et cette date. Désactivez-le d\'abord.'
                ], 409);
            }

            // Générer le préfixe (T-A pour Aller, T-R pour Retour)
            $prefix = $request->ticket_type === 'aller' ? 'T-A' : 'T-R';

            // Créer le lot
            $batch = BusTicketBatch::create([
                'batch_date' => $request->batch_date,
                'ticket_type' => $request->ticket_type,
                'quantity_generated' => $request->quantity,
                'quantity_sold' => 0,
                'quantity_remaining' => $request->quantity,
                'price_per_ticket' => $request->price_per_ticket,
                'batch_prefix' => $prefix,
                'start_number' => 1,
                'end_number' => $request->quantity,
                'generated_by' => auth()->id(),
                'is_active' => true,
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lot de tickets généré avec succès',
                'data' => $batch->load('generatedBy')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating ticket batch: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir tous les lots de tickets
     */
    public function getBatches(Request $request)
    {
        try {
            $query = BusTicketBatch::with(['generatedBy', 'sales']);

            // Filtre par date
            if ($request->has('date')) {
                $query->byDate($request->date);
            }

            // Filtre par type
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Filtre actifs seulement
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }

            $batches = $query->orderBy('batch_date', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'data' => $batches
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lots',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les lots actifs pour la date d'aujourd'hui
     */
    public function getTodayBatches()
    {
        try {
            $today = Carbon::today()->toDateString();

            $batches = BusTicketBatch::with('generatedBy')
                ->byDate($today)
                ->active()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $batches
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lots du jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vendre un ticket à un élève
     */
    public function sellTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|exists:bus_ticket_batches,id',
            'student_id' => 'required|exists:students,id',
            'ticket_number' => 'required|string',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,check,other',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Récupérer le lot
            $batch = BusTicketBatch::findOrFail($request->batch_id);

            // Vérifier le stock
            if (!$batch->hasStock()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock épuisé pour ce lot de tickets'
                ], 400);
            }

            // Vérifier si le lot est actif
            if (!$batch->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce lot de tickets n\'est plus actif'
                ], 400);
            }

            // Vérifier que le numéro de ticket appartient bien au lot
            $ticketNumber = $request->ticket_number;
            if (strpos($ticketNumber, $batch->batch_prefix) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le numéro de ticket ne correspond pas au lot sélectionné'
                ], 400);
            }

            // Vérifier que le ticket n'a pas déjà été vendu
            $existingSale = BusTicketSale::where('ticket_number', $ticketNumber)
                ->where('batch_id', $batch->id)
                ->first();

            if ($existingSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de ticket a déjà été vendu'
                ], 400);
            }

            // Récupérer l'élève
            $student = Student::findOrFail($request->student_id);

            // Générer les données du QR code
            $qrCodeData = json_encode([
                'ticket_number' => $ticketNumber,
                'student_name' => $student->last_name . ' ' . $student->first_name,
                'matricule' => $student->matricule,
                'ticket_type' => $batch->ticket_type,
                'date' => $batch->batch_date->format('Y-m-d'),
                'price' => $batch->price_per_ticket
            ]);

            // Créer la vente
            $sale = BusTicketSale::create([
                'batch_id' => $batch->id,
                'ticket_number' => $ticketNumber,
                'student_id' => $request->student_id,
                'ticket_type' => $batch->ticket_type,
                'price' => $batch->price_per_ticket,
                'payment_method' => $request->payment_method,
                'sold_by' => auth()->id(),
                'sold_at' => now(),
                'qr_code_data' => $qrCodeData,
                'notes' => $request->notes
            ]);

            // Décrémenter le stock
            $batch->decrementStock();

            DB::commit();

            // Charger les relations
            $sale->load(['student', 'batch', 'soldBy']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket vendu avec succès',
                'data' => $sale
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error selling ticket: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vente du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir toutes les ventes
     */
    public function getSales(Request $request)
    {
        try {
            $query = BusTicketSale::with(['student', 'batch', 'soldBy']);

            // Filtre par date de vente
            if ($request->has('date')) {
                $query->bySaleDate($request->date);
            }

            // Filtre par type de ticket
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Filtre par élève
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filtre tickets utilisés/non utilisés
            if ($request->has('used')) {
                if ($request->used === 'true' || $request->used === true) {
                    $query->used();
                } else {
                    $query->unused();
                }
            }

            $sales = $query->orderBy('sold_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $sales
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des ventes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les ventes du jour
     */
    public function getTodaySales()
    {
        try {
            $today = Carbon::today()->toDateString();

            $sales = BusTicketSale::with(['student', 'batch', 'soldBy'])
                ->bySaleDate($today)
                ->orderBy('sold_at', 'desc')
                ->get();

            // Calculer les statistiques
            $stats = [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('price'),
                'aller_count' => $sales->where('ticket_type', 'aller')->count(),
                'retour_count' => $sales->where('ticket_type', 'retour')->count(),
                'aller_revenue' => $sales->where('ticket_type', 'aller')->sum('price'),
                'retour_revenue' => $sales->where('ticket_type', 'retour')->sum('price')
            ];

            return response()->json([
                'success' => true,
                'data' => $sales,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des ventes du jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport quotidien
     */
    public function getDailyReport(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        try {
            // Lots du jour
            $batches = BusTicketBatch::byDate($date)->get();

            // Ventes du jour
            $sales = BusTicketSale::bySaleDate($date)->get();

            // Statistiques
            $report = [
                'date' => $date,
                'batches' => [
                    'aller' => $batches->where('ticket_type', 'aller')->first(),
                    'retour' => $batches->where('ticket_type', 'retour')->first()
                ],
                'sales' => [
                    'total_count' => $sales->count(),
                    'total_revenue' => $sales->sum('price'),
                    'aller' => [
                        'count' => $sales->where('ticket_type', 'aller')->count(),
                        'revenue' => $sales->where('ticket_type', 'aller')->sum('price')
                    ],
                    'retour' => [
                        'count' => $sales->where('ticket_type', 'retour')->count(),
                        'revenue' => $sales->where('ticket_type', 'retour')->sum('price')
                    ]
                ],
                'payment_methods' => $sales->groupBy('payment_method')->map(function($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('price')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $report
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
     * Télécharger tous les tickets d'un lot en PDF
     */
    public function downloadBatchTickets($batchId)
    {
        try {
            $batch = BusTicketBatch::findOrFail($batchId);

            $html = $this->generateBatchTicketsHtml($batch);

            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $filename = "lot_tickets_{$batch->batch_prefix}_{$batch->batch_date->format('Y-m-d')}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du lot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le ticket PDF (après vente)
     */
    public function downloadTicket($saleId)
    {
        try {
            $sale = BusTicketSale::with(['student', 'batch'])->findOrFail($saleId);

            // Générer le QR code en SVG
            $qrCode = QrCode::size(120)->generate($sale->qr_code_data);

            $html = $this->generateTicketHtml($sale, $qrCode);

            $pdf = PDF::loadHTML($html);
            $pdf->setPaper([0, 0, 283, 425], 'portrait'); // 10cm x 15cm

            $filename = "ticket_{$sale->ticket_number}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour tous les tickets d'un lot
     */
    private function generateBatchTicketsHtml($batch)
    {
        $typeLabel = $batch->ticket_type === 'aller' ? 'ALLER' : 'RETOUR';
        $typeColor = $batch->ticket_type === 'aller' ? '#007bff' : '#28a745';
        $schoolSetting = \App\Models\SchoolSetting::first();

        $ticketsHtml = '';
        $count = 0;

        // Générer chaque ticket vierge en grille 5 par ligne
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
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .info-box {
            margin-bottom: 10px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 5px;
            font-size: 11px;
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
            height: 140px;
            border: 2px dashed ' . $typeColor . ';
            padding: 6px;
            text-align: center;
            vertical-align: middle;
            position: relative;
            background: linear-gradient(135deg, rgba(' . $this->hexToRgb($typeColor) . ', 0.1), white);
        }
        .ticket-number {
            font-size: 12px;
            font-weight: bold;
            color: ' . $typeColor . ';
            margin-bottom: 3px;
        }
        .ticket-date {
            font-size: 9px;
            margin-bottom: 3px;
            color: #666;
        }
        .ticket-qr {
            width: 55px;
            height: 55px;
            margin: 3px auto;
        }
        .ticket-fields {
            font-size: 7px;
            margin-top: 3px;
        }
        .field-label {
            color: #999;
            margin-bottom: 1px;
        }
        .field-line {
            height: 10px;
            border-bottom: 1px solid #ccc;
            margin-bottom: 2px;
        }
        .type-indicator {
            position: absolute;
            top: 2px;
            right: 2px;
            background: ' . $typeColor . ';
            color: white;
            padding: 2px 4px;
            font-size: 7px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ($schoolSetting->school_name ?? 'ÉCOLE') . '</h1>
        <p>TICKETS DE BUS JOURNALIERS - ' . $typeLabel . '</p>
        <p>Date: ' . $batch->batch_date->format('d/m/Y') . ' | Quantité: ' . $batch->quantity_generated . ' tickets</p>
    </div>

    <div class="info-box">
        <strong>Instructions :</strong> Découper chaque ticket le long des pointillés.
        L\'agent remplira le nom et la classe lors de la vente.
        Prix unitaire: <strong>' . number_format($batch->price_per_ticket, 0, ',', ' ') . ' FCFA</strong>
    </div>

    <div class="tickets-grid">';

        // Générer chaque ticket en grille
        for ($i = $batch->start_number; $i <= $batch->end_number; $i++) {
            if ($count % 5 === 0) {
                if ($count > 0) {
                    $html .= '</div>';
                }
                $html .= '<div class="ticket-row">';
            }

            $ticketNumber = $batch->batch_prefix . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

            // Générer QR code
            $qrCodeData = json_encode([
                'ticket_number' => $ticketNumber,
                'ticket_type' => $batch->ticket_type,
                'date' => $batch->batch_date->format('Y-m-d'),
                'price' => $batch->price_per_ticket,
                'batch_id' => $batch->id
            ]);
            $qrCodeSvg = QrCode::size(80)->generate($qrCodeData);
            $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

            $html .= '
            <div class="ticket">
                <span class="type-indicator">' . $typeLabel . '</span>
                <div class="ticket-number">' . $ticketNumber . '</div>
                <div class="ticket-date">' . $batch->batch_date->format('d/m/Y') . '</div>
                <img class="ticket-qr" src="' . $qrCodeDataUri . '" />
                <div class="ticket-fields">
                    <div class="field-label">Nom:</div>
                    <div class="field-line"></div>
                    <div class="field-label">Classe:</div>
                    <div class="field-line"></div>
                </div>
            </div>';

            $count++;
        }

        $html .= '
        </div>
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
     * Générer le HTML du ticket (après vente)
     */
    private function generateTicketHtml($sale, $qrCode)
    {
        $typeLabel = $sale->ticket_type === 'aller' ? 'ALLER' : 'RETOUR';
        $typeColor = $sale->ticket_type === 'aller' ? '#007bff' : '#28a745';
        $schoolSetting = \App\Models\SchoolSetting::first();

        // Générer une couleur unique pour l'élève
        $studentColor = $this->generateStudentColor($sale->student->matricule);

        return '
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
            padding: 15px;
        }
        .ticket {
            border: 3px dashed ' . $typeColor . ';
            padding: 20px;
            text-align: center;
            background: linear-gradient(135deg, rgba(' . $this->hexToRgb($typeColor) . ', 0.15), rgba(' . $this->hexToRgb($studentColor) . ', 0.15));
            border-left: 8px solid ' . $studentColor . ';
            max-width: 400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid ' . $typeColor . ';
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #333;
        }
        .header p {
            font-size: 14px;
            color: #666;
        }
        .ticket-type {
            background: ' . $typeColor . ';
            color: white;
            padding: 8px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .ticket-number {
            font-size: 24px;
            margin: 15px 0;
            font-weight: bold;
            color: ' . $typeColor . ';
        }
        .student-info {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border-left: 5px solid ' . $studentColor . ';
            text-align: left;
        }
        .student-info p {
            margin: 5px 0;
            font-size: 13px;
        }
        .student-info strong {
            color: #333;
        }
        .qr-code {
            margin: 20px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
            display: inline-block;
        }
        .price {
            font-size: 22px;
            font-weight: bold;
            color: ' . $typeColor . ';
            margin: 15px 0;
            padding: 10px;
            background: rgba(' . $this->hexToRgb($typeColor) . ', 0.1);
            border-radius: 5px;
        }
        .date {
            font-size: 11px;
            color: #666;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
        }
        .color-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: ' . $studentColor . ';
            border: 2px solid #333;
            border-radius: 3px;
            vertical-align: middle;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>' . ($schoolSetting->school_name ?? 'ÉCOLE') . '</h1>
            <p>TICKET DE BUS JOURNALIER</p>
        </div>

        <div class="ticket-type">🚌 ' . $typeLabel . '</div>

        <div class="ticket-number">' . $sale->ticket_number . '</div>

        <div class="student-info">
            <p><strong>Élève :</strong> ' . strtoupper($sale->student->last_name) . ' ' . $sale->student->first_name . '</p>
            <p><strong>Classe :</strong> ' . ($sale->student->classSeries->schoolClass->name ?? 'N/A') . ' - ' . ($sale->student->classSeries->name ?? 'N/A') . '</p>
            <p><strong>Matricule :</strong> ' . $sale->student->matricule . '</p>
            <p><strong>Code couleur élève :</strong> <span class="color-indicator"></span></p>
        </div>

        <div class="qr-code">
            ' . $qrCode . '
        </div>

        <div class="price">' . number_format($sale->price, 0, ',', ' ') . ' FCFA</div>

        <div class="date">
            Valable le: <strong>' . Carbon::parse($sale->batch->batch_date)->format('d/m/Y') . '</strong><br>
            Vendu le: ' . $sale->sold_at->format('d/m/Y à H:i') . '<br>
            Vendu par: ' . ($sale->soldBy->name ?? 'N/A') . '
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Générer une couleur unique pour chaque élève basée sur son matricule
     */
    private function generateStudentColor($matricule) {
        $hash = md5($matricule);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        // Ajuster pour des couleurs plus vives
        $r = max(80, min(220, $r));
        $g = max(80, min(220, $g));
        $b = max(80, min(220, $b));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Désactiver un lot
     */
    public function deactivateBatch($batchId)
    {
        try {
            $batch = BusTicketBatch::findOrFail($batchId);
            $batch->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Lot désactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scanner et valider un ticket (pour le chauffeur)
     */
    public function validateTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ticket = BusTicketSale::with(['student', 'batch'])
                ->where('ticket_number', $request->ticket_number)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket non trouvé'
                ], 404);
            }

            if ($ticket->is_used) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket a déjà été utilisé le ' . $ticket->used_at->format('d/m/Y à H:i'),
                    'data' => $ticket
                ], 400);
            }

            // Marquer comme utilisé
            $ticket->markAsUsed();

            return response()->json([
                'success' => true,
                'message' => 'Ticket validé avec succès',
                'data' => $ticket
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
