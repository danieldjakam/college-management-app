<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolSetting;

class StaffAttendanceController extends Controller
{
    /**
     * Scan QR code d'un membre du personnel pour enregistrer sa présence
     */
    public function scanQR(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'staff_qr_code' => 'required|string',
                'supervisor_id' => 'required|exists:users,id',
                'event_type' => 'sometimes|in:entry,exit,auto'
            ]);

            // Chercher d'abord dans les users qui ont un QR code
            // Inclure tous les rôles de personnel possible
            $user = User::where('qr_code', $request->staff_qr_code)
                       ->whereIn('role', ['teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire'])
                       ->where('is_active', true)
                       ->first();

            // Si pas trouvé dans users, chercher dans teachers
            if (!$user) {
                $teacher = Teacher::where('qr_code', $request->staff_qr_code)
                                ->where('is_active', true)
                                ->first();
                
                if ($teacher && $teacher->user) {
                    $user = $teacher->user;
                }
            }

            if (!$user) {
                // Vérifier si le QR code existe mais avec un rôle différent
                $userWithDifferentRole = User::where('qr_code', $request->staff_qr_code)
                    ->where('is_active', true)
                    ->first();
                
                if ($userWithDifferentRole) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Code QR invalide - rôle non autorisé pour la présence personnel'
                    ], 403);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif'
                ], 404);
            }

            // Déterminer le type de personnel
            $staffType = $this->getStaffType($user);

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $now = Carbon::now();
            $today = $now->toDateString();
            $eventType = $request->event_type ?? 'auto';

            // Vérifier le dernier mouvement pour déterminer l'action suivante
            $lastMovement = StaffAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at', 'desc')
                ->first();

            // Déterminer le type d'événement
            if ($eventType === 'auto') {
                // Auto-détection basée sur le dernier mouvement
                if (!$lastMovement || $lastMovement->event_type === 'exit') {
                    $eventType = 'entry';
                } else {
                    $eventType = 'exit';
                }
            }

            // Calculer les minutes de retard (seulement pour les entrées)
            $lateMinutes = 0;
            if ($eventType === 'entry') {
                $lateMinutes = $this->calculateLateMinutes($now, $staffType);
            }
            
            // Créer un nouvel enregistrement pour chaque mouvement
            $attendance = StaffAttendance::create([
                'user_id' => $user->id,
                'supervisor_id' => $request->supervisor_id,
                'school_year_id' => $currentSchoolYear->id,
                'attendance_date' => $today,
                'scanned_at' => $now,
                'is_present' => $eventType === 'entry',
                'event_type' => $eventType,
                'staff_type' => $staffType,
                'late_minutes' => $lateMinutes
            ]);

            // Calculer le temps de travail total pour la journée
            $totalWorkTime = $this->calculateDailyWorkTime($user->id, $today, $currentSchoolYear->id);

            $message = $eventType === 'entry' ? 'Entrée enregistrée avec succès' : 'Sortie enregistrée avec succès';
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'staff_member' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'staff_type' => $staffType
                    ],
                    'attendance' => $attendance,
                    'event_type' => $eventType,
                    'late_minutes' => $lateMinutes,
                    'scan_time' => $now->format('H:i:s'),
                    'daily_work_time' => $totalWorkTime
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la présence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les présences du jour
     */
    public function getDailyAttendance(Request $request): JsonResponse
    {
        try {
            $date = $request->get('date', Carbon::now()->toDateString());
            $staffType = $request->get('staff_type'); // optionnel

            $query = StaffAttendance::with(['user', 'supervisor'])
                ->forDate($date);

            if ($staffType) {
                $query->forStaffType($staffType);
            }

            $attendances = $query->orderBy('scanned_at', 'desc')->get();

            // Grouper par type de personnel
            $groupedAttendances = $attendances->groupBy('staff_type');

            // Statistiques du jour
            $stats = [
                'total_present' => $attendances->where('is_present', true)->count(),
                'total_absent' => $attendances->where('is_present', false)->count(),
                'total_late' => $attendances->where('late_minutes', '>', 0)->count(),
                'by_staff_type' => []
            ];

            foreach ($groupedAttendances as $type => $typeAttendances) {
                $stats['by_staff_type'][$type] = [
                    'total' => $typeAttendances->count(),
                    'present' => $typeAttendances->where('is_present', true)->count(),
                    'late' => $typeAttendances->where('late_minutes', '>', 0)->count()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'stats' => $stats,
                    'date' => $date
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des présences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un QR code pour un membre du personnel
     */
    public function generateQRCode(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::find($request->user_id);
            
            // Vérifier que c'est un membre du personnel
            if (!in_array($user->role, ['teacher', 'accountant', 'admin', 'surveillant_general'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un membre du personnel'
                ], 400);
            }

            // Générer un code QR unique simple
            $qrCode = 'STAFF_' . $user->id;
            
            // Pas besoin de générer de fichier, on utilise l'API externe côté frontend
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCode) . '&margin=1';

            // Mettre à jour l'utilisateur avec le nouveau QR code
            $user->update(['qr_code' => $qrCode]);

            // Si c'est un enseignant, mettre à jour aussi dans la table teachers
            if ($user->role === 'teacher') {
                $teacher = Teacher::where('user_id', $user->id)->first();
                if ($teacher) {
                    $teacher->update(['qr_code' => $qrCode]);
                }
            }

            // Générer directement le PDF du badge
            $badgeHtml = $this->generateBadgeHtmlForPDF($user, $qrCode);
            
            // Configuration DomPDF avec optimisations
            $pdf = Pdf::loadHtml($badgeHtml);
            $pdf->setPaper('A4', 'portrait');
            
            // Optimisations pour améliorer la performance
            $pdf->setOptions([
                'isPhpEnabled' => false,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'dpi' => 96, // Réduire la DPI pour des PDF plus rapides
                'enable_css_float' => false,
                'enable_html5_parser' => false
            ]);

            // Nom du fichier
            $filename = 'badge_' . str_replace(' ', '_', $user->name) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Retourner le PDF en téléchargement direct
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML du badge personnel pour PDF
     */
    private function generateBadgeHtmlForPDF($user, $qrCode)
    {
        // Récupérer les paramètres de l'école
        $schoolSettings = SchoolSetting::first();
        
        // Convertir le logo en base64 pour DOMPDF
        $logoBase64 = '';
        if ($schoolSettings && $schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoContent = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoContent);
            }
        }

        // Log de début pour debug
        \Log::info("Starting badge generation for user: " . $user->id . " (" . $user->name . ")", ['photo_url' => $user->photo]);
        
        // Convertir la photo du personnel en base64 (optimisée)
        $photoBase64 = '';
        if ($user->photo) {
            try {
                $photoContent = null;
                if (str_starts_with($user->photo, 'http')) {
                    // Corriger l'URL pour pointer vers localhost/serveur local
                    $correctedUrl = str_replace(['127.0.0.1:8000', 'localhost:8000'], '192.168.1.229:8000', $user->photo);
                    
                    // Pour les URLs, essayer d'abord l'accès direct au fichier
                    $relativePath = str_replace(['http://127.0.0.1:8000/', 'http://localhost:8000/', 'http://192.168.1.229:8000/'], '', $user->photo);
                    // Enlever "storage/" du début si présent car storage_path('app/public/') inclut déjà le chemin vers public
                    $relativePath = ltrim($relativePath, '/');
                    if (str_starts_with($relativePath, 'storage/')) {
                        $relativePath = substr($relativePath, 8); // Enlever "storage/"
                    }
                    $localPath = storage_path('app/public/' . $relativePath);
                    
                    if (file_exists($localPath)) {
                        // Utiliser le fichier local si disponible (plus rapide)
                        $photoContent = file_get_contents($localPath);
                        \Log::info("Photo loaded from local path: " . $localPath);
                    } else {
                        // Sinon essayer l'URL corrigée
                        $context = stream_context_create([
                            'http' => [
                                'timeout' => 5, // 5 secondes max
                                'user_agent' => 'Mozilla/5.0'
                            ]
                        ]);
                        $photoContent = file_get_contents($correctedUrl, false, $context);
                        \Log::info("Photo loaded from URL: " . $correctedUrl);
                    }
                } else {
                    // Si c'est un chemin local
                    $photoPath = storage_path('app/public/' . $user->photo);
                    if (file_exists($photoPath)) {
                        $photoContent = file_get_contents($photoPath);
                        \Log::info("Photo loaded from relative path: " . $photoPath);
                    }
                }
                
                if ($photoContent) {
                    // Optimiser l'image si elle est trop grosse (> 50KB)
                    if (strlen($photoContent) > 50000) {
                        // Créer une image temporaire plus petite
                        $tempImage = imagecreatefromstring($photoContent);
                        if ($tempImage) {
                            // Redimensionner à 80x80 max pour le badge
                            $newImage = imagecreatetruecolor(80, 80);
                            $width = imagesx($tempImage);
                            $height = imagesy($tempImage);
                            
                            imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, 80, 80, $width, $height);
                            
                            // Convertir en PNG optimisé
                            ob_start();
                            imagepng($newImage, null, 6); // Compression 6/9
                            $photoContent = ob_get_clean();
                            
                            imagedestroy($tempImage);
                            imagedestroy($newImage);
                        }
                    }
                    
                    $photoBase64 = 'data:image/png;base64,' . base64_encode($photoContent);
                    \Log::info("Photo successfully converted to base64 for user: " . $user->id);
                }
            } catch (\Exception $e) {
                // Logs détaillés pour debug
                \Log::warning('Erreur chargement photo utilisateur: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'user_photo' => $user->photo,
                    'error_message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Si pas de photo, utiliser une image par défaut
        if (!$photoBase64) {
            $photoBase64 = 'data:image/svg+xml;base64,' . base64_encode('
                <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" fill="#e2e8f0"/>
                    <circle cx="50" cy="35" r="15" fill="#9ca3af"/>
                    <path d="M20 80 C20 65, 35 50, 50 50 C65 50, 80 65, 80 80 L80 100 L20 100 Z" fill="#9ca3af"/>
                </svg>
            ');
        }

        // Déterminer le type et la couleur
        $staffTypes = [
            'teacher' => ['label' => 'ENSEIGNANT', 'color' => '#4a4a8a'],
            'accountant' => ['label' => 'COMPTABLE', 'color' => '#2e7d32'],
            'comptable_superieur' => ['label' => 'COMPTABLE SUPÉRIEUR', 'color' => '#1976d2'],
            'surveillant_general' => ['label' => 'SURVEILLANT GÉNÉRAL', 'color' => '#f57c00'],
            'admin' => ['label' => 'ADMINISTRATEUR', 'color' => '#d32f2f']
        ];

        $staffConfig = $staffTypes[$user->role] ?? ['label' => 'PERSONNEL', 'color' => '#7f8c8d'];

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Badge Personnel - {$user->name}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Arial', 'Helvetica', sans-serif; 
                    background: #f5f5f5; 
                    padding: 20mm;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .badge-container {
                    width: 85.6mm;
                    height: 54mm;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    position: relative;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border: 1px solid #e0e0e0;
                }
                
                /* Header Section */
                .badge-header {
                    background: {$staffConfig['color']};
                    color: white;
                    padding: 6px 12px;
                    text-align: center;
                    font-size: 8px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                
                .school-logo {
                    width: 16px;
                    height: 16px;
                    object-fit: contain;
                    filter: brightness(0) invert(1);
                }
                
                /* Tableau principal */
                .content-table {
                    width: 100%;
                    height: calc(100% - 24px - 16px);
                    border-collapse: collapse;
                    table-layout: fixed;
                }
                
                .content-table td {
                    vertical-align: middle;
                    padding: 6px;
                    border: none;
                }
                
                /* Colonne 1 - Photo (25%) */
                .photo-cell {
                    width: 25%;
                    text-align: center;
                }
                
                .staff-photo {
                    width: 24mm;
                    height: 30mm;
                    object-fit: cover;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                    background: #f9f9f9;
                }
                
                /* Colonne 2 - Informations (50%) */
                .info-cell {
                    width: 50%;
                    padding-left: 8px;
                    padding-right: 8px;
                }
                
                .id-number-label {
                    font-size: 6px;
                    color: {$staffConfig['color']};
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 1px;
                    letter-spacing: 0.5px;
                    display: block;
                }
                
                .id-number {
                    font-size: 10px;
                    color: {$staffConfig['color']};
                    font-weight: bold;
                    margin-bottom: 4px;
                    display: block;
                }
                
                .name-label {
                    font-size: 6px;
                    color: {$staffConfig['color']};
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 1px;
                    letter-spacing: 0.5px;
                    display: block;
                }
                
                .staff-name {
                    font-size: 9px;
                    color: #2c2c2c;
                    font-weight: bold;
                    margin-bottom: 4px;
                    line-height: 1.1;
                    display: block;
                }
                
                .role-label {
                    font-size: 6px;
                    color: {$staffConfig['color']};
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 1px;
                    letter-spacing: 0.5px;
                    display: block;
                }
                
                .staff-role {
                    font-size: 8px;
                    color: #2c2c2c;
                    font-weight: normal;
                    line-height: 1.1;
                    display: block;
                }
                
                /* Colonne 3 - QR Code (25%) */
                .qr-cell {
                    width: 25%;
                    text-align: center;
                }
                
                .qr-code {
                    width: 20mm;
                    height: 20mm;
                    object-fit: contain;
                    border: 1px solid #ddd;
                }
                
                /* Footer Section */
                .badge-footer {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: " . $this->adjustBrightness($staffConfig['color'], 60) . ";
                    color: {$staffConfig['color']};
                    padding: 3px 12px;
                    text-align: center;
                    font-size: 7px;
                    font-weight: bold;
                    letter-spacing: 3px;
                    text-transform: uppercase;
                }
                
                @page {
                    size: A4;
                    margin: 10mm;
                }
            </style>
        </head>
        <body>
            <div class='badge-container'>
                <!-- Header -->
                <div class='badge-header'>
                    " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo' class='school-logo'>" : '') . "
                    <span>IDENTIFICATION EMPLOYÉ</span>
                </div>
                
                <!-- Main Content - Tableau 3 colonnes -->
                <table class='content-table'>
                    <tr>
                        <!-- Colonne 1: Photo -->
                        <td class='photo-cell'>
                            <img src='{$photoBase64}' alt='Photo' class='staff-photo'>
                        </td>
                        
                        <!-- Colonne 2: Informations -->
                        <td class='info-cell'>
                            <span class='id-number-label'>N° D'IDENTIFICATION</span>
                            <span class='id-number'>{$user->id}</span>
                            
                            <span class='name-label'>NOM</span>
                            <span class='staff-name'>{$user->name}</span>
                            
                            <span class='role-label'>POSTE / EMPLOI</span>
                            <span class='staff-role'>{$staffConfig['label']}</span>
                        </td>
                        
                        <!-- Colonne 3: QR Code -->
                        <td class='qr-cell'>
                            <img src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrCode) . "&margin=1' alt='QR Code' class='qr-code'>
                        </td>
                    </tr>
                </table>
                
                <!-- Footer -->
                <div class='badge-footer'>
                    " . ($schoolSettings->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA') . "
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Générer plusieurs badges sur un même PDF
     */
    public function generateMultipleBadges(Request $request)
    {
        try {
            $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'required|exists:users,id',
            ]);

            $userIds = $request->user_ids;
            $users = User::whereIn('id', $userIds)
                ->whereIn('role', ['teacher', 'accountant', 'admin', 'surveillant_general'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun personnel valide trouvé'
                ], 400);
            }

            // Générer le HTML avec plusieurs badges
            $html = $this->generateMultipleBadgesHtml($users);
            
            // Configuration DomPDF
            $pdf = Pdf::loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            
            $pdf->setOptions([
                'isPhpEnabled' => false,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'dpi' => 96,
                'enable_css_float' => false,
                'enable_html5_parser' => false
            ]);

            // Nom du fichier
            $filename = 'badges_personnel_' . count($users) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des badges',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour plusieurs badges
     */
    private function generateMultipleBadgesHtml($users)
    {
        $schoolSettings = SchoolSetting::first();
        
        // Convertir le logo en base64
        $logoBase64 = '';
        if ($schoolSettings && $schoolSettings->school_logo) {
            $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoContent = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoContent);
            }
        }

        $badgesHtml = '';
        $badgeCount = 0;
        
        foreach ($users as $user) {
            // Générer QR code si nécessaire
            $qrCode = $user->qr_code ?: 'STAFF_' . $user->id;
            if (!$user->qr_code) {
                $user->update(['qr_code' => $qrCode]);
                if ($user->role === 'teacher') {
                    $teacher = Teacher::where('user_id', $user->id)->first();
                    if ($teacher) {
                        $teacher->update(['qr_code' => $qrCode]);
                    }
                }
            }

            // Convertir la photo en base64
            $photoBase64 = $this->getUserPhotoBase64($user);

            // Générer le HTML du badge
            $badgeHtml = $this->generateSingleBadgeHtml($user, $qrCode, $photoBase64, $logoBase64, $schoolSettings);
            
            // Ajouter le badge avec gestion des sauts de page
            if ($badgeCount > 0 && $badgeCount % 4 === 0) {
                $badgesHtml .= '<div style="page-break-before: always;"></div>';
            }
            
            $badgesHtml .= '<div class="badge-wrapper">' . $badgeHtml . '</div>';
            $badgeCount++;
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Badges Personnel - " . count($users) . " badges</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Arial', 'Helvetica', sans-serif; 
                    background: white; 
                    padding: 10mm;
                }
                
                .badge-wrapper {
                    display: inline-block;
                    margin: 5mm;
                    page-break-inside: avoid;
                }
                
                .badge-container {
                    width: 85.6mm;
                    height: 54mm;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    position: relative;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border: 1px solid #e0e0e0;
                }
                
                /* Styles de badge (repris du badge simple) */
                .badge-header {
                    color: white;
                    padding: 6px 12px;
                    text-align: center;
                    font-size: 8px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }
                
                .school-logo {
                    width: 16px;
                    height: 16px;
                    object-fit: contain;
                    filter: brightness(0) invert(1);
                }
                
                .content-table {
                    width: 100%;
                    height: calc(100% - 24px - 16px);
                    border-collapse: collapse;
                    table-layout: fixed;
                }
                
                .content-table td {
                    vertical-align: middle;
                    padding: 6px;
                    border: none;
                }
                
                .photo-cell {
                    width: 25%;
                    text-align: center;
                }
                
                .staff-photo {
                    width: 24mm;
                    height: 30mm;
                    object-fit: cover;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                    background: #f9f9f9;
                }
                
                .info-cell {
                    width: 50%;
                    padding-left: 8px;
                    padding-right: 8px;
                }
                
                .id-number-label, .name-label, .role-label {
                    font-size: 6px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 1px;
                    letter-spacing: 0.5px;
                    display: block;
                }
                
                .id-number {
                    font-size: 10px;
                    font-weight: bold;
                    margin-bottom: 4px;
                    display: block;
                }
                
                .staff-name {
                    font-size: 9px;
                    color: #2c2c2c;
                    font-weight: bold;
                    margin-bottom: 4px;
                    line-height: 1.1;
                    display: block;
                }
                
                .staff-role {
                    font-size: 8px;
                    color: #2c2c2c;
                    font-weight: normal;
                    line-height: 1.1;
                    display: block;
                }
                
                .qr-cell {
                    width: 25%;
                    text-align: center;
                }
                
                .qr-code {
                    width: 20mm;
                    height: 20mm;
                    object-fit: contain;
                    border: 1px solid #ddd;
                }
                
                .badge-footer {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    padding: 3px 12px;
                    text-align: center;
                    font-size: 7px;
                    font-weight: bold;
                    letter-spacing: 3px;
                    text-transform: uppercase;
                }
                
                @page {
                    size: A4;
                    margin: 10mm;
                }
                
                @media print {
                    .badge-wrapper {
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            {$badgesHtml}
        </body>
        </html>";
    }

    /**
     * Générer le HTML d'un badge individuel
     */
    private function generateSingleBadgeHtml($user, $qrCode, $photoBase64, $logoBase64, $schoolSettings)
    {
        $staffTypes = [
            'teacher' => ['label' => 'ENSEIGNANT', 'color' => '#4a4a8a'],
            'accountant' => ['label' => 'COMPTABLE', 'color' => '#2e7d32'],
            'comptable_superieur' => ['label' => 'COMPTABLE SUPÉRIEUR', 'color' => '#1976d2'],
            'surveillant_general' => ['label' => 'SURVEILLANT GÉNÉRAL', 'color' => '#f57c00'],
            'admin' => ['label' => 'ADMINISTRATEUR', 'color' => '#d32f2f']
        ];

        $staffConfig = $staffTypes[$user->role] ?? ['label' => 'PERSONNEL', 'color' => '#7f8c8d'];
        $footerColor = $this->adjustBrightness($staffConfig['color'], 60);

        return "
        <div class='badge-container'>
            <div class='badge-header' style='background: {$staffConfig['color']};'>
                " . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo' class='school-logo'>" : '') . "
                <span>IDENTIFICATION EMPLOYÉ</span>
            </div>
            
            <table class='content-table'>
                <tr>
                    <td class='photo-cell'>
                        <img src='{$photoBase64}' alt='Photo' class='staff-photo'>
                    </td>
                    
                    <td class='info-cell'>
                        <span class='id-number-label' style='color: {$staffConfig['color']};'>N° D'IDENTIFICATION</span>
                        <span class='id-number' style='color: {$staffConfig['color']};'>{$user->id}</span>
                        
                        <span class='name-label' style='color: {$staffConfig['color']};'>NOM</span>
                        <span class='staff-name'>{$user->name}</span>
                        
                        <span class='role-label' style='color: {$staffConfig['color']};'>POSTE / EMPLOI</span>
                        <span class='staff-role'>{$staffConfig['label']}</span>
                    </td>
                    
                    <td class='qr-cell'>
                        <img src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrCode) . "&margin=1' alt='QR Code' class='qr-code'>
                    </td>
                </tr>
            </table>
            
            <div class='badge-footer' style='background: {$footerColor}; color: {$staffConfig['color']};'>
                " . ($schoolSettings->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA') . "
            </div>
        </div>";
    }

    /**
     * Obtenir la photo de l'utilisateur en base64
     */
    private function getUserPhotoBase64($user)
    {
        $photoBase64 = '';
        if ($user->photo) {
            try {
                $photoContent = null;
                if (str_starts_with($user->photo, 'http')) {
                    $relativePath = str_replace(['http://127.0.0.1:8000/', 'http://localhost:8000/', 'http://192.168.1.229:8000/'], '', $user->photo);
                    $relativePath = ltrim($relativePath, '/');
                    if (str_starts_with($relativePath, 'storage/')) {
                        $relativePath = substr($relativePath, 8);
                    }
                    $localPath = storage_path('app/public/' . $relativePath);
                    
                    if (file_exists($localPath)) {
                        $photoContent = file_get_contents($localPath);
                    }
                } else {
                    $photoPath = storage_path('app/public/' . $user->photo);
                    if (file_exists($photoPath)) {
                        $photoContent = file_get_contents($photoPath);
                    }
                }
                
                if ($photoContent) {
                    // Optimiser l'image si trop grosse
                    if (strlen($photoContent) > 50000) {
                        $tempImage = imagecreatefromstring($photoContent);
                        if ($tempImage) {
                            $newImage = imagecreatetruecolor(80, 80);
                            $width = imagesx($tempImage);
                            $height = imagesy($tempImage);
                            
                            imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, 80, 80, $width, $height);
                            
                            ob_start();
                            imagepng($newImage, null, 6);
                            $photoContent = ob_get_clean();
                            
                            imagedestroy($tempImage);
                            imagedestroy($newImage);
                        }
                    }
                    
                    $photoBase64 = 'data:image/png;base64,' . base64_encode($photoContent);
                }
            } catch (\Exception $e) {
                \Log::warning('Erreur chargement photo utilisateur: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'user_photo' => $user->photo
                ]);
            }
        }

        // Image par défaut
        if (!$photoBase64) {
            $photoBase64 = 'data:image/svg+xml;base64,' . base64_encode('
                <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" fill="#e2e8f0"/>
                    <circle cx="50" cy="35" r="15" fill="#9ca3af"/>
                    <path d="M20 80 C20 65, 35 50, 50 50 C65 50, 80 65, 80 80 L80 100 L20 100 Z" fill="#9ca3af"/>
                </svg>
            ');
        }

        return $photoBase64;
    }

    /**
     * Obtenir la liste du personnel avec QR codes
     */
    public function getStaffWithQR(): JsonResponse
    {
        try {
            $staff = User::whereIn('role', ['teacher', 'accountant', 'admin', 'surveillant_general'])
                ->where('is_active', true)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->contact,
                        'role' => $user->role,
                        'staff_type' => $this->getStaffType($user),
                        'has_qr_code' => !empty($user->qr_code),
                        'qr_code' => $user->qr_code,
                        'photo' => $user->photo,
                        'photo_url' => $user->photo ? (
                            str_starts_with($user->photo, 'http') 
                                ? $user->photo 
                                : url('storage/' . $user->photo)
                        ) : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $staff
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'un membre du personnel
     */
    public function getStaffReport(Request $request, $staffId): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->toDateString());

            $user = User::findOrFail($staffId);
            $stats = StaffAttendance::getStaffStats($staffId, $startDate, $endDate);

            $attendances = StaffAttendance::forUser($staffId)
                ->forDateRange($startDate, $endDate)
                ->orderBy('attendance_date', 'desc')
                ->get();

            // Grouper les présences par jour et calculer les paires entrée-sortie
            $dailyDetails = $this->calculateDailyWorkPairs($attendances);

            return response()->json([
                'success' => true,
                'data' => [
                    'staff_member' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'staff_type' => $this->getStaffType($user)
                    ],
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ],
                    'stats' => $stats,
                    'attendances' => $attendances,
                    'daily_details' => $dailyDetails
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
     * Obtenir les statistiques globales par type de personnel
     */
    public function getEntryExitStats(Request $request): JsonResponse
    {
        try {
            $date = $request->get('date', Carbon::now()->toDateString());

            $stats = [];
            $staffTypes = ['teacher', 'accountant', 'supervisor', 'admin'];

            foreach ($staffTypes as $staffType) {
                $typeStats = StaffAttendance::getStaffTypeStats($staffType, $date, $date);
                $typeStats['entries'] = StaffAttendance::forStaffType($staffType)
                    ->forDate($date)
                    ->entries()
                    ->count();
                $typeStats['exits'] = StaffAttendance::forStaffType($staffType)
                    ->forDate($date)
                    ->exits()
                    ->count();
                
                $stats[$staffType] = $typeStats;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'stats_by_type' => $stats
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
     * Déterminer le type de personnel basé sur le rôle
     */
    private function getStaffType(User $user): string
    {
        switch ($user->role) {
            case 'teacher':
                return 'teacher';
            case 'accountant':
            case 'comptable_superieur':
            case 'general_accountant':
                return 'accountant';
            case 'surveillant_general':
                return 'supervisor';
            case 'admin':
                return 'admin';
            case 'secretaire':
                return 'secretaire';
            default:
                return 'teacher'; // fallback
        }
    }

    /**
     * Calculer les minutes de retard
     */
    private function calculateLateMinutes(Carbon $scanTime, string $staffType): int
    {
        // Heures de début par défaut (peut être configuré plus tard)
        $workStartTimes = [
            'teacher' => '07:30',
            'accountant' => '08:00',
            'supervisor' => '07:00',
            'admin' => '08:00'
        ];

        $expectedStartTime = $workStartTimes[$staffType] ?? '08:00';
        $expectedStart = Carbon::createFromTimeString($expectedStartTime);
        $scanDateTime = Carbon::createFromTimeString($scanTime->format('H:i:s'));

        if ($scanDateTime->greaterThan($expectedStart)) {
            return $scanDateTime->diffInMinutes($expectedStart);
        }

        return 0;
    }

    /**
     * Calculer le temps de travail total pour une journée
     */
    private function calculateDailyWorkTime($userId, $date, $schoolYearId)
    {
        $movements = StaffAttendance::where('user_id', $userId)
            ->where('attendance_date', $date)
            ->where('school_year_id', $schoolYearId)
            ->orderBy('scanned_at', 'asc')
            ->get();

        $totalMinutes = 0;
        $entryTime = null;

        foreach ($movements as $movement) {
            if ($movement->event_type === 'entry') {
                $entryTime = Carbon::parse($movement->scanned_at);
            } elseif ($movement->event_type === 'exit' && $entryTime) {
                $exitTime = Carbon::parse($movement->scanned_at);
                $totalMinutes += $entryTime->diffInMinutes($exitTime);
                $entryTime = null; // Reset pour la prochaine paire entrée/sortie
            }
        }

        // Convertir en heures avec 2 décimales
        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculer les paires entrée-sortie pour chaque jour
     */
    private function calculateDailyWorkPairs($attendances)
    {
        $groupedByDate = $attendances->groupBy('attendance_date');
        $dailyDetails = [];

        foreach ($groupedByDate as $date => $dayAttendances) {
            $movements = $dayAttendances->sortBy('scanned_at');
            $workPairs = [];
            $entryTime = null;
            $totalDayMinutes = 0;
            $totalLateMinutes = 0;

            foreach ($movements as $movement) {
                if ($movement->event_type === 'entry') {
                    $entryTime = Carbon::parse($movement->scanned_at);
                    $totalLateMinutes += $movement->late_minutes ?? 0;
                } elseif ($movement->event_type === 'exit' && $entryTime) {
                    $exitTime = Carbon::parse($movement->scanned_at);
                    $sessionMinutes = $entryTime->diffInMinutes($exitTime);
                    $totalDayMinutes += $sessionMinutes;

                    $workPairs[] = [
                        'entry_time' => $entryTime->format('H:i'),
                        'exit_time' => $exitTime->format('H:i'),
                        'duration_minutes' => $sessionMinutes,
                        'duration_formatted' => $this->formatDuration($sessionMinutes)
                    ];

                    $entryTime = null; // Reset pour la prochaine paire
                }
            }

            // Si il y a une entrée sans sortie (encore au travail)
            if ($entryTime) {
                $workPairs[] = [
                    'entry_time' => $entryTime->format('H:i'),
                    'exit_time' => null,
                    'duration_minutes' => null,
                    'duration_formatted' => 'En cours...'
                ];
            }

            $dailyDetails[] = [
                'date' => $date,
                'work_pairs' => $workPairs,
                'total_minutes' => $totalDayMinutes,
                'total_hours' => round($totalDayMinutes / 60, 2),
                'total_formatted' => $this->formatDuration($totalDayMinutes),
                'late_minutes' => $totalLateMinutes,
                'is_present' => count($workPairs) > 0,
                'movements_count' => $movements->count()
            ];
        }

        return $dailyDetails;
    }

    /**
     * Formater une durée en minutes vers un format lisible
     */
    private function formatDuration($minutes)
    {
        if (!$minutes || $minutes <= 0) return '0min';
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h{$remainingMinutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$remainingMinutes}min";
        }
    }

    /**
     * Ajuster la luminosité d'une couleur hexadécimale
     */
    private function adjustBrightness($hex, $percent)
    {
        // Supprimer le # si présent
        $hex = ltrim($hex, '#');
        
        // Convertir en RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajuster la luminosité
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        
        // Convertir de nouveau en hex
        return '#' . str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT) . 
                     str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT) . 
                     str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);
    }
}