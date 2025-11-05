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
     * Envoyer une notification de paiement avec reçu PDF aux parents
     */
    public function sendPaymentNotification($payment)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $student = $payment->student;

        // Vérifier que l'étudiant a un contact parent
        if (!$student || !$student->parent_phone) {
            Log::info('Étudiant sans contact parent pour notification de paiement WhatsApp', [
                'payment_id' => $payment->id,
                'student_id' => $student ? $student->id : null
            ]);
            return false;
        }

        $message = $this->formatPaymentMessage($payment);

        // D'abord envoyer le message texte
        $textResult = $this->sendMessage($student->parent_phone, $message);

        // Ensuite générer et envoyer le PDF du reçu
        $pdfResult = false;
        try {
            $receiptPDF = $this->generateReceiptPDF($payment);
            if ($receiptPDF && isset($receiptPDF['url']) && isset($receiptPDF['filename'])) {
                $caption = "📄 Reçu de paiement N° {$payment->receipt_number}\n\nMerci pour votre confiance. 🙏";
                $pdfResult = $this->sendDocumentMessage(
                    $student->parent_phone,
                    $receiptPDF['url'],
                    $receiptPDF['filename'],
                    $caption
                );
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération/envoi du PDF du reçu', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        if ($textResult || $pdfResult) {
            Log::info('Notification de paiement WhatsApp envoyée', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'parent_phone' => $student->parent_phone,
                'text_sent' => $textResult,
                'pdf_sent' => $pdfResult
            ]);
        }

        return $textResult; // Au minimum le message texte doit passer
    }

    /**
     * Envoyer une notification d'alerte de stock faible
     */
    public function sendLowStockAlert($items)
    {
        $settings = SchoolSetting::getSettings();
        
        if (!$settings->whatsapp_notifications_enabled || 
            !$settings->whatsapp_notification_number ||
            !$settings->whatsapp_api_url ||
            !$settings->whatsapp_instance_id ||
            !$settings->whatsapp_token) {
            Log::info('Configuration WhatsApp incomplète pour alerte stock');
            return false;
        }

        $message = $this->formatLowStockMessage($items);
        
        return $this->sendMessage($settings->whatsapp_notification_number, $message);
    }

    /**
     * Envoyer une notification d'entrée/sortie au personnel
     */
    public function sendStaffAttendanceNotification($staffAttendance)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $user = $staffAttendance->user;
        
        // Vérifier que l'utilisateur a un numéro de téléphone
        if (!$user || !$user->contact) {
            Log::info('Personnel sans numéro de téléphone pour notification WhatsApp', [
                'attendance_id' => $staffAttendance->id,
                'user_id' => $user ? $user->id : null
            ]);
            return false;
        }

        $message = $this->formatStaffAttendanceMessage($staffAttendance);
        
        $result = $this->sendMessage($user->contact, $message);
        
        if ($result) {
            Log::info('Notification personnel envoyée avec succès', [
                'attendance_id' => $staffAttendance->id,
                'user_id' => $user->id,
                'phone' => $user->contact
            ]);
        }
        
        return $result;
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
     * Formater le message de notification de paiement
     */
    protected function formatPaymentMessage($payment)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $student = $payment->student;
        
        // Informations sur le paiement
        $paymentAmount = number_format($payment->total_amount, 0, ',', ' ') . ' FCFA';
        $paymentDate = $payment->payment_date->format('d/m/Y');
        $paymentTime = $payment->created_at->setTimezone('Africa/Douala')->format('H:i');
        
        // Informations sur les réductions si applicable
        $reductionText = '';
        if ($payment->has_reduction && $payment->reduction_amount > 0) {
            $reductionAmount = number_format($payment->reduction_amount, 0, ',', ' ') . ' FCFA';
            $reductionText = "\n💰 *Réduction appliquée:* {$reductionAmount}";
        }
        
        // Méthode de paiement
        $paymentMethodText = match($payment->payment_method) {
            'cash' => '💵 Espèces',
            'card' => '💳 Carte bancaire',
            'transfer' => '🏦 Virement',
            'check' => '📝 Chèque',
            'rame_physical' => '🎫 RAME (Physique)',
            default => $payment->payment_method
        };
        
        // Numéro de référence si disponible
        $referenceText = $payment->reference_number ? 
            "\n📄 *Référence:* {$payment->reference_number}" : '';
        
        return "✅ *PAIEMENT CONFIRMÉ - {$schoolName}*\n\n" .
               "👤 *Élève:* {$student->full_name}\n" .
               "📚 *Classe:* " . ($student->classSeries->name ?? 'N/A') . "\n" .
               "💳 *Méthode:* {$paymentMethodText}\n" .
               "💰 *Montant payé:* {$paymentAmount}" . $reductionText . "\n" .
               "📅 *Date:* {$paymentDate} à {$paymentTime}" . $referenceText . "\n\n" .
               "📄 *Reçu de paiement:* Un reçu détaillé a été généré.\n\n" .
               "✨ Merci pour votre confiance !\n\n" .
               "📱 Notification automatique du système de gestion scolaire.";
    }

    /**
     * Générer le reçu de paiement (version simplifiée pour WhatsApp)
     */
    protected function generatePaymentReceipt($payment)
    {
        // Cette méthode pourrait générer un PDF du reçu ou retourner du HTML
        // Pour l'instant, on retourne les informations essentielles
        $student = $payment->student;
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        
        return "REÇU DE PAIEMENT N° {$payment->id}\n" .
               "École: {$schoolName}\n" .
               "Élève: {$student->full_name}\n" .
               "Montant: " . number_format($payment->total_amount, 0, ',', ' ') . " FCFA\n" .
               "Date: " . $payment->payment_date->format('d/m/Y H:i');
    }

    /**
     * Formater le message de notification de présence du personnel
     */
    protected function formatStaffAttendanceMessage($staffAttendance)
    {
        $settings = SchoolSetting::getSettings();
        $schoolName = $settings->school_name ?? 'CAMPUS UNIVERSITAIRE DE BAFOUSSAM';
        $user = $staffAttendance->user;
        
        // Déterminer le titre et le sexe
        $title = $this->getPersonTitle($user);
        $eventType = $staffAttendance->event_type === 'entry' ? 'ENTRÉE' : 'SORTIE';
        
        // Format de la date et heure - Utiliser le fuseau horaire Africa/Douala
        $date = $staffAttendance->scanned_at->setTimezone('Africa/Douala')->format('Y-m-d');
        $time = $staffAttendance->scanned_at->setTimezone('Africa/Douala')->format('H:i:s');
        
        // Préparer le message de base
        $message = "*ATTENDANCE / QRCODE {$eventType}*\n\n" .
                   "{$schoolName}\n\n" .
                   "*{$title}* : {$user->name}\n\n" .
                   "LE : {$date}\n\n" .
                   "HEURE DE {$eventType}: {$time}\n\n" .
                   "VOTRE POINTAGE A ÉTÉ ENREGISTRÉ. NOUS VOUS REMERCIONS POUR VOS SERVICES.";
        
        return $message;
    }

    /**
     * Déterminer le titre de civilité
     */
    protected function getPersonTitle($user)
    {
        // Vous pouvez ajouter une logique plus sophistiquée si vous avez un champ sexe/genre
        // Pour l'instant, utilisation de "M./Mme/Mlle"
        return "M./Mme/Mlle";
    }

    /**
     * Formater le message de notification d'entrée/sortie
     */
    protected function formatAttendanceMessage(Attendance $attendance)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $student = $attendance->student;
        
        // Gérer les absences (uniquement si ce n'est pas un scan de sortie)
        if (!$attendance->is_present && $attendance->event_type !== 'exit') {
            return "⚠️ *ABSENCE SIGNALÉE - {$schoolName}*\n\n" .
                   "👤 *Élève:* {$student->full_name}\n" .
                   "📚 *Classe:* " . ($student->classSeries->name ?? 'N/A') . "\n" .
                   "📅 *Date:* " . $attendance->attendance_date->format('d/m/Y') . "\n" .
                   "🕐 *Heure de contrôle:* " . $attendance->scanned_at->setTimezone('Africa/Douala')->format('H:i') . "\n\n" .
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
               "🕐 *Heure:* " . $attendance->scanned_at->setTimezone('Africa/Douala')->format('H:i') . "\n" .
               "📅 *Date:* " . $attendance->attendance_date->format('d/m/Y') . "\n\n" .
               "ℹ️ Votre enfant {$eventMessage} à {$attendance->scanned_at->setTimezone('Africa/Douala')->format('H:i')}.\n\n" .
               "📱 Notification automatique du système de gestion scolaire.";
    }

    /**
     * Formater le message pour une alerte de stock faible
     */
    protected function formatLowStockMessage($items)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        $itemCount = is_countable($items) ? count($items) : 0;
        
        if ($itemCount === 0) {
            return "✅ *STOCK NORMAL - {$schoolName}*\n\n" .
                   "Aucun article en stock faible actuellement.\n\n" .
                   "📱 Notification automatique du système de gestion d'inventaire.";
        }
        
        $message = "⚠️ *ALERTE STOCK FAIBLE - {$schoolName}*\n\n" .
                   "📦 *{$itemCount}* article" . ($itemCount > 1 ? 's' : '') . " nécessite" . ($itemCount > 1 ? 'nt' : '') . " un réapprovisionnement :\n\n";
        
        $count = 0;
        foreach ($items as $item) {
            if ($count >= 10) { // Limiter à 10 articles pour éviter des messages trop longs
                $remaining = $itemCount - $count;
                $message .= "➕ ... et {$remaining} autre" . ($remaining > 1 ? 's' : '') . " article" . ($remaining > 1 ? 's' : '') . "\n\n";
                break;
            }
            
            $stockLevel = $item->quantite <= 0 ? '❌ RUPTURE' : 
                         ($item->quantite <= $item->quantite_min / 2 ? '🔴 CRITIQUE' : '🟠 FAIBLE');
            
            $message .= "• *{$item->nom}* ({$item->categorie})\n" .
                       "  📍 {$item->localisation}\n" .
                       "  📊 Stock: {$item->quantite}/{$item->quantite_min} - {$stockLevel}\n\n";
            $count++;
        }
        
        $message .= "🎯 *Action requise :*\n" .
                   "• Vérifiez les stocks physiques\n" .
                   "• Planifiez les achats nécessaires\n" .
                   "• Contactez les responsables concernés\n\n" .
                   "📅 Alerte générée le " . now()->setTimezone('Africa/Douala')->format('d/m/Y à H:i') . "\n\n" .
                   "📱 Notification automatique du système de gestion d'inventaire.";
        
        return $message;
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
               "📅 *Soumis le:* " . $need->created_at->setTimezone('Africa/Douala')->format('d/m/Y à H:i') . "\n\n" .
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
     * Générer une image du reçu de paiement
     */
    protected function generateReceiptImage($payment)
    {
        try {
            // Utiliser la nouvelle méthode pour générer le reçu parent
            $paymentController = app()->make(\App\Http\Controllers\PaymentController::class);
            $receiptData = $paymentController->generateParentReceipt($payment->id);

            if (!$receiptData['success']) {
                Log::error('Impossible de générer le HTML du reçu parent', ['payment_id' => $payment->id]);
                return null;
            }

            $receiptHtml = $receiptData['html'];

            // Utiliser wkhtmltoimage ou une alternative pour convertir HTML en image
            return $this->createReceiptImageFile($receiptHtml, $payment->id);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de l\'image du reçu', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Générer le PDF du reçu et retourner son URL publique
     */
    protected function generateReceiptPDF($payment)
    {
        try {
            // Charger les informations nécessaires
            $payment->load(['student.classSeries.schoolClass.level', 'paymentDetails.paymentTranche']);
            $schoolSettings = \App\Models\SchoolSetting::getSettings();

            // Générer le HTML du reçu
            $paymentController = app()->make(\App\Http\Controllers\PaymentController::class);

            // Utiliser la réflexion pour accéder à la méthode privée
            $reflection = new \ReflectionClass($paymentController);
            $method = $reflection->getMethod('generateReceiptHtmlForPDF');
            $method->setAccessible(true);
            $receiptHtml = $method->invoke($paymentController, $payment, $schoolSettings);

            // Créer le nom de fichier
            $filename = "Recu_{$payment->receipt_number}_" . time() . ".pdf";
            $pdfPath = public_path("receipts/pdf/{$filename}");

            // Créer le dossier s'il n'existe pas
            $pdfDir = public_path('receipts/pdf');
            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            // Générer le PDF avec DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($receiptHtml);
            $pdf->setPaper('A4', 'portrait');
            $pdf->save($pdfPath);

            // Retourner l'URL publique
            $publicUrl = url("receipts/pdf/{$filename}");

            // En environnement local, uploader sur un service temporaire
            if (app()->environment('local') && strpos($publicUrl, 'localhost') !== false) {
                Log::info('Environnement local détecté, upload sur service temporaire...');
                $uploadedUrl = $this->uploadToTemporaryFileService($pdfPath, $filename);
                if ($uploadedUrl) {
                    $publicUrl = $uploadedUrl;
                    Log::info('PDF uploadé sur service temporaire', ['url' => $uploadedUrl]);
                }
            }

            Log::info('PDF du reçu généré avec succès', [
                'payment_id' => $payment->id,
                'filename' => $filename,
                'url' => $publicUrl
            ]);

            return [
                'url' => $publicUrl,
                'filename' => $filename,
                'path' => $pdfPath
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF du reçu', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Créer le fichier image du reçu à partir du HTML
     */
    protected function createReceiptImageFile($html, $paymentId)
    {
        try {
            // Créer un nom de fichier unique
            $filename = "receipt_payment_{$paymentId}_" . time() . ".png";
            $imagePath = public_path("receipts/{$filename}");
            
            // Créer le dossier s'il n'existe pas
            $receiptDir = public_path('receipts');
            if (!file_exists($receiptDir)) {
                mkdir($receiptDir, 0755, true);
            }
            
            // Utiliser la méthode de conversion HTML vers image
            $this->convertHtmlToImage($html, $imagePath);
            
            // Retourner l'URL publique
            // Note: Pour que UltraMsg puisse accéder à l'image, l'URL doit être publiquement accessible
            // En local, utiliser ngrok ou héberger sur un serveur public
            $publicUrl = url("receipts/{$filename}");
            
            // Alternative : uploader sur un service cloud (à implémenter si nécessaire)
            // return $this->uploadToCloudService($imagePath, $filename);
            
            // Pour les tests locaux, essayons d'uploader sur un service temporaire
            if (app()->environment('local') && parse_url($publicUrl, PHP_URL_HOST) === 'localhost') {
                $uploadedUrl = $this->uploadToTempImageService($imagePath);
                if ($uploadedUrl) {
                    return $uploadedUrl;
                }
            }
            
            return $publicUrl;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la conversion HTML vers image', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function convertHtmlToImage($html, $imagePath)
    {
        try {
            // Méthode 1: Essayer wkhtmltoimage si disponible
            if ($this->isWkhtmltoImageAvailable()) {
                return $this->convertWithWkhtmltoimage($html, $imagePath);
            }
            
            // Méthode 2: Fallback vers image basique
            return $this->createFallbackImage($html, $imagePath);
            
        } catch (\Exception $e) {
            Log::error('Erreur conversion HTML vers image', ['error' => $e->getMessage()]);
            // Fallback vers image basique en cas d'erreur
            return $this->createFallbackImage($html, $imagePath);
        }
    }

    protected function isWkhtmltoImageAvailable()
    {
        $output = shell_exec('which wkhtmltoimage 2>/dev/null');
        return !empty($output);
    }

    protected function convertWithWkhtmltoimage($html, $imagePath)
    {
        // Créer un fichier HTML temporaire
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.html';
        file_put_contents($tempHtmlFile, $html);
        
        try {
            // Commande wkhtmltoimage avec options pour un rendu optimal
            $command = sprintf(
                'wkhtmltoimage --width 600 --height 800 --format png --quality 95 "%s" "%s" 2>/dev/null',
                $tempHtmlFile,
                $imagePath
            );
            
            $exitCode = 0;
            $output = [];
            exec($command, $output, $exitCode);
            
            if ($exitCode === 0 && file_exists($imagePath)) {
                Log::info('Image générée avec wkhtmltoimage', ['path' => $imagePath]);
                return true;
            } else {
                Log::warning('Échec wkhtmltoimage', ['exit_code' => $exitCode, 'output' => implode("\n", $output)]);
                return false;
            }
        } finally {
            // Nettoyer le fichier temporaire
            if (file_exists($tempHtmlFile)) {
                unlink($tempHtmlFile);
            }
        }
    }

    /**
     * Créer une image simple du reçu (version basique sans HTML)
     */
    protected function createFallbackImage($html, $imagePath)
    {
        // Extraire les informations du HTML pour créer une image complète
        // Récupérer les données de paiement depuis le HTML
        preg_match('/Reçu N° ([^<]+)/', $html, $receiptMatches);
        preg_match('/Élève:<\/span>\s*<span[^>]*>([^<]+)/', $html, $studentMatches);
        preg_match('/Classe:<\/span>\s*<span[^>]*>([^<]+)/', $html, $classMatches);
        preg_match('/TOTAL PAYÉ: ([^<]+)/', $html, $totalMatches);
        
        $receiptNumber = $receiptMatches[1] ?? 'N/A';
        $studentName = $studentMatches[1] ?? 'N/A';
        $className = $classMatches[1] ?? 'N/A';
        $totalAmount = $totalMatches[1] ?? 'N/A';
        
        $width = 600;
        $height = 800;
        
        // Créer l'image
        $image = imagecreatetruecolor($width, $height);
        
        // Définir les couleurs
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $green = imagecolorallocate($image, 46, 139, 87); // Couleur verte du design
        $lightGray = imagecolorallocate($image, 240, 240, 240);
        $darkGray = imagecolorallocate($image, 100, 100, 100);
        
        // Remplir le fond
        imagefill($image, 0, 0, $white);
        
        // En-tête coloré
        imagefilledrectangle($image, 0, 0, $width, 120, $green);
        
        // Titre
        $y = 30;
        imagestring($image, 5, 180, $y, 'RECU DE PAIEMENT', $white);
        $y += 40;
        imagestring($image, 3, 120, $y, 'COLLEGE POLYVALENT BILINGUE DOUALA', $white);
        $y += 25;
        imagestring($image, 2, 220, $y, 'Annee academique 2025-2026', $white);
        
        // Corps du reçu
        $y = 150;
        
        // Rectangle pour les infos
        imagefilledrectangle($image, 30, $y, $width-30, $y+50, $lightGray);
        $y += 15;
        imagestring($image, 4, 50, $y, "Recu N: " . $receiptNumber, $black);
        $y += 20;
        imagestring($image, 3, 50, $y, date('d/m/Y H:i'), $darkGray);
        
        $y += 60;
        imagestring($image, 4, 50, $y, "Eleve: " . $studentName, $black);
        $y += 30;
        imagestring($image, 3, 50, $y, "Classe: " . $className, $black);
        
        $y += 60;
        // Rectangle pour le montant
        imagefilledrectangle($image, 30, $y, $width-30, $y+40, $green);
        $y += 15;
        imagestring($image, 4, 50, $y, "TOTAL PAYE: " . $totalAmount, $white);
        
        $y += 80;
        imagestring($image, 2, 50, $y, "Paiement confirme et valide", $darkGray);
        $y += 20;
        imagestring($image, 2, 50, $y, "Conservez ce recu precieusement", $darkGray);
        
        // Pied de page
        $y = $height - 60;
        imagestring($image, 2, 50, $y, "Tel: 233 43 25 47 | Email: contact@cpb-douala.com", $darkGray);
        $y += 20;
        imagestring($image, 1, 180, $y, "Recu envoye automatiquement par WhatsApp", $darkGray);
        
        // Sauvegarder l'image
        imagepng($image, $imagePath);
        imagedestroy($image);
        
        return true;
    }

    /**
     * Uploader l'image sur un service temporaire accessible publiquement
     */
    /**
     * Upload un PDF sur un service temporaire (file.io)
     */
    protected function uploadToTemporaryFileService($filePath, $filename)
    {
        try {
            Log::info('Tentative d\'upload du PDF sur file.io', ['filename' => $filename]);

            // Utiliser file.io - service gratuit sans clé API
            $response = Http::attach(
                'file',
                file_get_contents($filePath),
                $filename
            )->post('https://file.io', [
                'expires' => '1d' // Expire dans 1 jour
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['success'] && isset($responseData['link'])) {
                    Log::info('PDF uploadé sur file.io avec succès', [
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
    protected function uploadToTmpFiles($filePath, $filename)
    {
        try {
            Log::info('Tentative d\'upload du PDF sur tmpfiles.org', ['filename' => $filename]);

            $response = Http::attach(
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
                    Log::info('PDF uploadé sur tmpfiles.org avec succès', ['url' => $url]);
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

    protected function uploadToTempImageService($imagePath)
    {
        try {
            // Utiliser un service comme imgbb.com, postimages.org, ou similaire
            // Ici, nous utilisons imgbb.com comme exemple (nécessite une clé API gratuite)

            // Encoder l'image en base64
            $imageData = base64_encode(file_get_contents($imagePath));

            // Utiliser un service d'upload temporaire (exemple avec imgbb)
            // Vous pouvez créer un compte gratuit sur imgbb.com et obtenir une clé API
            $apiKey = env('IMGBB_API_KEY', null);

            if (!$apiKey) {
                Log::info('Aucune clé API imgbb configurée, utilisation de l\'URL locale');
                return null;
            }

            $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
                'key' => $apiKey,
                'image' => $imageData,
                'expiration' => 86400 // Expire dans 24h
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['data']['url'])) {
                    Log::info('Image uploadée sur imgbb avec succès', [
                        'url' => $responseData['data']['url']
                    ]);
                    return $responseData['data']['url'];
                }
            }

            Log::warning('Échec upload imgbb', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload sur service temporaire', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Envoyer une image via WhatsApp UltraMsg
     */
    public function sendImageMessage($phoneNumber, $imageUrl, $caption = '')
    {
        try {
            $settings = SchoolSetting::getSettings();
            
            if (!$settings->whatsapp_api_url || !$settings->whatsapp_instance_id || !$settings->whatsapp_token) {
                Log::info('Configuration WhatsApp incomplète pour envoi d\'image');
                return false;
            }

            // CORRECTION: URL pour envoyer des images via UltraMsg
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/image";
            
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $params = [
                'token' => $settings->whatsapp_token,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'image' => $imageUrl,
                'caption' => $caption
            ];

            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info('Image WhatsApp envoyée via UltraMsg', [
                    'to' => $phoneNumber,
                    'image_url' => $imageUrl,
                    'response' => $responseBody
                ]);
                return true;
            } else {
                Log::error('Erreur HTTP lors de l\'envoi d\'image WhatsApp via UltraMsg', [
                    'to' => $phoneNumber,
                    'image_url' => $imageUrl,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi d\'image WhatsApp via UltraMsg', [
                'to' => $phoneNumber,
                'image_url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer un document PDF via WhatsApp
     */
    public function sendDocumentMessage($phoneNumber, $documentUrl, $filename, $caption = '')
    {
        try {
            $settings = SchoolSetting::getSettings();

            if (!$settings->whatsapp_api_url || !$settings->whatsapp_instance_id || !$settings->whatsapp_token) {
                Log::info('Configuration WhatsApp incomplète pour envoi de document');
                return false;
            }

            // URL pour envoyer des documents via UltraMsg
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/document";

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $params = [
                'token' => $settings->whatsapp_token,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'document' => $documentUrl,
                'filename' => $filename,
                'caption' => $caption
            ];

            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info('Document WhatsApp envoyé via UltraMsg', [
                    'to' => $phoneNumber,
                    'document_url' => $documentUrl,
                    'filename' => $filename,
                    'response' => $responseBody
                ]);
                return true;
            } else {
                Log::error('Erreur HTTP lors de l\'envoi de document WhatsApp via UltraMsg', [
                    'to' => $phoneNumber,
                    'document_url' => $documentUrl,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi de document WhatsApp via UltraMsg', [
                'to' => $phoneNumber,
                'document_url' => $documentUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer un message WhatsApp via UltraMsg API selon votre exemple d'intégration
     */
    public function sendMessage($phoneNumber, $message)
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

            // CORRECTION FINALE: Token DOIT être en paramètre GET selon UltraMsg
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat?token={$settings->whatsapp_token}";
            
            // Headers selon UltraMsg
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            // Paramètres POST (sans token car il est en GET)
            $params = [
                'to' => $this->formatPhoneNumber($phoneNumber),
                'body' => $message
            ];

            // Log pour débogage
            Log::info('Envoi WhatsApp UltraMsg', [
                'url' => $url,
                'to' => $this->formatPhoneNumber($phoneNumber),
                'has_token' => !empty($settings->whatsapp_token)
            ]);

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
        // Supprimer tous les caractères non numériques sauf le +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Si le numéro commence déjà par +237, retourner tel quel
        if (substr($cleaned, 0, 4) === '+237') {
            return $cleaned;
        }

        // Si le numéro commence par 237 (sans +), ajouter le +
        if (substr($cleaned, 0, 3) === '237') {
            return '+' . $cleaned;
        }

        // Si le numéro commence par 0, remplacer par +237
        if (substr($cleaned, 0, 1) === '0') {
            return '+237' . substr($cleaned, 1);
        }

        // Si le numéro commence par 6 ou 2 (numéros camerounais typiques)
        // Ajouter le code pays +237
        if (preg_match('/^[62]/', $cleaned)) {
            return '+237' . $cleaned;
        }

        // Si le numéro commence par +, le retourner tel quel
        if (substr($cleaned, 0, 1) === '+') {
            return $cleaned;
        }

        // Par défaut, ajouter +237 (Cameroun)
        return '+237' . $cleaned;
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
                      "📅 " . now()->setTimezone('Africa/Douala')->format('d/m/Y à H:i') . "\n\n" .
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
     * Envoyer une notification d'affectation de matière à un enseignant
     */
    public function sendTeacherAssignmentNotification($teacherAssignment)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $teacher = $teacherAssignment->teacher;
        
        // Vérifier que l'enseignant a un numéro de téléphone
        if (!$teacher || !$teacher->phone_number) {
            Log::info('Enseignant sans numéro de téléphone pour notification WhatsApp', [
                'assignment_id' => $teacherAssignment->id,
                'teacher_id' => $teacher ? $teacher->id : null
            ]);
            return false;
        }

        $message = $this->formatTeacherAssignmentMessage($teacherAssignment);
        
        $result = $this->sendMessage($teacher->phone_number, $message);
        
        if ($result) {
            Log::info('Notification d\'affectation enseignant envoyée avec succès', [
                'assignment_id' => $teacherAssignment->id,
                'teacher_id' => $teacher->id,
                'phone' => $teacher->phone_number
            ]);
        }
        
        return $result;
    }

    /**
     * Envoyer une notification de nomination comme professeur principal
     */
    public function sendMainTeacherNotification($mainTeacher)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $teacher = $mainTeacher->teacher;
        
        // Vérifier que l'enseignant a un numéro de téléphone
        if (!$teacher || !$teacher->phone_number) {
            Log::info('Enseignant sans numéro de téléphone pour notification professeur principal WhatsApp', [
                'main_teacher_id' => $mainTeacher->id,
                'teacher_id' => $teacher ? $teacher->id : null
            ]);
            return false;
        }

        $message = $this->formatMainTeacherMessage($mainTeacher);
        
        $result = $this->sendMessage($teacher->phone_number, $message);
        
        if ($result) {
            Log::info('Notification professeur principal envoyée avec succès', [
                'main_teacher_id' => $mainTeacher->id,
                'teacher_id' => $teacher->id,
                'phone' => $teacher->phone_number
            ]);
        }
        
        return $result;
    }

    /**
     * Formater le message de notification d'affectation enseignant
     */
    protected function formatTeacherAssignmentMessage($teacherAssignment)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA';
        $teacher = $teacherAssignment->teacher;
        $classSeriesSubject = $teacherAssignment->classSeriesSubject;
        $subject = $classSeriesSubject->subject;
        $classSeries = $classSeriesSubject->classSeries;
        $schoolYear = $teacherAssignment->schoolYear;

        // S'assurer que l'enseignant a un compte utilisateur
        $credentials = $this->ensureTeacherHasUserAccount($teacher);

        $message = "🎓 *NOUVELLE AFFECTATION - {$schoolName}*\n\n" .
               "👨‍🏫 *Cher(e) {$teacher->first_name} {$teacher->last_name},*\n\n" .
               "Vous avez été affecté(e) à une nouvelle matière :\n\n" .
               "📚 *Matière :* {$subject->name}\n" .
               "🏫 *Classe :* {$classSeries->name}\n" .
               "📊 *Niveau :* " . ($classSeries->schoolClass->level->name ?? 'N/A') . "\n" .
               "📅 *Année scolaire :* " . ($schoolYear->name ?? 'Actuelle') . "\n\n" .
               "✅ Votre affectation est maintenant active.\n" .
               "📖 Vous pouvez consulter la liste des élèves sur votre tableau de bord enseignant.\n\n";

        // Ajouter les informations de connexion
        if ($credentials) {
            $message .= "🔐 *INFORMATIONS DE CONNEXION*\n\n" .
                       "🌐 *Lien :* http://admin.cpb-douala.com\n" .
                       "👤 *Nom d'utilisateur :* {$credentials['username']}\n" .
                       "🔑 *Mot de passe :* {$credentials['password']}\n\n";
        }

        $message .= "📞 Pour toute question, contactez l'administration.\n\n" .
                   "📱 Notification automatique du système de gestion scolaire.";

        return $message;
    }

    /**
     * S'assurer que l'enseignant a un compte utilisateur
     */
    protected function ensureTeacherHasUserAccount($teacher)
    {
        // Si l'enseignant a déjà un compte utilisateur
        if ($teacher->user_id && $teacher->user) {
            return [
                'username' => $teacher->user->username ?? $teacher->user->email,
                'password' => 'password123'
            ];
        }

        // Créer un compte utilisateur pour l'enseignant
        try {
            $username = $this->generateUsername($teacher);
            $defaultPassword = 'password123';

            $user = \App\Models\User::create([
                'name' => $teacher->full_name,
                'username' => $username,
                'email' => $teacher->email ?? "{$username}@cpb-douala.com",
                'password' => bcrypt($defaultPassword),
                'role' => 'teacher',
                'is_active' => true,
                'staff_identifier' => $teacher->staff_identifier
            ]);

            // Lier l'utilisateur à l'enseignant
            $teacher->update(['user_id' => $user->id]);

            return [
                'username' => $username,
                'password' => $defaultPassword
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur création compte utilisateur enseignant', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Générer un nom d'utilisateur unique pour l'enseignant
     */
    protected function generateUsername($teacher)
    {
        // Format: prenom.nom (en minuscules, sans accents)
        $firstName = $this->removeAccents(strtolower($teacher->first_name));
        $lastName = $this->removeAccents(strtolower($teacher->last_name));
        $baseUsername = "{$firstName}.{$lastName}";

        // Vérifier si le nom d'utilisateur existe déjà
        $username = $baseUsername;
        $counter = 1;

        while (\App\Models\User::where('username', $username)->exists()) {
            $username = "{$baseUsername}{$counter}";
            $counter++;
        }

        return $username;
    }

    /**
     * Supprimer les accents d'une chaîne
     */
    protected function removeAccents($string)
    {
        $unwanted_array = [
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
            'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U',
            'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
            'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
        ];
        return strtr($string, $unwanted_array);
    }

    /**
     * Formater le message de notification professeur principal
     */
    protected function formatMainTeacherMessage($mainTeacher)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA';
        $teacher = $mainTeacher->teacher;
        $classSeries = $mainTeacher->classSeries;
        $schoolYear = $mainTeacher->schoolYear;

        return "👑 *NOMINATION PROFESSEUR PRINCIPAL - {$schoolName}*\n\n" .
               "👨‍🏫 *Félicitations {$teacher->first_name} {$teacher->last_name} !*\n\n" .
               "Vous avez été nommé(e) professeur principal de :\n\n" .
               "🏫 *Classe :* {$classSeries->name}\n" .
               "📊 *Niveau :* " . ($classSeries->schoolClass->level->name ?? 'N/A') . "\n" .
               "📅 *Année scolaire :* " . ($schoolYear->name ?? 'Actuelle') . "\n\n" .
               "🎯 *Vos responsabilités incluent :*\n" .
               "• Suivi pédagogique des élèves\n" .
               "• Communication avec les parents\n" .
               "• Coordination avec l'équipe pédagogique\n" .
               "• Participation aux conseils de classe\n\n" .
               "📖 Accédez à votre tableau de bord pour voir tous les détails.\n\n" .
               "🏆 Bonne réussite dans vos nouvelles fonctions !\n\n" .
               "📱 Notification automatique du système de gestion scolaire.";
    }

    /**
     * Envoyer une notification d'appel manuel aux parents
     */
    public function sendManualAttendanceNotification($studentAttendance, $student)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }
        
        // Vérifier que l'étudiant a un contact parent
        if (!$student || !$student->parent_phone) {
            Log::info('Étudiant sans contact parent pour notification WhatsApp (appel manuel)', [
                'student_attendance_id' => $studentAttendance->id,
                'student_id' => $student ? $student->id : null
            ]);
            return false;
        }

        $message = $this->formatManualAttendanceMessage($studentAttendance, $student);
        
        $result = $this->sendMessage($student->parent_phone, $message);
        
        if ($result) {
            Log::info('Notification WhatsApp envoyée pour appel manuel', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'attendance_status' => $studentAttendance->is_present ? 'Présent' : 'Absent',
                'parent_phone' => $student->parent_phone
            ]);
        }
        
        return $result;
    }

    /**
     * Formater le message de notification d'appel manuel
     */
    protected function formatManualAttendanceMessage($studentAttendance, $student)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'École';
        
        $statusIcon = $studentAttendance->is_present ? '✅' : '❌';
        $statusText = $studentAttendance->is_present ? 'PRÉSENT' : 'ABSENT';
        $statusMessage = $studentAttendance->is_present 
            ? 'était présent(e) lors de l\'appel en classe' 
            : 'était absent(e) lors de l\'appel en classe';
        
        $className = 'N/A';
        if ($studentAttendance->schoolClass) {
            $className = $studentAttendance->schoolClass->name;
        }
        
        return "{$statusIcon} *APPEL MANUEL - {$statusText} - {$schoolName}*\n\n" .
               "👤 *Élève:* {$student->name}\n" .
               "📚 *Classe:* {$className}\n" .
               "📅 *Date:* " . $studentAttendance->attendance_date->format('d/m/Y') . "\n" .
               "🕐 *Heure de l'appel:* " . $studentAttendance->created_at->setTimezone('Africa/Douala')->format('H:i') . "\n\n" .
               "📋 Votre enfant {$statusMessage}.\n\n" .
               ($studentAttendance->is_present 
                   ? "🎓 Bonne journée scolaire !" 
                   : "📞 Veuillez contacter l'école si votre enfant devait être présent.") . "\n\n" .
               "👨‍🏫 *Appel effectué par:* " . ($studentAttendance->markedBy->name ?? 'Personnel') . "\n\n" .
               "📱 Notification automatique du système de gestion scolaire.";
    }

    /**
     * Envoyer une notification aux parents via WhatsApp
     */
    public function sendParentNotification($parentNotification)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $parent = $parentNotification->parent;
        
        // Vérifier que le parent a un numéro de téléphone
        if (!$parent || !$parent->phone) {
            Log::info('Parent sans numéro de téléphone pour notification WhatsApp', [
                'notification_id' => $parentNotification->id,
                'parent_id' => $parent ? $parent->id : null
            ]);
            return false;
        }

        $message = $this->formatParentNotificationMessage($parentNotification);
        
        $result = $this->sendMessage($parent->phone, $message);
        
        if ($result) {
            // Marquer la notification comme envoyée par WhatsApp
            $parentNotification->update([
                'whatsapp_sent' => true,
                'whatsapp_sent_at' => now()
            ]);
            
            Log::info('Notification parent WhatsApp envoyée avec succès', [
                'notification_id' => $parentNotification->id,
                'parent_id' => $parent->id,
                'phone' => $parent->phone,
                'title' => $parentNotification->title
            ]);
        }
        
        return $result;
    }

    /**
     * Formater le message de notification pour les parents
     */
    protected function formatParentNotificationMessage($parentNotification)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA';
        
        // Icônes selon le type de notification
        $typeIcons = [
            'general' => '📢',
            'attendance' => '⚠️',
            'academic' => '📚',
            'behavior' => '🚨',
            'payment' => '💰',
            'event' => '📅'
        ];
        
        $icon = $typeIcons[$parentNotification->type] ?? '📢';
        
        // Priorité
        $priorityIcon = match($parentNotification->priority) {
            'urgent' => '🚨 *URGENT*',
            'high' => '⚠️ *PRIORITÉ ÉLEVÉE*',
            'normal' => '📢 *INFORMATION*',
            'low' => '💬 *NOTE*',
            default => '📢 *NOTIFICATION*'
        };
        
        $message = "{$icon} {$priorityIcon} - {$schoolName}\n\n";
        
        // Titre
        $message .= "📋 *{$parentNotification->title}*\n\n";
        
        // Si c'est pour un élève spécifique
        if ($parentNotification->student) {
            $message .= "👤 *Élève concerné:* {$parentNotification->student->first_name} {$parentNotification->student->last_name}\n";
            
            // Ajouter la classe si disponible
            if ($parentNotification->student->classSeries && $parentNotification->student->classSeries->first()) {
                $className = $parentNotification->student->classSeries->first()->schoolClass->name ?? '';
                if ($className) {
                    $message .= "📚 *Classe:* {$className}\n";
                }
            }
            $message .= "\n";
        }
        
        // Message principal
        $message .= $parentNotification->message . "\n\n";
        
        // Pied de page
        $message .= "📅 *Date:* " . $parentNotification->created_at->setTimezone('Africa/Douala')->format('d/m/Y à H:i') . "\n";
        
        if ($parentNotification->admin) {
            $message .= "👨‍💼 *Envoyé par:* {$parentNotification->admin->name}\n";
        }
        
        $message .= "\n📱 Notification automatique du système de gestion scolaire.";
        
        // Ajouter un appel à l'action selon la priorité
        if ($parentNotification->priority === 'urgent') {
            $message .= "\n\n🚨 *VEUILLEZ PRENDRE LES MESURES NÉCESSAIRES RAPIDEMENT*";
        } elseif ($parentNotification->type === 'attendance') {
            $message .= "\n\n📞 *Pour toute justification, contactez l'établissement.*";
        }
        
        return $message;
    }

    /**
     * Envoyer les pièces jointes d'une notification parent via WhatsApp
     */
    public function sendParentNotificationAttachments($parentNotification)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        $parent = $parentNotification->parent;

        if (!$parent || !$parent->phone) {
            Log::info('Parent sans numéro pour envoi pièces jointes WhatsApp', [
                'notification_id' => $parentNotification->id
            ]);
            return false;
        }

        $attachments = $parentNotification->attachments;
        if (!$attachments || $attachments->isEmpty()) {
            return false;
        }

        $successCount = 0;
        foreach ($attachments as $attachment) {
            try {
                // Obtenir l'URL publique du fichier
                $fileUrl = url('storage/' . $attachment->file_path);

                // Créer un message de légende pour le document
                $caption = "📄 {$attachment->file_name}\n" .
                          "📎 Pièce jointe - {$parentNotification->title}\n" .
                          "📅 " . $attachment->created_at->setTimezone('Africa/Douala')->format('d/m/Y à H:i');

                // Envoyer le document via WhatsApp
                $result = $this->sendDocumentMessage(
                    $parent->phone,
                    $fileUrl,
                    $attachment->file_name,
                    $caption
                );

                if ($result) {
                    // Marquer l'attachment comme envoyé
                    $attachment->update([
                        'whatsapp_sent' => true
                    ]);
                    $successCount++;

                    Log::info('Pièce jointe envoyée via WhatsApp', [
                        'attachment_id' => $attachment->id,
                        'notification_id' => $parentNotification->id,
                        'filename' => $attachment->file_name
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erreur envoi pièce jointe WhatsApp', [
                    'attachment_id' => $attachment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $successCount > 0;
    }

    /**
     * Envoyer le récapitulatif complet des affectations à un enseignant
     */
    public function sendTeacherAssignmentsSummary($teacher, $schoolYearId = null)
    {
        if (!$this->isWhatsAppConfigured()) {
            return false;
        }

        // Vérifier que l'enseignant a un numéro de téléphone
        if (!$teacher || !$teacher->phone_number) {
            Log::info('Enseignant sans numéro de téléphone pour notification récapitulatif WhatsApp', [
                'teacher_id' => $teacher ? $teacher->id : null
            ]);
            return false;
        }

        // Si pas d'année scolaire spécifiée, prendre l'année courante
        if (!$schoolYearId) {
            $schoolYear = \App\Models\SchoolYear::where('is_current', true)->first();
            $schoolYearId = $schoolYear ? $schoolYear->id : null;
        }

        if (!$schoolYearId) {
            Log::error('Aucune année scolaire trouvée pour récapitulatif enseignant');
            return false;
        }

        // Récupérer toutes les affectations de l'enseignant
        $assignments = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('school_year_id', $schoolYearId)
            ->where('is_active', true)
            ->with([
                'classSeriesSubject.subject',
                'classSeriesSubject.classSeries.schoolClass.level',
                'schoolYear'
            ])
            ->get();

        if ($assignments->isEmpty()) {
            Log::info('Aucune affectation trouvée pour cet enseignant', [
                'teacher_id' => $teacher->id,
                'school_year_id' => $schoolYearId
            ]);
            return false;
        }

        // Formater le message récapitulatif
        $message = $this->formatTeacherAssignmentsSummaryMessage($teacher, $assignments);

        // Envoyer le message
        $result = $this->sendMessage($teacher->phone_number, $message);

        if ($result) {
            Log::info('Récapitulatif des affectations enseignant envoyé avec succès', [
                'teacher_id' => $teacher->id,
                'phone' => $teacher->phone_number,
                'assignments_count' => $assignments->count()
            ]);
        }

        return $result;
    }

    /**
     * Formater le message récapitulatif des affectations d'un enseignant
     */
    protected function formatTeacherAssignmentsSummaryMessage($teacher, $assignments)
    {
        $schoolName = SchoolSetting::getSettings()->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA';
        $schoolYear = $assignments->first()->schoolYear;

        $message = "🎓 *RÉCAPITULATIF DES AFFECTATIONS - {$schoolName}*\n\n" .
                   "👩‍🏫 *Chère/Cher {$teacher->first_name} {$teacher->last_name},*\n\n" .
                   "Voici la liste complète de vos affectations pour l'année en cours :\n\n";

        // Lister toutes les affectations
        foreach ($assignments as $index => $assignment) {
            $subject = $assignment->classSeriesSubject->subject;
            $classSeries = $assignment->classSeriesSubject->classSeries;
            $level = $classSeries->schoolClass->level ?? null;

            $message .= "📚 *Affectation " . ($index + 1) . "*\n" .
                       "   • *Matière :* {$subject->name}\n" .
                       "   • *Classe :* {$classSeries->name}\n";

            if ($level) {
                $message .= "   • *Niveau :* {$level->name}\n";
            }

            $message .= "\n";
        }

        // Total
        $message .= "📊 *Total :* " . $assignments->count() . " affectation" . ($assignments->count() > 1 ? 's' : '') . "\n\n";

        // S'assurer que l'enseignant a un compte utilisateur et récupérer les credentials
        $credentials = $this->ensureTeacherHasUserAccount($teacher);

        // Ajouter les informations de connexion
        if ($credentials) {
            $message .= "🔐 *INFORMATIONS DE CONNEXION*\n\n" .
                       "🌐 *Lien :* http://admin.cpb-douala.com\n" .
                       "👤 *Nom d'utilisateur :* {$credentials['username']}\n" .
                       "🔑 *Mot de passe :* {$credentials['password']}\n\n";
        }

        $message .= "📖 Vous pouvez consulter toutes vos classes et élèves sur votre tableau de bord enseignant.\n\n" .
                   "📞 Pour toute question, contactez l'administration.\n\n" .
                   "📱 Notification automatique du système de gestion scolaire.";

        return $message;
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