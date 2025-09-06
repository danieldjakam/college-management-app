<?php

namespace App\Http\Controllers;

use App\Models\ParentNotification;
use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Obtenir toutes les notifications (pour l'admin)
     */
    public function index()
    {
        try {
            $notifications = ParentNotification::with(['parent', 'student', 'admin'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une notification pour un parent spécifique
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:parent_guardians,id',
            'student_id' => 'nullable|exists:students,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:general,attendance,academic,behavior,payment,event',
            'priority' => 'required|in:low,normal,high,urgent',
            'send_to_all' => 'nullable|boolean',
            'send_to_class' => 'nullable|exists:school_classes,id'
        ]);

        try {
            DB::beginTransaction();
            
            $admin = Auth::user();
            $notificationsCreated = [];

            // Si envoyer à tous les parents
            if ($request->send_to_all) {
                $parents = ParentGuardian::where('is_active', true)->get();
                
                foreach ($parents as $parent) {
                    $notification = ParentNotification::create([
                        'parent_id' => $parent->id,
                        'student_id' => $request->student_id,
                        'admin_id' => $admin->id,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                        'priority' => $request->priority
                    ]);
                    $notificationsCreated[] = $notification;
                }
            }
            // Si envoyer à une classe spécifique
            elseif ($request->send_to_class) {
                $students = Student::whereHas('classSeries', function($query) use ($request) {
                    $query->where('class_id', $request->send_to_class);
                })->get();
                
                $parentIds = [];
                foreach ($students as $student) {
                    // Récupérer les parents de chaque élève
                    $parentStudents = DB::table('parent_student')
                        ->where('student_id', $student->id)
                        ->pluck('parent_id');
                    
                    foreach ($parentStudents as $parentId) {
                        if (!in_array($parentId, $parentIds)) {
                            $parentIds[] = $parentId;
                            
                            $notification = ParentNotification::create([
                                'parent_id' => $parentId,
                                'student_id' => $student->id,
                                'admin_id' => $admin->id,
                                'title' => $request->title,
                                'message' => $request->message,
                                'type' => $request->type,
                                'priority' => $request->priority
                            ]);
                            $notificationsCreated[] = $notification;
                        }
                    }
                }
            }
            // Si envoyer à un parent spécifique
            elseif ($request->parent_id) {
                $notification = ParentNotification::create([
                    'parent_id' => $request->parent_id,
                    'student_id' => $request->student_id,
                    'admin_id' => $admin->id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'priority' => $request->priority
                ]);
                $notificationsCreated[] = $notification;
            }
            // Si un étudiant est spécifié, envoyer à tous ses parents
            elseif ($request->student_id) {
                $parentIds = DB::table('parent_student')
                    ->where('student_id', $request->student_id)
                    ->pluck('parent_id');
                
                foreach ($parentIds as $parentId) {
                    $notification = ParentNotification::create([
                        'parent_id' => $parentId,
                        'student_id' => $request->student_id,
                        'admin_id' => $admin->id,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                        'priority' => $request->priority
                    ]);
                    $notificationsCreated[] = $notification;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($notificationsCreated) . ' notification(s) envoyée(s) avec succès',
                'data' => $notificationsCreated
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les parents pour le select
     */
    public function getParents()
    {
        try {
            $parents = ParentGuardian::where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'phone')
                ->orderBy('first_name')
                ->get()
                ->map(function ($parent) {
                    return [
                        'id' => $parent->id,
                        'name' => $parent->first_name . ' ' . $parent->last_name,
                        'phone' => $parent->phone
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $parents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des parents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les étudiants pour le select
     */
    public function getStudents()
    {
        try {
            $students = Student::with('classSeries.schoolClass')
                ->select('id', 'first_name', 'last_name', 'class_series_id')
                ->orderBy('first_name')
                ->get()
                ->map(function ($student) {
                    $className = 'N/A';
                    if ($student->classSeries && $student->classSeries->first()) {
                        $className = $student->classSeries->first()->schoolClass->name ?? 'N/A';
                    }
                    
                    return [
                        'id' => $student->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'class' => $className
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les classes pour le select
     */
    public function getClasses()
    {
        try {
            $classes = DB::table('school_classes')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id)
    {
        try {
            $notification = ParentNotification::findOrFail($id);
            $notification->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des notifications
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => ParentNotification::count(),
                'unread' => ParentNotification::where('is_read', false)->count(),
                'today' => ParentNotification::whereDate('created_at', today())->count(),
                'by_priority' => [
                    'urgent' => ParentNotification::where('priority', 'urgent')->count(),
                    'high' => ParentNotification::where('priority', 'high')->count(),
                    'normal' => ParentNotification::where('priority', 'normal')->count(),
                    'low' => ParentNotification::where('priority', 'low')->count()
                ],
                'by_type' => [
                    'general' => ParentNotification::where('type', 'general')->count(),
                    'attendance' => ParentNotification::where('type', 'attendance')->count(),
                    'academic' => ParentNotification::where('type', 'academic')->count(),
                    'behavior' => ParentNotification::where('type', 'behavior')->count(),
                    'payment' => ParentNotification::where('type', 'payment')->count(),
                    'event' => ParentNotification::where('type', 'event')->count()
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
}