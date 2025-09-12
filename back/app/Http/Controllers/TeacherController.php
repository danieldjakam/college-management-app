<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Models\TeacherSubject;
use App\Models\ClassSeries;
use App\Models\SchoolYear;
use App\Exports\TeachersExport;
use App\Exports\TeachersImportableExport;
use App\Imports\TeachersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class TeacherController extends Controller
{
    /**
     * Lister tous les enseignants
     */
    public function index(Request $request)
    {
        try {
            $query = Teacher::query();

            // Filtrer par statut si spécifié
            if ($request->has('active')) {
                $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            }

            // Recherche par nom, prénom, téléphone ou teacher_id
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('teacher_id', 'like', "%{$search}%");
                });
            }

            // Inclure les relations si demandé
            if ($request->has('with_details')) {
                $query->with(['user', 'mainClasses' => function($q) {
                    $q->with('schoolClass');
                }]);
            }

            $teachers = $query->orderBy('last_name')
                             ->orderBy('first_name')
                             ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel enseignant
     */
    public function store(Request $request)
    {
        // Log pour déboguer
        \Log::info('TeacherController::store called', [
            'request_data' => $request->all(),
            'user' => auth()->user() ? auth()->user()->toArray() : null,
            'headers' => $request->headers->all()
        ]);
        
        try {
            \Log::info('Validation started');
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'nullable|string|max:50|unique:teachers,teacher_id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'email' => 'nullable|email|unique:teachers,email',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:m,f',
                'qualification' => 'nullable|string|max:255',
                'hire_date' => 'nullable|date',
                'type_personnel' => 'nullable|in:V,SP,P',
                'is_active' => 'boolean',
                // Champs pour créer un compte utilisateur
                'create_user_account' => 'boolean',
                'username' => 'required_if:create_user_account,true|string|unique:users,username',
                'password' => 'required_if:create_user_account,true|string|min:6'
            ]);

            if ($validator->fails()) {
                \Log::info('Validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            \Log::info('Validation passed');

            \Log::info('Starting database transaction');
            DB::beginTransaction();

            $teacherData = $validator->validated();
            $userId = null;

            // Créer un compte utilisateur si demandé
            if ($request->create_user_account && $request->username && $request->password) {
                \Log::info('Creating user account', ['username' => $request->username]);
                // Si pas d'email fourni, générer un email temporaire basé sur le nom d'utilisateur
                $email = $teacherData['email'] ?? $teacherData['username'] . '@school.local';
                
                $user = User::create([
                    'name' => $teacherData['first_name'] . ' ' . $teacherData['last_name'],
                    'username' => $teacherData['username'],
                    'email' => $email,
                    'contact' => $teacherData['phone_number'], // Ajouter le numéro de téléphone
                    'password' => Hash::make($teacherData['password']),
                    'role' => 'teacher'
                ]);
                $userId = $user->id;
                \Log::info('User created successfully', ['user_id' => $userId]);
            }

            // Créer l'enseignant
            \Log::info('Creating teacher record');
            $teacher = Teacher::create([
                'teacher_id' => $teacherData['teacher_id'] ?? null,
                'first_name' => $teacherData['first_name'],
                'last_name' => $teacherData['last_name'],
                'phone_number' => $teacherData['phone_number'],
                'email' => $teacherData['email'] ?? null,
                'address' => $teacherData['address'] ?? null,
                'date_of_birth' => $teacherData['date_of_birth'] ?? null,
                'gender' => $teacherData['gender'] ?? null,
                'qualification' => $teacherData['qualification'] ?? null,
                'hire_date' => $teacherData['hire_date'] ?? now(),
                'is_active' => $teacherData['is_active'] ?? true,
                'type_personnel' => $teacherData['type_personnel'] ?? 'V',
                'user_id' => $userId
            ]);
            
            \Log::info('Teacher created successfully', ['teacher_id' => $teacher->id]);

            \Log::info('Committing transaction');
            DB::commit();

            $teacher->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Enseignant créé avec succès',
                'data' => $teacher
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un enseignant spécifique
     */
    public function show(Teacher $teacher)
    {
        try {
            $teacher->load([
                'user',
                'mainClasses' => function($q) {
                    $q->with('schoolClass');
                },
                'teacherSubjects' => function($q) {
                    $q->with(['subject', 'classSeries.schoolClass', 'schoolYear'])
                      ->where('is_active', true);
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un enseignant
     */
    public function update(Request $request, Teacher $teacher)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'nullable|string|max:50|unique:teachers,teacher_id,' . $teacher->id,
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'email' => 'nullable|email|unique:teachers,email,' . $teacher->id,
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:m,f',
                'qualification' => 'nullable|string|max:255',
                'hire_date' => 'nullable|date',
                'type_personnel' => 'nullable|in:V,SP,P',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $teacher->update($validator->validated());

            // Mettre à jour le compte utilisateur associé si existe
            if ($teacher->user) {
                $teacher->user->update([
                    'name' => $teacher->first_name . ' ' . $teacher->last_name,
                    'email' => $teacher->email
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Enseignant mis à jour avec succès',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un enseignant
     */
    public function destroy(Teacher $teacher)
    {
        try {
            DB::beginTransaction();

            // Vérifier si l'enseignant est professeur principal
            if ($teacher->mainClasses()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant ne peut pas être supprimé car il est professeur principal d\'une ou plusieurs classes'
                ], 400);
            }

            // Supprimer les assignations de matières
            $teacher->teacherSubjects()->delete();

            // Supprimer le compte utilisateur associé si existe
            if ($teacher->user) {
                $teacher->user->delete();
            }

            $teacher->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enseignant supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'enseignant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Basculer le statut actif/inactif d'un enseignant
     */
    public function toggleStatus(Teacher $teacher)
    {
        try {
            $teacher->is_active = !$teacher->is_active;
            $teacher->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut de l\'enseignant mis à jour avec succès',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner des matières à un enseignant
     */
    public function assignSubjects(Request $request, Teacher $teacher)
    {
        try {
            $validator = Validator::make($request->all(), [
                'school_year_id' => 'nullable|exists:school_years,id',
                'assignments' => 'required|array',
                'assignments.*.subject_id' => 'required|exists:subjects,id',
                'assignments.*.class_series_id' => 'required|exists:school_classes,id',
                'assignments.*.coefficient' => 'nullable|numeric|min:0.5|max:10',
                'assignments.*.is_main_teacher' => 'boolean',
                'assignments.*.is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Utiliser l'année scolaire courante si non spécifiée
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()?->id;
            
            if (!$schoolYearId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Année scolaire non trouvée'
                ], 422);
            }

            // Ajouter les nouvelles assignations (ne pas supprimer les anciennes, juste ajouter)
            foreach ($request->assignments as $assignment) {
                // Vérifier si l'assignation existe déjà
                $existingAssignment = TeacherSubject::where('teacher_id', $teacher->id)
                    ->where('subject_id', $assignment['subject_id'])
                    ->where('class_series_id', $assignment['class_series_id'])
                    ->where('school_year_id', $schoolYearId)
                    ->first();

                if ($existingAssignment) {
                    // Mettre à jour l'assignation existante
                    $existingAssignment->update([
                        'coefficient' => $assignment['coefficient'] ?? 1,
                        'is_main_teacher' => $assignment['is_main_teacher'] ?? false,
                        'is_active' => $assignment['is_active'] ?? true
                    ]);
                } else {
                    // Créer une nouvelle assignation
                    TeacherSubject::create([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $assignment['subject_id'],
                        'class_series_id' => $assignment['class_series_id'],
                        'school_year_id' => $schoolYearId,
                        'coefficient' => $assignment['coefficient'] ?? 1,
                        'is_main_teacher' => $assignment['is_main_teacher'] ?? false,
                        'is_active' => $assignment['is_active'] ?? true
                    ]);
                }
            }

            DB::commit();

            // Recharger les assignations
            $teacher->load(['teacherSubjects' => function($q) use ($schoolYearId) {
                $q->with(['subject', 'classSeries.schoolClass'])
                  ->where('school_year_id', $schoolYearId);
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Assignations de matières mises à jour avec succès',
                'data' => $teacher
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation des matières',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une affectation spécifique d'un enseignant
     */
    public function removeAssignment(Teacher $teacher, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'class_series_id' => 'required|exists:school_classes,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Supprimer l'affectation spécifique
            $deleted = TeacherSubject::where('teacher_id', $teacher->id)
                ->where('subject_id', $request->subject_id)
                ->where('class_series_id', $request->class_series_id)
                ->delete();

            if ($deleted === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Affectation non trouvée'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Affectation supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'affectation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques d'un enseignant pour une année
     */
    public function getStats(Teacher $teacher, Request $request)
    {
        try {
            $schoolYearId = $request->school_year_id ?? SchoolYear::where('is_current', true)->first()->id;

            $stats = [
                'subject_count' => $teacher->getSubjectCountForYear($schoolYearId),
                'class_count' => $teacher->getClassCountForYear($schoolYearId),
                'is_main_teacher' => $teacher->mainClasses()->exists(),
                'main_classes_count' => $teacher->mainClasses()->count()
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
     * Export teachers to Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $filename = 'enseignants_' . date('Y-m-d_H-i-s') . '.xlsx';
            return Excel::download(new TeachersExport(), $filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export teachers to CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            $filename = 'enseignants_' . date('Y-m-d_H-i-s') . '.csv';
            return Excel::download(new TeachersImportableExport(), $filename, \Maatwebsite\Excel\Excel::CSV);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export teachers to PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $filename = 'enseignants_' . date('Y-m-d_H-i-s') . '.pdf';
            return Excel::download(new TeachersExport(), $filename, \Maatwebsite\Excel\Excel::DOMPDF);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import teachers from CSV
     */
    public function importCsv(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:csv,txt|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier invalide',
                    'errors' => $validator->errors()
                ], 422);
            }

            $import = new TeachersImport();
            Excel::import($import, $request->file('file'));
            
            $results = $import->getResults();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Import terminé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export teachers in importable CSV format
     */
    public function exportImportable(Request $request)
    {
        try {
            $filename = 'enseignants_importable_' . date('Y-m-d_H-i-s') . '.csv';
            return Excel::download(new TeachersImportableExport(), $filename, \Maatwebsite\Excel\Excel::CSV);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV importable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download CSV template for teachers import
     */
    public function downloadTemplate()
    {
        try {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="template_enseignants.csv"'
            ];

            $csvData = "id,nom,prenom,telephone,email,adresse,date_naissance,sexe,qualification,date_embauche,statut\n";
            $csvData .= ",DUPONT,Jean,123456789,jean.dupont@email.com,123 Rue de la Paix,01/01/1980,m,Licence en Mathématiques,01/09/2020,1\n";
            $csvData .= ",MARTIN,Sophie,987654321,sophie.martin@email.com,456 Avenue des Ecoles,15/05/1985,f,Master en Français,01/09/2021,1\n";
            $csvData .= "3,BERNARD,Pierre,555123456,pierre.bernard@email.com,789 Boulevard Education,20/03/1978,m,CAPES Histoire-Géographie,01/09/2019,0\n";

            return Response::make($csvData, 200, $headers);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        try {
            $stats = [
                'total_teachers' => Teacher::count(),
                'active_teachers' => Teacher::where('is_active', true)->count(),
                'inactive_teachers' => Teacher::where('is_active', false)->count(),
                'with_user_account' => Teacher::whereNotNull('user_id')->count(),
                'main_teachers' => Teacher::has('mainClasses')->count(),
            ];

            $recent_teachers = Teacher::latest()->take(5)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_teachers' => $recent_teachers
                ],
                'message' => 'Statistiques récupérées avec succès'
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
     * Create a user account for an existing teacher
     */
    public function createUserAccount(Request $request, Teacher $teacher)
    {
        try {
            // Vérifier si l'enseignant a déjà un compte utilisateur
            if ($teacher->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant a déjà un compte utilisateur'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users,username|max:255',
                'password' => 'required|string|min:6',
                'email' => 'nullable|email|unique:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Utiliser l'email de l'enseignant ou celui fourni, ou générer un email temporaire
            $email = $request->email ?: $teacher->email ?: $request->username . '@school.local';

            // Créer le compte utilisateur
            $user = User::create([
                'name' => $teacher->full_name,
                'username' => $request->username,
                'email' => $email,
                'password' => Hash::make($request->password),
                'role' => 'teacher'
            ]);

            // Lier l'utilisateur à l'enseignant
            $teacher->update(['user_id' => $user->id]);

            // Si un nouvel email a été fourni, mettre à jour l'enseignant aussi
            if ($request->email && $request->email !== $teacher->email) {
                $teacher->update(['email' => $request->email]);
            }

            DB::commit();

            $teacher->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Compte utilisateur créé avec succès',
                'data' => $teacher
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove user account from a teacher
     */
    public function removeUserAccount(Teacher $teacher)
    {
        try {
            if (!$teacher->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant n\'a pas de compte utilisateur'
                ], 400);
            }

            DB::beginTransaction();

            // Supprimer le compte utilisateur
            if ($teacher->user) {
                $teacher->user->delete();
            }

            // Retirer la liaison
            $teacher->update(['user_id' => null]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte utilisateur supprimé avec succès',
                'data' => $teacher
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du compte utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un badge pour un enseignant
     */
    public function generateBadge(Teacher $teacher)
    {
        try {
            // Vérifier que l'enseignant est actif
            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de générer un badge pour un enseignant inactif'
                ], 400);
            }

            // Utiliser la même logique que StaffAttendanceController mais pour les enseignants
            // Rediriger vers StaffAttendanceController si l'enseignant a un compte utilisateur
            if ($teacher->user_id && $teacher->user) {
                $staffController = new StaffAttendanceController();
                return $staffController->downloadBadgePDF(new Request(['user_id' => $teacher->user_id]));
            }

            // Si pas de compte utilisateur, générer un badge simple pour l'enseignant
            return $this->generateTeacherBadgePDF($teacher);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du badge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer plusieurs badges d'enseignants (2 par page A4)
     */
    public function generateMultipleBadges(Request $request)
    {
        try {
            $request->validate([
                'teacher_ids' => 'required|array|min:1',
                'teacher_ids.*' => 'required|exists:teachers,id',
            ]);

            $teacherIds = $request->teacher_ids;
            $teachers = Teacher::whereIn('id', $teacherIds)
                ->where('is_active', true)
                ->get();

            if ($teachers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun enseignant valide trouvé'
                ], 400);
            }

            // Générer le HTML avec plusieurs badges (2 par page)
            $html = $this->generateMultipleTeacherBadgesHtml($teachers);

            // Configuration DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html);
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
            $filename = 'badges_enseignants_' . count($teachers) . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
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
     * Générer le HTML pour plusieurs badges d'enseignants (2 par page A4)
     */
    private function generateMultipleTeacherBadgesHtml($teachers)
    {
        $schoolSettings = \App\Models\SchoolSetting::first();
        
        // Charger l'image de background
        $backgroundBase64 = '';
        $backgroundPath = public_path('assets/images/card-background-cpb.png');
        if (file_exists($backgroundPath)) {
            $backgroundContent = file_get_contents($backgroundPath);
            $backgroundBase64 = 'data:image/png;base64,' . base64_encode($backgroundContent);
        }

        $badgesHtml = '';
        $badgeCount = 0;

        foreach ($teachers as $teacher) {
            // Générer QR code si nécessaire
            $qrCode = $teacher->qr_code ?: 'TEACHER_' . $teacher->id;
            if (!$teacher->qr_code) {
                $teacher->update(['qr_code' => $qrCode]);
            }

            // Convertir la photo en base64 (si elle existe)
            $photoBase64 = '';
            // Vous pouvez ajouter la logique de photo ici si nécessaire

            // Générer le HTML du badge
            $badgeHtml = $this->generateSingleTeacherBadgeHtml($teacher, $qrCode, $photoBase64, $schoolSettings);

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
        <title>Badges Enseignants - " . count($teachers) . " badges</title>
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
                margin: 0 auto;
            }
            
            .teacher-name {
                position: absolute;
                top: 8mm;
                left: 8mm;
                right: 8mm;
                font-size: 11pt;
                font-weight: bold;
                color: #2c3e50;
                text-align: center;
                background: rgba(255,255,255,0.9);
                padding: 2mm;
                border-radius: 4px;
            }
            
            .teacher-role {
                position: absolute;
                top: 18mm;
                left: 8mm;
                right: 8mm;
                font-size: 9pt;
                color: #7f8c8d;
                text-align: center;
                background: rgba(255,255,255,0.8);
                padding: 1mm;
                border-radius: 3px;
            }
            
            .qr-code {
                position: absolute;
                bottom: 5mm;
                right: 5mm;
                width: 15mm;
                height: 15mm;
            }
            
            .school-name {
                position: absolute;
                bottom: 8mm;
                left: 5mm;
                font-size: 8pt;
                color: #34495e;
                font-weight: bold;
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
     * Générer le HTML d'un badge individuel pour enseignant
     */
    private function generateSingleTeacherBadgeHtml($teacher, $qrCode, $photoBase64, $schoolSettings)
    {
        $schoolName = $schoolSettings->school_name ?? 'École';
        
        return "
        <div class='badge-container'>
            <div class='teacher-name'>{$teacher->full_name}</div>
            <div class='teacher-role'>Enseignant</div>
            <div class='school-name'>{$schoolName}</div>
            <img src='https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrCode) . "&margin=1' alt='QR Code' class='qr-code'>
        </div>";
    }

    /**
     * Générer un PDF de badge simple pour un enseignant
     */
    private function generateTeacherBadgePDF($teacher)
    {
        $qrCode = $teacher->qr_code ?: 'TEACHER_' . $teacher->id;
        if (!$teacher->qr_code) {
            $teacher->update(['qr_code' => $qrCode]);
        }

        $schoolSettings = \App\Models\SchoolSetting::first();
        $html = $this->generateSingleTeacherBadgeHtml($teacher, $qrCode, '', $schoolSettings);

        $fullHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Badge - {$teacher->full_name}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Arial', 'Helvetica', sans-serif;
                    padding: 20mm;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }
                .badge-container {
                    width: 95.6mm;
                    height: 54mm;
                    position: relative;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                .teacher-name {
                    position: absolute;
                    top: 8mm;
                    left: 8mm;
                    right: 8mm;
                    font-size: 11pt;
                    font-weight: bold;
                    color: white;
                    text-align: center;
                    background: rgba(0,0,0,0.3);
                    padding: 2mm;
                    border-radius: 4px;
                }
                .teacher-role {
                    position: absolute;
                    top: 18mm;
                    left: 8mm;
                    right: 8mm;
                    font-size: 9pt;
                    color: #ecf0f1;
                    text-align: center;
                    background: rgba(0,0,0,0.2);
                    padding: 1mm;
                    border-radius: 3px;
                }
                .qr-code {
                    position: absolute;
                    bottom: 5mm;
                    right: 5mm;
                    width: 15mm;
                    height: 15mm;
                    background: white;
                    padding: 1mm;
                    border-radius: 2px;
                }
                .school-name {
                    position: absolute;
                    bottom: 8mm;
                    left: 5mm;
                    font-size: 8pt;
                    color: white;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            {$html}
        </body>
        </html>";

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($fullHtml);
        $pdf->setPaper([0, 0, 270.236, 153.071], 'landscape'); // Format carte de crédit
        
        $filename = 'badge_enseignant_' . $teacher->full_name . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}