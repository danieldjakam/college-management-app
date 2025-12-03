<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée quand la génération de bulletins en lot est terminée
 */
class BulletinBatchCompleted extends Notification
{
    use Queueable;

    protected $result;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $result)
    {
        $this->result = $result;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database']; // Visible dans l'interface utilisateur
        // Ajouter 'mail' si vous voulez envoyer par email aussi
    }

    /**
     * Get the array representation of the notification (pour la database).
     */
    public function toArray($notifiable): array
    {
        if ($this->result['success']) {
            return [
                'type' => 'bulletin_batch_completed',
                'title' => 'Génération de bulletins terminée',
                'message' => "{$this->result['generated_count']} bulletins générés avec succès",
                'icon' => 'success',
                'data' => [
                    'generated_count' => $this->result['generated_count'],
                    'error_count' => $this->result['error_count'],
                    'duration' => $this->result['duration'],
                    'generated' => $this->result['generated'] ?? [],
                    'errors' => $this->result['errors'] ?? []
                ]
            ];
        } else {
            return [
                'type' => 'bulletin_batch_failed',
                'title' => 'Erreur génération bulletins',
                'message' => $this->result['error'],
                'icon' => 'error',
                'data' => []
            ];
        }
    }

    /**
     * Get the mail representation of the notification (optionnel).
     */
    public function toMail($notifiable): MailMessage
    {
        if ($this->result['success']) {
            return (new MailMessage)
                ->subject('Génération de bulletins terminée')
                ->greeting('Bonjour ' . $notifiable->name . ',')
                ->line("{$this->result['generated_count']} bulletins ont été générés avec succès.")
                ->line("Temps d'exécution: {$this->result['duration']} secondes")
                ->when($this->result['error_count'] > 0, function($mail) {
                    return $mail->line("⚠️ {$this->result['error_count']} erreurs rencontrées.");
                })
                ->action('Voir les bulletins', url('/bulletins'))
                ->line('Merci d\'utiliser notre plateforme!');
        } else {
            return (new MailMessage)
                ->subject('Erreur génération bulletins')
                ->error()
                ->greeting('Bonjour ' . $notifiable->name . ',')
                ->line('Une erreur est survenue lors de la génération des bulletins.')
                ->line('Erreur: ' . $this->result['error'])
                ->line('Veuillez réessayer ou contacter le support technique.');
        }
    }
}
