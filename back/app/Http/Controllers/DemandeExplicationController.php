<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\DemandeExplication;
use App\Models\User;
use App\Models\SchoolYear;

class DemandeExplicationController extends Controller
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
        
        return SchoolYear::where('is_current', true)->first() ?? 
               SchoolYear::where('is_active', true)->first();
    }

    /**
     * Lister les demandes d'explication
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $workingYear = $this->getUserWorkingYear();
            
            $query = DemandeExplication::with(['emetteur', 'destinataire'])
                ->where('school_year_id', $workingYear->id ?? 1);

            // Filtrage selon le rôle
            $vue = $request->get('vue', 'emises'); // emises, recues, toutes
            
            if ($vue === 'emises') {
                $query->where('emetteur_id', $user->id);
            } elseif ($vue === 'recues') {
                $query->where('destinataire_id', $user->id);
            } elseif ($vue === 'toutes' && in_array($user->role, ['admin'])) {
                // Seuls les admins peuvent voir toutes les demandes
            } else {
                // Par défaut, voir les demandes émises et reçues
                $query->where(function($q) use ($user) {
                    $q->where('emetteur_id', $user->id)
                      ->orWhere('destinataire_id', $user->id);
                });
            }

            // Filtres optionnels
            if ($request->has('statut')) {
                $query->where('statut', $request->get('statut'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            if ($request->has('priorite')) {
                $query->where('priorite', $request->get('priorite'));
            }

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('sujet', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $demandes = $query->paginate($perPage);

            // Statistiques rapides
            $stats = [
                'total' => $query->count(),
                'en_attente' => DemandeExplication::where('destinataire_id', $user->id)
                    ->whereIn('statut', ['envoyee', 'lue'])
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count(),
                'repondues' => DemandeExplication::where('destinataire_id', $user->id)
                    ->where('statut', 'repondue')
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $demandes,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des demandes d\'explication: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des demandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une demande spécifique
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $demande = DemandeExplication::with(['emetteur', 'destinataire', 'schoolYear'])
                ->findOrFail($id);

            // Vérifier les permissions
            if ($demande->emetteur_id !== $user->id && 
                $demande->destinataire_id !== $user->id && 
                !in_array($user->role, ['admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette demande'
                ], 403);
            }

            // Marquer comme lue si c'est le destinataire qui consulte
            if ($demande->destinataire_id === $user->id && $demande->statut === 'envoyee') {
                $demande->marquerCommeLue();
                $demande->refresh();
            }

            return response()->json([
                'success' => true,
                'data' => $demande
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la demande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer une nouvelle demande d'explication
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'destinataire_id' => 'required|exists:users,id',
                'sujet' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'in:financier,absence,retard,disciplinaire,autre',
                'priorite' => 'in:basse,normale,haute,urgente',
                'envoi_immediat' => 'boolean'
            ], [
                'destinataire_id.required' => 'Veuillez sélectionner un destinataire',
                'destinataire_id.exists' => 'Le destinataire sélectionné n\'existe pas',
                'sujet.required' => 'Le sujet est obligatoire',
                'sujet.max' => 'Le sujet ne peut pas dépasser 255 caractères',
                'message.required' => 'Le message est obligatoire'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $workingYear = $this->getUserWorkingYear();

            // Vérifier que le destinataire existe et est du personnel
            $destinataire = User::findOrFail($request->destinataire_id);
            $rolesPersonnel = ['teacher', 'admin', 'secretaire', 'surveillant_general', 'accountant', 'comptable_superieur'];
            
            if (!in_array($destinataire->role, $rolesPersonnel)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le destinataire doit être un membre du personnel'
                ], 422);
            }

            $demande = DemandeExplication::create([
                'emetteur_id' => $user->id,
                'destinataire_id' => $request->destinataire_id,
                'school_year_id' => $workingYear->id ?? 1,
                'sujet' => $request->sujet,
                'message' => $request->message,
                'type' => $request->get('type', 'autre'),
                'priorite' => $request->get('priorite', 'normale'),
                'notes_internes' => $request->get('notes_internes'),
                'statut' => $request->get('envoi_immediat', true) ? 'envoyee' : 'brouillon'
            ]);

            // Si envoi immédiat, définir la date d'envoi
            if ($request->get('envoi_immediat', true)) {
                $demande->date_envoi = now();
                $demande->save();
            }

            $demande->load(['emetteur', 'destinataire']);

            return response()->json([
                'success' => true,
                'message' => $request->get('envoi_immediat', true) ? 
                    'Demande d\'explication envoyée avec succès' : 
                    'Brouillon sauvegardé avec succès',
                'data' => $demande
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la demande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la demande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une demande d'explication
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $demande = DemandeExplication::findOrFail($id);

            // Vérifier les permissions (seul l'émetteur peut modifier)
            if ($demande->emetteur_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez modifier que vos propres demandes'
                ], 403);
            }

            // Ne peut modifier que les brouillons
            if ($demande->statut !== 'brouillon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les demandes en brouillon peuvent être modifiées'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'destinataire_id' => 'sometimes|exists:users,id',
                'sujet' => 'sometimes|string|max:255',
                'message' => 'sometimes|string',
                'type' => 'sometimes|in:financier,absence,retard,disciplinaire,autre',
                'priorite' => 'sometimes|in:basse,normale,haute,urgente'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only([
                'destinataire_id', 'sujet', 'message', 'type', 
                'priorite', 'notes_internes'
            ]);

            // Si envoi immédiat demandé
            if ($request->get('envoi_immediat')) {
                $updateData['statut'] = 'envoyee';
                $updateData['date_envoi'] = now();
            }

            $demande->update($updateData);
            $demande->load(['emetteur', 'destinataire']);

            return response()->json([
                'success' => true,
                'message' => $request->get('envoi_immediat') ? 
                    'Demande envoyée avec succès' : 
                    'Demande mise à jour avec succès',
                'data' => $demande
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la demande: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Répondre à une demande d'explication
     */
    public function repondre(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $demande = DemandeExplication::findOrFail($id);

            // Vérifier les permissions (seul le destinataire peut répondre)
            if ($demande->destinataire_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez répondre qu\'aux demandes qui vous sont adressées'
                ], 403);
            }

            // Vérifier que la demande peut recevoir une réponse
            if (!in_array($demande->statut, ['envoyee', 'lue'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande ne peut plus recevoir de réponse'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'reponse' => 'required|string'
            ], [
                'reponse.required' => 'La réponse est obligatoire'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $demande->ajouterReponse($request->reponse);
            $demande->load(['emetteur', 'destinataire']);

            return response()->json([
                'success' => true,
                'message' => 'Réponse envoyée avec succès',
                'data' => $demande
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réponse: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la réponse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clôturer une demande d'explication
     */
    public function cloturer($id)
    {
        try {
            $user = Auth::user();
            $demande = DemandeExplication::findOrFail($id);

            // Vérifier les permissions (émetteur ou admin)
            if ($demande->emetteur_id !== $user->id && !in_array($user->role, ['admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez clôturer que vos propres demandes'
                ], 403);
            }

            // Vérifier que la demande peut être clôturée
            if ($demande->statut === 'cloturee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande est déjà clôturée'
                ], 422);
            }

            $demande->cloturer();
            $demande->load(['emetteur', 'destinataire']);

            return response()->json([
                'success' => true,
                'message' => 'Demande clôturée avec succès',
                'data' => $demande
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la clôture: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la clôture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une demande d'explication
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $demande = DemandeExplication::findOrFail($id);

            // Vérifier les permissions (émetteur ou admin, et seulement les brouillons)
            if ($demande->emetteur_id !== $user->id && !in_array($user->role, ['admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez supprimer que vos propres demandes'
                ], 403);
            }

            // Ne peut supprimer que les brouillons
            if ($demande->statut !== 'brouillon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les demandes en brouillon peuvent être supprimées'
                ], 422);
            }

            $demande->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la liste du personnel pour les destinataires
     */
    public function getPersonnel()
    {
        try {
            $personnel = User::whereIn('role', [
                'teacher', 'admin', 'secretaire', 'surveillant_general', 
                'accountant', 'comptable_superieur'
            ])
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get()
            ->map(function($user) {
                $roleLabels = [
                    'teacher' => 'Enseignant',
                    'admin' => 'Administrateur', 
                    'secretaire' => 'Secrétaire',
                    'surveillant_general' => 'Surveillant Général',
                    'accountant' => 'Comptable',
                    'comptable_superieur' => 'Comptable Supérieur'
                ];
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $roleLabels[$user->role] ?? $user->role
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $personnel
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du personnel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des demandes d'explication
     */
    public function statistiques()
    {
        try {
            $user = Auth::user();
            $workingYear = $this->getUserWorkingYear();

            $stats = [
                'emises' => DemandeExplication::where('emetteur_id', $user->id)
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count(),
                'recues' => DemandeExplication::where('destinataire_id', $user->id)
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count(),
                'en_attente_reponse' => DemandeExplication::where('emetteur_id', $user->id)
                    ->whereIn('statut', ['envoyee', 'lue'])
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count(),
                'a_repondre' => DemandeExplication::where('destinataire_id', $user->id)
                    ->whereIn('statut', ['envoyee', 'lue'])
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count(),
                'repondues' => DemandeExplication::where('destinataire_id', $user->id)
                    ->where('statut', 'repondue')
                    ->where('school_year_id', $workingYear->id ?? 1)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}