<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Services\WhatsAppService;

class TestSendReceiptWhatsApp extends Command
{
    protected $signature = 'test:send-receipt {payment_id} {phone_number}';
    protected $description = 'Tester l\'envoi d\'un reçu via WhatsApp';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        $phoneNumber = $this->argument('phone_number');

        $this->info("🔍 Recherche du paiement #{$paymentId}...");

        $payment = Payment::with(['student.classSeries.schoolClass.level', 'paymentDetails.paymentTranche'])
            ->find($paymentId);

        if (!$payment) {
            $this->error("❌ Paiement #{$paymentId} introuvable");
            return 1;
        }

        $this->info("✅ Paiement trouvé:");
        $this->line("   - Reçu N°: {$payment->receipt_number}");
        $this->line("   - Étudiant: {$payment->student->full_name}");
        $this->line("   - Montant: {$payment->total_amount} FCFA");
        $this->line("");

        $this->info("📱 Envoi du reçu au numéro: {$phoneNumber}");
        $this->line("");

        try {
            $whatsAppService = new WhatsAppService();
            $reflection = new \ReflectionClass($whatsAppService);

            // Test de génération du PDF
            $this->info("📄 Génération du PDF...");
            $method = $reflection->getMethod('generateReceiptPDF');
            $method->setAccessible(true);
            $pdfResult = $method->invoke($whatsAppService, $payment);

            if ($pdfResult && isset($pdfResult['url'])) {
                $this->info("✅ PDF généré: {$pdfResult['filename']}");
                $this->line("   URL: {$pdfResult['url']}");
                $this->line("");
            } else {
                $this->error("❌ Erreur lors de la génération du PDF");
                return 1;
            }

            // Formater le message
            $formatPaymentMessageMethod = $reflection->getMethod('formatPaymentMessage');
            $formatPaymentMessageMethod->setAccessible(true);
            $message = $formatPaymentMessageMethod->invoke($whatsAppService, $payment);

            // Envoi du message texte
            $this->info("📤 Envoi du message texte...");
            $sendMessageMethod = $reflection->getMethod('sendMessage');
            $sendMessageMethod->setAccessible(true);
            $textResult = $sendMessageMethod->invoke($whatsAppService, $phoneNumber, $message);

            if ($textResult) {
                $this->info("✅ Message texte envoyé");
            } else {
                $this->error("❌ Échec envoi message texte");
            }

            // Envoi du PDF
            $this->info("📤 Envoi du PDF...");
            $caption = "📄 Reçu de paiement N° {$payment->receipt_number}\n\nMerci pour votre confiance. 🙏";
            $sendDocMethod = $reflection->getMethod('sendDocumentMessage');
            $sendDocMethod->setAccessible(true);
            $pdfSentResult = $sendDocMethod->invoke($whatsAppService, $phoneNumber, $pdfResult['url'], $pdfResult['filename'], $caption);

            $result = $textResult || $pdfSentResult;

            if ($result) {
                $this->info("✅ Notification envoyée avec succès!");
                $this->line("");
                $this->info("📋 Le parent devrait recevoir:");
                $this->line("   1. Un message texte de confirmation");
                $this->line("   2. Le reçu en PDF");
            } else {
                $this->error("❌ Erreur lors de l'envoi de la notification");
                $this->line("");
                $this->warn("💡 Vérifiez:");
                $this->line("   - La configuration WhatsApp dans school_settings");
                $this->line("   - Les logs: tail -f storage/logs/laravel.log");
            }

        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
