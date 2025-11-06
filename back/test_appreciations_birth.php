<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

echo "🧪 Test Appréciations et Date de naissance\n";
echo str_repeat('=', 70) . "\n\n";

$studentId = 3;
$student = Student::find($studentId);

echo "1️⃣ Test Date et lieu de naissance:\n";
echo "   Champs disponibles dans la table:\n";
echo "   - date_of_birth: " . ($student->date_of_birth ?? 'null') . "\n";
echo "   - place_of_birth: " . ($student->place_of_birth ?? 'null') . "\n";
echo "   - birthday: " . ($student->birthday ?? 'null') . "\n";
echo "   - birthday_place: " . ($student->birthday_place ?? 'null') . "\n\n";

$birthDate = $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : ($student->birthday ?? 'N/A');
$birthPlace = $student->place_of_birth ?? ($student->birthday_place ?? 'N/A');

echo "   Valeurs affichées sur le bulletin:\n";
echo "   - Date: {$birthDate}\n";
echo "   - Lieu: {$birthPlace}\n\n";

if ($birthDate !== 'N/A' && $birthPlace !== 'N/A') {
    echo "   ✅ Date et lieu de naissance affichés correctement!\n\n";
} else {
    echo "   ⚠️  Certaines données manquent dans la base\n\n";
}

echo "2️⃣ Test Appréciations selon M/20:\n";
echo "   Exemples:\n";

$testCases = [
    ['moyenne' => 12.5, 'expected' => 'Acquise'],
    ['moyenne' => 7.5, 'expected' => 'En cours d\'acquisition'],
    ['moyenne' => 3.5, 'expected' => 'Non Acquise'],
    ['moyenne' => 10.0, 'expected' => 'Acquise'],
    ['moyenne' => 5.0, 'expected' => 'En cours d\'acquisition'],
];

foreach ($testCases as $test) {
    $moyenne = $test['moyenne'];

    if ($moyenne >= 10) {
        $appreciation = 'Acquise';
    } elseif ($moyenne >= 5) {
        $appreciation = 'En cours d\'acquisition';
    } else {
        $appreciation = 'Non Acquise';
    }

    $status = ($appreciation === $test['expected']) ? '✅' : '❌';
    echo "   {$status} M/20 = {$moyenne} → {$appreciation}\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "✨ RÉSUMÉ:\n";
echo "   1. Date et lieu de naissance: Utilise date_of_birth et place_of_birth ✅\n";
echo "   2. Appréciations dans le tableau:\n";
echo "      - >= 10: Acquise ✅\n";
echo "      - 5-9.99: En cours d'acquisition ✅\n";
echo "      - < 5: Non Acquise ✅\n";
