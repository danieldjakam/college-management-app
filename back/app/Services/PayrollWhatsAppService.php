<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use App\Models\PayrollPeriod;
use App\Models\SalaryCut;
use App\Models\Payslip;
use App\Models\PayrollWhatsappNotification;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayrollWhatsAppService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = SchoolSetting::getSettings();
    }

    /**
     * Envoyer une notification de coupure de salaire
     */
    public function sendSalaryCutNotification(SalaryCut $salaryCut): array
    {
        $employee = $salaryCut->employee;
        
        if (!$employee->telephone_whatsapp) {
            return [
                'success' => false,
                'message' => 'Numéro WhatsApp non disponible pour cet employé'
            ];
        }

        $message = $salaryCut->getNotificationMessage();
        
        // Créer l'enregistrement de notification
        $notification = PayrollWhatsappNotification::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $salaryCut->period_id,
            'type' => 'salary_cut',
            'message' => $message,
            'telephone' => $employee->telephone_whatsapp,
            'statut' => 'pending'
        ]);

        // Envoyer via WhatsApp
        $result = $this->sendWhatsAppMessage($employee->telephone_whatsapp, $message);
        
        if ($result['success']) {
            $notification->markAsSent();
            $salaryCut->markNotificationSent();
            
            Log::info("Notification de coupure envoyée", [
                'employee_id' => $employee->id,
                'salary_cut_id' => $salaryCut->id,
                'telephone' => $employee->telephone_whatsapp
            ]);
        } else {
            $notification->markAsFailed($result['message']);
            
            Log::error("Échec envoi notification de coupure", [
                'employee_id' => $employee->id,
                'salary_cut_id' => $salaryCut->id,
                'error' => $result['message']
            ]);
        }

        return $result;
    }

    /**
     * Envoyer les notifications massives de salaires disponibles
     */
    public function sendSalaryAvailableNotifications(PayrollPeriod $period): array
    {
        $payslips = $period->payslips()->with('employee')->get();
        $results = [
            'total' => $payslips->count(),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            
            if (!$employee->telephone_whatsapp) {
                $results['skipped']++;
                $results['details'][] = [
                    'employee' => $employee->nom_complet,
                    'status' => 'skipped',
                    'reason' => 'Numéro WhatsApp non disponible'
                ];
                continue;
            }

            $message = $payslip->getSalaryAvailableMessage();
            
            // Créer l'enregistrement de notification
            $notification = PayrollWhatsappNotification::create([
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'type' => 'salary_available',
                'message' => $message,
                'telephone' => $employee->telephone_whatsapp,
                'statut' => 'pending'
            ]);

            // Envoyer via WhatsApp
            $result = $this->sendWhatsAppMessage($employee->telephone_whatsapp, $message);
            
            if ($result['success']) {
                $notification->markAsSent();
                $results['sent']++;
                $results['details'][] = [
                    'employee' => $employee->nom_complet,
                    'status' => 'sent',
                    'telephone' => $employee->telephone_whatsapp
                ];
                
                Log::info("Notification salaire disponible envoyée", [
                    'employee_id' => $employee->id,
                    'period_id' => $period->id,
                    'telephone' => $employee->telephone_whatsapp
                ]);
            } else {
                $notification->markAsFailed($result['message']);
                $results['failed']++;
                $results['details'][] = [
                    'employee' => $employee->nom_complet,
                    'status' => 'failed',
                    'error' => $result['message']
                ];
                
                Log::error("Échec envoi notification salaire disponible", [
                    'employee_id' => $employee->id,
                    'period_id' => $period->id,
                    'error' => $result['message']
                ]);
            }

            // Petit délai entre les envois pour éviter la surcharge
            usleep(500000); // 0.5 seconde
        }

        // Marquer les notifications comme envoyées pour la période
        if ($results['sent'] > 0) {
            $period->markNotificationsSent();
        }

        Log::info("Notifications massives terminées", [
            'period_id' => $period->id,
            'total' => $results['total'],
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped']
        ]);

        return $results;
    }

    /**
     * Envoyer un message WhatsApp individuel
     */
    private function sendWhatsAppMessage(string $telephone, string $message): array
    {
        try {
            // Vérifier la configuration WhatsApp
            if (!$this->isWhatsAppConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Configuration WhatsApp incomplète'
                ];
            }

            // Utiliser la même logique que le service existant
            $result = $this->sendMessage($telephone, $message);
            
            return [
                'success' => $result,
                'message' => $result ? 'Message envoyé avec succès' : 'Échec envoi message'
            ];
        } catch (\Exception $e) {
            Log::error("Erreur envoi WhatsApp paie", [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un message WhatsApp (copie de la logique du WhatsAppService existant)
     */
    protected function sendMessage($phoneNumber, $message)
    {
        try {
            // Si aucune API n'est configurée, simuler l'envoi
            if (!$this->settings->whatsapp_api_url || !$this->settings->whatsapp_instance_id || !$this->settings->whatsapp_token) {
                Log::info('Simulation envoi WhatsApp paie', [
                    'to' => $phoneNumber,
                    'message' => $message
                ]);
                return true;
            }

            // Construction de l'URL selon la logique existante
            $url = "https://api.ultramsg.com/instance{$this->settings->whatsapp_instance_id}/messages/chat";
            
            // Headers selon la logique existante
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            // Paramètres selon la logique existante
            $params = [
                'token' => $this->settings->whatsapp_token,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'body' => $message
            ];

            // Utilisation de Http::asForm() pour envoyer en application/x-www-form-urlencoded
            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info('Réponse UltraMsg reçue (paie)', [
                    'response' => $responseBody
                ]);
                
                // UltraMsg peut retourner du texte ou du JSON selon le cas
                $responseData = json_decode($responseBody, true);
                
                if ($responseData && isset($responseData['sent']) && $responseData['sent'] === 'true') {
                    Log::info('Message WhatsApp paie envoyé avec succès via UltraMsg', [
                        'to' => $phoneNumber,
                        'message_id' => $responseData['id'] ?? null,
                        'response' => $responseData
                    ]);
                    return true;
                } else {
                    // Même si pas de JSON valide, considérer comme succès si HTTP 200
                    Log::info('Message WhatsApp paie probablement envoyé via UltraMsg', [
                        'to' => $phoneNumber,
                        'response' => $responseBody
                    ]);
                    return true;
                }
            } else {
                Log::error('Erreur HTTP lors de l\'envoi WhatsApp paie via UltraMsg', [
                    'to' => $phoneNumber,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi WhatsApp paie via UltraMsg', [
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
        return $this->settings->whatsapp_notifications_enabled && 
               $this->settings->whatsapp_notification_number &&
               $this->settings->whatsapp_api_url &&
               $this->settings->whatsapp_instance_id &&
               $this->settings->whatsapp_token;
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
     * Nettoyer le numéro de téléphone
     */
    private function cleanPhoneNumber(string $phone): ?string
    {
        // Supprimer tous les caractères non numériques
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        if (empty($clean)) {
            return null;
        }
        
        // Si le numéro commence par 0, le remplacer par 237 (indicatif Cameroun)
        if (substr($clean, 0, 1) === '0') {
            $clean = '237' . substr($clean, 1);
        }
        
        // Si le numéro ne commence pas par 237, l'ajouter
        if (substr($clean, 0, 3) !== '237') {
            $clean = '237' . $clean;
        }
        
        // Vérifier la longueur (237 + 9 chiffres = 12 au total)
        if (strlen($clean) !== 12) {
            return null;
        }
        
        return $clean;
    }

    /**
     * Renvoyer une notification échouée
     */
    public function retryNotification(PayrollWhatsappNotification $notification): array
    {
        if (!$notification->canRetry()) {
            return [
                'success' => false,
                'message' => 'Cette notification ne peut pas être renvoyée'
            ];
        }

        // Marquer comme en attente
        $notification->retry();
        
        // Renvoyer le message
        $result = $this->sendWhatsAppMessage($notification->telephone, $notification->message);
        
        if ($result['success']) {
            $notification->markAsSent();
        } else {
            $notification->markAsFailed($result['message']);
        }
        
        return $result;
    }

    /**
     * Obtenir les statistiques des notifications pour une période
     */
    public function getNotificationStats(PayrollPeriod $period): array
    {
        $notifications = $period->whatsappNotifications();
        
        return [
            'total' => $notifications->count(),
            'sent' => $notifications->sent()->count(),
            'failed' => $notifications->failed()->count(),
            'pending' => $notifications->pending()->count(),
            'by_type' => [
                'salary_cut' => $notifications->byType('salary_cut')->count(),
                'salary_available' => $notifications->byType('salary_available')->count(),
            ]
        ];
    }

    /**
     * Tester la connexion à l'API WhatsApp
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl, [
                'token' => $this->apiToken,
                'to' => '237000000000', // Numéro de test
                'body' => 'Test de connexion API'
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Connexion OK' : 'Erreur de connexion'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
}