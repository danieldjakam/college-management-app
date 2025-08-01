<?php

namespace App\Services;

use App\Models\SchoolSetting;
use App\Models\Need;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        // Configuration dynamique basée sur les paramètres de l'école
        $settings = SchoolSetting::getSettings();
        $this->apiUrl = $settings->whatsapp_api_url;
        $this->apiKey = $settings->whatsapp_token;
    }

    /**
     * Envoyer une notification WhatsApp pour un nouveau besoin
     */
    public function sendNewNeedNotification(Need $need)
    {
        $settings = SchoolSetting::getSettings();
        
        if (!$settings->whatsapp_notifications_enabled || 
            !$settings->whatsapp_notification_number ||
            !$settings->whatsapp_api_url ||
            !$settings->whatsapp_instance_id ||
            !$settings->whatsapp_token) {
            Log::info('Configuration WhatsApp incomplète');
            return false;
        }

        $message = $this->formatNewNeedMessage($need);
        
        return $this->sendMessage($settings->whatsapp_notification_number, $message);
    }


    /**
     * Envoyer une notification de changement de statut à l'admin
     */
    public function sendStatusUpdateNotification(Need $need, $previousStatus)
    {
        $settings = SchoolSetting::getSettings();
        
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $message = $this->formatStatusUpdateMessage($need, $previousStatus);
        
        return $this->sendMessage($settings->whatsapp_notification_number, $message);
    }

    /**
     * Envoyer une notification de changement de statut au demandeur
     */
    public function sendStatusUpdateNotificationToRequester(Need $need, $previousStatus)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        // Vérifier que l'utilisateur a un numéro de téléphone
        if (!$need->user || !$need->user->contact) {
            Log::info('Utilisateur sans numéro de téléphone pour notification WhatsApp', [
                'need_id' => $need->id,
                'user_id' => $need->user_id
            ]);
            return false;
        }

        $message = $this->formatStatusUpdateMessageForRequester($need, $previousStatus);
        
        return $this->sendMessage($need->user->contact, $message);
    }

    /**
     * Envoyer une notification d'entrée/sortie aux parents
     */
    public function sendAttendanceNotification(Attendance $attendance)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $student = $attendance->student;
        
        // Vérifier que l'étudiant a un contact parent
        if (!$student || !$student->parent_phone) {
            Log::info('Étudiant sans contact parent pour notification WhatsApp', [
                'attendance_id' => $attendance->id,
                'student_id' => $student ? $student->id : null
            ]);
            return false;
        }

        $message = $this->formatAttendanceMessage($attendance);
        
        $result = $this->sendMessage($student->parent_phone, $message);
        
        if ($result) {
            // Marquer comme notifié
            $attendance->update([
                'parent_notified' => true,
                'notified_at' => now()
            ]);
        }
        
        return $result;
    }

    /**
     * Formater le message de notification d'entrée/sortie
     */
    protected function formatAttendanceMessage(Attendance $attendance)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $student = $attendance->student;
        
        // Gérer les absences
        if (!$attendance->is_present) {
            return "⚠️ *ABSENCE SIGNALÉE - {$schoolName}*\n\n" .
                   "👤 *Élève:* {$student->full_name}\n" .
                   "📚 *Classe:* " . ($student->classSeries->name ?? 'N/A') . "\n" .
                   "📅 *Date:* " . $attendance->attendance_date->format('d/m/Y') . "\n" .
                   "🕐 *Heure de contrôle:* " . $attendance->scanned_at->format('H:i') . "\n\n" .
                   "❌ Votre enfant n'était pas présent lors du contrôle de présence.\n\n" .
                   "📞 Veuillez contacter l'école si votre enfant devait être présent.\n\n" .
                   "📱 Notification automatique du système de gestion scolaire.";
        }
        
        // Messages pour présences (entrée/sortie)
        $eventIcon = $attendance->event_type === 'entry' ? '🟢' : '🔴';
        $eventText = $attendance->event_type === 'entry' ? 'ENTRÉE' : 'SORTIE';
        $eventMessage = $attendance->event_type === 'entry' 
            ? 'est arrivé(e) à l\'école' 
            : 'a quitté l\'école';
        
        return "{$eventIcon} *{$eventText} DÉTECTÉE - {$schoolName}*\n\n" .
               "👤 *Élève:* {$student->full_name}\n" .
               "📚 *Classe:* " . ($student->classSeries->name ?? 'N/A') . "\n" .
               "🕐 *Heure:* " . $attendance->scanned_at->format('H:i') . "\n" .
               "📅 *Date:* " . $attendance->attendance_date->format('d/m/Y') . "\n\n" .
               "ℹ️ Votre enfant {$eventMessage} à {$attendance->scanned_at->format('H:i')}.\n\n" .
               "📱 Notification automatique du système de gestion scolaire.";
    }

    /**
     * Formater le message pour un nouveau besoin
     */
    protected function formatNewNeedMessage(Need $need)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        
        return "🔔 *NOUVEAU BESOIN - {$schoolName}*\n\n" .
               "👤 *Demandeur:* {$need->user->name}\n" .
               "📝 *Besoin:* {$need->name}\n" .
               "💰 *Montant:* {$need->formatted_amount}\n" .
               "📄 *Description:* {$need->description}\n\n" .
               "📅 *Soumis le:* " . $need->created_at->format('d/m/Y à H:i') . "\n\n" .
               "⚠️ Ce besoin nécessite votre attention pour approbation/rejet.";
    }


    /**
     * Formater le message pour un changement de statut (admin)
     */
    protected function formatStatusUpdateMessage(Need $need, $previousStatus)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $statusIcon = $need->isApproved() ? '✅' : '❌';
        
        $approvedByName = $need->approvedBy ? $need->approvedBy->name : 'Système';
        $approvedDate = $need->approved_at ? $need->approved_at->format('d/m/Y à H:i') : 'N/A';
        $rejectionText = $need->rejection_reason ? "📝 *Motif du rejet:* {$need->rejection_reason}\n\n" : '';
        
        return "{$statusIcon} *BESOIN MIS À JOUR - {$schoolName}*\n\n" .
               "👤 *Demandeur:* {$need->user->name}\n" .
               "📝 *Besoin:* {$need->name}\n" .
               "💰 *Montant:* {$need->formatted_amount}\n\n" .
               "🔄 *Statut:* {$previousStatus} → *{$need->status_label}*\n" .
               "👨‍💼 *Traité par:* {$approvedByName}\n" .
               "📅 *Le:* {$approvedDate}\n\n" .
               $rejectionText .
               "ℹ️ Besoin traité dans le système de gestion.";
    }

    /**
     * Formater le message pour un changement de statut (demandeur)
     */
    protected function formatStatusUpdateMessageForRequester(Need $need, $previousStatus)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $statusIcon = $need->isApproved() ? '✅' : '❌';
        
        if ($need->isApproved()) {
            return "✅ *BONNE NOUVELLE !*\n\n" .
                   "Votre demande de besoin a été *APPROUVÉE* par l'administration de {$schoolName}.\n\n" .
                   "📝 *Besoin:* {$need->name}\n" .
                   "💰 *Montant:* {$need->formatted_amount}\n" .
                   "📅 *Approuvé le:* " . $need->approved_at->format('d/m/Y à H:i') . "\n\n" .
                   "🎉 Votre demande a été acceptée. Vous pouvez vous rapprocher de l'administration pour la suite des démarches.\n\n" .
                   "Merci pour votre confiance !";
        } else {
            $rejectionText = $need->rejection_reason ? "\n\n📝 *Motif:* {$need->rejection_reason}" : '';
            
            return "❌ *DEMANDE REJETÉE*\n\n" .
                   "Nous regrettons de vous informer que votre demande de besoin a été *REJETÉE* par l'administration de {$schoolName}.\n\n" .
                   "📝 *Besoin:* {$need->name}\n" .
                   "💰 *Montant:* {$need->formatted_amount}\n" .
                   "📅 *Rejeté le:* " . $need->approved_at->format('d/m/Y à H:i') . 
                   $rejectionText . "\n\n" .
                   "💡 N'hésitez pas à contacter l'administration pour plus d'informations ou à soumettre une nouvelle demande si nécessaire.";
        }
    }

    /**
     * Envoyer un message WhatsApp via UltraMsg API selon votre exemple d'intégration
     */
    protected function sendMessage($phoneNumber, $message)
    {
        try {
            $settings = SchoolSetting::getSettings();
            
            // Si aucune API n'est configurée, simuler l'envoi
            if (!$settings->whatsapp_api_url || !$settings->whatsapp_instance_id || !$settings->whatsapp_token) {
                Log::info('Simulation envoi WhatsApp', [
                    'to' => $phoneNumber,
                    'message' => $message
                ]);
                return true;
            }

            // Construction de l'URL selon votre exemple : https://api.ultramsg.com/instance97191/messages/chat
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat";
            
            // Headers selon votre exemple
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            // Paramètres selon votre exemple exact
            $params = [
                'token' => $settings->whatsapp_token, // vdnvcpgsd1veydwc dans votre exemple
                'to' => $this->formatPhoneNumber($phoneNumber), // +237676781795 dans votre exemple
                'body' => $message // Le message à envoyer
            ];

            // Utilisation de Http::asForm() pour envoyer en application/x-www-form-urlencoded
            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info('Réponse UltraMsg reçue', [
                    'response' => $responseBody
                ]);
                
                // UltraMsg peut retourner du texte ou du JSON selon le cas
                $responseData = json_decode($responseBody, true);
                
                if ($responseData && isset($responseData['sent']) && $responseData['sent'] === 'true') {
                    Log::info('Message WhatsApp envoyé avec succès via UltraMsg', [
                        'to' => $phoneNumber,
                        'message_id' => $responseData['id'] ?? null,
                        'response' => $responseData
                    ]);
                    return true;
                } else {
                    // Même si pas de JSON valide, considérer comme succès si HTTP 200
                    Log::info('Message WhatsApp probablement envoyé via UltraMsg', [
                        'to' => $phoneNumber,
                        'response' => $responseBody
                    ]);
                    return true;
                }
            } else {
                Log::error('Erreur HTTP lors de l\'envoi WhatsApp via UltraMsg', [
                    'to' => $phoneNumber,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi WhatsApp via UltraMsg', [
                'to' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Vérifier si WhatsApp est correctement configuré
     */
    protected function isWhatsAppConfigured()
    {
        $settings = SchoolSetting::getSettings();
        
        return $settings->whatsapp_notifications_enabled && 
               $settings->whatsapp_notification_number &&
               $settings->whatsapp_api_url &&
               $settings->whatsapp_instance_id &&
               $settings->whatsapp_token;
    }

    /**
     * Formater le numéro de téléphone au format international
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Supprimer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Si le numéro commence par 0, remplacer par +237 (Cameroun)
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '237' . substr($cleaned, 1);
        }
        
        // Si le numéro ne commence pas par +, l'ajouter
        if (substr($cleaned, 0, 1) !== '+') {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Tester la configuration WhatsApp
     */
    public function testConfiguration()
    {
        $settings = SchoolSetting::getSettings();
        
        if (!$settings->whatsapp_notifications_enabled || 
            !$settings->whatsapp_notification_number ||
            !$settings->whatsapp_api_url ||
            !$settings->whatsapp_instance_id ||
            !$settings->whatsapp_token) {
            return [
                'success' => false,
                'message' => 'Configuration WhatsApp incomplète. Vérifiez les paramètres API (URL, Instance ID, Token)'
            ];
        }

        $testMessage = "🧪 *TEST DE CONFIGURATION ULTRAMSG*\n\n" .
                      "Ce message confirme que les notifications WhatsApp sont correctement configurées pour " .
                      ($settings->school_name ?? 'votre école') . ".\n\n" .
                      "📅 " . now()->format('d/m/Y à H:i') . "\n\n" .
                      "✅ Configuration UltraMsg opérationnelle";

        $result = $this->sendMessage($settings->whatsapp_notification_number, $testMessage);

        if (!$result) {
            // Vérifier les logs pour donner un message d'erreur plus spécifique
            $recentLogs = $this->getRecentWhatsAppError();
            if (strpos($recentLogs, 'non-payment') !== false || strpos($recentLogs, 'Stopped') !== false) {
                return [
                    'success' => false,
                    'message' => '❌ Instance UltraMsg suspendue pour non-paiement. Veuillez renouveler votre abonnement UltraMsg.'
                ];
            } elseif (strpos($recentLogs, 'Path not found') !== false) {
                return [
                    'success' => false,
                    'message' => '❌ URL ou Instance ID incorrect. Vérifiez vos paramètres UltraMsg.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '❌ Échec de l\'envoi du test. Vérifiez votre configuration UltraMsg (Instance ID, Token, numéro de téléphone).'
                ];
            }
        }

        return [
            'success' => true,
            'message' => '✅ Test envoyé avec succès via UltraMsg'
        ];
    }

    /**
     * Récupérer la dernière erreur WhatsApp des logs
     */
    private function getRecentWhatsAppError()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile)) return '';
            
            $logs = file_get_contents($logFile);
            $lines = explode("\n", $logs);
            $recentLines = array_slice($lines, -50); // Dernières 50 lignes
            
            foreach (array_reverse($recentLines) as $line) {
                if (strpos($line, 'WhatsApp') !== false || strpos($line, 'UltraMsg') !== false) {
                    return $line;
                }
            }
            
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}