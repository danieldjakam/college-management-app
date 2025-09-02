<?php

/**
 * Script de diagnostic détaillé WhatsApp UltraMsg
 * Pour identifier précisément pourquoi les messages ne partent pas
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNOSTIC WHATSAPP ULTRAMSG ===\n\n";

try {
    // 1. Récupérer la configuration
    $settings = SchoolSetting::getSettings();
    
    echo "1. CONFIGURATION:\n";
    echo "   Notifications activées: " . ($settings->whatsapp_notifications_enabled ? "✅ OUI" : "❌ NON") . "\n";
    echo "   API URL: " . ($settings->whatsapp_api_url ?: "❌ NON CONFIGURÉ") . "\n";
    echo "   Instance ID: " . ($settings->whatsapp_instance_id ?: "❌ NON CONFIGURÉ") . "\n";
    echo "   Token: " . ($settings->whatsapp_token ? "✅ PRÉSENT (" . substr($settings->whatsapp_token, 0, 10) . "...)" : "❌ NON CONFIGURÉ") . "\n";
    echo "   Numéro de test: " . ($settings->whatsapp_notification_number ?: "❌ NON CONFIGURÉ") . "\n\n";
    
    if (!$settings->whatsapp_token || !$settings->whatsapp_instance_id) {
        echo "❌ PROBLÈME: Configuration incomplète\n";
        exit(1);
    }
    
    // 2. Format du numéro
    $phoneNumber = $settings->whatsapp_notification_number;
    $formattedPhone = formatPhoneNumber($phoneNumber);
    echo "2. NUMÉRO DE TÉLÉPHONE:\n";
    echo "   Original: {$phoneNumber}\n";
    echo "   Formaté: {$formattedPhone}\n\n";
    
    // 3. Construction de l'URL
    $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat?token={$settings->whatsapp_token}";
    echo "3. URL D'ENVOI:\n";
    echo "   URL: {$url}\n\n";
    
    // 4. Test de l'API directe
    echo "4. TEST API DIRECT:\n";
    
    $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];
    
    $params = [
        'to' => $formattedPhone,
        'body' => "🧪 TEST DIRECT " . date('H:i:s') . "\n\nCe message teste la configuration UltraMsg."
    ];
    
    echo "   Paramètres POST:\n";
    echo "   - to: {$params['to']}\n";
    echo "   - body: " . substr($params['body'], 0, 50) . "...\n\n";
    
    echo "   Envoi en cours...\n";
    
    // Activer le logging détaillé
    config(['logging.channels.single.level' => 'debug']);
    
    $response = Http::withHeaders($headers)->asForm()->post($url, $params);
    
    echo "   Status HTTP: " . $response->status() . "\n";
    echo "   Headers de réponse:\n";
    foreach ($response->headers() as $key => $values) {
        echo "   - {$key}: " . implode(', ', $values) . "\n";
    }
    echo "   Body de réponse: " . $response->body() . "\n\n";
    
    // 5. Analyser la réponse
    if ($response->successful()) {
        $responseBody = $response->body();
        $responseData = json_decode($responseBody, true);
        
        if ($responseData) {
            echo "5. ANALYSE DE LA RÉPONSE JSON:\n";
            foreach ($responseData as $key => $value) {
                echo "   - {$key}: {$value}\n";
            }
            
            if (isset($responseData['sent']) && $responseData['sent'] === 'true') {
                echo "\n✅ SUCCÈS: Message envoyé!\n";
                echo "   Message ID: " . ($responseData['id'] ?? 'N/A') . "\n";
            } else {
                echo "\n❌ PROBLÈME: sent != 'true'\n";
                if (isset($responseData['error'])) {
                    echo "   Erreur: " . $responseData['error'] . "\n";
                }
            }
        } else {
            echo "5. RÉPONSE NON-JSON:\n";
            echo "   Contenu: {$responseBody}\n";
            
            // Vérifier les messages d'erreur communs
            if (strpos(strtolower($responseBody), 'wrong token') !== false) {
                echo "\n❌ ERREUR: Token incorrect\n";
            } elseif (strpos(strtolower($responseBody), 'not found') !== false) {
                echo "\n❌ ERREUR: Instance non trouvée\n";
            } elseif (strpos(strtolower($responseBody), 'stopped') !== false) {
                echo "\n❌ ERREUR: Instance suspendue (probablement non-paiement)\n";
            }
        }
    } else {
        echo "5. ERREUR HTTP:\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Body: " . $response->body() . "\n";
    }
    
    // 6. Test alternatif avec cURL direct
    echo "\n6. TEST AVEC cURL DIRECT:\n";
    
    $curlCommand = "curl -X POST '{$url}' " .
                  "-H 'Content-Type: application/x-www-form-urlencoded' " .
                  "-d 'to={$formattedPhone}' " .
                  "-d 'body=Test cURL direct'";
    
    echo "   Commande: {$curlCommand}\n\n";
    
    $curlOutput = shell_exec($curlCommand);
    echo "   Résultat cURL: {$curlOutput}\n\n";
    
    // 7. Vérifier la connectivité internet
    echo "7. TEST CONNECTIVITÉ:\n";
    $pingTest = Http::get('https://api.ultramsg.com');
    echo "   Ping UltraMsg: " . ($pingTest->successful() ? "✅ OK" : "❌ ÉCHEC") . "\n";
    
    if (!$pingTest->successful()) {
        echo "   Problème de connectivité vers UltraMsg\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR FATALE: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

function formatPhoneNumber($phoneNumber)
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

echo "\n=== FIN DU DIAGNOSTIC ===\n";
?>