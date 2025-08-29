<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class UserManagementController extends Controller
{
    /**
     * Liste de tous les utilisateurs
     */
    public function index(Request $request)
    {
        try {
            $query = User::select('id', 'name', 'username', 'email', 'contact', 'photo', 'role', 'qualification', 'is_active', 'created_at')
                ->whereIn('role', ['principal', 'surveillant_general', 'general_accountant', 'comptable_superieur', 'comptable', 'secretaire', 'teacher', 'accountant', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe']); // Tous les rôles gérables

            // Système de recherche
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
                });
            }

            // Filtre par rôle
            if ($request->has('role') && !empty($request->role) && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            // Filtre par statut
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('is_active', $request->status === 'active');
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'contact' => 'nullable|string|max:20',
                'photo' => 'nullable|string|max:500',
                'role' => 'required|in:principal,user,surveillant_general,general_accountant,comptable_superieur,comptable,secretaire,accountant,responsable_pedagogique,dean_of_studies,censeur_esg,censeur,surveillant_secteur,caissiere,bibliothecaire,chef_travaux,chef_securite,reprographe',
                'qualification' => 'nullable|string|max:100',
                'generate_password' => 'boolean'
            ], [
                'name.required' => 'Le nom complet est obligatoire.',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
                'email.required' => 'L\'adresse e-mail est obligatoire.',
                'email.email' => 'L\'adresse e-mail doit être valide.',
                'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre utilisateur.',
                'contact.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
                'contact.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
                'role.required' => 'Le rôle est obligatoire.',
                'role.in' => 'Le rôle sélectionné n\'est pas valide.',
                'qualification.max' => 'La qualification ne peut pas dépasser 100 caractères.'
            ]);

            if ($validator->fails()) {
                // Construire un message d'erreur plus informatif basé sur les erreurs
                $errors = $validator->errors();
                $firstError = $errors->first();
                
                return response()->json([
                    'success' => false,
                    'message' => $firstError, // Utiliser la première erreur comme message principal
                    'errors' => $errors
                ], 422);
            }

            // Générer un mot de passe ou utiliser celui fourni
            $password = $request->password ?? $this->generatePassword();
            
            // Générer un username à partir de l'email
            $username = explode('@', $request->email)[0];
            
            $user = User::create([
                'name' => $request->name,
                'username' => $username,
                'email' => $request->email,
                'contact' => $request->contact,
                'photo' => $request->photo,
                'password' => Hash::make($password),
                'role' => $request->role,
                'qualification' => $request->qualification,
                'is_active' => true,
                'email_verified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'username', 'email', 'contact', 'photo', 'role', 'qualification', 'is_active', 'created_at']),
                    'password' => $password // Retourner le mot de passe généré pour l'admin
                ],
                'message' => 'Utilisateur créé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($id)
    {
        try {
            $user = User::select('id', 'name', 'username', 'email', 'contact', 'photo', 'role', 'qualification', 'is_active', 'created_at')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'contact' => 'nullable|string|max:20',
                'photo' => 'nullable|string|max:500', // Nullable en update
                'role' => 'required|in:principal,user,surveillant_general,general_accountant,comptable_superieur,comptable,secretaire,accountant,responsable_pedagogique,dean_of_studies,censeur_esg,censeur,surveillant_secteur,caissiere,bibliothecaire,chef_travaux,chef_securite,reprographe',
                'qualification' => 'nullable|string|max:100',
                'is_active' => 'boolean'
            ], [
                'name.required' => 'Le nom complet est obligatoire.',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
                'email.required' => 'L\'adresse e-mail est obligatoire.',
                'email.email' => 'L\'adresse e-mail doit être valide.',
                'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre utilisateur.',
                'contact.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
                'contact.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
                'role.required' => 'Le rôle est obligatoire.',
                'role.in' => 'Le rôle sélectionné n\'est pas valide.',
                'qualification.max' => 'La qualification ne peut pas dépasser 100 caractères.'
            ]);

            if ($validator->fails()) {
                // Construire un message d'erreur plus informatif basé sur les erreurs
                $errors = $validator->errors();
                $firstError = $errors->first();
                
                return response()->json([
                    'success' => false,
                    'message' => $firstError, // Utiliser la première erreur comme message principal
                    'errors' => $errors
                ], 422);
            }

            $updateData = [
                'name' => $request->name,
                'username' => explode('@', $request->email)[0], // Générer username depuis email
                'email' => $request->email,
                'contact' => $request->contact,
                'role' => $request->role,
                'qualification' => $request->qualification,
                'is_active' => $request->is_active ?? $user->is_active
            ];

            // N'update la photo que si elle est fournie
            if ($request->has('photo') && $request->photo !== null) {
                $updateData['photo'] = $request->photo;
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $user->only(['id', 'name', 'username', 'email', 'contact', 'photo', 'role', 'qualification', 'is_active', 'created_at']),
                'message' => 'Utilisateur mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword($id)
    {
        try {
            $user = User::findOrFail($id);
            $newPassword = $this->generatePassword();

            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'new_password' => $newPassword
                ],
                'message' => 'Mot de passe réinitialisé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            
            $user->update([
                'is_active' => !$user->is_active
            ]);

            return response()->json([
                'success' => true,
                'data' => $user->only(['id', 'name', 'username', 'email', 'contact', 'photo', 'role', 'qualification', 'is_active']),
                'message' => $user->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Empêcher la suppression des admins
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un administrateur'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un mot de passe aléatoire
     */
    private function generatePassword($length = 8)
    {
        return Str::random($length);
    }

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public function getStats()
    {
        try {
            // Statistiques pour tous les utilisateurs non-admin
            $managedRoles = ['surveillant_general', 'comptable', 'secretaire', 'teacher', 'accountant'];
            $stats = [
                'total_users' => User::whereIn('role', $managedRoles)->count(),
                'active_users' => User::whereIn('role', $managedRoles)->where('is_active', true)->count(),
                'inactive_users' => User::whereIn('role', $managedRoles)->where('is_active', false)->count(),
                'by_role' => [
                    'surveillant_general' => User::where('role', 'surveillant_general')->count(),
                    'comptable' => User::where('role', 'comptable')->count(),
                    'secretaire' => User::where('role', 'secretaire')->count(),
                    'teacher' => User::where('role', 'teacher')->count(),
                    'accountant' => User::where('role', 'accountant')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
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
     * Générer une carte d'identité professionnelle pour un utilisateur
     */
    public function generateProfessionalCard($id)
    {
        try {
            $user = User::findOrFail($id);

            // Vérifier que l'utilisateur n'est pas admin
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer une carte pour un administrateur'
                ], 403);
            }

            // Générer le contenu QR pour l'utilisateur professionnel
            $qrContent = "STAFF_ID_" . $user->id;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrContent);

            // Préparer les données pour la carte
            $cardData = [
                'user' => $user,
                'qr_content' => $qrContent,
                'qr_url' => $qrUrl,
                'generated_at' => now()->format('d/m/Y H:i:s')
            ];

            // Générer le PDF de la carte
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.staff.professional-card', $cardData);
            $pdf->setPaper([0, 0, 320, 200], 'landscape'); // Format carte bancaire (86mm x 54mm en points)

            $fileName = 'carte_professionnelle_' . Str::slug($user->name) . '_' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la carte professionnelle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données QR pour un utilisateur (pour affichage en frontend)
     */
    public function getUserQR($id)
    {
        try {
            $user = User::findOrFail($id);

            // Vérifier que l'utilisateur n'est pas admin
            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer un QR pour un administrateur'
                ], 403);
            }

            // Générer le contenu QR pour l'utilisateur professionnel
            $qrContent = "STAFF_ID_" . $user->id;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrContent);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_role' => $user->role,
                    'qr_content' => $qrContent,
                    'qr_url' => $qrUrl
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter le personnel administratif en PDF
     */
    public function exportAdministrativeStaffPdf(): \Illuminate\Http\Response
    {
        try {
            // Récupérer le personnel administratif (users sauf teachers)
            $administrativeStaff = User::select('id', 'name', 'role', 'qualification', 'contact', 'created_at')
                ->where('is_active', true)
                ->whereIn('role', ['principal', 'surveillant_general', 'general_accountant', 'comptable_superieur', 'comptable', 'secretaire', 'accountant', 'admin', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'])
                ->orderBy('name')
                ->get();

            // Récupérer les paramètres de l'école
            $schoolSettings = SchoolSetting::getSettings();
            
            // Convertir le logo en base64 pour DOMPDF
            $logoBase64 = '';
            $schoolSettingsModel = SchoolSetting::first();
            if ($schoolSettingsModel && $schoolSettingsModel->school_logo) {
                $logoPath = storage_path('app/public/' . $schoolSettingsModel->school_logo);
                if (file_exists($logoPath)) {
                    $logoContent = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoContent);
                }
            }

            // Préparer les données pour le PDF
            $data = [
                'staff' => $administrativeStaff,
                'schoolSettings' => $schoolSettings,
                'logoBase64' => $logoBase64,
                'currentDate' => now()->format('d/m/Y'),
                'academicYear' => now()->year . '/' . (now()->year + 1),
                'totalStaff' => $administrativeStaff->count()
            ];

            // Générer le HTML
            $html = $this->generateAdministrativeStaffHtml($data);

            // Créer le PDF
            $pdf = Pdf::loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'Fichier_Personnel_Administratif_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le PDF du personnel administratif
     */
    private function generateAdministrativeStaffHtml($data): string
    {
        $staff = $data['staff'];
        $schoolSettings = $data['schoolSettings'];
        $logoBase64 = $data['logoBase64'];
        $currentDate = $data['currentDate'];
        $academicYear = $data['academicYear'];
        $totalStaff = $data['totalStaff'];

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Fichier du Personnel Administratif</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.2;
                    color: #000;
                }
                
                .header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                }
                
                .logo-section {
                    width: 120px;
                    text-align: center;
                }
                
                .school-logo {
                    width: 80px;
                    height: 80px;
                    object-fit: contain;
                }
                
                .school-info {
                    flex: 1;
                    text-align: center;
                    margin: 0 20px;
                }
                
                .school-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                
                .school-details {
                    font-size: 9px;
                    margin-bottom: 3px;
                    line-height: 1.3;
                }
                
                .academic-year {
                    position: absolute;
                    top: 10px;
                    right: 0;
                    font-size: 10px;
                    font-weight: bold;
                }
                
                .title {
                    font-size: 18px;
                    font-weight: bold;
                    text-transform: uppercase;
                    text-decoration: underline;
                    text-align: center;
                    margin: 25px 0;
                }
                
                .staff-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                
                .staff-table th {
                    background-color: #f0f0f0;
                    border: 1px solid #000;
                    padding: 8px 4px;
                    text-align: center;
                    font-weight: bold;
                    font-size: 9px;
                    text-transform: uppercase;
                    line-height: 1.1;
                }
                
                .staff-table td {
                    border: 1px solid #000;
                    padding: 6px 4px;
                    font-size: 9px;
                    vertical-align: middle;
                    line-height: 1.2;
                }
                
                .staff-table .number-col {
                    width: 6%;
                    text-align: center;
                }
                
                .staff-table .name-col {
                    width: 25%;
                    text-align: left;
                    padding-left: 6px;
                }
                
                .staff-table .position-col {
                    width: 20%;
                    text-align: center;
                }
                
                .staff-table .seniority-col {
                    width: 15%;
                    text-align: center;
                }
                
                .staff-table .qualification-col {
                    width: 16%;
                    text-align: center;
                }
                
                .staff-table .contact-col {
                    width: 18%;
                    text-align: center;
                }
                
                .signature-section {
                    position: absolute;
                    bottom: 30px;
                    right: 50px;
                    text-align: center;
                }
                
                .signature-line {
                    border-bottom: 1px solid #000;
                    width: 150px;
                    margin: 20px 0 5px 0;
                }
                
                @page {
                    margin: 15mm;
                    size: A4;
                }
            </style>
        </head>
        <body>
            <div style="position: relative;">
                <div class="academic-year">Année scolaire ' . $academicYear . '</div>
                
                <div class="header">
                    <div class="logo-section">
                        ' . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo École' class='school-logo'>" : '') . '
                    </div>
                    <div class="school-info">
                        <div class="school-name">' . strtoupper($schoolSettings['school_name'] ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA') . '</div>
                        <div class="school-details">B.P : 4100 Tél : 233-43-25-47</div>
                        <div class="school-details">DOUALA</div>
                        <div class="school-details">Autorisation de création : N°185/MINESE/SG/DWESTVO/SDPES/SEPTC DU 16 JUIN 2015</div>
                        <div class="school-details">Autorisation d\'ouverture : N°210 MINESE/SG/DWESTVO/SDPES/SEPTC DU 06 NOVEMBRE 2015</div>
                    </div>
                </div>
                
                <div class="title">Fichier du Personnel Administratif CBPD</div>
                
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th class="number-col">N°</th>
                            <th class="name-col">NOM(S) & PRÉNOM(S)</th>
                            <th class="position-col">POSTE</th>
                            <th class="seniority-col">ANCIENNETÉ<br>CBPD</th>
                            <th class="qualification-col">QUALIFICATION</th>
                            <th class="contact-col">CONTACT</th>
                        </tr>
                    </thead>
                    <tbody>';

        $counter = 1;
        foreach ($staff as $user) {
            $fullName = strtoupper($user->name);
            $position = $this->getPositionLabel($user->role);
            $contact = $user->contact ?? '-';
            
            // Calculer l'ancienneté (approximation basée sur created_at)
            if ($user->created_at) {
                $years = max(1, (int) $user->created_at->diffInYears(now()));
                $seniority = $years . ' an' . ($years > 1 ? 's' : '');
            } else {
                $seniority = '-';
            }
            
            // Utiliser la qualification de l'utilisateur ou trait si vide
            $qualification = $user->qualification ?: '-';
            
            $html .= '
                <tr>
                    <td class="number-col">' . $counter . '.</td>
                    <td class="name-col">' . $fullName . '</td>
                    <td class="position-col">' . $position . '</td>
                    <td class="seniority-col">' . $seniority . '</td>
                    <td class="qualification-col">' . $qualification . '</td>
                    <td class="contact-col">' . $contact . '</td>
                </tr>';
            
            $counter++;
        }

        $html .= '
                    </tbody>
                </table>
                
                <div class="signature-section">
                    <div>Douala, Le ______________</div>
                    <div class="signature-line"></div>
                    <div>Le Principal</div>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Obtenir le libellé du poste selon le rôle
     */
    private function getPositionLabel($role): string
    {
        $positions = [
            // Rôles existants dans l'application
            'admin' => 'PRINCIPAL',
            'surveillant_general' => 'SURVEILLANT GÉNÉRAL',
            'general_accountant' => 'COMPTABLE GÉNÉRAL',
            'comptable_superieur' => 'COMPTABLE SUPÉRIEUR',
            'comptable' => 'COMPTABLE',
            'accountant' => 'COMPTABLE',
            'secretaire' => 'SECRÉTAIRE',
            'teacher' => 'ENSEIGNANT',
            
            // Rôles additionnels du fichier k.png (peuvent ne pas avoir accès à l'app)
            'principal' => 'PRINCIPAL',
            'responsable_pedagogique' => 'RESPONSABLE PÉDAGOGIQUE',
            'dean_of_studies' => 'DEAN OF STUDIES',
            'censeur_esg' => 'CENSEUR ESG',
            'censeur' => 'CENSEUR',
            'surveillant_secteur' => 'SURVEILLANT DE SECTEUR',
            'caissiere' => 'CAISSIÈRE',
            'bibliothecaire' => 'BIBLIOTHÉCAIRE',
            'chef_travaux' => 'CHEF DES TRAVAUX',
            'chef_securite' => 'CHEFS DE SÉCURITÉ',
            'reprographe' => 'REPROGRAPHE',
        ];
        
        return $positions[$role] ?? strtoupper($role);
    }

    /**
     * Obtenir une qualification approximative selon le rôle
     */
    private function getQualificationForRole($role): string
    {
        $qualifications = [
            // Rôles existants dans l'application
            'admin' => 'Maîtrise',
            'surveillant_general' => 'Licence',
            'general_accountant' => 'BTS Comptabilité',
            'comptable_superieur' => 'Licence Comptabilité',
            'comptable' => 'BTS',
            'accountant' => 'BTS',
            'secretaire' => 'BAC + 2',
            'teacher' => 'Licence',
            
            // Rôles additionnels du fichier k.png
            'principal' => 'Maîtrise',
            'responsable_pedagogique' => 'Licence',
            'dean_of_studies' => 'GCE A+1',
            'censeur_esg' => 'TMSI (bac +2)',
            'censeur' => 'BAC + 2',
            'surveillant_secteur' => 'PROBATOIRE',
            'caissiere' => 'PROBATOIRE',
            'bibliothecaire' => 'BAC A4',
            'chef_travaux' => 'BP',
            'chef_securite' => 'BAC',
            'reprographe' => 'BAC',
        ];
        
        return $qualifications[$role] ?? 'BAC';
    }

    /**
     * Générer les badges de tout le personnel éligible
     */
    public function generateAllStaffBadges()
    {
        try {
            // Récupérer tous les utilisateurs éligibles pour les badges
            $staffUsers = User::select('id', 'name', 'role', 'contact', 'email')
                ->where('is_active', true)
                ->whereIn('role', [
                    'surveillant_general', 'general_accountant', 'comptable_superieur', 
                    'comptable', 'secretaire', 'teacher', 'accountant',
                    'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 
                    'surveillant_secteur', 'caissiere', 'bibliothecaire', 
                    'chef_travaux', 'chef_securite', 'reprographe'
                ])
                ->orderBy('name')
                ->get();

            if ($staffUsers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun membre du personnel trouvé pour générer les badges'
                ], 404);
            }

            // Récupérer les paramètres de l'école
            $schoolSettings = SchoolSetting::getSettings();
            
            // Générer le HTML pour tous les badges
            $html = $this->generateAllStaffBadgesHtml($staffUsers, $schoolSettings);
            
            // Créer le PDF
            $pdf = Pdf::loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'badges_tout_le_personnel_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des badges de tout le personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour tous les badges du personnel
     */
    private function generateAllStaffBadgesHtml($staffUsers, $schoolSettings): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Badges Personnel - ' . ($schoolSettings['school_name'] ?? 'École') . '</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.2;
                }
                
                .page {
                    width: 210mm;
                    min-height: 297mm;
                    padding: 10mm;
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                    align-content: flex-start;
                }
                
                .badge {
                    width: 85mm;
                    height: 54mm;
                    border: 2px solid #333;
                    border-radius: 8px;
                    margin: 3mm;
                    padding: 3mm;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    page-break-inside: avoid;
                    background: white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .badge-header {
                    text-align: center;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 2mm;
                    margin-bottom: 2mm;
                }
                
                .school-name {
                    font-size: 8px;
                    font-weight: bold;
                    color: #333;
                    text-transform: uppercase;
                }
                
                .badge-body {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-grow: 1;
                }
                
                .user-info {
                    flex: 1;
                    padding-right: 3mm;
                }
                
                .user-name {
                    font-size: 12px;
                    font-weight: bold;
                    color: #333;
                    margin-bottom: 1mm;
                    text-transform: uppercase;
                }
                
                .user-role {
                    font-size: 9px;
                    color: #666;
                    margin-bottom: 2mm;
                    font-style: italic;
                }
                
                .user-contact {
                    font-size: 8px;
                    color: #888;
                }
                
                .qr-code {
                    width: 15mm;
                    height: 15mm;
                    border: 1px solid #ddd;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f9f9f9;
                }
                
                .qr-code img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                }
                
                .badge-footer {
                    border-top: 1px solid #ddd;
                    padding-top: 1mm;
                    text-align: center;
                }
                
                .badge-id {
                    font-size: 7px;
                    color: #999;
                }
                
                .year {
                    font-size: 8px;
                    color: #666;
                }
                
                @page {
                    margin: 10mm;
                    size: A4 portrait;
                }
            </style>
        </head>
        <body>
            <div class="page">';

        foreach ($staffUsers as $index => $user) {
            // Générer le contenu QR pour l'utilisateur
            $qrContent = "STAFF_ID_" . $user->id;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrContent);
            
            $roleLabel = $this->getPositionLabel($user->role);
            $contact = $user->contact ?: $user->email;
            
            $html .= '
                <div class="badge">
                    <div class="badge-header">
                        <div class="school-name">' . ($schoolSettings['school_name'] ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA') . '</div>
                    </div>
                    <div class="badge-body">
                        <div class="user-info">
                            <div class="user-name">' . strtoupper($user->name) . '</div>
                            <div class="user-role">' . $roleLabel . '</div>
                            <div class="user-contact">' . $contact . '</div>
                        </div>
                        <div class="qr-code">
                            <img src="' . $qrUrl . '" alt="QR Code" />
                        </div>
                    </div>
                    <div class="badge-footer">
                        <div class="badge-id">ID: ' . $user->id . '</div>
                        <div class="year">' . date('Y') . '/' . (date('Y') + 1) . '</div>
                    </div>
                </div>';
            
            // Nouvelle page tous les 10 badges (5x2)
            if (($index + 1) % 10 === 0 && $index < count($staffUsers) - 1) {
                $html .= '
            </div>
            <div style="page-break-before: always;"></div>
            <div class="page">';
            }
        }

        $html .= '
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Générer un badge individuel pour un membre du personnel
     */
    public function generateIndividualBadge($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Vérifier que l'utilisateur est éligible pour un badge
            $staffRoles = [
                'surveillant_general', 'general_accountant', 'comptable_superieur', 
                'comptable', 'secretaire', 'teacher', 'accountant',
                'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 
                'surveillant_secteur', 'caissiere', 'bibliothecaire', 
                'chef_travaux', 'chef_securite', 'reprographe'
            ];
            
            if (!in_array($user->role, $staffRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas éligible pour un badge'
                ], 403);
            }
            
            // Récupérer les paramètres de l'école
            $schoolSettings = SchoolSetting::getSettings();
            
            // Générer le HTML pour le badge individuel (même design que les badges multiples)
            $html = $this->generateSingleBadgeHtml($user, $schoolSettings);
            
            // Créer le PDF
            $pdf = Pdf::loadHtml($html);
            $pdf->setPaper([0, 0, 241.89, 152.4], 'landscape'); // Format carte bancaire (85mm x 54mm)
            
            $filename = 'badge_' . Str::slug($user->name) . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du badge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour un badge individuel
     */
    private function generateSingleBadgeHtml($user, $schoolSettings): string
    {
        // Générer le contenu QR pour l'utilisateur
        $qrContent = "STAFF_ID_" . $user->id;
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrContent);
        
        $roleLabel = $this->getPositionLabel($user->role);
        $contact = $user->contact ?: $user->email;
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Badge - ' . $user->name . '</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.2;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .badge {
                    width: 85mm;
                    height: 54mm;
                    border: 2px solid #333;
                    border-radius: 8px;
                    padding: 3mm;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    background: white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .badge-header {
                    text-align: center;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 2mm;
                    margin-bottom: 2mm;
                }
                
                .school-name {
                    font-size: 8px;
                    font-weight: bold;
                    color: #333;
                    text-transform: uppercase;
                }
                
                .badge-body {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-grow: 1;
                }
                
                .user-info {
                    flex: 1;
                    padding-right: 3mm;
                }
                
                .user-name {
                    font-size: 12px;
                    font-weight: bold;
                    color: #333;
                    margin-bottom: 1mm;
                    text-transform: uppercase;
                }
                
                .user-role {
                    font-size: 9px;
                    color: #666;
                    margin-bottom: 2mm;
                    font-style: italic;
                }
                
                .user-contact {
                    font-size: 8px;
                    color: #888;
                }
                
                .qr-code {
                    width: 15mm;
                    height: 15mm;
                    border: 1px solid #ddd;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f9f9f9;
                }
                
                .qr-code img {
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                }
                
                .badge-footer {
                    border-top: 1px solid #ddd;
                    padding-top: 1mm;
                    text-align: center;
                }
                
                .badge-id {
                    font-size: 7px;
                    color: #999;
                }
                
                .year {
                    font-size: 8px;
                    color: #666;
                }
                
                @page {
                    margin: 0;
                    size: 85mm 54mm;
                }
            </style>
        </head>
        <body>
            <div class="badge">
                <div class="badge-header">
                    <div class="school-name">' . ($schoolSettings['school_name'] ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA') . '</div>
                </div>
                <div class="badge-body">
                    <div class="user-info">
                        <div class="user-name">' . strtoupper($user->name) . '</div>
                        <div class="user-role">' . $roleLabel . '</div>
                        <div class="user-contact">' . $contact . '</div>
                    </div>
                    <div class="qr-code">
                        <img src="' . $qrUrl . '" alt="QR Code" />
                    </div>
                </div>
                <div class="badge-footer">
                    <div class="badge-id">ID: ' . $user->id . '</div>
                    <div class="year">' . date('Y') . '/' . (date('Y') + 1) . '</div>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }
}