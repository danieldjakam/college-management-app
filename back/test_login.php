<?php
/**
 * Script de test pour vérifier l'authentification et CORS en production
 * Usage: php test_login.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "===========================================\n";
echo "TEST D'AUTHENTIFICATION EN PRODUCTION\n";
echo "===========================================\n\n";

// Test 1: Vérifier l'endpoint login via curl
echo "1. Test de l'endpoint /api/auth/login...\n";

$url = 'http://admin1.cpb-douala.com/api/auth/login';
$data = json_encode([
    'username' => 'admin',
    'password' => 'password123'
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: http://admin.cpb-douala.com'
    ],
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: $error\n";
} else {
    echo "Status Code: $httpCode\n";
    
    // Séparer les headers du body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "\nHeaders reçus:\n";
    echo $headers . "\n";
    
    if ($httpCode == 200) {
        echo "✅ Login réussi!\n";
        $data = json_decode($body, true);
        if (isset($data['access_token'])) {
            echo "Token reçu: " . substr($data['access_token'], 0, 50) . "...\n";
        }
    } else {
        echo "❌ Échec du login\n";
        echo "Réponse: $body\n";
    }
}

echo "\n===========================================\n";
echo "2. Test des headers CORS...\n";
echo "===========================================\n";

// Test preflight request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'OPTIONS',
    CURLOPT_HTTPHEADER => [
        'Origin: http://admin.cpb-douala.com',
        'Access-Control-Request-Method: POST',
        'Access-Control-Request-Headers: Content-Type, Authorization'
    ],
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status Code OPTIONS: $httpCode\n";
echo "Headers OPTIONS:\n$response\n";

// Vérifier les headers CORS spécifiques
if (strpos($response, 'Access-Control-Allow-Origin') !== false) {
    echo "✅ Header Access-Control-Allow-Origin présent\n";
} else {
    echo "❌ Header Access-Control-Allow-Origin manquant\n";
}

if (strpos($response, 'Access-Control-Allow-Methods') !== false) {
    echo "✅ Header Access-Control-Allow-Methods présent\n";
} else {
    echo "❌ Header Access-Control-Allow-Methods manquant\n";
}

echo "\n===========================================\n";
echo "3. Test de la route /api/me...\n";
echo "===========================================\n";

// D'abord, obtenir un token
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'username' => 'admin',
        'password' => 'password123'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$loginResponse = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($loginResponse, true);

if (isset($loginData['access_token'])) {
    $token = $loginData['access_token'];
    echo "Token obtenu, test de /api/me...\n";
    
    // Test /api/me avec le token
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://admin1.cpb-douala.com/api/me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Origin: http://admin.cpb-douala.com'
        ],
        CURLOPT_HEADER => true
    ]);
    
    $meResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status Code /api/me: $httpCode\n";
    
    if ($httpCode == 200) {
        echo "✅ Endpoint /api/me accessible\n";
    } else {
        echo "❌ Problème avec /api/me\n";
        echo "Réponse: $meResponse\n";
    }
} else {
    echo "❌ Impossible d'obtenir un token pour tester /api/me\n";
}

echo "\n✅ Tests terminés!\n";
echo "\nPour tester depuis le frontend:\n";
echo "1. Ouvrez http://admin.cpb-douala.com\n";
echo "2. Utilisez: admin / password123\n";
echo "3. Vérifiez la console du navigateur pour les erreurs CORS\n";