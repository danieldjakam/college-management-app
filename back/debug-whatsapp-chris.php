<?php
/**
 * Script de debug WhatsApp pour Chris
 * Vérifier pourquoi les notifications ne marchent pas
 */

require_once 'vendor/autoload.php';

// Charger les configurations Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\WhatsAppService;

echo "🔍 DEBUG WHATSAPP POUR CHRIS\n";
echo "============================\n\n";

try {
    // 1. Vérifier la configuration WhatsApp
    echo "1️⃣ VÉRIFICATION DE LA CONFIGURATION WHATSAPP:\n";
    $settings = SchoolSetting::getSettings();
    
    echo "✅ Notifications activées: " . ($settings->whatsapp_notifications_enabled ? 'OUI' : 'NON') . "\n";
    echo "🔧 API URL: " . ($settings->whatsapp_api_url ?? 'NON CONFIGURÉ') . "\n";
    echo "🆔 Instance ID: " . ($settings->whatsapp_instance_id ?? 'NON CONFIGURÉ') . "\n";
    echo "🔑 Token: " . (isset($settings->whatsapp_token) ? 'CONFIGURÉ' : 'NON CONFIGURÉ') . "\n";
    echo "📱 Numéro notification: " . ($settings->whatsapp_notification_number ?? 'NON CONFIGURÉ') . "\n\n";
    
    // 2. Vérifier l'étudiant spécifique
    echo "2️⃣ VÉRIFICATION DE L'ÉLÈVE LA COMPTESSE DIVINE:\n";
    $student = Student::where('first_name', 'LA COMPTESSE DIVINE')
                     ->where('last_name', 'YAMAPI TCHASSI')
                     ->first();
    
    if ($student) {
        echo "✅ Élève trouvé: {$student->first_name} {$student->last_name}\n";
        echo "📝 Matricule: {$student->student_number}\n";
        echo "📞 Téléphone parent: " . ($student->parent_phone ?? 'NON CONFIGURÉ') . "\n";
        echo "📧 Email parent: " . ($student->parent_email ?? 'NON CONFIGURÉ') . "\n";
        echo "🏫 Classe série ID: {$student->class_series_id}\n\n";
        
        if (!$student->parent_phone) {
            echo "❌ PROBLÈME TROUVÉ: L'élève n'a pas de numéro de téléphone parent!\n";
            echo "💡 SOLUTION: Ajouter un numéro de téléphone parent pour cet élève.\n\n";
        }
    } else {
        echo "❌ Élève non trouvé dans la base de données!\n\n";
    }
    
    // 3. Test de configuration WhatsApp
    echo "3️⃣ TEST DE LA CONFIGURATION WHATSAPP:\n";
    $whatsappService = new WhatsAppService();
    $testResult = $whatsappService->testConfiguration();
    
    echo "Résultat du test: " . ($testResult['success'] ? '✅ SUCCÈS' : '❌ ÉCHEC') . "\n";
    echo "Message: " . $testResult['message'] . "\n\n";
    
    // 4. Lister quelques élèves avec numéros de téléphone
    echo "4️⃣ ÉLÈVES AVEC NUMÉROS DE TÉLÉPHONE PARENT:\n";
    $studentsWithPhone = Student::whereNotNull('parent_phone')
                               ->where('parent_phone', '!=', '')
                               ->limit(5)
                               ->get(['first_name', 'last_name', 'parent_phone', 'student_number']);
    
    if ($studentsWithPhone->count() > 0) {
        foreach ($studentsWithPhone as $s) {
            echo "✅ {$s->first_name} {$s->last_name} ({$s->student_number}) - {$s->parent_phone}\n";
        }
    } else {
        echo "❌ AUCUN ÉLÈVE n'a de numéro de téléphone parent configuré!\n";
    }
    
    echo "\n🎯 RECOMMANDATIONS:\n";
    echo "1. Configurer les numéros de téléphone des parents dans la base de données\n";
    echo "2. Vérifier que votre compte UltraMsg est actif\n";
    echo "3. Tester avec un élève qui a un numéro de parent configuré\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}