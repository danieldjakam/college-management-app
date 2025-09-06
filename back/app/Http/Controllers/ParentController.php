<?php

namespace App\Http\Controllers;

use App\Models\ParentGuardian;
use App\Models\ParentNotification;
use App\Models\Student;
use App\Models\Event;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ParentController extends Controller
{
    /**
     * Connexion du parent
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email|string',
            'password' => 'required_without:pin_code|string',
            'pin_code' => 'required_without:password|digits:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de connexion invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Recherche du parent
            $query = ParentGuardian::where('is_active', true);
            
            if ($request->email) {
                $query->where('email', $request->email);
            } else {
                $query->where('phone', $request->phone);
            }

            $parent = $query->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte parent introuvable'
                ], 404);
            }

            // Vérification du mot de passe ou PIN
            $authenticated = false;
            if ($request->password) {
                $authenticated = Hash::check($request->password, $parent->password);
            } elseif ($request->pin_code) {
                $authenticated = Hash::check($request->pin_code, $parent->pin_code);
            }

            if (!$authenticated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            // Mise à jour dernière connexion
            $parent->update(['last_login_at' => now()]);

            // Création du token
            $token = $parent->createToken('parent-app')->plainTextToken;

            // Chargement des enfants
            $parent->load(['children' => function($query) {
                $query->with(['classSeries.schoolClass']);
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'parent' => $parent,
                    'token' => $token,
                    'children_count' => $parent->children->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tableau de bord du parent
     */
    public function dashboard(Request $request)
    {
        try {
            $parent = $request->user();
            
            // Charger les enfants simplement
            $children = $parent->children;

            // Récupérer les informations de classe directement via une requête
            $childrenStats = [];
            foreach ($children as $child) {
                // Récupérer la classe via la table class_series
                $classSeries = DB::table('class_series')
                    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
                    ->where('class_series.id', $child->class_series_id)
                    ->select('school_classes.name as class_name')
                    ->first();

                $className = $classSeries ? $classSeries->class_name : 'Non assigné';

                $childrenStats[] = [
                    'student' => [
                        'id' => $child->id,
                        'first_name' => $child->first_name,
                        'last_name' => $child->last_name,
                        'full_name' => $child->full_name,
                        'student_number' => $child->student_number,
                        'class_series' => [
                            [
                                'school_class' => [
                                    'name' => $className
                                ]
                            ]
                        ]
                    ],
                    'attendance_rate' => $this->calculateAttendanceRate($child->id),
                    'absences_this_week' => 0,
                    'average_grade' => null
                ];
            }

            // Récupérer les notifications du parent
            $notifications = ParentNotification::where('parent_id', $parent->id)
                ->with('student')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $unreadCount = ParentNotification::where('parent_id', $parent->id)
                ->where('is_read', false)
                ->count();
            
            $urgentNotifications = ParentNotification::where('parent_id', $parent->id)
                ->where('priority', 'urgent')
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'children_stats' => $childrenStats,
                    'notifications' => [
                        'unread_count' => $unreadCount,
                        'recent' => $notifications
                    ],
                    'upcoming_events' => [],
                    'unread_messages' => 0,
                    'summary' => [
                        'total_children' => $children->count(),
                        'total_absences_week' => 0,
                        'urgent_notifications' => $urgentNotifications
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du tableau de bord',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des enfants du parent
     */
    public function getChildren(Request $request)
    {
        try {
            $parent = $request->user();
            
            $children = $parent->children()
                ->with([
                    'classSeries.schoolClass',
                    'classSeries.series',
                    'grades' => function($query) {
                        $query->latest()->limit(10);
                    },
                    'attendances' => function($query) {
                        $query->whereDate('date', now()->format('Y-m-d'));
                    }
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $children->map(function($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->full_name,
                        'matricule' => $child->student_number,
                        'photo' => $child->photo,
                        'class' => $child->classSeries->first()?->schoolClass?->name,
                        'series' => $child->classSeries->first()?->series?->name,
                        'present_today' => !$child->attendances->contains('status', 'absent'),
                        'recent_grades_count' => $child->grades->count(),
                        'relationship' => $child->pivot->relationship_type,
                        'is_primary_contact' => $child->pivot->is_primary_contact
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des enfants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les notifications
     */
    public function getNotifications(Request $request)
    {
        try {
            $parent = $request->user();
            
            $notifications = $parent->notifications()
                ->with('student:id,first_name,last_name')
                ->orderBy('is_read')
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        try {
            $parent = $request->user();
            
            $notification = $parent->notifications()->findOrFail($notificationId);
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les événements du calendrier
     */
    public function getCalendarEvents(Request $request)
    {
        try {
            $parent = $request->user();
            $childrenIds = $parent->children->pluck('id');

            // Événements généraux
            $generalEvents = Event::where('type', 'general')
                ->where('date', '>=', now()->startOfMonth())
                ->where('date', '<=', now()->endOfMonth()->addMonth())
                ->get();

            // Évaluations des enfants
            $evaluations = DB::table('evaluations')
                ->join('series_subjects', 'evaluations.series_subject_id', '=', 'series_subjects.id')
                ->join('class_series', 'series_subjects.school_class_id', '=', 'class_series.class_id')
                ->join('students', function($join) use ($childrenIds) {
                    $join->on('class_series.id', '=', 'students.class_series_id')
                         ->whereIn('students.id', $childrenIds);
                })
                ->select([
                    'evaluations.id',
                    'evaluations.name as title',
                    'evaluations.date',
                    'evaluations.type',
                    'students.first_name',
                    'students.last_name',
                    'students.id as student_id'
                ])
                ->where('evaluations.date', '>=', now())
                ->get();

            // Formater les événements
            $events = [];

            foreach ($generalEvents as $event) {
                $events[] = [
                    'id' => 'event_' . $event->id,
                    'title' => $event->title,
                    'date' => $event->date,
                    'type' => 'school_event',
                    'color' => '#28a745',
                    'description' => $event->description
                ];
            }

            foreach ($evaluations as $eval) {
                $events[] = [
                    'id' => 'eval_' . $eval->id,
                    'title' => $eval->title . ' - ' . $eval->first_name,
                    'date' => $eval->date,
                    'type' => 'evaluation',
                    'evaluation_type' => $eval->type,
                    'student_id' => $eval->student_id,
                    'color' => '#007bff',
                    'description' => 'Évaluation pour ' . $eval->first_name . ' ' . $eval->last_name
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $events
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du calendrier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le taux de présence - Version sécurisée
     */
    private function calculateAttendanceRate($studentId)
    {
        try {
            // Vérifier si la table attendances existe et a des données
            if (!DB::getSchemaBuilder()->hasTable('attendances')) {
                return 100; // Si pas de table attendance, considérer comme 100%
            }

            $total = DB::table('attendances')
                ->where('student_id', $studentId)
                ->whereDate('date', '>=', now()->startOfMonth())
                ->count();

            if ($total == 0) {
                return 100; // Aucune donnée de présence = 100%
            }

            $present = DB::table('attendances')
                ->where('student_id', $studentId)
                ->where('status', 'present')
                ->whereDate('date', '>=', now()->startOfMonth())
                ->count();

            return round(($present / $total) * 100, 1);
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une valeur par défaut
            return 95; // Valeur par défaut
        }
    }

    /**
     * Obtenir l'ID de la séquence courante
     */
    private function getCurrentSequenceId()
    {
        return DB::table('sequences')
            ->where('is_active', true)
            ->value('id') ?? 1;
    }
}