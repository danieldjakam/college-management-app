<?php

/**
 * Script de correction finale WhatsApp UltraMsg
 * Corrige définitivement le problème de token
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CORRECTION FINALE WHATSAPP ULTRAMSG ===\n\n";

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;

try {
    // 1. Test de plusieurs approches pour envoyer le token
    
    $settings = SchoolSetting::getSettings();
    $phoneNumber = '+237696118389'; // Votre numéro de test
    $message = "🔧 TEST CORRECTION FINALE - " . date('H:i:s');
    
    echo "Instance ID: {$settings->whatsapp_instance_id}\n";
    echo "Token: " . substr($settings->whatsapp_token, 0, 10) . "...\n\n";
    
    // APPROCHE 1: Token en paramètre GET séparé (recommandé par UltraMsg)
    echo "1. TEST avec token en paramètre GET séparé:\n";
    
    $url1 = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat";
    $params1 = [
        'token' => $settings->whatsapp_token,
        'to' => $phoneNumber,
        'body' => $message . " (Approche 1)"
    ];
    
    $response1 = Http::asForm()->post($url1, $params1);
    echo "   Status: " . $response1->status() . "\n";
    echo "   Response: " . $response1->body() . "\n\n";
    
    // APPROCHE 2: Token en GET avec Http::withOptions
    echo "2. TEST avec withOptions:\n";
    
    $url2 = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat";
    $params2 = [
        'to' => $phoneNumber,
        'body' => $message . " (Approche 2)"
    ];
    
    $response2 = Http::withOptions([
        'query' => ['token' => $settings->whatsapp_token]
    ])->asForm()->post($url2, $params2);
    
    echo "   Status: " . $response2->status() . "\n";
    echo "   Response: " . $response2->body() . "\n\n";
    
    // APPROCHE 3: cURL brut
    echo "3. TEST avec cURL brut:\n";
    
    $curlData = http_build_query([
        'to' => $phoneNumber,
        'body' => $message . " (cURL brut)"
    ]);
    
    $curlUrl = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat?token={$settings->whatsapp_token}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $curlUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $curlResponse = curl_exec($ch);
    $curlStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Status: {$curlStatus}\n";
    echo "   Response: {$curlResponse}\n\n";
    
    // Identifier l'approche qui fonctionne
    $workingMethod = null;
    
    $responses = [
        1 => json_decode($response1->body(), true),
        2 => json_decode($response2->body(), true),
        3 => json_decode($curlResponse, true)
    ];
    
    foreach ($responses as $method => $data) {
        if ($data && isset($data['sent']) && $data['sent'] === 'true') {
            $workingMethod = $method;
            echo "✅ MÉTHODE {$method} FONCTIONNE!\n";
            break;
        }
    }
    
    if (!$workingMethod) {
        echo "❌ Aucune méthode ne fonctionne. Vérifiez votre configuration UltraMsg.\n";
        
        // Afficher les détails pour diagnostic
        foreach ($responses as $method => $data) {
            if ($data && isset($data['error'])) {
                echo "   Méthode {$method} - Erreur: {$data['error']}\n";
            }
        }
        exit(1);
    }
    
    // Appliquer la correction dans le code
    echo "\n4. APPLICATION DE LA CORRECTION:\n";
    
    $serviceFile = __DIR__ . '/app/Services/WhatsAppService.php';
    $backup = $serviceFile . '.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($serviceFile, $backup)) {
        throw new Exception("Impossible de créer la sauvegarde");
    }
    echo "   ✅ Sauvegarde créée: " . basename($backup) . "\n";
    
    $content = file_get_contents($serviceFile);
    
    if ($workingMethod == 1) {
        // Méthode 1: Token en POST data
        $oldCode = 'protected function sendMessage($phoneNumber, $message)
    {
        try {
            $settings = SchoolSetting::getSettings();
            
            // Si aucune API n\'est configurée, simuler l\'envoi
            if (!$settings->whatsapp_api_url || !$settings->whatsapp_instance_id || !$settings->whatsapp_token) {
                Log::info(\'Simulation envoi WhatsApp\', [
                    \'to\' => $phoneNumber,
                    \'message\' => $message
                ]);
                return true;
            }

            // CORRECTION FINALE: Token DOIT être en paramètre GET selon UltraMsg
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat?token={$settings->whatsapp_token}";
            
            // Headers selon UltraMsg
            $headers = [
                \'Content-Type\' => \'application/x-www-form-urlencoded\'
            ];
            
            // Paramètres POST (sans token car il est en GET)
            $params = [
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'body\' => $message
            ];';
        
        $newCode = 'protected function sendMessage($phoneNumber, $message)
    {
        try {
            $settings = SchoolSetting::getSettings();
            
            // Si aucune API n\'est configurée, simuler l\'envoi
            if (!$settings->whatsapp_api_url || !$settings->whatsapp_instance_id || !$settings->whatsapp_token) {
                Log::info(\'Simulation envoi WhatsApp\', [
                    \'to\' => $phoneNumber,
                    \'message\' => $message
                ]);
                return true;
            }

            // CORRECTION FINALE TESTÉE: Token dans les données POST
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat";
            
            // Headers selon UltraMsg
            $headers = [
                \'Content-Type\' => \'application/x-www-form-urlencoded\'
            ];
            
            // Paramètres POST avec token
            $params = [
                \'token\' => $settings->whatsapp_token,
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'body\' => $message
            ];';
        
    } elseif ($workingMethod == 2) {
        // Méthode 2: Token avec withOptions
        $oldCode = '            // CORRECTION FINALE: Token DOIT être en paramètre GET selon UltraMsg
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat?token={$settings->whatsapp_token}";
            
            // Headers selon UltraMsg
            $headers = [
                \'Content-Type\' => \'application/x-www-form-urlencoded\'
            ];
            
            // Paramètres POST (sans token car il est en GET)
            $params = [
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'body\' => $message
            ];

            // Log pour débogage
            Log::info(\'Envoi WhatsApp UltraMsg\', [
                \'url\' => $url,
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'has_token\' => !empty($settings->whatsapp_token)
            ]);

            // Utilisation de Http::asForm() pour envoyer en application/x-www-form-urlencoded
            $response = Http::withHeaders($headers)->asForm()->post($url, $params);';
        
        $newCode = '            // CORRECTION FINALE TESTÉE: Token avec withOptions
            $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/chat";
            
            // Headers selon UltraMsg
            $headers = [
                \'Content-Type\' => \'application/x-www-form-urlencoded\'
            ];
            
            // Paramètres POST
            $params = [
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'body\' => $message
            ];

            // Log pour débogage
            Log::info(\'Envoi WhatsApp UltraMsg\', [
                \'url\' => $url,
                \'to\' => $this->formatPhoneNumber($phoneNumber),
                \'has_token\' => !empty($settings->whatsapp_token)
            ]);

            // Token en paramètre GET avec withOptions
            $response = Http::withOptions([
                \'query\' => [\'token\' => $settings->whatsapp_token]
            ])->withHeaders($headers)->asForm()->post($url, $params);';
        
    } elseif ($workingMethod == 3) {
        // Méthode 3: cURL brut
        $oldCode = '            // Utilisation de Http::asForm() pour envoyer en application/x-www-form-urlencoded
            $response = Http::withHeaders($headers)->asForm()->post($url, $params);

            if ($response->successful()) {';
        
        $newCode = '            // CORRECTION FINALE TESTÉE: Utiliser cURL brut car Laravel HTTP a des problèmes
            $curlData = http_build_query($params);
            $curlUrl = $url . "?token={$settings->whatsapp_token}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $curlUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $responseBody = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Simuler l\'objet Response de Laravel
            $response = (object)[
                \'status\' => $httpStatus,
                \'body\' => $responseBody,
                \'successful\' => $httpStatus >= 200 && $httpStatus < 300
            ];

            if ($response->successful) {';
    }
    
    if (isset($oldCode) && isset($newCode)) {
        $content = str_replace($oldCode, $newCode, $content);
        
        if (file_put_contents($serviceFile, $content) === false) {
            throw new Exception("Impossible d'écrire le fichier corrigé");
        }
        
        echo "   ✅ Code corrigé avec la méthode {$workingMethod}\n";
    }
    
    // Test final
    echo "\n5. TEST FINAL:\n";
    
    // Vider le cache PHP
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Tester avec le service corrigé
    $whatsappService = new \App\Services\WhatsAppService();
    $testResult = $whatsappService->testConfiguration();
    
    echo "   " . ($testResult['success'] ? "✅" : "❌") . " " . $testResult['message'] . "\n";
    
    if ($testResult['success']) {
        echo "\n🎉 SUCCÈS! WhatsApp UltraMsg fonctionne maintenant.\n";
        echo "📱 Vérifiez votre téléphone pour le message de test.\n";
    }
    
    echo "\n📁 Sauvegarde: " . basename($backup) . "\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    if (isset($backup) && file_exists($backup) && isset($serviceFile)) {
        copy($backup, $serviceFile);
        echo "✅ Fichier original restauré\n";
    }
    
    exit(1);
}

echo "\n=== CORRECTION TERMINÉE ===\n";
?>