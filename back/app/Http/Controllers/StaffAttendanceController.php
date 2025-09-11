<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\StaffAttendance;
use App\Models\StaffAttendanceClass;
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
        // LOG: Début de la requête avec tous les détails
        \Log::info('=== STAFF ATTENDANCE SCAN QR - DÉBUT DE REQUÊTE ===', [
            'timestamp' => now()->toISOString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'Bearer ***' : 'MANQUANT',
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
                'x_requested_with' => $request->header('X-Requested-With'),
            ],
            'request_data' => $request->except(['password', 'token']),
            'authenticated_user' => auth('api')->check() ? auth('api')->user()->id : 'NON_AUTHENTIFIÉ',
        ]);
        
        try {
            $request->validate([
                'staff_qr_code' => 'required|string',
                'supervisor_id' => 'required|exists:users,id',
                'event_type' => 'sometimes|in:entry,exit,auto',
                'skip_class_selection' => 'sometimes|boolean'  // Nouveau paramètre pour forcer le scan sans classes
            ]);
            
            \Log::info('STAFF ATTENDANCE - Validation réussie', [
                'staff_qr_code' => $request->staff_qr_code,
                'supervisor_id' => $request->supervisor_id,
                'event_type' => $request->event_type ?? 'auto'
            ]);

            // Chercher d'abord dans les users qui ont un QR code
            // ACCEPTER TOUS LES UTILISATEURS ACTIFS - Pas de restriction de rôle
            $user = User::where('qr_code', $request->staff_qr_code)
                ->where('is_active', true)
                ->first();

            // Si pas trouvé dans users, chercher dans teachers
            $teacher = null;
            if (!$user) {
                $teacher = Teacher::where('qr_code', $request->staff_qr_code)
                    ->where('is_active', true)
                    ->first();

                if ($teacher) {
                    if ($teacher->user) {
                        $user = $teacher->user;
                    } else {
                        // Créer un VRAI compte utilisateur pour l'enseignant
                        $user = User::create([
                            'name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'username' => strtolower($teacher->first_name) . '_' . $teacher->id,
                            'email' => $teacher->email ?: strtolower($teacher->first_name) . $teacher->id . '@school.local',
                            'password' => \Hash::make('123456'), // Mot de passe temporaire
                            'role' => 'teacher',
                            'qr_code' => $teacher->qr_code,
                            'is_active' => $teacher->is_active,
                            'contact' => $teacher->phone_number,
                        ]);
                        
                        // Lier l'utilisateur à l'enseignant
                        $teacher->update(['user_id' => $user->id]);
                        
                        \Log::info('STAFF ATTENDANCE - Compte utilisateur créé automatiquement pour enseignant', [
                            'teacher_id' => $teacher->id,
                            'user_id' => $user->id,
                            'teacher_name' => $user->name,
                            'username' => $user->username,
                            'qr_code' => $request->staff_qr_code
                        ]);
                    }
                }
            }

            if (!$user) {
                // LOG: QR code scanné non trouvé
                \Log::warning('QR code staff attendance - Code non trouvé', [
                    'scanned_qr_code' => $request->staff_qr_code,
                    'supervisor_id' => $request->supervisor_id,
                    'scanned_at' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif',
                    'debug_info' => [
                        'scanned_qr' => $request->staff_qr_code
                    ]
                ], 404);
            }

            // Déterminer le type de personnel
            $staffType = $this->getStaffType($user, $teacher);

            // SI VACATAIRE OU SEMI-PERMANENT -> Demander sélection de classe (SAUF si skip_class_selection = true)
            if (in_array($staffType, ['vacataire', 'semi_permanent']) && !$request->get('skip_class_selection', false)) {
                \Log::info('STAFF ATTENDANCE - Enseignant Vacataire/Semi-Permanent détecté', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'staff_type' => $staffType,
                    'needs_class_selection' => true
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Sélectionnez la classe où vous allez enseigner',
                    'data' => [
                        'needs_class_selection' => true, // ← FLAG IMPORTANT dans data
                        'staff_member' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                            'staff_type' => $staffType,
                            'expected_qr' => $request->staff_qr_code,
                            'scanned_qr' => $request->staff_qr_code
                        ]
                    ]
                ]);
            }

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

            // NOUVELLE LOGIQUE : 1 ENTRÉE + 1 SORTIE MAXIMUM PAR JOUR
            $todaysMovements = StaffAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at', 'asc')
                ->get();

            // Compter les entrées et sorties du jour
            $entriesCount = $todaysMovements->where('event_type', 'entry')->count();
            $exitsCount = $todaysMovements->where('event_type', 'exit')->count();

            // PROTECTION CONTRE LES SCANS MULTIPLES RÉCENTS
            $lastMovement = $todaysMovements->last();
            if ($lastMovement && $lastMovement->scanned_at) {
                $timeDifference = Carbon::parse($lastMovement->scanned_at)->diffInSeconds($now);
                if ($timeDifference < 5) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scan trop récent. Veuillez attendre ' . (5 - $timeDifference) . ' secondes avant de rescanner.',
                        'time_remaining' => 5 - $timeDifference,
                        'last_scan_time' => $lastMovement->scanned_at,
                        'debug' => [
                            'current_time' => $now->format('Y-m-d H:i:s'),
                            'last_scan' => $lastMovement->scanned_at,
                            'diff_seconds' => $timeDifference
                        ]
                    ], 429); // 429 Too Many Requests
                }
            }

            // ÉTAPE 1 : Transformer le mode AUTO en entry/exit AVANT la validation
            if ($eventType === 'auto') {
                // MODE AUTO (fallback) : gardé pour compatibilité mais préférer les boutons
                if ($entriesCount === 0) {
                    $eventType = 'entry';
                } elseif ($entriesCount === 1 && $exitsCount === 0) {
                    $eventType = 'exit';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Limite de scans atteinte pour aujourd\'hui. Utilisez les boutons "Arrivée" et "Départ" pour un contrôle précis.',
                        'error_code' => 'DAILY_SCAN_LIMIT_EXCEEDED',
                        'data' => [
                            'entries_today' => $entriesCount,
                            'exits_today' => $exitsCount,
                            'suggestion' => 'Mode auto désactivé. Utilisez les boutons spécifiques.'
                        ]
                    ], 422);
                }
            }

            // ÉTAPE 2 : LOGIQUE DE VALIDATION DIFFÉRENTIELLE SELON LE TYPE DE PERSONNEL
            if ($staffType === 'permanent') {
                // 🔒 PERMANENTS : STRICT (1 entrée + 1 sortie max par jour)
                if ($eventType === 'entry' && $entriesCount >= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Entrée déjà effectuée aujourd\'hui. Les permanents ne peuvent faire qu\'une seule entrée par jour.',
                        'error_code' => 'ENTRY_ALREADY_RECORDED',
                        'data' => [
                            'entries_today' => $entriesCount,
                            'first_entry' => $todaysMovements->where('event_type', 'entry')->first()->scanned_at,
                            'suggestion' => 'Utilisez le bouton "Départ" pour scanner votre sortie'
                        ]
                    ], 422);
                }
                
                if ($eventType === 'exit') {
                    if ($entriesCount === 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Aucune entrée trouvée aujourd\'hui. Vous devez d\'abord scanner votre arrivée.',
                            'error_code' => 'NO_ENTRY_RECORDED',
                            'data' => [
                                'entries_today' => $entriesCount,
                                'suggestion' => 'Utilisez d\'abord le bouton "Arrivée" pour scanner votre entrée'
                            ]
                        ], 422);
                    }
                    
                    if ($exitsCount >= 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sortie déjà effectuée aujourd\'hui. Les permanents ne peuvent faire qu\'une seule sortie par jour.',
                            'error_code' => 'EXIT_ALREADY_RECORDED',
                            'data' => [
                                'exits_today' => $exitsCount,
                                'first_exit' => $todaysMovements->where('event_type', 'exit')->first()->scanned_at,
                                'work_completed' => true
                            ]
                        ], 422);
                    }
                }
                
            } else {
                // 🔄 VACATAIRES/SEMI-PERMANENTS : FLEXIBLE (plusieurs cycles possibles mais dans l'ordre)
                if ($eventType === 'entry') {
                    // Vérifier qu'il n'y a pas une entrée sans sortie correspondante
                    if ($entriesCount > $exitsCount) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Vous devez d\'abord scanner votre sortie avant de faire une nouvelle entrée.',
                            'error_code' => 'MUST_SCAN_EXIT_FIRST',
                            'data' => [
                                'entries_today' => $entriesCount,
                                'exits_today' => $exitsCount,
                                'last_entry' => $todaysMovements->where('event_type', 'entry')->last()->scanned_at,
                                'suggestion' => 'Utilisez le bouton "Départ" pour scanner votre sortie d\'abord'
                            ]
                        ], 422);
                    }
                    // ✅ OK pour l'entrée
                    
                } elseif ($eventType === 'exit') {
                    // Vérifier qu'il y a au moins une entrée
                    if ($entriesCount === 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Aucune entrée trouvée aujourd\'hui. Vous devez d\'abord scanner une arrivée.',
                            'error_code' => 'NO_ENTRY_RECORDED',
                            'data' => [
                                'entries_today' => $entriesCount,
                                'suggestion' => 'Utilisez d\'abord le bouton "Arrivée" pour scanner votre entrée'
                            ]
                        ], 422);
                    }
                    
                    // Vérifier qu'il n'y a pas déjà autant de sorties que d'entrées
                    if ($exitsCount >= $entriesCount) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Vous devez d\'abord faire une nouvelle entrée avant de scanner une sortie.',
                            'error_code' => 'MUST_SCAN_ENTRY_FIRST',
                            'data' => [
                                'entries_today' => $entriesCount,
                                'exits_today' => $exitsCount,
                                'last_exit' => $todaysMovements->where('event_type', 'exit')->last()->scanned_at,
                                'suggestion' => 'Utilisez le bouton "Arrivée" pour scanner une nouvelle entrée d\'abord'
                            ]
                        ], 422);
                    }
                    // ✅ OK pour la sortie
                }
            }

            // Calculer les minutes de retard (seulement pour les entrées)
            $lateMinutes = 0;
            if ($eventType === 'entry') {
                $lateMinutes = $this->calculateLateMinutes($now, $staffType);
            }

            // CAS SPÉCIAL : ADIBONE HUGUETTE - Forcer la sortie à 18h00
            $finalScanTime = $now;
            if ($eventType === 'exit' && stripos($user->name, 'ADIBONE HUGUETTE') !== false) {
                // Forcer l'heure de sortie à 18h00 (6 PM) pour ADIBONE HUGUETTE
                $finalScanTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 18:00:00');
                
                \Log::info('CAS SPÉCIAL - ADIBONE HUGUETTE : Sortie forcée à 18h00', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'real_scan_time' => $now->format('H:i:s'),
                    'forced_scan_time' => '18:00:00',
                    'date' => $today
                ]);
            }

            // Créer un nouvel enregistrement pour chaque mouvement
            $attendance = StaffAttendance::create([
                'user_id' => $user->id,
                'supervisor_id' => $request->supervisor_id,
                'school_year_id' => $currentSchoolYear->id,
                'attendance_date' => $today,
                'scanned_at' => $finalScanTime, // Utiliser l'heure finale (normale ou forcée)
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('STAFF ATTENDANCE - Erreur de validation', [
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('STAFF ATTENDANCE - Erreur système', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'authenticated_user' => auth('api')->check() ? auth('api')->user()->id : 'NON_AUTHENTIFIÉ',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la présence',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'authenticated' => auth('api')->check()
                ]
            ], 500);
        }
    }

    /**
     * Scan QR code avec sélection de classe (pour Vacataire/Semi-Permanent)
     */
    public function scanQRWithClass(Request $request): JsonResponse
    {
        \Log::info('=== STAFF ATTENDANCE SCAN QR WITH CLASS - DÉBUT DE REQUÊTE ===', [
            'timestamp' => now()->toISOString(),
            'request_data' => $request->except(['password', 'token']),
            'authenticated_user' => auth('api')->check() ? auth('api')->user()->id : 'NON_AUTHENTIFIÉ',
        ]);
        
        try {
            $request->validate([
                'staff_qr_code' => 'required|string',
                'supervisor_id' => 'required|exists:users,id',
                'class_id' => 'required|exists:school_classes,id',
                'event_type' => 'sometimes|in:entry,exit,auto'
            ]);
            
            // Chercher l'utilisateur (même logique que scanQR)
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            $user = User::where('qr_code', $request->staff_qr_code)
                ->whereIn('role', $staffRoles)
                ->where('is_active', true)
                ->first();

            // Si pas trouvé dans users, chercher dans teachers
            $teacher = null;
            if (!$user) {
                $teacher = Teacher::where('qr_code', $request->staff_qr_code)
                    ->where('is_active', true)
                    ->first();

                if ($teacher) {
                    if ($teacher->user) {
                        $user = $teacher->user;
                    } else {
                        // Créer un VRAI compte utilisateur pour l'enseignant
                        $user = User::create([
                            'name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'username' => strtolower($teacher->first_name) . '_' . $teacher->id,
                            'email' => $teacher->email ?: strtolower($teacher->first_name) . $teacher->id . '@school.local',
                            'password' => \Hash::make('123456'), // Mot de passe temporaire
                            'role' => 'teacher',
                            'qr_code' => $teacher->qr_code,
                            'is_active' => $teacher->is_active,
                            'contact' => $teacher->phone_number,
                        ]);
                        
                        // Lier l'utilisateur à l'enseignant
                        $teacher->update(['user_id' => $user->id]);
                        
                        \Log::info('STAFF ATTENDANCE WITH CLASS - Compte utilisateur créé automatiquement pour enseignant', [
                            'teacher_id' => $teacher->id,
                            'user_id' => $user->id,
                            'teacher_name' => $user->name,
                            'username' => $user->username,
                            'qr_code' => $request->staff_qr_code
                        ]);
                    }
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif'
                ], 404);
            }

            // Vérifier que c'est bien un Vacataire ou Semi-Permanent
            $staffType = $this->getStaffType($user, $teacher);
            if (!in_array($staffType, ['vacataire', 'semi_permanent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette API est uniquement pour les enseignants Vacataires et Semi-Permanents'
                ], 400);
            }

            // Obtenir la classe sélectionnée
            $schoolClass = \App\Models\SchoolClass::find($request->class_id);
            if (!$schoolClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Classe non trouvée'
                ], 404);
            }

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

            // POUR VACATAIRE/SEMI-PERMANENT: Vérifier les mouvements du jour
            $todaysMovements = StaffAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at', 'asc')
                ->get();

            // Compter les entrées et sorties du jour
            $entriesCount = $todaysMovements->where('event_type', 'entry')->count();
            $exitsCount = $todaysMovements->where('event_type', 'exit')->count();

            // Protection contre les scans multiples récents
            $lastMovement = $todaysMovements->last();
            if ($lastMovement && $lastMovement->scanned_at) {
                $timeDifference = Carbon::parse($lastMovement->scanned_at)->diffInSeconds($now);
                if ($timeDifference < 5) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scan trop récent. Veuillez attendre ' . (5 - $timeDifference) . ' secondes avant de rescanner.',
                        'time_remaining' => 5 - $timeDifference
                    ], 429);
                }
            }

            // VALIDATION STRICTE selon le bouton cliqué
            if ($eventType === 'entry') {
                // Bouton ARRIVÉE : Vérifier qu'aucune entrée n'existe
                if ($entriesCount >= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Entrée déjà effectuée aujourd\'hui. Vous ne pouvez faire qu\'une seule entrée par jour.',
                        'error_code' => 'ENTRY_ALREADY_RECORDED',
                        'data' => [
                            'error_code' => 'ENTRY_ALREADY_RECORDED',
                            'entries_today' => $entriesCount,
                            'first_entry' => $todaysMovements->where('event_type', 'entry')->first()->scanned_at,
                            'suggestion' => 'Utilisez le bouton "Départ" pour scanner votre sortie'
                        ]
                    ], 422);
                }
                
            } elseif ($eventType === 'exit') {
                // Bouton DÉPART : Vérifier qu'une entrée existe ET qu'aucune sortie n'existe
                if ($entriesCount === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune entrée trouvée aujourd\'hui. Vous devez d\'abord scanner votre arrivée.',
                        'error_code' => 'NO_ENTRY_RECORDED',
                        'data' => [
                            'error_code' => 'NO_ENTRY_RECORDED',
                            'entries_today' => $entriesCount,
                            'suggestion' => 'Utilisez d\'abord le bouton "Arrivée" pour scanner votre entrée'
                        ]
                    ], 422);
                }
                
                if ($exitsCount >= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sortie déjà effectuée aujourd\'hui. Vous ne pouvez faire qu\'une seule sortie par jour.',
                        'error_code' => 'EXIT_ALREADY_RECORDED',
                        'data' => [
                            'error_code' => 'EXIT_ALREADY_RECORDED',
                            'exits_today' => $exitsCount,
                            'first_exit' => $todaysMovements->where('event_type', 'exit')->first()->scanned_at,
                            'work_completed' => true
                        ]
                    ], 422);
                }
            }

            // Calculer le retard (seulement pour les entrées)
            $lateMinutes = 0;
            if ($eventType === 'entry') {
                $lateMinutes = $this->calculateLateMinutes($now, $staffType);
            }

            // Créer l'enregistrement de présence avec la classe
            $attendance = StaffAttendance::create([
                'user_id' => $user->id,
                'supervisor_id' => $request->supervisor_id,
                'school_year_id' => $currentSchoolYear->id,
                'attendance_date' => $today,
                'scanned_at' => $now,
                'scanned_qr_code' => $request->staff_qr_code,
                'is_present' => $eventType === 'entry',
                'event_type' => $eventType,
                'staff_type' => $staffType,
                'late_minutes' => $lateMinutes,
                'class_id' => $request->class_id, // Stocker la classe
                'notes' => "Classe: {$schoolClass->name}"
            ]);

            // Calculer le temps de travail total pour la journée
            $totalWorkTime = $this->calculateDailyWorkTime($user->id, $today, $currentSchoolYear->id);

            \Log::info('QR code staff attendance with class - Scan réussi', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'staff_type' => $staffType,
                'class_id' => $request->class_id,
                'class_name' => $schoolClass->name,
                'event_type' => $eventType,
                'attendance_id' => $attendance->id
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
                        'staff_type' => $staffType
                    ],
                    'attendance' => [
                        'id' => $attendance->id,
                        'user_id' => $attendance->user_id,
                        'scanned_at' => $attendance->scanned_at,
                        'event_type' => $attendance->event_type,
                        'late_minutes' => $attendance->late_minutes,
                        'class_id' => $attendance->class_id
                    ],
                    'class' => [
                        'id' => $schoolClass->id,
                        'name' => $schoolClass->name
                    ],
                    'event_type' => $eventType,
                    'late_minutes' => $lateMinutes,
                    'scan_time' => $now->format('H:i:s'),
                    'daily_work_time' => $totalWorkTime
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('STAFF ATTENDANCE WITH CLASS - Erreur de validation', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('STAFF ATTENDANCE WITH CLASS - Erreur système', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur système lors du scan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer les scans d'aujourd'hui pour un utilisateur (pour les tests)
     */
    public function clearTodayScans(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'date' => 'sometimes|date',
                'clear_all' => 'sometimes|boolean'
            ]);

            $date = $request->date ?? Carbon::now()->toDateString();
            
            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $query = StaffAttendance::where('attendance_date', $date)
                ->where('school_year_id', $currentSchoolYear->id);

            // Si clear_all est true, supprimer tous les scans du jour
            if ($request->clear_all === true) {
                $deletedCount = $query->delete();
                $message = "Suppression réussie de tous les {$deletedCount} scan(s) pour la date {$date}";
                
                \Log::info('STAFF ATTENDANCE - Suppression TOUS les scans du jour', [
                    'date' => $date,
                    'deleted_count' => $deletedCount,
                    'school_year_id' => $currentSchoolYear->id
                ]);
            } else {
                // Supprimer pour un utilisateur spécifique
                $userId = $request->user_id;
                if (!$userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'user_id requis si clear_all n\'est pas spécifié'
                    ], 400);
                }

                $deletedCount = $query->where('user_id', $userId)->delete();
                $message = "Suppression réussie de {$deletedCount} scan(s) pour l'utilisateur {$userId} le {$date}";
                
                \Log::info('STAFF ATTENDANCE - Suppression scans utilisateur', [
                    'user_id' => $userId,
                    'date' => $date,
                    'deleted_count' => $deletedCount,
                    'school_year_id' => $currentSchoolYear->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'date' => $date,
                    'deleted_count' => $deletedCount,
                    'clear_all' => $request->clear_all ?? false
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('STAFF ATTENDANCE - Erreur suppression scans', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des scans',
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

            $query = StaffAttendance::with(['user', 'supervisor', 'schoolClasses'])
                ->whereDate('created_at', $date);

            if ($staffType) {
                $query->forStaffType($staffType);
            }

            $attendances = $query->orderBy('scanned_at', 'desc')->get();

            // Grouper par type de personnel
            $groupedAttendances = $attendances->groupBy('staff_type');

            // Statistiques du jour - calculer en fonction des personnes uniques
            // Une personne est présente si elle a au moins une entrée, absente sinon
            $uniquePersons = $attendances->groupBy('user_id');
            
            $totalPresent = 0;
            $totalLate = 0;
            $presentPersons = [];
            
            foreach ($uniquePersons as $userId => $userAttendances) {
                $hasEntry = $userAttendances->where('event_type', 'entry')->count() > 0;
                if ($hasEntry) {
                    $totalPresent++;
                    $presentPersons[] = $userId;
                    
                    // Vérifier si cette personne est en retard (au moins une entrée en retard)
                    if ($userAttendances->where('event_type', 'entry')->where('late_minutes', '>', 0)->count() > 0) {
                        $totalLate++;
                    }
                }
            }

            $stats = [
                'total_present' => $totalPresent,
                'total_absent' => 0, // On ne peut pas calculer les absents sans connaître le personnel total
                'total_late' => $totalLate,
                'total_entries' => $attendances->where('event_type', 'entry')->count(),
                'total_exits' => $attendances->where('event_type', 'exit')->count(),
                'by_staff_type' => []
            ];

            foreach ($groupedAttendances as $type => $typeAttendances) {
                // Calculer les statistiques par type en comptant les personnes uniques
                $uniquePersonsOfType = $typeAttendances->groupBy('user_id');
                $presentOfType = 0;
                $lateOfType = 0;
                
                foreach ($uniquePersonsOfType as $userId => $userAttendances) {
                    $hasEntry = $userAttendances->where('event_type', 'entry')->count() > 0;
                    if ($hasEntry) {
                        $presentOfType++;
                        
                        // Vérifier si cette personne est en retard
                        if ($userAttendances->where('event_type', 'entry')->where('late_minutes', '>', 0)->count() > 0) {
                            $lateOfType++;
                        }
                    }
                }
                
                $stats['by_staff_type'][$type] = [
                    'total_movements' => $typeAttendances->count(),
                    'unique_persons' => $uniquePersonsOfType->count(),
                    'present' => $presentOfType,
                    'late' => $lateOfType,
                    'entries' => $typeAttendances->where('event_type', 'entry')->count(),
                    'exits' => $typeAttendances->where('event_type', 'exit')->count()
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
        \Log::info('=== getDailyStaffAttendance APPELÉ ===', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'params' => $request->all(),
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'Bearer ***' : 'MANQUANT'
            ]
        ]);
        
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
                        // Déterminer si c'est du personnel permanent
                        $staffType = $this->getStaffType($member);
                        $isPermanentStaff = in_array($staffType, ['accountant', 'admin', 'secretaire', 'supervisor']);
                        
                        if ($isPermanentStaff) {
                            // Personnel permanent: demi-journée automatique
                            $halfDayMinutes = 4 * 60; // 4 heures
                            $totalWorkingMinutes += $halfDayMinutes;
                            
                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => 'Auto (17:30)',
                                'working_minutes' => $halfDayMinutes,
                                'working_hours' => $this->formatWorkingTime($halfDayMinutes) . ' (demi-journée)',
                                'is_half_day' => true
                            ];
                        } else {
                            // Enseignants: en cours
                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => null,
                                'working_minutes' => null,
                                'working_hours' => 'En cours',
                                'is_half_day' => false
                            ];
                        }
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
                        // Déterminer si c'est du personnel permanent
                        $staffType = $this->getStaffType($member);
                        $isPermanentStaff = in_array($staffType, ['accountant', 'admin', 'secretaire', 'supervisor']);
                        
                        if ($isPermanentStaff) {
                            // Personnel permanent: demi-journée automatique
                            $halfDayMinutes = 4 * 60; // 4 heures
                            $totalWorkingMinutes += $halfDayMinutes;
                            
                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => 'Auto (17:30)',
                                'working_minutes' => $halfDayMinutes,
                                'working_hours' => $this->formatWorkingTime($halfDayMinutes) . ' (demi-journée)',
                                'is_half_day' => true
                            ];
                        } else {
                            // Enseignants: en cours
                            $entryExitPairs[] = [
                                'entry_time' => $currentEntry['scanned_at'],
                                'exit_time' => null,
                                'working_minutes' => null,
                                'working_hours' => 'En cours',
                                'is_half_day' => false
                            ];
                        }
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

            // Ajouter le badge avec gestion des sauts de page (2 badges par page)
            if ($badgeCount > 0 && $badgeCount % 2 === 0) {
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
                padding: 15mm 10mm;
                text-align: center;
            }

            .badge-wrapper {
                display: inline-block;
                margin: 8mm auto;
                page-break-inside: avoid;
                width: 100%;
                text-align: center;
                margin-bottom: 15mm;
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
            $dailyDetails = $this->calculateDailyWorkPairs($attendances, $user);

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
    private function getStaffType(User $user, ?Teacher $teacherObj = null): string
    {
        switch ($user->role) {
            case 'teacher':
                // Utiliser l'objet Teacher fourni ou chercher via la relation
                $teacher = $teacherObj ?: $user->teacher;
                if ($teacher && $teacher->type_personnel) {
                    switch ($teacher->type_personnel) {
                        case 'P': return 'permanent';
                        case 'V': return 'vacataire'; 
                        case 'SP': return 'semi_permanent';
                    }
                }
                return 'teacher'; // fallback si pas de relation ou type_personnel null
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
     * Calculer les minutes de retard selon les horaires du collège
     * Horaires: 7h-8h30 (tolérance), après 8h30 = retard
     */
    private function calculateLateMinutes(Carbon $scanTime, string $staffType): int
    {
        // Horaires du collège: 7h00-8h30 = à l'heure, après 8h30 = retard
        $lateThreshold = Carbon::createFromTimeString('08:30:00');
        $scanDateTime = Carbon::createFromTimeString($scanTime->format('H:i:s'));

        // Si scan après 8h30, calculer les minutes de retard
        if ($scanDateTime->greaterThan($lateThreshold)) {
            return $lateThreshold->diffInMinutes($scanDateTime);
        }

        return 0;
    }

    /**
     * Calculer le temps de travail total pour une journée
     * Règle: 
     * - Fermeture à 17h30, pas d'heures supplémentaires comptées après
     * - Si entrée sans sortie (personnel administratif/permanent) = demi-journée (4h)
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
        
        // Heure limite de fermeture: 17h30
        $closingTime = Carbon::createFromTimeString('17:30:00');
        
        // Obtenir le type de personnel pour déterminer si c'est du personnel permanent
        $user = User::find($userId);
        $staffType = $this->getStaffType($user);
        $isPermanentStaff = in_array($staffType, ['accountant', 'admin', 'secretaire', 'supervisor']);

        foreach ($movements as $movement) {
            if ($movement->event_type === 'entry') {
                $entryTime = Carbon::parse($movement->scanned_at);
            } elseif ($movement->event_type === 'exit' && $entryTime) {
                $exitTime = Carbon::parse($movement->scanned_at);
                
                // Si sortie après 17h30, limiter le calcul à 17h30
                $effectiveExitTime = $exitTime;
                if ($exitTime->format('H:i:s') > '17:30:00') {
                    $effectiveExitTime = Carbon::createFromFormat('Y-m-d H:i:s', 
                        $exitTime->format('Y-m-d') . ' 17:30:00');
                }
                
                $totalMinutes += $entryTime->diffInMinutes($effectiveExitTime);
                $entryTime = null; // Reset pour la prochaine paire entrée/sortie
            }
        }

        // NOUVELLE RÈGLE: Si il y a une entrée sans sortie (personnel permanent)
        // Considérer comme demi-journée = 4 heures (240 minutes)
        if ($entryTime && $isPermanentStaff) {
            $halfDayMinutes = 4 * 60; // 4 heures = 240 minutes
            $totalMinutes += $halfDayMinutes;
            
            \Log::info('Personnel permanent - Demi-journée appliquée', [
                'user_id' => $userId,
                'user_name' => $user->name,
                'staff_type' => $staffType,
                'entry_time' => $entryTime->format('H:i:s'),
                'date' => $date,
                'half_day_minutes' => $halfDayMinutes
            ]);
        }

        // Convertir en heures avec 2 décimales
        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculer les paires entrée-sortie pour chaque jour
     */
    private function calculateDailyWorkPairs($attendances, $user = null)
    {
        $groupedByDate = $attendances->groupBy('attendance_date');
        $dailyDetails = [];
        
        // Déterminer si c'est du personnel permanent
        $staffType = $user ? $this->getStaffType($user) : null;
        $isPermanentStaff = $staffType && in_array($staffType, ['accountant', 'admin', 'secretaire', 'supervisor']);

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

            // Si il y a une entrée sans sortie
            if ($entryTime) {
                $halfDayMinutes = 4 * 60; // 4 heures = 240 minutes
                
                // NOUVELLE RÈGLE: Personnel permanent sans sortie = demi-journée
                if ($isPermanentStaff) {
                    $totalDayMinutes += $halfDayMinutes;
                    $workPairs[] = [
                        'entry_time' => $entryTime->format('H:i'),
                        'exit_time' => 'Auto (17:30)',
                        'duration_minutes' => $halfDayMinutes,
                        'duration_formatted' => $this->formatDuration($halfDayMinutes) . ' (demi-journée)',
                        'is_half_day' => true
                    ];
                } else {
                    // Pour les autres (enseignants), afficher "En cours"
                    $workPairs[] = [
                        'entry_time' => $entryTime->format('H:i'),
                        'exit_time' => null,
                        'duration_minutes' => null,
                        'duration_formatted' => 'En cours...',
                        'is_half_day' => false
                    ];
                }
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

    /**
     * Scan QR code avec sélection de classes multiples (pour Vacataire/Semi-Permanent)
     */
    public function scanQRWithClasses(Request $request): JsonResponse
    {
        \Log::info('=== STAFF ATTENDANCE SCAN QR WITH CLASSES - DÉBUT DE REQUÊTE ===', [
            'timestamp' => now()->toISOString(),
            'request_data' => $request->except(['password', 'token']),
            'authenticated_user' => auth('api')->check() ? auth('api')->user()->id : 'NON_AUTHENTIFIÉ',
        ]);
        
        try {
            $request->validate([
                'staff_qr_code' => 'required|string',
                'supervisor_id' => 'required|exists:users,id',
                'class_ids' => 'required|array|min:1',
                'class_ids.*' => 'exists:school_classes,id',
                'event_type' => 'sometimes|in:entry,exit,auto'
            ]);
            
            // Chercher l'utilisateur (même logique que scanQR)
            $staffRoles = ['principal', 'teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire', 'responsable_pedagogique', 'dean_of_studies', 'censeur_esg', 'censeur', 'surveillant_secteur', 'caissiere', 'bibliothecaire', 'chef_travaux', 'chef_securite', 'reprographe'];
            $user = User::where('qr_code', $request->staff_qr_code)
                ->whereIn('role', $staffRoles)
                ->where('is_active', true)
                ->first();

            // Si pas trouvé dans users, chercher dans teachers
            $teacher = null;
            if (!$user) {
                $teacher = Teacher::where('qr_code', $request->staff_qr_code)
                    ->where('is_active', true)
                    ->first();

                if ($teacher) {
                    if ($teacher->user) {
                        $user = $teacher->user;
                    } else {
                        // Créer un compte utilisateur pour l'enseignant
                        $user = User::create([
                            'name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'username' => strtolower($teacher->first_name) . '_' . $teacher->id,
                            'email' => $teacher->email ?: strtolower($teacher->first_name) . $teacher->id . '@school.local',
                            'password' => \Hash::make('123456'),
                            'role' => 'teacher',
                            'qr_code' => $teacher->qr_code,
                            'is_active' => $teacher->is_active,
                            'contact' => $teacher->phone_number,
                        ]);
                        
                        $teacher->update(['user_id' => $user->id]);
                    }
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code QR invalide - membre du personnel non trouvé ou inactif'
                ], 404);
            }

            // Vérifier que c'est bien un Vacataire ou Semi-Permanent
            $staffType = $this->getStaffType($user, $teacher);
            if (!in_array($staffType, ['vacataire', 'semi_permanent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette API est uniquement pour les enseignants Vacataires et Semi-Permanents'
                ], 400);
            }

            // Obtenir les classes sélectionnées
            $schoolClasses = \App\Models\SchoolClass::whereIn('id', $request->class_ids)->get();
            if ($schoolClasses->count() !== count($request->class_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une ou plusieurs classes non trouvées'
                ], 404);
            }

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

            // Vérifier les mouvements du jour
            $todaysMovements = StaffAttendance::where('user_id', $user->id)
                ->where('attendance_date', $today)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at', 'asc')
                ->get();

            // Compter les entrées et sorties du jour
            $entriesCount = $todaysMovements->where('event_type', 'entry')->count();
            $exitsCount = $todaysMovements->where('event_type', 'exit')->count();

            // Protection contre les scans multiples récents
            $lastMovement = $todaysMovements->last();
            if ($lastMovement && $lastMovement->scanned_at) {
                $timeDifference = Carbon::parse($lastMovement->scanned_at)->diffInSeconds($now);
                if ($timeDifference < 5) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scan trop récent. Veuillez attendre ' . (5 - $timeDifference) . ' secondes avant de rescanner.',
                        'time_remaining' => 5 - $timeDifference
                    ], 429);
                }
            }

            // VALIDATION FLEXIBLE pour Vacataires/Semi-Permanents
            if ($eventType === 'entry') {
                // Pour l'entrée : vérifier qu'il n'y a pas d'entrée en cours (pas de sortie correspondante)
                if ($entriesCount > $exitsCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous êtes déjà présent. Veuillez d\'abord scanner votre sortie.',
                        'error_code' => 'MUST_SCAN_EXIT_FIRST',
                        'data' => [
                            'error_code' => 'MUST_SCAN_EXIT_FIRST',
                            'entries_today' => $entriesCount,
                            'exits_today' => $exitsCount,
                            'last_entry' => $todaysMovements->where('event_type', 'entry')->last()->scanned_at
                        ]
                    ], 422);
                }
                
            } elseif ($eventType === 'exit') {
                // Pour la sortie : vérifier qu'il y a au moins une entrée non sortie
                if ($entriesCount === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune entrée trouvée aujourd\'hui. Veuillez d\'abord scanner votre entrée.',
                        'error_code' => 'MUST_SCAN_ENTRY_FIRST',
                        'data' => [
                            'error_code' => 'MUST_SCAN_ENTRY_FIRST',
                            'entries_today' => $entriesCount
                        ]
                    ], 422);
                }
                
                if ($exitsCount >= $entriesCount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune entrée en cours à clôturer.',
                        'error_code' => 'NO_PENDING_ENTRY',
                        'data' => [
                            'error_code' => 'NO_PENDING_ENTRY',
                            'entries_today' => $entriesCount,
                            'exits_today' => $exitsCount
                        ]
                    ], 422);
                }
            }

            // Calculer le retard
            $lateMinutes = 0;
            if ($eventType === 'entry') {
                $lateMinutes = $this->calculateLateMinutes($now, $staffType);
            }

            // Utiliser une transaction pour créer l'attendance et les liens avec les classes
            DB::beginTransaction();
            try {
                // Créer l'enregistrement de présence
                $attendance = StaffAttendance::create([
                    'user_id' => $user->id,
                    'supervisor_id' => $request->supervisor_id,
                    'school_year_id' => $currentSchoolYear->id,
                    'attendance_date' => $today,
                    'scanned_at' => $now,
                    'scanned_qr_code' => $request->staff_qr_code,
                    'is_present' => $eventType === 'entry',
                    'event_type' => $eventType,
                    'staff_type' => $staffType,
                    'late_minutes' => $lateMinutes,
                    'notes' => "Classes: " . $schoolClasses->pluck('name')->implode(', ')
                ]);

                // Créer les liens avec les classes
                foreach ($schoolClasses as $class) {
                    StaffAttendanceClass::create([
                        'staff_attendance_id' => $attendance->id,
                        'school_class_id' => $class->id
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Calculer le temps de travail total
            $totalWorkTime = $this->calculateDailyWorkTime($user->id, $today, $currentSchoolYear->id);

            \Log::info('QR code staff attendance with classes - Scan réussi', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'staff_type' => $staffType,
                'class_ids' => $request->class_ids,
                'class_names' => $schoolClasses->pluck('name'),
                'event_type' => $eventType,
                'attendance_id' => $attendance->id
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
                        'staff_type' => $staffType
                    ],
                    'attendance' => [
                        'id' => $attendance->id,
                        'user_id' => $attendance->user_id,
                        'scanned_at' => $attendance->scanned_at,
                        'event_type' => $attendance->event_type,
                        'late_minutes' => $attendance->late_minutes
                    ],
                    'classes' => $schoolClasses->map(function($class) {
                        return [
                            'id' => $class->id,
                            'name' => $class->name
                        ];
                    }),
                    'event_type' => $eventType,
                    'late_minutes' => $lateMinutes,
                    'scan_time' => $now->format('H:i:s'),
                    'daily_work_time' => $totalWorkTime
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('STAFF ATTENDANCE WITH CLASSES - Erreur de validation', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des données',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('STAFF ATTENDANCE WITH CLASSES - Erreur système', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur système lors du scan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les classes de l'entrée du jour pour un staff
     */
    public function getTodayEntryClasses($staffId): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur existe
            $user = User::find($staffId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Obtenir l'année scolaire actuelle
            $currentSchoolYear = SchoolYear::where('is_current', true)->first();
            if (!$currentSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ], 400);
            }

            $today = now()->toDateString();

            // Récupérer toutes les entrées et sorties du jour pour identifier la dernière entrée non sortie
            $todayAttendances = StaffAttendance::where('user_id', $staffId)
                ->where('attendance_date', $today)
                ->where('school_year_id', $currentSchoolYear->id)
                ->orderBy('scanned_at', 'asc')
                ->get();

            // Identifier la dernière entrée qui n'a pas encore de sortie correspondante
            $entryAttendance = null;
            $entriesCount = 0;
            $exitsCount = 0;
            
            foreach ($todayAttendances as $attendance) {
                if ($attendance->event_type === 'entry') {
                    $entriesCount++;
                    // Si c'est une nouvelle entrée après qu'on ait égalisé entrées/sorties
                    if ($entriesCount > $exitsCount) {
                        $entryAttendance = $attendance; // Dernière entrée non sortie
                    }
                } elseif ($attendance->event_type === 'exit') {
                    $exitsCount++;
                }
            }
            
            // Charger les classes pour l'entrée trouvée
            if ($entryAttendance) {
                $entryAttendance->load('attendanceClasses.schoolClass');
            }

            if (!$entryAttendance) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'class_ids' => [],
                        'classes' => []
                    ],
                    'message' => 'Aucune entrée trouvée aujourd\'hui'
                ]);
            }

            // Récupérer les classes liées à cette entrée
            $classes = $entryAttendance->attendanceClasses->map(function($ac) {
                return [
                    'id' => $ac->school_class_id,
                    'name' => $ac->schoolClass->name ?? 'Classe inconnue'
                ];
            });

            $classIds = $classes->pluck('id')->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'class_ids' => $classIds,
                    'classes' => $classes
                ],
                'message' => 'Classes de l\'entrée récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('GET TODAY ENTRY CLASSES - Erreur', [
                'staff_id' => $staffId,
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
