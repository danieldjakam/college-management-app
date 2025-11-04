<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Test de la complétion Séquence 2\n";
echo str_repeat("=", 60) . "\n\n";

$seriesId = 15; // 6ème A
$students = DB::table('students')
    ->where('class_series_id', $seriesId)
    ->where('is_active', true)
    ->orderBy('last_name')
    ->limit(5)
    ->get();

echo "📚 Premiers 5 élèves de 6ème A:\n\n";

foreach ($students as $student) {
    echo "👤 {$student->first_name} {$student->last_name} (ID: {$student->id})\n";

    // Get subjects count
    $subjectsCount = DB::table('class_series_subjects')
        ->where('class_series_id', $seriesId)
        ->count();

    foreach ([1, 2] as $seqNum) {
        $sequence = DB::table('sequences')->where('number', $seqNum)->first();

        $gradesCount = DB::table('grades')
            ->where('student_id', $student->id)
            ->where('sequence_id', $sequence->id)
            ->whereNotNull('score')
            ->count();

        $percentage = round(($gradesCount / $subjectsCount) * 100, 1);

        echo "  Seq {$seqNum}: {$gradesCount}/{$subjectsCount} = {$percentage}%\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
