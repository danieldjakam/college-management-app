<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BulletinService;

echo "🧪 Test du bulletin trimestre avec Anglais (13.50 + 16.60)\n";
echo str_repeat('=', 60) . "\n\n";

// Trouver un étudiant qui a des notes en Anglais pour seq1 et seq2
$studentWithAnglais = DB::table('grades as g1')
    ->join('grades as g2', function($join) {
        $join->on('g1.student_id', '=', 'g2.student_id')
             ->on('g1.subject_id', '=', 'g2.subject_id');
    })
    ->join('subjects', 'subjects.id', '=', 'g1.subject_id')
    ->join('sequences as seq1', 'seq1.id', '=', 'g1.sequence_id')
    ->join('sequences as seq2', 'seq2.id', '=', 'g2.sequence_id')
    ->where('subjects.name', 'LIKE', '%Anglais%')
    ->where('seq1.number', 1)
    ->where('seq2.number', 2)
    ->whereNotNull('g1.score')
    ->whereNotNull('g2.score')
    ->select('g1.student_id', 'subjects.name as subject_name',
             'g1.score as seq1_score', 'g2.score as seq2_score',
             'subjects.id as subject_id')
    ->first();

if (!$studentWithAnglais) {
    echo "❌ Aucun étudiant trouvé avec des notes en Anglais pour seq1 et seq2\n";
    exit;
}

echo "📚 Étudiant trouvé:\n";
echo "   Student ID: {$studentWithAnglais->student_id}\n";
echo "   Matière: {$studentWithAnglais->subject_name}\n";
echo "   Seq 1: {$studentWithAnglais->seq1_score}\n";
echo "   Seq 2: {$studentWithAnglais->seq2_score}\n";
echo "   DS attendu: " . (($studentWithAnglais->seq1_score + $studentWithAnglais->seq2_score) / 2) . "\n\n";

// Générer les données du bulletin
$bulletinService = app(BulletinService::class);
$data = $bulletinService->generateTrimesterBulletinData(1, $studentWithAnglais->student_id, 'premier');

// Trouver la matière Anglais dans les résultats
foreach ($data['subjects'] as $subject) {
    if (stripos($subject['name'], 'Anglais') !== false) {
        echo "📋 Résultat du bulletin trimestre:\n";
        echo "   Matière: {$subject['name']}\n";
        echo "   DS (N/20): " . ($subject['ds'] ?? 'null') . "\n";
        echo "   Composition: " . ($subject['composition'] ?? 'null') . "\n";
        echo "   Moyenne (M/20): " . ($subject['score'] ?? 'null') . "\n";

        $expectedDS = ($studentWithAnglais->seq1_score + $studentWithAnglais->seq2_score) / 2;
        $actualDS = $subject['ds'] ?? 0;

        if (abs($expectedDS - $actualDS) < 0.01) {
            echo "\n✅ DS CORRECT! Attendu: {$expectedDS}, Obtenu: {$actualDS}\n";
        } else {
            echo "\n❌ DS INCORRECT! Attendu: {$expectedDS}, Obtenu: {$actualDS}\n";
        }
        break;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
