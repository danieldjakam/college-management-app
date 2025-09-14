<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== TEST CALCUL DS1 POUR HASSIM ACHTA ===\n";

$service = new App\Services\BulletinService();

// Test pour Mathématiques (SeriesSubject ID: 5)
echo "Testing DS1 calculation for student 251 (HASSIM ACHTA) in Mathématiques (subject ID: 5)\n";

$ds1 = $service->calculateDSAverage(1, 251, 5);
echo "DS1 calculé: " . ($ds1 !== null ? $ds1 : 'NULL') . "\n";
echo "DS1 attendu: 15.00 (moyenne de 12.00 et 18.00)\n";

// Test composition
echo "\n=== TEST COMPOSITION ===\n";
$composition = $service->getCompositionGrade(1, 251, 5);
echo "Composition 1 calculée: " . ($composition !== null ? $composition : 'NULL') . "\n";

// Test trimester grade
echo "\n=== TEST TRIMESTER GRADE ===\n";
$trimesterGrade = $service->calculateTrimesterGrade(1, 251, 5);
echo "Note trimestre 1 calculée: " . ($trimesterGrade !== null ? $trimesterGrade : 'NULL') . "\n";

echo "\nDone.\n";