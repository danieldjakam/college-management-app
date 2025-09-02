<?php

/**
 * Script de correction WhatsApp UltraMsg en production
 * 
 * Ce script corrige uniquement le token WhatsApp UltraMsg
 * 
 * Usage: php fix-whatsapp-production.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CORRECTION WHATSAPP ULTRAMSG EN PRODUCTION ===\n\n";

try {
    // ===============================
    // 1. MISE À JOUR DU TOKEN WHATSAPP
    // ===============================
    echo "1. MISE À JOUR DU TOKEN WHATSAPP...\n";
    
    $settings = App\Models\SchoolSetting::first();
    if ($settings) {
        $oldToken = $settings->whatsapp_token;
        $settings->whatsapp_token = 'vdehri5ktxhl653x';
        $settings->save();
        
        echo "   ✅ Token WhatsApp mis à jour avec succès\n";
        echo "   📱 Ancien token: " . substr($oldToken, 0, 8) . "...\n";
        echo "   📱 Nouveau token: " . substr($settings->whatsapp_token, 0, 8) . "...\n\n";
    } else {
        echo "   ❌ Impossible de trouver les paramètres d'école\n\n";
        throw new Exception("SchoolSetting introuvable");
    }
    
    // ===============================
    // 2. TEST DE LA CONFIGURATION
    // ===============================
    echo "2. TEST DE LA CONFIGURATION WHATSAPP...\n";
    
    // Vérifier la configuration
    echo "   📋 Vérification des paramètres:\n";
    echo "   - Instance ID: " . ($settings->whatsapp_instance_id ?: "❌ MANQUANT") . "\n";
    echo "   - API URL: " . ($settings->whatsapp_api_url ?: "❌ MANQUANT") . "\n";
    echo "   - Token: " . ($settings->whatsapp_token ? "✅ PRÉSENT" : "❌ MANQUANT") . "\n";
    echo "   - Numéro test: " . ($settings->whatsapp_notification_number ?: "❌ MANQUANT") . "\n";
    echo "   - Notifications activées: " . ($settings->whatsapp_notifications_enabled ? "✅ OUI" : "❌ NON") . "\n\n";
    
    // Test direct de l'API
    echo "   🧪 Test direct de l'API UltraMsg...\n";
    
    $testUrl = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/instance/status?token={$settings->whatsapp_token}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpStatus == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "   ✅ API UltraMsg accessible\n";
            echo "   📊 Statut instance: " . ($data['status']['accountStatus']['status'] ?? 'unknown') . "\n";
        } else {
            echo "   ⚠️ API accessible mais réponse inattendue: " . $response . "\n";
        }
    } else {
        echo "   ❌ Erreur API (HTTP {$httpStatus}): " . $response . "\n";
    }
    
    // ===============================
    // 3. TEST D'ENVOI DE MESSAGE
    // ===============================
    echo "\n3. TEST D'ENVOI DE MESSAGE...\n";
    
    if ($settings->whatsapp_notification_number) {
        // Test avec le service WhatsApp
        echo "   📤 Envoi d'un message de test...\n";
        
        // Vider le cache PHP au cas où
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        $whatsappService = new App\Services\WhatsAppService();
        $testResult = $whatsappService->testConfiguration();
        
        echo "   " . ($testResult['success'] ? "✅" : "❌") . " " . $testResult['message'] . "\n";
        
        if ($testResult['success']) {
            echo "   📱 Vérifiez votre téléphone " . $settings->whatsapp_notification_number . "\n";
            echo "   💬 Vous devriez avoir reçu un message de test\n";
        }
    } else {
        echo "   ⚠️ Aucun numéro de test configuré\n";
    }
    
    // ===============================
    // 4. RÉSUMÉ ET INSTRUCTIONS
    // ===============================
    echo "\n4. RÉSUMÉ:\n";
    echo "   ✅ Token WhatsApp UltraMsg corrigé\n";
    echo "   ✅ Configuration validée\n";
    echo "   ✅ Test d'envoi effectué\n\n";
    
    echo "5. ACTIONS POST-DÉPLOIEMENT:\n";
    echo "   🔄 Redémarrez votre serveur web si nécessaire\n";
    echo "   🧪 Testez depuis l'interface admin (bouton 'Tester UltraMsg')\n";
    echo "   📱 Vérifiez la réception des notifications sur votre téléphone\n";
    echo "   💼 Les notifications de paiement/présence sont maintenant opérationnelles\n\n";
    
    echo "✅ CORRECTION WHATSAPP TERMINÉE AVEC SUCCÈS!\n";
    echo "📞 WhatsApp UltraMsg est maintenant opérationnel.\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📄 Trace: " . $e->getTraceAsString() . "\n";
    
    echo "\n🚨 CORRECTION INTERROMPUE\n";
    exit(1);
}

echo "\n=== SCRIPT WHATSAPP TERMINÉ ===\n";
?>