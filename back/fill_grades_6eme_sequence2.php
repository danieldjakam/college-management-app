<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎯 Remplissage des notes de 6ème A - Séquence 2\n";
echo str_repeat("=", 60) . "\n\n";

// Configuration
$seriesId = 15; // 6ème A
$sequenceNumber = 2;

// Récupérer l'année scolaire courante
$schoolYear = DB::table('school_years')->where('is_current', true)->first();
if (!$schoolYear) {
    echo "❌ Aucune année scolaire courante trouvée!\n";
    exit(1);
}

echo "📅 Année scolaire: {$schoolYear->name} (ID: {$schoolYear->id})\n";

// Récupérer la séquence 2
$sequence = DB::table('sequences')->where('number', $sequenceNumber)->first();
if (!$sequence) {
    echo "❌ Séquence {$sequenceNumber} non trouvée!\n";
    exit(1);
}

echo "📝 Séquence: {$sequence->name} (ID: {$sequence->id}, Trimestre: {$sequence->trimester_id})\n";

// Récupérer la série
$series = DB::table('class_series')
    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
    ->where('class_series.id', $seriesId)
    ->select('class_series.*', 'school_classes.name as class_name')
    ->first();

echo "🎓 Classe: {$series->class_name} {$series->name}\n\n";

// Récupérer les élèves de la classe
$students = DB::table('students')
    ->where('class_series_id', $seriesId)
    ->where('school_year_id', $schoolYear->id)
    ->where('is_active', true)
    ->orderBy('last_name')
    ->orderBy('first_name')
    ->get();

echo "👥 Nombre d'élèves: " . count($students) . "\n\n";

// Récupérer les matières de la classe
$subjects = DB::table('class_series_subjects')
    ->join('subjects', 'class_series_subjects.subject_id', '=', 'subjects.id')
    ->where('class_series_subjects.class_series_id', $seriesId)
    ->where('class_series_subjects.is_active', true)
    ->select('class_series_subjects.*', 'subjects.name as subject_name')
    ->get();

echo "📚 Matières trouvées: " . count($subjects) . "\n";
foreach ($subjects as $subject) {
    echo "  - {$subject->subject_name} (Coef: {$subject->coefficient})\n";
}
echo "\n";

// Récupérer ou créer les évaluations pour la séquence 2
$createdEvaluations = 0;
$evaluations = [];

foreach ($subjects as $subject) {
    // Vérifier si l'évaluation existe déjà
    $evaluation = DB::table('evaluations')
        ->where('class_series_subject_id', $subject->id)
        ->where('sequence_id', $sequence->id)
        ->where('school_year_id', $schoolYear->id)
        ->where('evaluation_type', 'sequence')
        ->first();

    if (!$evaluation) {
        // Créer l'évaluation
        $evaluationId = DB::table('evaluations')->insertGetId([
            'class_series_subject_id' => $subject->id,
            'sequence_id' => $sequence->id,
            'school_year_id' => $schoolYear->id,
            'name' => "{$subject->subject_name} - Séquence {$sequenceNumber}",
            'evaluation_type' => 'sequence',
            'trimester_id' => $sequence->trimester_id,
            'max_score' => 20,
            'coefficient' => $subject->coefficient,
            'date' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $evaluation = DB::table('evaluations')->where('id', $evaluationId)->first();
        $createdEvaluations++;
        echo "✅ Évaluation créée: {$subject->subject_name}\n";
    }

    $evaluations[$subject->id] = $evaluation;
}

echo "\n📝 Évaluations créées: {$createdEvaluations}\n";
echo "📝 Évaluations totales: " . count($evaluations) . "\n\n";

// Générer des notes aléatoires pour chaque élève
echo "🎲 Génération des notes...\n\n";

$gradesCreated = 0;
$gradesUpdated = 0;

foreach ($students as $student) {
    echo "👤 {$student->first_name} {$student->last_name}:\n";

    foreach ($subjects as $subject) {
        if (!isset($evaluations[$subject->id])) continue;

        $evaluation = $evaluations[$subject->id];

        // Générer une note aléatoire entre 8 et 20
        $score = rand(80, 200) / 10; // Notes entre 8.0 et 20.0

        // Vérifier si la note existe déjà
        $existingGrade = DB::table('grades')
            ->where('student_id', $student->id)
            ->where('evaluation_id', $evaluation->id)
            ->where('sequence_id', $sequence->id)
            ->where('trimester_id', $sequence->trimester_id)
            ->first();

        if ($existingGrade) {
            // Mettre à jour
            DB::table('grades')
                ->where('id', $existingGrade->id)
                ->update([
                    'score' => $score,
                    'updated_at' => now()
                ]);
            $gradesUpdated++;
        } else {
            // Créer
            DB::table('grades')->insert([
                'student_id' => $student->id,
                'evaluation_id' => $evaluation->id,
                'class_series_subject_id' => $subject->id,
                'sequence_id' => $sequence->id,
                'trimester_id' => $sequence->trimester_id,
                'school_year_id' => $schoolYear->id,
                'score' => $score,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $gradesCreated++;
        }

        echo "  ✓ {$subject->subject_name}: {$score}/20\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ TERMINÉ!\n";
echo "📊 Statistiques:\n";
echo "  - Notes créées: {$gradesCreated}\n";
echo "  - Notes mises à jour: {$gradesUpdated}\n";
echo "  - Total: " . ($gradesCreated + $gradesUpdated) . "\n";
echo str_repeat("=", 60) . "\n";
