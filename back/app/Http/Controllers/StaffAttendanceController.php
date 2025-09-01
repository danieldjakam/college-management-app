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
use App\Services\WhatsAppService;

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
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            $user = User::where('qr_code', $request->staff_qr_code)
                ->whereIn('role', $staffRoles)
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
                // LOG: QR code scanné non trouvé
                \Log::warning('QR code staff attendance - Code non trouvé', [
                    'scanned_qr_code' => $request->staff_qr_code,
                    'supervisor_id' => $request->supervisor_id,
                    'scanned_at' => now()
                ]);

                // Vérifier si le QR code existe mais avec un rôle différent
                $userWithDifferentRole = User::where('qr_code', $request->staff_qr_code)
                    ->where('is_active', true)
                    ->first();

                if ($userWithDifferentRole) {
                    \Log::warning('QR code staff attendance - Rôle non autorisé', [
                        'scanned_qr_code' => $request->staff_qr_code,
                        'user_name' => $userWithDifferentRole->name,
                        'user_role' => $userWithDifferentRole->role,
                        'supervisor_id' => $request->supervisor_id
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Code QR invalide - rôle non autorisé pour la présence personnel',
                        'debug_info' => [
                            'scanned_qr' => $request->staff_qr_code,
                            'found_user' => $userWithDifferentRole->name,
                            'user_role' => $userWithDifferentRole->role
                        ]
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif',
                    'debug_info' => [
                        'scanned_qr' => $request->staff_qr_code,
                        'searched_roles' => $staffRoles
                    ]
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

            // PROTECTION CONTRE LES SCANS MULTIPLES
            // Empêcher les scans dans un délai de 30 secondes
            if ($lastMovement && $lastMovement->scanned_at) {
                $timeDifference = Carbon::parse($lastMovement->scanned_at)->diffInSeconds($now);
                if ($timeDifference < 30) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scan trop récent. Veuillez attendre ' . (30 - $timeDifference) . ' secondes avant de rescanner.',
                        'time_remaining' => 30 - $timeDifference
                    ], 429); // 429 Too Many Requests
                }
            }

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
                'scanned_qr_code' => $request->staff_qr_code,  // Enregistrer le QR exact scanné
                'is_present' => $eventType === 'entry',
                'event_type' => $eventType,
                'staff_type' => $staffType,
                'late_minutes' => $lateMinutes
            ]);

            // Calculer le temps de travail total pour la journée
            $totalWorkTime = $this->calculateDailyWorkTime($user->id, $today, $currentSchoolYear->id);

            // Envoyer notification WhatsApp au personnel
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendStaffAttendanceNotification($attendance);
            } catch (\Exception $e) {
                \Log::warning('Erreur envoi notification WhatsApp personnel', [
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // LOG: Scan réussi avec détails complets
            \Log::info('QR code staff attendance - Scan réussi', [
                'scanned_qr_code' => $request->staff_qr_code,
                'user_qr_code' => $user->qr_code,
                'user_name' => $user->name,
                'user_id' => $user->id,
                'event_type' => $eventType,
                'staff_type' => $staffType,
                'supervisor_id' => $request->supervisor_id,
                'qr_match' => $request->staff_qr_code === $user->qr_code ? 'EXACT' : 'DIFFERENT'
            ]);

            $message = $eventType === 'entry' ? 'Entrée enregistrée avec succès' : 'Sortie enregistrée avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'staff_member' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'staff_type' => $staffType,
                        'expected_qr' => $user->qr_code,
                        'scanned_qr' => $request->staff_qr_code
                    ],
                    'attendance' => $attendance,
                    'event_type' => $eventType,
                    'late_minutes' => $lateMinutes,
                    'scan_time' => $now->format('H:i:s'),
                    'daily_work_time' => $totalWorkTime,
                    'validation' => [
                        'qr_match' => $request->staff_qr_code === $user->qr_code,
                        'found_via' => $user->qr_code ? 'users_table' : 'teachers_table'
                    ]
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
    public function getDailyStaffAttendance(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'role' => 'sometimes|string',
        ]);

        $date = $request->input('date');
        $role = $request->input('role');

        try {
            // Récupérer tous les membres du personnel actifs
            $staffQuery = User::select([
                'users.id',
                'users.name',
                'users.username',
                'users.role',
                'teachers.first_name',
                'teachers.last_name',
                'teachers.type_personnel'
            ])
                ->leftJoin('teachers', 'users.id', '=', 'teachers.user_id')
                ->whereIn('users.role', ['teacher', 'accountant', 'admin', 'secretaire', 'surveillant_general', 'comptable_superieur'])
                ->where('users.is_active', true);

            // Appliquer les filtres
            if ($role) {
                $staffQuery->where('users.role', $role);
            }

            $staff = $staffQuery->get();

            // Récupérer toutes les présences pour la date donnée
            $attendances = StaffAttendance::select([
                'user_id',
                'scanned_at',
                'event_type'
            ])
                ->whereDate('attendance_date', $date)
                ->orderBy('scanned_at', 'asc')
                ->get()
                ->groupBy('user_id');

            // Combiner les données
            $result = $staff->map(function ($member) use ($attendances) {
                $memberAttendances = $attendances->get($member->id, collect());

                // Organiser les entrées et sorties par paires
                $entryExitPairs = [];
                $totalWorkingMinutes = 0;
                $isPresent = false;

                if ($memberAttendances->count() > 0) {
                    $events = $memberAttendances->toArray();
                    $currentEntry = null;

                    foreach ($events as $event) {
                        if ($event['event_type'] === 'entry') {
                            $currentEntry = $event;
                            $isPresent = true;
                        } elseif ($event['event_type'] === 'exit' && $currentEntry) {
                            $entryTime = Carbon::parse($currentEntry['scanned_at']);
                            $exitTime = Carbon::parse($event['scanned_at']);
                            $workingMinutes = $entryTime->diffInMinutes($exitTime);
                            $totalWorkingMinutes += $workingMinutes;

                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => $event['scanned_at'],
                                'working_minutes' => $workingMinutes,
                                'working_hours' => $this->formatWorkingTime($workingMinutes)
                            ];
                            $currentEntry = null;
                        }
                    }

                    // Si il y a une entrée sans sortie (encore présent)
                    if ($currentEntry) {
                        $entryExitPairs[] = [
                            'entry_time' => $currentEntry['scanned_at'],
                            'exit_time' => null,
                            'working_minutes' => null,
                            'working_hours' => 'En cours'
                        ];
                    }
                }

                // Première entrée et dernière sortie
                $firstEntry = $memberAttendances->where('event_type', 'entry')->first();
                $lastExit = $memberAttendances->where('event_type', 'exit')->last();

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->username,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'role' => $member->role,
                    'employment_type' => $member->role === 'teacher' ? ($member->type_personnel ?: 'P') : 'P',
                    'is_present' => $isPresent,
                    'first_arrival' => $firstEntry ? $firstEntry->scanned_at : null,
                    'last_exit' => $lastExit ? $lastExit->scanned_at : null,
                    'entry_exit_pairs' => $entryExitPairs,
                    'total_working_minutes' => $totalWorkingMinutes,
                    'total_working_hours' => $this->formatWorkingTime($totalWorkingMinutes),
                    'attendance_count' => $memberAttendances->count()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Données de présence récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des données de présence',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater le temps de travail en heures et minutes
     */
    private function formatWorkingTime($minutes)
    {
        if ($minutes === null || $minutes === 0) {
            return '0h 0min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . $remainingMinutes . 'min';
    }

    public function exportStaffAttendancePDF(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'role' => 'sometimes|string',
        ]);

        $date = $request->input('date');
        $role = $request->input('role');

        try {
            // Réutiliser la même logique que getDailyStaffAttendance
            $staffQuery = User::select([
                'users.id',
                'users.name',
                'users.username',
                'users.role',
                'teachers.first_name',
                'teachers.last_name',
                'teachers.type_personnel'
            ])
                ->leftJoin('teachers', 'users.id', '=', 'teachers.user_id')
                ->whereIn('users.role', ['teacher', 'accountant', 'admin', 'secretaire', 'surveillant_general', 'comptable_superieur'])
                ->where('users.is_active', true);

            // Appliquer les filtres
            if ($role) {
                $staffQuery->where('users.role', $role);
            }

            $staffQuery->orderBy('users.role')
                ->orderBy('teachers.last_name')
                ->orderBy('teachers.first_name');

            $staff = $staffQuery->get();

            // Récupérer toutes les présences pour la date donnée
            $attendances = StaffAttendance::select([
                'user_id',
                'scanned_at',
                'event_type'
            ])
                ->whereDate('attendance_date', $date)
                ->orderBy('scanned_at', 'asc')
                ->get()
                ->groupBy('user_id');

            // Combiner les données avec la même logique que getDailyStaffAttendance
            $attendanceData = $staff->map(function ($member) use ($attendances) {
                $memberAttendances = $attendances->get($member->id, collect());

                // Organiser les entrées et sorties par paires
                $entryExitPairs = [];
                $totalWorkingMinutes = 0;
                $isPresent = false;

                if ($memberAttendances->count() > 0) {
                    $events = $memberAttendances->toArray();
                    $currentEntry = null;

                    foreach ($events as $event) {
                        if ($event['event_type'] === 'entry') {
                            $currentEntry = $event;
                            $isPresent = true;
                        } elseif ($event['event_type'] === 'exit' && $currentEntry) {
                            $entryTime = Carbon::parse($currentEntry['scanned_at']);
                            $exitTime = Carbon::parse($event['scanned_at']);
                            $workingMinutes = $entryTime->diffInMinutes($exitTime);
                            $totalWorkingMinutes += $workingMinutes;

                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => $event['scanned_at'],
                                'working_minutes' => $workingMinutes,
                                'working_hours' => $this->formatWorkingTime($workingMinutes)
                            ];
                            $currentEntry = null;
                        }
                    }

                    // Si il y a une entrée sans sortie (encore présent)
                    if ($currentEntry) {
                        $entryExitPairs[] = [
                            'entry_time' => $currentEntry['scanned_at'],
                            'exit_time' => null,
                            'working_minutes' => null,
                            'working_hours' => 'En cours'
                        ];
                    }
                }

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->username,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'role' => $member->role,
                    'employment_type' => $member->role === 'teacher' ? ($member->type_personnel ?: 'P') : 'P',
                    'is_present' => $isPresent,
                    'entry_exit_pairs' => $entryExitPairs,
                    'total_working_minutes' => $totalWorkingMinutes,
                    'total_working_hours' => $this->formatWorkingTime($totalWorkingMinutes),
                    'attendance_count' => $memberAttendances->count()
                ];
            });

            // Calculer les statistiques
            $total = $attendanceData->count();
            $present = $attendanceData->where('is_present', true)->count();
            $absent = $total - $present;
            $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            // Déterminer le titre du filtre
            $filterTitle = '';
            if ($role) {
                $roleLabels = [
                    'teacher' => 'Enseignants',
                    'accountant' => 'Comptables',
                    'admin' => 'Administrateurs',
                    'secretaire' => 'Secrétaires',
                    'surveillant_general' => 'Surveillants Généraux',
                    'comptable_superieur' => 'Comptables Supérieurs'
                ];
                $filterTitle = $roleLabels[$role] ?? $role;
            }
            if (!$filterTitle) {
                $filterTitle = 'Tout le personnel';
            }

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            $schoolYear = $currentSchoolYear ? $currentSchoolYear->name : date('Y') . '-' . (date('Y') + 1);

            // Préparer les données pour la vue PDF
            $data = [
                'attendanceData' => $attendanceData,
                'date' => Carbon::parse($date)->locale('fr')->isoFormat('dddd, D MMMM YYYY'),
                'filterTitle' => $filterTitle,
                'schoolYear' => $schoolYear,
                'stats' => [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'attendance_rate' => $attendanceRate
                ],
                'generatedAt' => Carbon::now()->locale('fr')->isoFormat('dddd, D MMMM YYYY [à] HH:mm')
            ];

            // Générer le HTML puis le PDF
            $html = view('reports.staff-attendance', $data)->render();
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            $filename = 'presences_personnel_' . $date . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateQRCode(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $user = User::find($request->user_id);

            // Vérifier que c'est un membre du personnel
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            if (!in_array($user->role, $staffRoles)) {
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
     * Formater le numéro de téléphone avec des espaces tous les 3 chiffres
     */
    private function formatPhoneNumber($phone)
    {
        // Nettoyer le numéro (garder seulement les chiffres)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Si le numéro commence par 237, on le garde tel quel
        if (str_starts_with($cleanPhone, '237')) {
            $cleanPhone = substr($cleanPhone, 3); // Enlever le 237
        }

        // Ajouter des espaces tous les 3 caractères
        $formattedPhone = '';
        for ($i = 0; $i < strlen($cleanPhone); $i++) {
            if ($i > 0 && $i % 3 === 0) {
                $formattedPhone .= ' ';
            }
            $formattedPhone .= $cleanPhone[$i];
        }

        return '+237 ' . $formattedPhone;
    }

    /**
     * Raccourcir le nom si trop long
     */
    private function truncateName($name, $maxLength = 23)
    {
        if (strlen($name) <= $maxLength) {
            return $name;
        }

        // Essayer de couper au dernier espace avant la limite
        $truncated = substr($name, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > 15) { // Au moins 15 caractères
            return substr($name, 0, $lastSpace) . '...';
        }

        // Sinon couper brutalement
        return substr($name, 0, $maxLength - 3) . '...';
    }

    /**
     * Générer le HTML du badge personnel pour PDF
     */
    private function generateBadgeHtmlForPDF($user, $qrCode)
    {
        // Récupérer les paramètres de l'école
        $schoolSettings = SchoolSetting::first();

        // Log de début pour debug
        \Log::info("Starting badge generation for user: " . $user->id . " (" . $user->name . ")", ['photo_url' => $user->photo]);

        // Convertir l'image de background CPB en base64
        $backgroundBase64 = '';
        $backgroundPath = public_path('assets/images/card-background-cpb.png');
        if (file_exists($backgroundPath)) {
            $backgroundContent = file_get_contents($backgroundPath);
            $backgroundBase64 = 'data:image/png;base64,' . base64_encode($backgroundContent);
            \Log::info("Background image loaded from: " . $backgroundPath);
        } else {
            \Log::warning("Background image not found at: " . $backgroundPath);
        }

        // Convertir la photo du personnel en base64 (optimisée)
        $photoBase64 = '';
        if ($user->photo) {
            try {
                $photoContent = null;
                if (str_starts_with($user->photo, 'http')) {
                    // Corriger l'URL pour pointer vers localhost/serveur local
                    $correctedUrl = str_replace(['127.0.0.1:8000', 'localhost:8000', '192.168.1.229:8000'], $_ENV['APP_URL'], $user->photo);

                    // Pour les URLs, essayer d'abord l'accès direct au fichier
                    $relativePath = str_replace(['http://127.0.0.1:8000/', 'http://localhost:8000/', 'http://192.168.1.229:8000/', $_ENV['APP_URL']], '', $user->photo);
                    $relativePath = ltrim($relativePath, '/');
                    if (str_starts_with($relativePath, 'storage/')) {
                        $relativePath = substr($relativePath, 8);
                    }
                    $localPath = storage_path('app/public/' . $relativePath);

                    if (file_exists($localPath)) {
                        $photoContent = file_get_contents($localPath);
                        \Log::info("Photo loaded from local path: " . $localPath);
                    } else {
                        $context = stream_context_create([
                            'http' => [
                                'timeout' => 5,
                                'user_agent' => 'Mozilla/5.0'
                            ]
                        ]);
                        $photoContent = file_get_contents($correctedUrl, false, $context);
                        \Log::info("Photo loaded from URL: " . $correctedUrl);
                    }
                } else {
                    $photoPath = storage_path('app/public/' . $user->photo);
                    if (file_exists($photoPath)) {
                        $photoContent = file_get_contents($photoPath);
                        \Log::info("Photo loaded from relative path: " . $photoPath);
                    }
                }

                if ($photoContent) {
                    // Optimiser l'image si elle est trop grosse (> 50KB)
                    if (strlen($photoContent) > 50000) {
                        $tempImage = imagecreatefromstring($photoContent);
                        if ($tempImage) {
                            // Redimensionner à 120x120 pour s'adapter au cercle
                            $newImage = imagecreatetruecolor(120, 120);
                            $width = imagesx($tempImage);
                            $height = imagesy($tempImage);

                            imagecopyresampled($newImage, $tempImage, 0, 0, 0, 0, 120, 120, $width, $height);

                            ob_start();
                            imagepng($newImage, null, 6);
                            $photoContent = ob_get_clean();

                            imagedestroy($tempImage);
                            imagedestroy($newImage);
                        }
                    }

                    $photoBase64 = 'data:image/png;base64,' . base64_encode($photoContent);
                    \Log::info("Photo successfully converted to base64 for user: " . $user->id);
                }
            } catch (\Exception $e) {
                \Log::warning('Erreur chargement photo utilisateur: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'user_photo' => $user->photo,
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        // Si pas de photo, utiliser une image par défaut circulaire
        if (!$photoBase64) {
            $photoBase64 = 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="60" fill="#e2e8f0"/>
                <circle cx="60" cy="45" r="20" fill="#9ca3af"/>
                <path d="M30 100 C30 80, 45 65, 60 65 C75 65, 90 80, 90 100 L90 120 L30 120 Z" fill="#9ca3af"/>
            </svg>
        ');
        }

        // Déterminer le libellé du poste
        $staffTypes = [
            'teacher' => 'Enseignant',
            'accountant' => 'Comptable',
            'comptable_superieur' => 'Comptable Supérieur',
            'surveillant_general' => 'Surveillant Général',
            'admin' => 'Administrateur',
            'general_accountant' => 'Comptable Général',
            'secretaire' => 'Secrétaire',
            'responsable_pedagogique' => 'Responsable Pédagogique',
            'dean_of_studies' => 'Dean of Studies',
            'censeur_esg' => 'Censeur ESG',
            'censeur' => 'Censeur',
            'surveillant_secteur' => 'Surveillant de Secteur',
            'caissiere' => 'Caissière',
            'bibliothecaire' => 'Bibliothécaire',
            'chef_travaux' => 'Chef des Travaux',
            'chef_securite' => 'Chef de Sécurité',
            'reprographe' => 'Reprographe'
        ];

        $staffLabel = $staffTypes[$user->role] ?? 'Personnel';

        // MODIFICATIONS ICI : Formater le téléphone et raccourcir le nom
        $userPhone = $user->contact ?? $user->telephone ?? '000000000';
        $formattedPhone = $this->formatPhoneNumber($userPhone);
        $truncatedName = $this->truncateName($user->name);

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
                padding: 20mm;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: #f5f5f5;
            }
            
            .badge-container {
                width: 95.6mm;
                height: 54mm;
                position: relative;
                background-image: url('{$backgroundBase64}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            /* Nom de l'utilisateur - à côté de l'icône personne */
            .staff-name {
                position: absolute;
                left: 58px;
                top: 44px;
                color: black;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                max-width: 120px;
                line-height: 1.1;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Poste - juste en dessous du nom */
            .staff-role {
                position: absolute;
                left: 58px;
                top: 56px;
                color: black;
                font-size: 7px;
                font-weight: normal;
                max-width: 120px;
                line-height: 1.1;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Téléphone personnel - au-dessus du téléphone école */
            .staff-phone {
                position: absolute;
                left: 58px;
                top: 80px;
                color: black;
                font-size: 7px;
                font-weight: bold;
                font-family: 'Open Sans', 'Arial', sans-serif;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Photo dans la zone circulaire */
            .staff-photo {
                position: absolute;
                right: 47px;
                top: 55px;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid white;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            
            /* QR Code dans la zone en pointillés */
            .qr-code {
                position: absolute;
                right: 135px;
                bottom: -1px;
                width: 45px;
                height: 45px;
                object-fit: contain;
                background: white;
                border-radius: 4px;
                padding: 2px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            /* ID Badge - petit numéro en haut */
            .staff-id {
                position: absolute;
                left: 26px;
                top: 36px;
                color: black;
                font-size: 6px;
                font-weight: bold;
                background: rgba(255,255,255,0.2);
                padding: 2px 6px;
                border-radius: 10px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            @page {
                size: A4;
                margin: 10mm;
            }
        </style>
    </head>
    <body>
        <div class='badge-container'>
            <!-- ID Badge -->
            <!-- <div class='staff-id'>ID: {$user->id}</div> -->
            
            <!-- Nom de l'utilisateur -->
            <div class='staff-name'>{$truncatedName}</div>
            
            <!-- Poste -->
            <div class='staff-role'>{$staffLabel}</div>
            
            <!-- Téléphone personnel -->
            <div class='staff-phone'>{$formattedPhone}</div>
            
            <!-- Photo -->
            <img src='{$photoBase64}' alt='Photo' class='staff-photo'>
            
            <!-- QR Code -->
            <img src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrCode) . "&margin=1' alt='QR Code' class='qr-code'>
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
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            $users = User::whereIn('id', $userIds)
                ->whereIn('role', $staffRoles)
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

        // Charger l'image de background CPB
        $backgroundBase64 = '';
        $backgroundPath = public_path('assets/images/card-background-cpb.png');
        if (file_exists($backgroundPath)) {
            $backgroundContent = file_get_contents($backgroundPath);
            $backgroundBase64 = 'data:image/png;base64,' . base64_encode($backgroundContent);
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
            $badgeHtml = $this->generateSingleBadgeHtml($user, $qrCode, $photoBase64, '', $schoolSettings);

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
        <title>Badges Personnel CPB - " . count($users) . " badges</title>
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
                width: 95.6mm;
                height: 54mm;
                position: relative;
                background-image: url('{$backgroundBase64}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            /* Nom de l'utilisateur - à côté de l'icône personne */
            .staff-name {
                position: absolute;
                left: 58px;
                top: 44px;
                color: black;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                max-width: 150px;
                line-height: 1.1;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Poste - juste en dessous du nom */
            .staff-role {
                position: absolute;
                left: 58px;
                top: 56px;
                color: black;
                font-size: 7px;
                font-weight: normal;
                max-width: 120px;
                line-height: 1.1;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Téléphone personnel - au-dessus du téléphone école */
            .staff-phone {
                position: absolute;
                left: 58px;
                top: 80px;
                color: black;
                font-size: 7px;
                font-weight: bold;
                font-family: 'Open Sans', 'Arial', sans-serif;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            
            /* Photo dans la zone circulaire */
            .staff-photo {
                position: absolute;
                right: 47px;
                top: 55px;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid white;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            
            /* QR Code dans la zone en pointillés */
            .qr-code {
                position: absolute;
                right: 135px;
                bottom: -1px;
                width: 45px;
                height: 45px;
                object-fit: contain;
                background: white;
                border-radius: 4px;
                padding: 2px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            /* ID Badge - petit numéro en haut */
            .staff-id {
                position: absolute;
                left: 26px;
                top: 36px;
                color: black;
                font-size: 6px;
                font-weight: bold;
                background: rgba(255,255,255,0.2);
                padding: 2px 6px;
                border-radius: 10px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
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
        // Charger l'image de background CPB
        $backgroundBase64 = '';
        $backgroundPath = public_path('assets/images/card-background-cpb.png');
        if (file_exists($backgroundPath)) {
            $backgroundContent = file_get_contents($backgroundPath);
            $backgroundBase64 = 'data:image/png;base64,' . base64_encode($backgroundContent);
        }

        $staffTypes = [
            'teacher' => 'Enseignant',
            'accountant' => 'Comptable',
            'comptable_superieur' => 'Comptable Supérieur',
            'surveillant_general' => 'Surveillant Général',
            'admin' => 'Administrateur',
            'general_accountant' => 'Comptable Général',
            'secretaire' => 'Secrétaire',
            'responsable_pedagogique' => 'Responsable Pédagogique',
            'dean_of_studies' => 'Dean of Studies',
            'censeur_esg' => 'Censeur ESG',
            'censeur' => 'Censeur',
            'surveillant_secteur' => 'Surveillant de Secteur',
            'caissiere' => 'Caissière',
            'bibliothecaire' => 'Bibliothécaire',
            'chef_travaux' => 'Chef des Travaux',
            'chef_securite' => 'Chef de Sécurité',
            'reprographe' => 'Reprographe'
        ];

        $staffLabel = $staffTypes[$user->role] ?? 'Personnel';

        // APPLIQUER LES MÊMES MODIFICATIONS
        $userPhone = $user->contact ?? $user->telephone ?? '000000000';
        $formattedPhone = $this->formatPhoneNumber($userPhone);
        $truncatedName = $this->truncateName($user->name);

        return "
    <div class='badge-container' style='background-image: url(\"{$backgroundBase64}\");'>
        <!-- <div class='staff-id'>ID: {$user->id}</div> -->
        <div class='staff-name'>{$truncatedName}</div>
        <div class='staff-role'>{$staffLabel}</div>
        <div class='staff-phone'>{$formattedPhone}</div>
        <img src='{$photoBase64}' alt='Photo' class='staff-photo'>
        <img src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrCode) . "&margin=1' alt='QR Code' class='qr-code'>
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
                    $relativePath = str_replace(['http://127.0.0.1:8000/', 'http://localhost:8000/', 'http://192.168.1.229:8000/', $_ENV['APP_URL']], '', $user->photo);
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
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            $staff = User::whereIn('role', $staffRoles)
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
            case 'caissiere':
                return 'accountant';
            case 'surveillant_general':
            case 'surveillant_secteur':
                return 'supervisor';
            case 'admin':
            case 'chef_securite':
                return 'admin';
            case 'secretaire':
            case 'reprographe':
                return 'secretaire';
            case 'bibliothecaire':
                return 'bibliothecaire';
            case 'responsable_pedagogique':
            case 'dean_of_studies':
            case 'censeur_esg':
            case 'censeur':
            case 'chef_travaux':
                return 'teacher'; // Rôles pédagogiques/académiques
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
