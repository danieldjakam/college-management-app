<?php

/**
 * Script pour configurer imgbb et tester l'envoi d'image WhatsApp
 * 
 * imgbb offre un service gratuit d'hébergement d'images avec API
 * Créez un compte gratuit sur https://imgbb.com et obtenez une clé API
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONFIGURATION IMGBB POUR WHATSAPP ===\n\n";

// Clé API imgbb - Obtenez la vôtre gratuitement sur https://api.imgbb.com/
$IMGBB_API_KEY = '6d207e02198a847aa98d0a2a901485a5'; // Clé de test publique (limitée)

echo "1. AJOUT DE LA CLÉ IMGBB DANS .env...\n";

$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Vérifier si IMGBB_API_KEY existe déjà
if (strpos($envContent, 'IMGBB_API_KEY=') === false) {
    // Ajouter la clé
    $envContent .= "\n# imgbb API pour upload d'images WhatsApp\nIMGBB_API_KEY={$IMGBB_API_KEY}\n";
    file_put_contents($envFile, $envContent);
    echo "   ✅ Clé imgbb ajoutée au .env\n";
} else {
    echo "   ℹ️ Clé imgbb déjà présente dans .env\n";
}

echo "\n2. TEST D'UPLOAD D'IMAGE...\n";

// Créer une image de test simple
$testImage = __DIR__ . '/test-imgbb.png';

// Créer une image simple avec GD
$width = 400;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Couleurs
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 46, 139, 87);
$black = imagecolorallocate($image, 0, 0, 0);

// Fond blanc
imagefill($image, 0, 0, $white);

// Rectangle coloré
imagefilledrectangle($image, 10, 10, $width-10, $height-10, $blue);

// Texte
$text = "Test Reçu WhatsApp";
$fontSize = 5;
$textWidth = imagefontwidth($fontSize) * strlen($text);
$textHeight = imagefontheight($fontSize);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;
imagestring($image, $fontSize, $x, $y, $text, $white);

// Sauvegarder l'image
imagepng($image, $testImage);
imagedestroy($image);

echo "   ✅ Image de test créée: test-imgbb.png\n";

// Tester l'upload sur imgbb
echo "\n3. UPLOAD SUR IMGBB...\n";

$imageData = base64_encode(file_get_contents($testImage));

$response = \Illuminate\Support\Facades\Http::asForm()->post('https://api.imgbb.com/1/upload', [
    'key' => $IMGBB_API_KEY,
    'image' => $imageData,
    'expiration' => 3600 // Expire dans 1 heure
]);

if ($response->successful()) {
    $data = $response->json();
    if (isset($data['data']['url'])) {
        echo "   ✅ Upload réussi!\n";
        echo "   📸 URL de l'image: " . $data['data']['url'] . "\n";
        echo "   🔗 URL de visualisation: " . $data['data']['url_viewer'] . "\n";
        echo "   ⏱️ Expire dans: 1 heure\n";
        
        // Tester l'envoi WhatsApp avec cette image
        echo "\n4. TEST ENVOI WHATSAPP AVEC IMAGE...\n";
        
        $phoneNumber = '659339778';
        $imageUrl = $data['data']['url'];
        
        $settings = \App\Models\SchoolSetting::getSettings();
        $url = "https://api.ultramsg.com/instance{$settings->whatsapp_instance_id}/messages/image?token={$settings->whatsapp_token}";
        
        $params = [
            'to' => '+' . $phoneNumber,
            'image' => $imageUrl,
            'caption' => "🧪 Test envoi image via imgbb\n📱 Reçu de paiement\n✅ Image hébergée temporairement"
        ];
        
        $whatsappResponse = \Illuminate\Support\Facades\Http::asForm()->post($url, $params);
        
        if ($whatsappResponse->successful()) {
            $whatsappData = $whatsappResponse->json();
            if (isset($whatsappData['sent']) && $whatsappData['sent'] === 'true') {
                echo "   ✅ Image envoyée sur WhatsApp!\n";
                echo "   📱 Vérifiez votre téléphone: " . $phoneNumber . "\n";
            } else {
                echo "   ❌ Erreur WhatsApp: " . $whatsappResponse->body() . "\n";
            }
        } else {
            echo "   ❌ Erreur HTTP WhatsApp: " . $whatsappResponse->status() . "\n";
            echo "   Response: " . $whatsappResponse->body() . "\n";
        }
        
    } else {
        echo "   ❌ Erreur imgbb: " . json_encode($data) . "\n";
    }
} else {
    echo "   ❌ Erreur HTTP imgbb: " . $response->status() . "\n";
    echo "   Response: " . $response->body() . "\n";
}

// Nettoyer
if (file_exists($testImage)) {
    unlink($testImage);
}

echo "\n=== CONFIGURATION TERMINÉE ===\n";
echo "\n📝 INSTRUCTIONS:\n";
echo "1. La clé imgbb de test a été ajoutée à votre .env\n";
echo "2. Pour une utilisation en production, créez votre propre compte sur https://imgbb.com\n";
echo "3. Obtenez votre clé API gratuite personnelle\n";
echo "4. Remplacez IMGBB_API_KEY dans le .env\n";
echo "5. Les images de reçus seront automatiquement uploadées et envoyées\n";
?>