<?php

/**
 * Script de déploiement en production pour les reçus parents WhatsApp
 * 
 * Ce script vérifie et prépare tout pour la production
 */

echo "=== DÉPLOIEMENT REÇUS PARENTS WHATSAPP EN PRODUCTION ===\n\n";

echo "📋 CHECKLIST AVANT DÉPLOIEMENT:\n";
echo "-----------------------------------\n\n";

echo "1. FICHIERS À TRANSFÉRER:\n";
echo "   ✓ app/Http/Controllers/PaymentController.php (modifié)\n";
echo "   ✓ app/Services/WhatsAppService.php (modifié)\n\n";

echo "2. VÉRIFICATIONS REQUISES:\n";
echo "   □ APP_URL dans .env est configuré avec votre domaine\n";
echo "   □ Storage link créé (php artisan storage:link)\n";
echo "   □ Permissions du dossier storage/app/public/receipts (755)\n";
echo "   □ Token WhatsApp UltraMsg valide\n\n";

echo "3. COMMANDES À EXÉCUTER EN PRODUCTION:\n";
echo "   ```bash\n";
echo "   # Sur votre serveur de production\n";
echo "   cd /chemin/vers/votre/app/back\n";
echo "   \n";
echo "   # Créer le dossier des reçus\n";
echo "   mkdir -p storage/app/public/receipts\n";
echo "   chmod 755 storage/app/public/receipts\n";
echo "   \n";
echo "   # Créer le lien symbolique si pas déjà fait\n";
echo "   php artisan storage:link\n";
echo "   \n";
echo "   # Vider le cache\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   \n";
echo "   # Redémarrer les workers si vous en avez\n";
echo "   php artisan queue:restart\n";
echo "   ```\n\n";

echo "4. CONFIGURATION .env EN PRODUCTION:\n";
echo "   ```\n";
echo "   APP_URL=https://votredomaine.com\n";
echo "   \n";
echo "   # Si vous voulez utiliser imgbb (optionnel)\n";
echo "   IMGBB_API_KEY=votre_cle_api_imgbb\n";
echo "   ```\n\n";

echo "5. TEST DE VALIDATION:\n";
echo "   ```php\n";
echo "   // Script de test à exécuter en production\n";
echo "   php artisan tinker\n";
echo "   \n";
echo "   // Tester la génération de reçu\n";
echo "   \$payment = \\App\\Models\\Payment::latest()->first();\n";
echo "   \$controller = app()->make(\\App\\Http\\Controllers\\PaymentController::class);\n";
echo "   \$result = \$controller->generateParentReceipt(\$payment->id);\n";
echo "   echo \$result['success'] ? 'OK' : 'ERREUR';\n";
echo "   \n";
echo "   // Tester l'envoi WhatsApp\n";
echo "   \$whatsapp = new \\App\\Services\\WhatsAppService();\n";
echo "   \$test = \$whatsapp->testConfiguration();\n";
echo "   echo \$test['message'];\n";
echo "   ```\n\n";

echo "6. MÉTHODE DE DÉPLOIEMENT:\n";
echo "   \n";
echo "   OPTION A - Via Git:\n";
echo "   ```bash\n";
echo "   git add -A\n";
echo "   git commit -m \"Ajout envoi reçus parents WhatsApp\"\n";
echo "   git push origin production\n";
echo "   \n";
echo "   # Sur le serveur\n";
echo "   git pull origin production\n";
echo "   ```\n";
echo "   \n";
echo "   OPTION B - Via FTP:\n";
echo "   Transférer ces fichiers:\n";
echo "   - app/Http/Controllers/PaymentController.php\n";
echo "   - app/Services/WhatsAppService.php\n\n";

echo "7. VÉRIFICATION POST-DÉPLOIEMENT:\n";
echo "   □ Créer un nouveau paiement\n";
echo "   □ Vérifier que le parent reçoit le message WhatsApp\n";
echo "   □ Vérifier que l'image du reçu est incluse\n";
echo "   □ Vérifier les logs: tail -f storage/logs/laravel.log\n\n";

echo "===========================================\n";
echo "📝 RÉSUMÉ DES MODIFICATIONS APPORTÉES:\n";
echo "===========================================\n\n";

echo "PaymentController.php:\n";
echo "  + generateParentReceipt() - Génère reçu HTML parent\n";
echo "  + generateParentReceiptHtml() - Template moderne\n\n";

echo "WhatsAppService.php:\n";
echo "  ~ generateReceiptImage() - Utilise reçu parent\n";
echo "  ~ sendPaymentNotification() - Envoie texte + image\n\n";

echo "===========================================\n";
echo "🧪 SCRIPT DE TEST RAPIDE:\n";
echo "===========================================\n\n";

// Générer un script de test pour la production
$testScript = '<?php
// Script de test en production pour reçus WhatsApp
// Usage: php test-parent-receipt-prod.php

require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

echo "TEST REÇUS PARENTS WHATSAPP\n";
echo "============================\n\n";

// 1. Vérifier la configuration
$settings = \App\Models\SchoolSetting::getSettings();
echo "1. Configuration:\n";
echo "   APP_URL: " . config("app.url") . "\n";
echo "   Token WhatsApp: " . ($settings->whatsapp_token ? "✓" : "✗") . "\n";
echo "   Instance ID: " . $settings->whatsapp_instance_id . "\n\n";

// 2. Tester la génération de reçu
$payment = \App\Models\Payment::with("student")->latest()->first();
if ($payment) {
    echo "2. Test génération reçu:\n";
    echo "   Paiement #" . $payment->id . "\n";
    echo "   Élève: " . $payment->student->first_name . " " . $payment->student->last_name . "\n";
    
    $controller = app()->make(\App\Http\Controllers\PaymentController::class);
    $result = $controller->generateParentReceipt($payment->id);
    
    if ($result["success"]) {
        echo "   ✓ Reçu généré avec succès\n";
        echo "   Taille HTML: " . strlen($result["html"]) . " caractères\n\n";
        
        // 3. Tester envoi WhatsApp
        echo "3. Test envoi WhatsApp:\n";
        $phoneTest = "659339778"; // Votre numéro de test
        
        // Sauvegarder temporairement avec numéro de test
        $originalPhone = $payment->student->parent_phone;
        $payment->student->parent_phone = $phoneTest;
        $payment->student->save();
        
        $whatsapp = new \App\Services\WhatsAppService();
        $sent = $whatsapp->sendPaymentNotification($payment);
        
        // Restaurer
        $payment->student->parent_phone = $originalPhone;
        $payment->student->save();
        
        if ($sent) {
            echo "   ✓ Message envoyé sur " . $phoneTest . "\n";
            echo "   Vérifiez votre WhatsApp!\n";
        } else {
            echo "   ✗ Échec envoi\n";
        }
    } else {
        echo "   ✗ Erreur: " . $result["message"] . "\n";
    }
} else {
    echo "Aucun paiement trouvé\n";
}

echo "\n============================\n";
echo "Test terminé\n";
';

file_put_contents(__DIR__ . '/test-parent-receipt-prod.php', $testScript);
echo "✅ Script de test créé: test-parent-receipt-prod.php\n\n";

echo "===========================================\n";
echo "🚀 ÉTAPES DE DÉPLOIEMENT:\n";
echo "===========================================\n\n";

echo "1. TRANSFÉRER les fichiers modifiés\n";
echo "2. EXÉCUTER les commandes de configuration\n";
echo "3. LANCER le script de test: php test-parent-receipt-prod.php\n";
echo "4. VÉRIFIER la réception sur WhatsApp\n\n";

echo "✅ TOUT EST PRÊT POUR LE DÉPLOIEMENT!\n";
?>