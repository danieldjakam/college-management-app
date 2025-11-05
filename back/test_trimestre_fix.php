<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BulletinService;

echo "🧪 Test du bulletin trimestre pour AISSATOU (ID 3)\n";
echo str_repeat('=', 60) . "\n\n";

$bulletinService = app(BulletinService::class);
$data = $bulletinService->generateTrimesterBulletinData(1, 3, 'premier');

if (isset($data['subjects'][0])) {
    echo "📋 Premier sujet:\n";
    $subject = $data['subjects'][0];
    echo "   Nom: {$subject['name']}\n";
    echo "   DS: " . ($subject['ds'] ?? 'null') . "\n";
    echo "   Composition: " . ($subject['composition'] ?? 'null') . "\n";
    echo "   Score (trimestre final): " . ($subject['score'] ?? 'null') . "\n";
}

// Trouver Anglais
foreach ($data['subjects'] as $subject) {
    if (stripos($subject['name'], 'Anglais') !== false) {
        echo "\n📋 Anglais:\n";
        echo "   DS: " . ($subject['ds'] ?? 'null') . "\n";
        echo "   Composition: " . ($subject['composition'] ?? 'null') . "\n";
        echo "   Score (trimestre final): " . ($subject['score'] ?? 'null') . "\n";
        echo "\n✅ Attendu DS = (13.50 + 16.60) / 2 = 15.05\n";
        break;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
