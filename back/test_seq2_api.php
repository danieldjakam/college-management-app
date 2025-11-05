<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\BulletinController;
use Illuminate\Http\Request;

echo "🧪 Test de l'API students-status pour Séquence 2\n";
echo str_repeat("=", 60) . "\n\n";

$controller = new BulletinController(
    app(\App\Services\BulletinService::class)
);

$request = new Request();
$response = $controller->getStudentsBulletinStatus(15, $request);

$data = json_decode($response->getContent(), true);

if (isset($data['students'][0])) {
    $firstStudent = $data['students'][0];
    echo "👤 Premier élève: {$firstStudent['first_name']} {$firstStudent['last_name']}\n\n";

    if (isset($firstStudent['bulletins']['sequence_2'])) {
        echo "📋 Données Séquence 2:\n";
        echo json_encode($firstStudent['bulletins']['sequence_2'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Pas de données sequence_2\n";
    }
} else {
    echo "❌ Aucun étudiant trouvé\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
