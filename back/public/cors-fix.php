<?php
// CORS Fix pour production - À placer au tout début de index.php

// Origines autorisées
$allowed_origins = [
    'http://admin.cpb-douala.com',
    'https://admin.cpb-douala.com'
];

// Récupérer l'origine de la requête
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Vérifier si l'origine est autorisée
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
}

// Si c'est une requête OPTIONS (preflight), répondre immédiatement
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}