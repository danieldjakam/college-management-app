<?php

namespace App\Services;

use App\Models\SchoolSetting;
use App\Models\Need;
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
     * Envoyer une notification de changement de statut
     */
    public function sendStatusUpdateNotification(Need $need, $previousStatus)
    {
        $settings = SchoolSetting::getSettings();
        
        if (!$settings->whatsapp_notifications_enabled || 
            !$settings->whatsapp_notification_number ||
            !$settings->whatsapp_api_url ||
            !$settings->whatsapp_instance_id ||
            !$settings->whatsapp_token) {
            return false;
        }

        $message = $this->formatStatusUpdateMessage($need, $previousStatus);
        
        return $this->sendMessage($settings->whatsapp_notification_number, $message);
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
     * Formater le message pour un changement de statut
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
     * Envoyer un message WhatsApp via UltraMsg API
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

            // Construction de l'URL selon la documentation UltraMsg
            $url = rtrim($settings->whatsapp_api_url, '/') . '/instance' . $settings->whatsapp_instance_id . '/messages/chat';
            
            // Paramètres selon la documentation UltraMsg
            $params = [
                'token' => $settings->whatsapp_token,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'body' => $message,
                'priority' => '1',
                'referenceId' => uniqid('need_', true)
            ];

            $response = Http::asForm()->post($url, $params);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['sent']) && $responseData['sent'] === true) {
                    Log::info('Message WhatsApp envoyé avec succès via UltraMsg', [
                        'to' => $phoneNumber,
                        'message_id' => $responseData['id'] ?? null,
                        'response' => $responseData
                    ]);
                    return true;
                } else {
                    Log::error('Erreur UltraMsg - Message non envoyé', [
                        'to' => $phoneNumber,
                        'response' => $responseData
                    ]);
                    return false;
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

        return [
            'success' => $result,
            'message' => $result ? 'Test envoyé avec succès via UltraMsg' : 'Échec de l\'envoi du test. Vérifiez les logs pour plus de détails.'
        ];
    }
}