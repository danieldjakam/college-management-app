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

        // 🔄 VALIDATION FLEXIBLE pour Vacataires/Semi-Permanents (plusieurs cycles possibles mais dans l'ordre)
        if ($eventType === 'entry') {
            // Vérifier qu'il n'y a pas une entrée sans sortie correspondante
            if ($entriesCount > $exitsCount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez d\'abord scanner votre sortie avant de faire une nouvelle entrée.',
                    'error_code' => 'MUST_SCAN_EXIT_FIRST',
                    'data' => [
                        'error_code' => 'MUST_SCAN_EXIT_FIRST',
                        'entries_today' => $entriesCount,
                        'exits_today' => $exitsCount,
                        'last_entry' => $todaysMovements->where('event_type', 'entry')->last()->scanned_at,
                        'suggestion' => 'Utilisez le bouton "Départ" pour scanner votre sortie d\'abord'
                    ]
                ], 422);
            }
            // ✅ OK pour l'entrée (première ou après une sortie)
            
        } elseif ($eventType === 'exit') {
            // Vérifier qu'il y a au moins une entrée
            if ($entriesCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune entrée trouvée aujourd\'hui. Vous devez d\'abord scanner une arrivée.',
                    'error_code' => 'NO_ENTRY_RECORDED',
                    'data' => [
                        'error_code' => 'NO_ENTRY_RECORDED',
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
                        'error_code' => 'MUST_SCAN_ENTRY_FIRST',
                        'entries_today' => $entriesCount,
                        'exits_today' => $exitsCount,
                        'last_exit' => $todaysMovements->where('event_type', 'exit')->last()->scanned_at,
                        'suggestion' => 'Utilisez le bouton "Arrivée" pour scanner une nouvelle entrée d\'abord'
                    ]
                ], 422);
            }
            // ✅ OK pour la sortie
        }

        // Calculer le retard
        $lateMinutes = 0;
        if ($eventType === 'entry') {
            $lateMinutes = $this->calculateLateMinutes($now, $staffType);
        }

        // CAS SPÉCIAL : ADIBONE HUGUETTE - Forcer la sortie à 18h00 (également pour les Vacataires/Semi-Permanents)
        $finalScanTime = $now;
        if ($eventType === 'exit' && stripos($user->name, 'ADIBONE HUGUETTE') !== false) {
            // Forcer l'heure de sortie à 18h00 (6 PM) pour ADIBONE HUGUETTE
            $finalScanTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' 18:00:00');
            
            \Log::info('CAS SPÉCIAL - ADIBONE HUGUETTE (Multi-classes) : Sortie forcée à 18h00', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'real_scan_time' => $now->format('H:i:s'),
                'forced_scan_time' => '18:00:00',
                'date' => $today,
                'class_ids' => $request->class_ids
            ]);
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
                'scanned_at' => $finalScanTime, // Utiliser l'heure finale (normale ou forcée)
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

        // Récupérer l'entrée du jour
        $entryAttendance = StaffAttendance::where('user_id', $staffId)
            ->where('attendance_date', $today)
            ->where('school_year_id', $currentSchoolYear->id)
            ->where('event_type', 'entry')
            ->with('attendanceClasses.schoolClass')
            ->first();

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
