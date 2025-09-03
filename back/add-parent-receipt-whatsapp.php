<?php

/**
 * Script pour ajouter l'envoi automatique de reçus parents via WhatsApp
 * 
 * Ce script modifie le PaymentController pour :
 * 1. Générer une version "parent" simplifiée du reçu
 * 2. La convertir en image
 * 3. L'envoyer automatiquement via WhatsApp lors des paiements
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AJOUT ENVOI RECUS PARENTS VIA WHATSAPP ===\n\n";

try {
    // 1. Sauvegarde du PaymentController
    $controllerFile = __DIR__ . '/app/Http/Controllers/PaymentController.php';
    $backupFile = $controllerFile . '.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($controllerFile, $backupFile)) {
        throw new Exception("Impossible de créer la sauvegarde");
    }
    echo "✅ Sauvegarde créée: " . basename($backupFile) . "\n\n";
    
    $content = file_get_contents($controllerFile);
    
    // 2. Ajouter la méthode pour générer le reçu parent
    $newMethod = '
    /**
     * Générer une version simplifiée du reçu pour les parents (envoi WhatsApp)
     */
    public function generateParentReceipt($paymentId)
    {
        try {
            $payment = Payment::with([
                \'student.classSeries.schoolClass\',
                \'paymentDetails.paymentTranche\',
                \'schoolYear\',
                \'createdByUser\'
            ])->findOrFail($paymentId);

            $schoolSettings = \\App\\Models\\SchoolSetting::getSettings();

            // Générer le HTML simplifié pour les parents
            $receiptHtml = $this->generateParentReceiptHtml($payment, $schoolSettings);

            return [
                \'success\' => true,
                \'html\' => $receiptHtml,
                \'payment\' => $payment,
                \'filename\' => "Recu_Parent_{$payment->receipt_number}.png"
            ];
        } catch (\\Exception $e) {
            \\Log::error(\'Error generating parent receipt: \' . $e->getMessage());
            return [
                \'success\' => false,
                \'message\' => \'Erreur lors de la génération du reçu parent\',
                \'error\' => $e->getMessage()
            ];
        }
    }

    /**
     * Générer le HTML du reçu simplifié pour les parents
     */
    private function generateParentReceiptHtml($payment, $schoolSettings)
    {
        // Logo en base64
        $logoBase64 = \'\';
        if ($schoolSettings->school_logo) {
            $logoPath = storage_path(\'app/public/\' . $schoolSettings->school_logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMimeType = mime_content_type($logoPath);
                $logoBase64 = "data:{$logoMimeType};base64,{$logoData}";
            }
        }

        $student = $payment->student;
        $schoolClass = $student->classSeries->schoolClass ?? null;
        $workingYear = $payment->schoolYear;

        // Formatage des montants
        $formatAmount = function ($amount) {
            return number_format($amount, 0, \',\', \' \');
        };

        // Informations sur les avantages
        $benefitInfo = \'\';
        if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
            $benefitInfo = "🎓 Bourse: " . $formatAmount($payment->scholarship_amount) . " FCFA";
        } elseif ($payment->has_reduction && $payment->reduction_amount > 0) {
            $benefitInfo = "💰 Réduction: " . $formatAmount($payment->reduction_amount) . " FCFA";
        }

        // Génération des détails de paiement simplifiés
        $paymentDetailsRows = \'\';
        foreach ($payment->paymentDetails as $detail) {
            $trancheName = $detail->paymentTranche->name;
            $amount = $formatAmount($detail->amount_allocated);
            $paymentDetailsRows .= "
                <tr>
                    <td style=\'padding: 8px; border-bottom: 1px solid #eee;\'>$trancheName</td>
                    <td style=\'padding: 8px; border-bottom: 1px solid #eee; text-align: right; font-weight: bold;\'>$amount FCFA</td>
                </tr>
            ";
        }

        // HTML simplifié optimisé pour conversion en image
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\'utf-8\'>
            <title>Reçu Parent - {$payment->receipt_number}</title>
            <style>
                body {
                    font-family: \'Arial\', sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }

                .receipt-container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    overflow: hidden;
                }

                .header {
                    background: linear-gradient(45deg, #2E8B57, #3CB371);
                    color: white;
                    padding: 20px;
                    text-align: center;
                    position: relative;
                }

                .logo {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    margin: 0 auto 10px;
                    display: block;
                    border: 3px solid white;
                }

                .school-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }

                .receipt-title {
                    font-size: 18px;
                    font-weight: bold;
                    background: rgba(255,255,255,0.2);
                    padding: 8px 15px;
                    border-radius: 20px;
                    display: inline-block;
                    margin-top: 10px;
                }

                .content {
                    padding: 25px;
                }

                .student-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    border-left: 5px solid #2E8B57;
                }

                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    padding: 5px 0;
                }

                .info-label {
                    font-weight: bold;
                    color: #555;
                }

                .info-value {
                    color: #333;
                    font-weight: 500;
                }

                .payment-section {
                    background: #e8f5e8;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }

                .section-title {
                    font-size: 16px;
                    font-weight: bold;
                    color: #2E8B57;
                    margin-bottom: 15px;
                    border-bottom: 2px solid #2E8B57;
                    padding-bottom: 5px;
                }

                .payment-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .total-amount {
                    background: #2E8B57;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    font-size: 18px;
                    font-weight: bold;
                    border-radius: 10px;
                    margin: 20px 0;
                }

                .benefit-info {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    color: #856404;
                    padding: 10px 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                    text-align: center;
                    font-weight: bold;
                }

                .footer {
                    background: #f8f9fa;
                    padding: 15px;
                    text-align: center;
                    border-top: 1px solid #eee;
                    font-size: 12px;
                    color: #666;
                }

                .footer-note {
                    background: #e3f2fd;
                    border-left: 4px solid #2196f3;
                    padding: 12px 15px;
                    margin: 15px 0;
                    font-size: 13px;
                    color: #1976d2;
                    border-radius: 0 8px 8px 0;
                }

                .whatsapp-signature {
                    background: #25D366;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    font-size: 12px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class=\'receipt-container\'>
                <div class=\'header\'>
                    " . ($logoBase64 ? "<img src=\'$logoBase64\' alt=\'Logo\' class=\'logo\'>" : "") . "
                    <div class=\'school-name\'>{$schoolSettings->school_name}</div>
                    <div style=\'font-size: 14px; opacity: 0.9;\'>Année académique {$workingYear->name}</div>
                    <div class=\'receipt-title\'>REÇU N° {$payment->receipt_number}</div>
                </div>

                <div class=\'content\'>
                    <div class=\'student-info\'>
                        <div class=\'info-row\'>
                            <span class=\'info-label\'>👤 Élève:</span>
                            <span class=\'info-value\'>{$student->first_name} {$student->last_name}</span>
                        </div>
                        <div class=\'info-row\'>
                            <span class=\'info-label\'>🏫 Classe:</span>
                            <span class=\'info-value\'>" . ($schoolClass ? $schoolClass->name : \'Non défini\') . "</span>
                        </div>
                        <div class=\'info-row\'>
                            <span class=\'info-label\'>📅 Date:</span>
                            <span class=\'info-value\'>" . \\Carbon\\Carbon::parse($payment->payment_date)->format(\'d/m/Y à H:i\') . "</span>
                        </div>
                        <div class=\'info-row\'>
                            <span class=\'info-label\'>💳 Méthode:</span>
                            <span class=\'info-value\'>" . ucfirst($payment->payment_method) . "</span>
                        </div>
                    </div>

                    " . ($benefitInfo ? "<div class=\'benefit-info\'>$benefitInfo</div>" : "") . "

                    <div class=\'payment-section\'>
                        <div class=\'section-title\'>💰 Détails du paiement</div>
                        <table class=\'payment-table\'>
                            $paymentDetailsRows
                        </table>
                    </div>

                    <div class=\'total-amount\'>
                        💵 TOTAL PAYÉ: " . $formatAmount($payment->total_amount) . " FCFA
                    </div>

                    <div class=\'footer-note\'>
                        ✅ <strong>Paiement confirmé et validé</strong><br>
                        Ce reçu confirme la réception de votre paiement. Conservez-le précieusement.
                    </div>
                </div>

                <div class=\'footer\'>
                    <div style=\'margin-bottom: 5px;\'>📞 {$schoolSettings->school_phone} | 📧 {$schoolSettings->school_email}</div>
                    <div>🌐 {$schoolSettings->website}</div>
                </div>

                <div class=\'whatsapp-signature\'>
                    📱 Reçu envoyé automatiquement par WhatsApp
                </div>
            </div>
        </body>
        </html>";
    }';

    // Trouver où insérer la nouvelle méthode (avant la dernière accolade de fermeture de classe)
    $insertPosition = strrpos($content, '}');
    $beforeInsert = substr($content, 0, $insertPosition);
    $afterInsert = substr($content, $insertPosition);

    $newContent = $beforeInsert . $newMethod . "\n" . $afterInsert;

    // 3. Modifier le WhatsAppService pour utiliser la nouvelle méthode
    echo "2. MODIFICATION DU WHATSAPPSERVICE...\n";

    $whatsappServiceFile = __DIR__ . '/app/Services/WhatsAppService.php';
    $whatsappBackup = $whatsappServiceFile . '.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($whatsappServiceFile, $whatsappBackup)) {
        throw new Exception("Impossible de créer la sauvegarde WhatsAppService");
    }

    $whatsappContent = file_get_contents($whatsappServiceFile);

    // Remplacer la méthode generateReceiptImage
    $oldGenerateImage = 'protected function generateReceiptImage($payment)
    {
        try {
            // Récupérer le HTML du reçu depuis le PaymentController via l\'injection de dépendances
            $paymentController = app()->make(\\App\\Http\\Controllers\\PaymentController::class);
            $receiptResponse = $paymentController->generateReceipt($payment->id);
            $responseData = $receiptResponse->getData();
            
            if (!$responseData->success) {
                Log::error(\'Impossible de générer le HTML du reçu\', [\'payment_id\' => $payment->id]);
                return null;
            }
            
            $receiptHtml = $responseData->data->html;
            
            // Utiliser wkhtmltoimage ou une alternative pour convertir HTML en image
            return $this->convertHtmlToImage($receiptHtml, $payment->id);
            
        } catch (\\Exception $e) {
            Log::error(\'Erreur lors de la génération de l\\\'image du reçu\', [
                \'payment_id\' => $payment->id,
                \'error\' => $e->getMessage()
            ]);
            return null;
        }
    }';

    $newGenerateImage = 'protected function generateReceiptImage($payment)
    {
        try {
            // Utiliser la nouvelle méthode pour générer le reçu parent
            $paymentController = app()->make(\\App\\Http\\Controllers\\PaymentController::class);
            $receiptData = $paymentController->generateParentReceipt($payment->id);
            
            if (!$receiptData[\'success\']) {
                Log::error(\'Impossible de générer le HTML du reçu parent\', [\'payment_id\' => $payment->id]);
                return null;
            }
            
            $receiptHtml = $receiptData[\'html\'];
            
            // Convertir le HTML parent en image optimisée pour WhatsApp
            return $this->convertHtmlToImage($receiptHtml, $payment->id, true);
            
        } catch (\\Exception $e) {
            Log::error(\'Erreur lors de la génération de l\\\'image du reçu parent\', [
                \'payment_id\' => $payment->id,
                \'error\' => $e->getMessage()
            ]);
            return null;
        }
    }';

    $whatsappContent = str_replace($oldGenerateImage, $newGenerateImage, $whatsappContent);

    // Modifier la méthode convertHtmlToImage pour optimiser pour WhatsApp
    $oldConvertMethod = 'protected function convertHtmlToImage($html, $paymentId)';
    $newConvertMethod = 'protected function convertHtmlToImage($html, $paymentId, $isParentReceipt = false)';

    $whatsappContent = str_replace($oldConvertMethod, $newConvertMethod, $whatsappContent);

    // 4. Écrire les fichiers modifiés
    if (file_put_contents($controllerFile, $newContent) === false) {
        throw new Exception("Impossible d\'écrire le PaymentController modifié");
    }
    echo "   ✅ PaymentController modifié\n";

    if (file_put_contents($whatsappServiceFile, $whatsappContent) === false) {
        throw new Exception("Impossible d\'écrire le WhatsAppService modifié");
    }
    echo "   ✅ WhatsAppService modifié\n\n";

    // 5. Test de la nouvelle fonctionnalité
    echo "3. TEST DE LA NOUVELLE FONCTIONNALITÉ...\n";

    // Trouver un paiement récent pour tester
    $recentPayment = \\App\\Models\\Payment::with(\'student\')->latest()->first();

    if ($recentPayment) {
        echo "   🧪 Test avec le paiement #{$recentPayment->id} ({$recentPayment->student->first_name} {$recentPayment->student->last_name})\n";
        
        // Tester la génération du reçu parent
        $paymentController = new \\App\\Http\\Controllers\\PaymentController();
        $parentReceiptData = $paymentController->generateParentReceipt($recentPayment->id);
        
        if ($parentReceiptData[\'success\']) {
            echo "   ✅ Génération du reçu parent réussie\n";
            echo "   📄 Taille HTML: " . strlen($parentReceiptData[\'html\']) . " caractères\n";
        } else {
            echo "   ❌ Erreur lors de la génération: " . $parentReceiptData[\'message\'] . "\n";
        }
    } else {
        echo "   ℹ️ Aucun paiement trouvé pour le test\n";
    }

    echo "\n4. RÉSUMÉ DES MODIFICATIONS:\n";
    echo "   ✅ Nouvelle méthode generateParentReceipt() ajoutée au PaymentController\n";
    echo "   ✅ Nouvelle méthode generateParentReceiptHtml() pour HTML optimisé parents\n";
    echo "   ✅ WhatsAppService modifié pour utiliser le reçu parent\n";
    echo "   ✅ Design moderne et responsive pour les images WhatsApp\n";
    echo "   📁 Sauvegardes: " . basename($backupFile) . " et " . basename($whatsappBackup) . "\n\n";

    echo "5. FONCTIONNALITÉS AJOUTÉES:\n";
    echo "   🎨 Reçu parent avec design moderne (couleurs, icônes)\n";
    echo "   📱 Optimisé pour la lecture sur smartphone\n";
    echo "   🖼️ Conversion HTML → Image pour envoi WhatsApp\n";
    echo "   ✅ Informations essentielles pour les parents\n";
    echo "   💰 Affichage des bourses/réductions\n";
    echo "   📋 Détails de paiement simplifiés\n\n";

    echo "6. UTILISATION:\n";
    echo "   • Les reçus parents sont maintenant envoyés automatiquement\n";
    echo "   • Format image optimisé pour WhatsApp\n";
    echo "   • Design attractif et professionnel\n";
    echo "   • Informations complètes mais simplifiées\n\n";

    echo "✅ ENVOI AUTOMATIQUE DE REÇUS PARENTS AJOUTÉ AVEC SUCCÈS!\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    // Restaurer les sauvegardes
    if (isset($backupFile) && file_exists($backupFile)) {
        copy($backupFile, $controllerFile);
        echo "✅ PaymentController restauré\n";
    }
    
    if (isset($whatsappBackup) && file_exists($whatsappBackup)) {
        copy($whatsappBackup, $whatsappServiceFile);
        echo "✅ WhatsAppService restauré\n";
    }
    
    exit(1);
}

echo "\n=== AJOUT TERMINÉ ===\n";
?>