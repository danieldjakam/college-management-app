<?php
/**
 * Script de débogage CORS
 * À placer à la racine du domaine pour tester
 * Usage: http://admin1.cpb-douala.com/cors_debug.php
 */

// Headers CORS complets
header("Access-Control-Allow-Origin: http://admin.cpb-douala.com");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Si c'est une requête OPTIONS, répondre immédiatement
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test de base pour vérifier que le serveur répond
$response = [
    'success' => true,
    'message' => 'CORS configuré correctement',
    'server_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'Non défini',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Non défini',
        'time' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_NAME'] ?? 'Non défini'
    ],
    'headers_sent' => [
        'Access-Control-Allow-Origin' => 'http://admin.cpb-douala.com',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept',
        'Access-Control-Allow-Credentials' => 'true'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);