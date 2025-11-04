<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 Nettoyage des notes dupliquées - Séquence 2\n";
echo str_repeat("=", 60) . "\n\n";

$sequenceId = 2; // Séquence 2
$seriesId = 15; // 6ème A

// Get students
$students = DB::table('students')
    ->where('class_series_id', $seriesId)
    ->where('is_active', true)
    ->get();

$deletedCount = 0;

foreach ($students as $student) {
    // Get subjects
    $subjects = DB::table('class_series_subjects')
        ->where('class_series_id', $seriesId)
        ->get();

    foreach ($subjects as $subject) {
        // Get all grades for this student-subject-sequence
        $grades = DB::table('grades')
            ->where('student_id', $student->id)
            ->where('sequence_id', $sequenceId)
            ->where('class_series_subject_id', $subject->id)
            ->orderBy('id', 'asc') // Garder le premier créé
            ->get();

        if ($grades->count() > 1) {
            // Keep first, delete the rest
            $gradeIds = $grades->pluck('id')->toArray();
            $firstId = array_shift($gradeIds); // Remove first ID from array

            if (count($gradeIds) > 0) {
                $deleted = DB::table('grades')
                    ->whereIn('id', $gradeIds)
                    ->delete();

                $deletedCount += $deleted;
                echo "✓ {$student->first_name} {$student->last_name} - " .
                     "Supprimé {$deleted} doublon(s)\n";
            }
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Terminé! Notes supprimées: {$deletedCount}\n";
echo str_repeat("=", 60) . "\n";
