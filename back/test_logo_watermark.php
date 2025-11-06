<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BulletinService;
use App\Models\Student;

echo "🎨 Test Logo et Filigrane\n";
echo str_repeat('=', 70) . "\n\n";

$studentId = 3;
$trimesterNumber = 1;

$bulletinService = app(BulletinService::class);

echo "1️⃣ Génération du bulletin trimestre 1...\n";

try {
    // Générer les données
    $bulletinData = $bulletinService->generateTrimesterBulletinData($trimesterNumber, $studentId, 'premier');

    if (!$bulletinData) {
        throw new Exception("Impossible de générer les données du bulletin");
    }

    // Rendre le template HTML
    $htmlContent = $bulletinService->renderBulletinTemplate('trimester', $bulletinData, true);

    // Générer le PDF
    $student = Student::find($studentId);
    $fileName = 'bulletin_test_logo_' . $student->first_name . '_' . $student->last_name . '.pdf';
    $filePath = $bulletinService->generatePDF($htmlContent, $fileName);

    echo "   ✅ Bulletin généré: {$fileName}\n";
    echo "   📂 Chemin: storage/app/{$filePath}\n\n";

    // Vérifier la taille du fichier
    $fullPath = storage_path('app/' . $filePath);
    if (file_exists($fullPath)) {
        $fileSize = filesize($fullPath);
        echo "   📊 Taille du fichier: " . number_format($fileSize / 1024, 2) . " KB\n\n";

        if ($fileSize > 50000) {
            echo "   ✅ Fichier de bonne taille (le logo base64 est inclus)\n\n";
        } else {
            echo "   ⚠️  Fichier petit (le logo n'est peut-être pas inclus)\n\n";
        }
    } else {
        echo "   ❌ Fichier non trouvé: {$fullPath}\n\n";
    }

    echo "2️⃣ Vérifications visuelles:\n";
    echo "   - Le logo doit apparaître dans le cercle de l'en-tête (45px x 45px)\n";
    echo "   - Le logo doit apparaître en filigrane au centre de la page (opacité 8%)\n";
    echo "   - Le filigrane ne doit pas gêner la lecture du contenu\n\n";

    echo str_repeat('=', 70) . "\n";
    echo "✨ Pour vérifier:\n";
    echo "   1. Ouvrir le fichier PDF généré\n";
    echo "   2. Vérifier que le logo s'affiche dans le cercle de l'en-tête\n";
    echo "   3. Vérifier que le filigrane apparaît en arrière-plan\n";

} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "   " . $e->getTraceAsString() . "\n";
}
