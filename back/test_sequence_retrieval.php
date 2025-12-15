<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

echo "=== TEST RÉCUPÉRATION DES SÉQUENCES ===\n\n";

$service = new BulletinService();

// Utiliser reflection pour accéder à la méthode protected
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('getSequencesForTrimester');
$method->setAccessible(true);

echo "Test avec Trimestre 1:\n";
$sequences = $method->invoke($service, 1);

echo "Nombre de séquences retournées: " . $sequences->count() . "\n\n";

foreach ($sequences as $seq) {
    echo "  - ID: {$seq->id}\n";
    echo "    Name: {$seq->name}\n";
    echo "    Number: {$seq->number}\n";
    echo "    is_composition: {$seq->is_composition}\n";
    echo "    trimester_id: {$seq->trimester_id}\n\n";
}

// Tester pour GERMAINE
echo "\n=== TEST AVEC GERMAINE - ANGLAIS ===\n";

$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

$anglais = DB::table('class_series_subjects')
    ->join('subjects', 'class_series_subjects.subject_id', '=', 'subjects.id')
    ->where('class_series_subjects.class_series_id', $student->class_series_id)
    ->where('subjects.name', 'Anglais')
    ->select('class_series_subjects.*')
    ->first();

echo "Student ID: {$student->id}\n";
echo "Subject ID (class_series_subject): {$anglais->id}\n\n";

// Récupérer les notes individuelles
$individualGrades = $service->getIndividualSequenceGrades(1, $student->id, $anglais->id);

echo "Notes individuelles retournées:\n";
echo "  Séquence 1: " . ($individualGrades[0] ?? 'NULL') . "\n";
echo "  Séquence 2: " . ($individualGrades[1] ?? 'NULL') . "\n";

// Vérifier les grades dans la base
echo "\n=== GRADES RÉELS DANS LA BASE ===\n";
$gradesInDB = DB::table('grades')
    ->where('student_id', $student->id)
    ->where('class_series_subject_id', $anglais->id)
    ->where('trimester_id', 1)
    ->whereNotNull('score')
    ->orderBy('sequence_id')
    ->get();

foreach ($gradesInDB as $g) {
    echo "  Seq {$g->sequence_id}: {$g->score}/{$g->max_score}\n";
}
