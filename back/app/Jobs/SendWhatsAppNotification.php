<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recipient;
    public $personalizedMessage;
    public $attachmentUrls;
    public $communicationLogId;

    /**
     * Nombre de tentatives
     */
    public $tries = 3;

    /**
     * Timeout en secondes
     */
    public $timeout = 60;

    /**
     * Nombre maximum de messages par période (anti-spam)
     */
    const MAX_MESSAGES_PER_PERIOD = 10;

    /**
     * Durée de la période en minutes (30 minutes)
     */
    const RATE_LIMIT_PERIOD_MINUTES = 30;

    /**
     * Create a new job instance.
     */
    public function __construct($recipient, $personalizedMessage, $attachmentUrls = [], $communicationLogId = null)
    {
        $this->recipient = $recipient;
        $this->personalizedMessage = $personalizedMessage;
        $this->attachmentUrls = $attachmentUrls;
        $this->communicationLogId = $communicationLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        // Vérifier le rate limiting (anti-spam)
        $cacheKey = 'whatsapp_rate_limit';
        $sentCount = Cache::get($cacheKey, 0);

        // Si on a atteint la limite, relâcher le job pour 30 minutes
        if ($sentCount >= self::MAX_MESSAGES_PER_PERIOD) {
            $delayInSeconds = self::RATE_LIMIT_PERIOD_MINUTES * 60;

            Log::warning('Rate limit atteint - Job relâché', [
                'sent_count' => $sentCount,
                'delay_minutes' => self::RATE_LIMIT_PERIOD_MINUTES,
                'phone' => $this->recipient['phone']
            ]);

            // Relâcher le job pour qu'il soit réexécuté après le délai
            $this->release($delayInSeconds);
            return;
        }

        try {
            Log::info('Envoi WhatsApp en cours', [
                'phone' => $this->recipient['phone'],
                'student' => $this->recipient['student_name'],
                'count_in_period' => $sentCount + 1
            ]);

            // Envoyer le message texte
            $result = $whatsAppService->sendMessage(
                $this->recipient['phone'],
                $this->personalizedMessage
            );

            if ($result) {
                // Incrémenter le compteur de rate limiting
                $newCount = Cache::increment($cacheKey);

                // Si c'est le premier message, définir l'expiration du cache
                if ($newCount === 1) {
                    Cache::put($cacheKey, 1, now()->addMinutes(self::RATE_LIMIT_PERIOD_MINUTES));
                }

                // Mettre à jour le statut dans le log de communication
                $this->updateRecipientStatus('sent', null);

                Log::info('Message WhatsApp envoyé avec succès', [
                    'phone' => $this->recipient['phone'],
                    'total_sent_in_period' => $newCount,
                    'remaining_before_pause' => self::MAX_MESSAGES_PER_PERIOD - $newCount
                ]);

                // Envoyer les pièces jointes si présentes
                if (!empty($this->attachmentUrls)) {
                    foreach ($this->attachmentUrls as $attachment) {
                        try {
                            $caption = "📄 {$attachment['name']}\n📎 Pièce jointe";
                            $whatsAppService->sendDocumentMessage(
                                $this->recipient['phone'],
                                $attachment['url'],
                                $attachment['name'],
                                $caption
                            );

                            Log::info('Pièce jointe envoyée', [
                                'phone' => $this->recipient['phone'],
                                'file' => $attachment['name']
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Erreur envoi pièce jointe WhatsApp', [
                                'phone' => $this->recipient['phone'],
                                'attachment' => $attachment['name'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            } else {
                $this->updateRecipientStatus('failed', 'Échec envoi WhatsApp');
                Log::error('Échec envoi WhatsApp', [
                    'phone' => $this->recipient['phone']
                ]);
            }
        } catch (\Exception $e) {
            $this->updateRecipientStatus('failed', $e->getMessage());

            Log::error('Erreur lors de l\'envoi WhatsApp', [
                'phone' => $this->recipient['phone'],
                'error' => $e->getMessage()
            ]);

            // Relancer l'exception pour que Laravel réessaye automatiquement
            throw $e;
        }
    }

    /**
     * Mettre à jour le statut du destinataire dans le log de communication
     */
    private function updateRecipientStatus($status, $error = null)
    {
        if (!$this->communicationLogId) {
            return;
        }

        try {
            $log = \App\Models\CommunicationLog::find($this->communicationLogId);
            if (!$log) {
                return;
            }

            $recipients = $log->recipients_details ?? [];

            // Trouver et mettre à jour le destinataire
            foreach ($recipients as $index => $recipient) {
                if ($recipient['phone'] === $this->recipient['phone'] &&
                    $recipient['student_id'] === $this->recipient['student_id']) {

                    $recipients[$index]['status'] = $status;
                    $recipients[$index]['sent_at'] = now()->toDateTimeString();

                    if ($error) {
                        $recipients[$index]['error'] = $error;
                    }

                    break;
                }
            }

            // Recalculer les compteurs
            $successCount = 0;
            $failCount = 0;
            foreach ($recipients as $recipient) {
                if (isset($recipient['status'])) {
                    if ($recipient['status'] === 'sent') {
                        $successCount++;
                    } elseif ($recipient['status'] === 'failed') {
                        $failCount++;
                    }
                }
            }

            $log->update([
                'recipients_details' => $recipients,
                'successful_sends' => $successCount,
                'failed_sends' => $failCount
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut destinataire', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gérer l'échec du job après toutes les tentatives
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job WhatsApp échoué définitivement', [
            'phone' => $this->recipient['phone'],
            'student' => $this->recipient['student_name'],
            'error' => $exception->getMessage()
        ]);

        $this->updateRecipientStatus('failed', 'Échec après ' . $this->tries . ' tentatives: ' . $exception->getMessage());
    }
}
