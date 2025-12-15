<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 RESTAURATION DES sequence_id - SOLUTION DEFINITIVE\n\n";

// ÉTAPE 1: État actuel
$total = DB::table('grades')->count();
$nullSeqBefore = DB::table('grades')->whereNull('sequence_id')->count();
echo "📊 État AVANT restauration:\n";
echo "   Total notes: $total\n";
echo "   Notes avec sequence_id=NULL: $nullSeqBefore (" . round(($nullSeqBefore/$total)*100,1) . "%)\n\n";

// ÉTAPE 2: Restaurer les sequence_id depuis les evaluations
echo "🔄 Restauration des sequence_id depuis les evaluations...\n";

// REQUÊTE SQL DE RESTAURATION
// Pour toutes les notes avec sequence_id=NULL, on copie le sequence_id depuis l'evaluation
// SAUF si l'evaluation est de type 'composition' (celles-là doivent rester à NULL)
$restored = DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE g.sequence_id IS NULL
      AND e.sequence_id IS NOT NULL
      AND e.type != 'composition'
");

echo "   Requête UPDATE exécutée\n\n";

// ÉTAPE 3: Vérification
$nullSeqAfter = DB::table('grades')->whereNull('sequence_id')->count();
$compositionEvals = DB::table('evaluations')->where('type', 'composition')->count();
$notesRestorees = $nullSeqBefore - $nullSeqAfter;

echo "✅ RÉSULTAT:\n";
echo "   Notes avec sequence_id=NULL maintenant: $nullSeqAfter (" . round(($nullSeqAfter/$total)*100,1) . "%)\n";
echo "   Évaluations de composition: $compositionEvals\n";
echo "   Notes restaurées: $notesRestorees\n\n";

// ÉTAPE 4: Validation finale
echo "🔍 VALIDATION:\n";

// Vérifier un échantillon
$sample = DB::table('grades')
    ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
    ->whereNull('grades.sequence_id')
    ->select('grades.id', 'grades.sequence_id as grade_seq', 'evaluations.type', 'evaluations.sequence_id as eval_seq')
    ->limit(5)
    ->get();

echo "   Échantillon de notes avec sequence_id=NULL:\n";
foreach ($sample as $s) {
    echo "     - Note {$s->id}: type={$s->type}, eval_sequence_id={$s->eval_seq}\n";
}

// Vérifier qu'il ne reste que des compositions
$nonCompoNull = DB::table('grades')
    ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
    ->whereNull('grades.sequence_id')
    ->where('evaluations.type', '!=', 'composition')
    ->count();

echo "\n";
if ($nonCompoNull == 0) {
    echo "✅ PARFAIT! Toutes les notes avec sequence_id=NULL sont des compositions.\n";
} else {
    echo "⚠️  ATTENTION: Il reste $nonCompoNull notes NON-compositions avec sequence_id=NULL\n";
}

echo "\n🎉 RESTAURATION TERMINÉE!\n";
