<?php
/**
 * Script de configuration UltraMsg pour Chris
 * Configurer les credentials WhatsApp UltraMsg dans les paramètres de l'école
 */

require_once 'vendor/autoload.php';

// Charger les configurations Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolSetting;

echo "🚀 CONFIGURATION ULTRAMSG POUR CHRIS\n";
echo "=====================================\n\n";

try {
    // Récupérer ou créer les paramètres de l'école
    $settings = SchoolSetting::getSettings();

    echo "📝 Configuration des paramètres WhatsApp...\n";

    // Tes credentials UltraMsg
    $settings->update([
        'whatsapp_notifications_enabled' => true,
        'whatsapp_api_url' => 'https://api.ultramsg.com/instance97191/',
        'whatsapp_instance_id' => 'instance97191',
        'whatsapp_token' => 'vdehri5ktxhl653x',
        'whatsapp_notification_number' => '+237695123456', // Ton numéro pour recevoir les rapports (optionnel)
    ]);

    echo "✅ whatsapp_notifications_enabled: OUI\n";
    echo "✅ whatsapp_api_url: https://api.ultramsg.com/instance97191/\n";
    echo "✅ whatsapp_instance_id: instance97191\n";
    echo "✅ whatsapp_token: vdehri5k***\n";
    echo "✅ whatsapp_notification_number: +237695123456\n";

    echo "\n🎉 CONFIGURATION TERMINÉE AVEC SUCCÈS !\n";
    echo "=====================================\n\n";

    echo "📋 Vérification de la configuration:\n";
    $settings = SchoolSetting::getSettings();

    echo "🔧 API URL: " . ($settings->whatsapp_api_url ?? 'NON CONFIGURÉ') . "\n";
    echo "🆔 Instance ID: " . ($settings->whatsapp_instance_id ?? 'NON CONFIGURÉ') . "\n";
    echo "🔑 Token: " . (isset($settings->whatsapp_token) ? substr($settings->whatsapp_token, 0, 8) . '***' : 'NON CONFIGURÉ') . "\n";
    echo "✅ Notifications activées: " . ($settings->whatsapp_notifications_enabled ? 'OUI' : 'NON') . "\n";

    echo "\n🧪 TEST DE CONFIGURATION:\n";
    echo "Maintenant tu peux tester en faisant un appel dans l'app mobile.\n";
    echo "Les notifications WhatsApp devraient fonctionner!\n\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

Discipline
Travail de l’élève
Profil de la classe
Abs. non. J. (h)
Avertissement de conduite
TOTAL GENERAL
APPRECIATIONS
Moyenne. Générale.
Abs just. (h)
Blâme de conduite
COEF
CTBA
[Min – Max]
CBA
Retards (nombre de fois)
Exclusions (jours)
MOYENNE TRIM
CA
Nombre de moyennes
