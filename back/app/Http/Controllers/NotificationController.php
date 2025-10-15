<?php

namespace App\Http\Controllers;

use App\Models\CommunicationLog;
use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Obtenir toutes les communications (historique)
     */
    public function index()
    {
        try {
            $communications = CommunicationLog::with(['admin', 'schoolClass', 'student'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $communications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une communication WhatsApp aux parents
     */
    public function store(Request $request)
    {
        // Log des données reçues pour débogage
        Log::info('Données reçues pour notification', [
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('attachments'),
            'content_type' => $request->header('Content-Type')
        ]);

        // Validation flexible
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:general,attendance,academic,behavior,payment,event',
            'priority' => 'required|in:low,normal,high,urgent',
            'send_to_all' => 'nullable',  // Accepter boolean ou string
            'send_to_class' => 'nullable',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf|max:10240' // Max 10MB par fichier
        ]);

        // Convertir send_to_all en boolean si c'est une string
        if (isset($validated['send_to_all'])) {
            $validated['send_to_all'] = filter_var($validated['send_to_all'], FILTER_VALIDATE_BOOLEAN);
        }

        // Convertir send_to_class en integer si c'est une string
        if (isset($validated['send_to_class']) && $validated['send_to_class'] !== '') {
            $validated['send_to_class'] = (int) $validated['send_to_class'];
        }

        // Utiliser les données validées
        $request->merge($validated);

        try {
            DB::beginTransaction();

            $admin = Auth::user();
            $recipients = [];
            $attachmentFiles = $request->file('attachments', []);

            // Déterminer les destinataires en fonction du type d'envoi
            if ($request->send_to_all) {
                // Récupérer TOUS les étudiants actifs
                $students = Student::where('is_active', true)->get();
                $sendToType = 'all';
                $sendToClassId = null;
                $sendToStudentId = null;
            } elseif ($request->send_to_class) {
                // Récupérer les étudiants d'une classe spécifique
                $students = Student::whereHas('classSeries', function($query) use ($request) {
                    $query->where('class_id', $request->send_to_class);
                })->where('is_active', true)->get();
                $sendToType = 'class';
                $sendToClassId = $request->send_to_class;
                $sendToStudentId = null;
            } elseif ($request->student_id) {
                // Récupérer un étudiant spécifique
                $students = Student::where('id', $request->student_id)->get();
                $sendToType = 'student';
                $sendToClassId = null;
                $sendToStudentId = $request->student_id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez sélectionner les destinataires'
                ], 400);
            }

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé pour les critères sélectionnés'
                ], 404);
            }

            // Extraire tous les numéros de téléphone des parents
            foreach ($students as $student) {
                // Numéro du père/tuteur
                if (!empty($student->parent_phone)) {
                    $recipients[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'parent_name' => $student->parent_name ?? 'Parent',
                        'phone' => $student->parent_phone,
                        'parent_type' => 'father',
                        'status' => 'pending'
                    ];
                }

                // Numéro de la mère
                if (!empty($student->mother_phone)) {
                    $recipients[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'parent_name' => $student->mother_name ?? 'Mère',
                        'phone' => $student->mother_phone,
                        'parent_type' => 'mother',
                        'status' => 'pending'
                    ];
                }
            }

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun numéro de téléphone de parent trouvé pour les élèves sélectionnés'
                ], 404);
            }

            // Sauvegarder d'abord les pièces jointes si présentes
            $attachmentUrls = [];
            if (!empty($attachmentFiles)) {
                foreach ($attachmentFiles as $file) {
                    try {
                        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs('communications', $fileName, 'public');
                        $localPath = storage_path('app/public/' . $filePath);
                        $localUrl = url('storage/' . $filePath);

                        // Upload sur un service temporaire pour WhatsApp (file.io)
                        $publicUrl = $this->uploadToTemporaryService($localPath, $fileName);

                        $attachmentUrls[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $filePath,
                            'url' => $publicUrl ?: $localUrl, // Utiliser l'URL publique si disponible
                            'local_url' => $localUrl,
                            'size' => $file->getSize()
                        ];

                        Log::info('Pièce jointe préparée', [
                            'filename' => $fileName,
                            'local_url' => $localUrl,
                            'public_url' => $publicUrl
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur upload fichier communication', [
                            'file_name' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Envoyer les messages WhatsApp
            $successCount = 0;
            $failCount = 0;

            foreach ($recipients as $index => $recipient) {
                try {
                    // Préparer le message personnalisé
                    $personalizedMessage = $this->formatWhatsAppMessage(
                        $request->title,
                        $request->message,
                        $recipient['student_name'],
                        $recipient['parent_name'],
                        $request->priority
                    );

                    // Envoyer le message texte
                    $result = $this->whatsAppService->sendMessage(
                        $recipient['phone'],
                        $personalizedMessage
                    );

                    if ($result) {
                        $recipients[$index]['status'] = 'sent';
                        $recipients[$index]['sent_at'] = now()->toDateTimeString();
                        $successCount++;

                        // Envoyer les pièces jointes si présentes
                        if (!empty($attachmentUrls)) {
                            foreach ($attachmentUrls as $attachment) {
                                try {
                                    $caption = "📄 {$attachment['name']}\n📎 Pièce jointe - {$request->title}";
                                    $this->whatsAppService->sendDocumentMessage(
                                        $recipient['phone'],
                                        $attachment['url'],
                                        $attachment['name'],
                                        $caption
                                    );
                                } catch (\Exception $e) {
                                    Log::error('Erreur envoi pièce jointe WhatsApp', [
                                        'phone' => $recipient['phone'],
                                        'attachment' => $attachment['name'],
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }
                    } else {
                        $recipients[$index]['status'] = 'failed';
                        $recipients[$index]['error'] = 'Échec envoi WhatsApp';
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $recipients[$index]['status'] = 'failed';
                    $recipients[$index]['error'] = $e->getMessage();
                    $failCount++;

                    Log::error('Erreur envoi message WhatsApp', [
                        'phone' => $recipient['phone'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Créer le log de communication
            $communicationLog = CommunicationLog::create([
                'admin_id' => $admin->id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'send_to_type' => $sendToType,
                'send_to_class_id' => $sendToClassId,
                'send_to_student_id' => $sendToStudentId,
                'total_recipients' => count($recipients),
                'successful_sends' => $successCount,
                'failed_sends' => $failCount,
                'recipients_details' => $recipients,
                'has_attachments' => !empty($attachmentUrls),
                'attachments_count' => count($attachmentUrls)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} message(s) envoyé(s) avec succès via WhatsApp" .
                            ($failCount > 0 ? " ({$failCount} échec(s))" : ''),
                'data' => [
                    'log_id' => $communicationLog->id,
                    'total_recipients' => count($recipients),
                    'successful_sends' => $successCount,
                    'failed_sends' => $failCount,
                    'attachments_sent' => count($attachmentUrls)
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la communication',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater le message WhatsApp avec personnalisation
     */
    private function formatWhatsAppMessage($title, $message, $studentName, $parentName, $priority)
    {
        $priorityIcon = match($priority) {
            'urgent' => '🚨 *URGENT*',
            'high' => '⚠️ *PRIORITÉ ÉLEVÉE*',
            'normal' => '📢 *INFORMATION*',
            'low' => '💬 *NOTE*',
            default => '📢 *NOTIFICATION*'
        };

        $schoolName = 'COLLÈGE POLYVALENT BILINGUE DE DOUALA';

        return "{$priorityIcon} - {$schoolName}\n\n" .
               "📋 *{$title}*\n\n" .
               "👤 *Élève:* {$studentName}\n" .
               "👨‍👩‍👧 *À l'attention de:* {$parentName}\n\n" .
               "{$message}\n\n" .
               "📅 *Envoyé le:* " . now()->setTimezone('Africa/Douala')->format('d/m/Y à H:i') . "\n\n" .
               "📱 Message automatique du système de gestion scolaire.";
    }

    /**
     * Obtenir les étudiants pour le select
     */
    public function getStudents()
    {
        try {
            $students = Student::with('classSeries.schoolClass')
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'class_series_id', 'parent_phone', 'mother_phone')
                ->orderBy('first_name')
                ->get()
                ->map(function ($student) {
                    $className = 'N/A';
                    if ($student->classSeries && $student->classSeries->first()) {
                        $className = $student->classSeries->first()->schoolClass->name ?? 'N/A';
                    }

                    $hasParentPhone = !empty($student->parent_phone);
                    $hasMotherPhone = !empty($student->mother_phone);

                    return [
                        'id' => $student->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'class' => $className,
                        'has_parent_contact' => $hasParentPhone || $hasMotherPhone,
                        'contacts_count' => ($hasParentPhone ? 1 : 0) + ($hasMotherPhone ? 1 : 0)
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
                ->get()
                ->map(function($class) {
                    // Compter les étudiants avec au moins un numéro de parent
                    $studentsCount = Student::whereHas('classSeries', function($query) use ($class) {
                        $query->where('class_id', $class->id);
                    })
                    ->where('is_active', true)
                    ->where(function($query) {
                        $query->whereNotNull('parent_phone')
                              ->orWhereNotNull('mother_phone');
                    })
                    ->count();

                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'students_with_contacts' => $studentsCount
                    ];
                });

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
     * Méthode maintenue pour compatibilité (redirige vers getStudents)
     */
    public function getParents()
    {
        return $this->getStudents();
    }

    /**
     * Supprimer un log de communication
     */
    public function destroy($id)
    {
        try {
            $log = CommunicationLog::findOrFail($id);
            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Communication supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uploader un fichier sur un service temporaire accessible publiquement
     */
    private function uploadToTemporaryService($filePath, $filename)
    {
        try {
            Log::info('Tentative d\'upload sur file.io', ['filename' => $filename]);

            // Utiliser file.io - service gratuit sans clé API
            $response = \Illuminate\Support\Facades\Http::attach(
                'file',
                file_get_contents($filePath),
                $filename
            )->post('https://file.io', [
                'expires' => '1w' // Expire dans 1 semaine
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['success'] && isset($responseData['link'])) {
                    Log::info('Fichier uploadé sur file.io avec succès', [
                        'url' => $responseData['link']
                    ]);
                    return $responseData['link'];
                }
            }

            Log::warning('Échec upload file.io', ['response' => $response->body()]);

            // Fallback: essayer tmpfiles.org
            return $this->uploadToTmpFiles($filePath, $filename);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload sur file.io', [
                'error' => $e->getMessage()
            ]);
            // Essayer tmpfiles en fallback
            return $this->uploadToTmpFiles($filePath, $filename);
        }
    }

    /**
     * Upload sur tmpfiles.org (fallback)
     */
    private function uploadToTmpFiles($filePath, $filename)
    {
        try {
            Log::info('Tentative d\'upload sur tmpfiles.org', ['filename' => $filename]);

            $response = \Illuminate\Support\Facades\Http::attach(
                'file',
                file_get_contents($filePath),
                $filename
            )->post('https://tmpfiles.org/api/v1/upload');

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['data']['url'])) {
                    // tmpfiles retourne une URL du type: https://tmpfiles.org/123/file.pdf
                    // Il faut la convertir en URL de téléchargement direct
                    $url = str_replace('tmpfiles.org/', 'tmpfiles.org/dl/', $responseData['data']['url']);
                    Log::info('Fichier uploadé sur tmpfiles.org avec succès', ['url' => $url]);
                    return $url;
                }
            }

            Log::warning('Échec upload tmpfiles.org', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload sur tmpfiles.org', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtenir les statistiques
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => CommunicationLog::count(),
                'today' => CommunicationLog::whereDate('created_at', today())->count(),
                'total_sent' => CommunicationLog::sum('successful_sends'),
                'total_failed' => CommunicationLog::sum('failed_sends'),
                'by_priority' => [
                    'urgent' => CommunicationLog::where('priority', 'urgent')->count(),
                    'high' => CommunicationLog::where('priority', 'high')->count(),
                    'normal' => CommunicationLog::where('priority', 'normal')->count(),
                    'low' => CommunicationLog::where('priority', 'low')->count()
                ],
                'by_type' => [
                    'general' => CommunicationLog::where('type', 'general')->count(),
                    'attendance' => CommunicationLog::where('type', 'attendance')->count(),
                    'academic' => CommunicationLog::where('type', 'academic')->count(),
                    'behavior' => CommunicationLog::where('type', 'behavior')->count(),
                    'payment' => CommunicationLog::where('type', 'payment')->count(),
                    'event' => CommunicationLog::where('type', 'event')->count()
                ],
                'students_with_contacts' => Student::where('is_active', true)
                    ->where(function($query) {
                        $query->whereNotNull('parent_phone')
                              ->orWhereNotNull('mother_phone');
                    })->count()
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
