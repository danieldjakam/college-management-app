<?php

namespace App\Http\Controllers;

use App\Models\Need;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NeedController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Lister tous les besoins (admin uniquement)
     */
    public function index(Request $request)
    {
        try {
            $query = Need::with(['user', 'approvedBy'])
                         ->orderBy('created_at', 'desc');

            // Filtrer par statut
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filtrer par utilisateur
            if ($request->has('user_id') && $request->user_id !== '') {
                $query->where('user_id', $request->user_id);
            }

            // Filtrer par période
            if ($request->has('from_date') && $request->from_date !== '') {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date !== '') {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Recherche par nom ou description
            if ($request->has('search') && $request->search !== '') {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            $needs = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $needs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des besoins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les besoins de l'utilisateur connecté
     */
    public function myNeeds(Request $request)
    {
        try {
            $query = Need::where('user_id', auth()->id())
                         ->with(['approvedBy'])
                         ->orderBy('created_at', 'desc');

            // Filtrer par statut
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $needs = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $needs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de vos besoins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau besoin
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'amount' => 'required|numeric|min:0|max:999999999'
            ], [
                'name.required' => 'Le nom du besoin est obligatoire',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
                'description.required' => 'La description est obligatoire',
                'description.max' => 'La description ne peut pas dépasser 2000 caractères',
                'amount.required' => 'Le montant est obligatoire',
                'amount.numeric' => 'Le montant doit être un nombre',
                'amount.min' => 'Le montant doit être positif',
                'amount.max' => 'Le montant est trop élevé'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $need = Need::create([
                'name' => $request->name,
                'description' => $request->description,
                'amount' => $request->amount,
                'user_id' => auth()->id(),
                'status' => Need::STATUS_PENDING
            ]);

            DB::commit();

            // Envoyer la notification WhatsApp
            try {
                $result = $this->whatsappService->sendNewNeedNotification($need);
                $need->update(['whatsapp_sent' => $result]);
            } catch (\Exception $e) {
                // Ne pas faire échouer la création si l'envoi WhatsApp échoue
                \Log::error('Erreur envoi WhatsApp pour besoin #' . $need->id . ': ' . $e->getMessage());
            }

            $need->load(['user', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Besoin soumis avec succès',
                'data' => $need
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un besoin spécifique
     */
    public function show(Need $need)
    {
        try {
            // Vérifier les permissions : admin, general_accountant ou propriétaire du besoin
            if (!in_array(auth()->user()->role, ['admin', 'general_accountant']) && $need->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à ce besoin'
                ], 403);
            }

            $need->load(['user', 'approvedBy']);

            return response()->json([
                'success' => true,
                'data' => $need
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approuver un besoin (admin uniquement)
     */
    public function approve(Need $need)
    {
        try {
            if ($need->status !== Need::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce besoin a déjà été traité'
                ], 422);
            }

            $previousStatus = $need->status_label;

            $need->update([
                'status' => Need::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => null
            ]);

            // Envoyer notifications WhatsApp
            try {
                // Notification à l'admin
                $this->whatsappService->sendStatusUpdateNotification($need, $previousStatus);
                // Notification au demandeur
                $this->whatsappService->sendStatusUpdateNotificationToRequester($need, $previousStatus);
            } catch (\Exception $e) {
                \Log::error('Erreur envoi WhatsApp pour approbation besoin #' . $need->id . ': ' . $e->getMessage());
            }

            $need->load(['user', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Besoin approuvé avec succès',
                'data' => $need
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter un besoin (admin uniquement)
     */
    public function reject(Request $request, Need $need)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:1000'
            ], [
                'rejection_reason.required' => 'Le motif du rejet est obligatoire',
                'rejection_reason.max' => 'Le motif ne peut pas dépasser 1000 caractères'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($need->status !== Need::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce besoin a déjà été traité'
                ], 422);
            }

            $previousStatus = $need->status_label;

            $need->update([
                'status' => Need::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            // Envoyer notifications WhatsApp
            try {
                // Notification à l'admin
                $this->whatsappService->sendStatusUpdateNotification($need, $previousStatus);
                // Notification au demandeur
                $this->whatsappService->sendStatusUpdateNotificationToRequester($need, $previousStatus);
            } catch (\Exception $e) {
                \Log::error('Erreur envoi WhatsApp pour rejet besoin #' . $need->id . ': ' . $e->getMessage());
            }

            $need->load(['user', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Besoin rejeté avec succès',
                'data' => $need
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un besoin (propriétaire uniquement, seulement si en attente)
     */
    public function update(Request $request, Need $need)
    {
        try {
            // Vérifier que l'utilisateur est propriétaire du besoin
            if ($need->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à modifier ce besoin'
                ], 403);
            }

            // Vérifier que le besoin est en attente
            if ($need->status !== Need::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les besoins en attente peuvent être modifiés'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'amount' => 'required|numeric|min:0|max:999999999'
            ], [
                'name.required' => 'Le nom du besoin est obligatoire',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
                'description.required' => 'La description est obligatoire',
                'description.max' => 'La description ne peut pas dépasser 2000 caractères',
                'amount.required' => 'Le montant est obligatoire',
                'amount.numeric' => 'Le montant doit être un nombre',
                'amount.min' => 'Le montant doit être positif',
                'amount.max' => 'Le montant est trop élevé'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $need->update([
                'name' => $request->name,
                'description' => $request->description,
                'amount' => $request->amount
            ]);

            $need->load(['user', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Besoin modifié avec succès',
                'data' => $need
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un besoin (propriétaire uniquement, seulement si en attente)
     */
    public function destroy(Need $need)
    {
        try {
            // Vérifier que l'utilisateur est propriétaire du besoin
            if ($need->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à supprimer ce besoin'
                ], 403);
            }

            // Vérifier que le besoin est en attente
            if ($need->status !== Need::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les besoins en attente peuvent être supprimés'
                ], 422);
            }

            $need->delete();

            return response()->json([
                'success' => true,
                'message' => 'Besoin supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du besoin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des besoins (admin uniquement)
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => Need::count(),
                'pending' => Need::pending()->count(),
                'approved' => Need::approved()->count(),
                'rejected' => Need::rejected()->count(),
                'total_amount_pending' => Need::pending()->sum('amount'),
                'total_amount_approved' => Need::approved()->sum('amount'),
                'total_amount_rejected' => Need::rejected()->sum('amount'),
            ];

            // Statistiques par mois (6 derniers mois)
            $monthlyStats = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyStats[] = [
                    'month' => $date->format('Y-m'),
                    'month_name' => $date->translatedFormat('F Y'),
                    'count' => Need::whereMonth('created_at', $date->month)
                                 ->whereYear('created_at', $date->year)
                                 ->count(),
                    'amount' => Need::whereMonth('created_at', $date->month)
                              ->whereYear('created_at', $date->year)
                              ->sum('amount')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $stats,
                    'monthly' => $monthlyStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la configuration WhatsApp (admin uniquement)
     */
    public function testWhatsApp()
    {
        try {
            $result = $this->whatsappService->testConfiguration();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}