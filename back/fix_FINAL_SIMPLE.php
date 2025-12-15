<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 CORRECTION FINALE - VERSION SIMPLE ET SÛRE\n";
echo "=============================================\n\n";

echo "📋 CE SCRIPT FAIT UNE SEULE CHOSE:\n";
echo "  Mettre sequence_id=NULL pour les grades de compositions\n";
echo "  (SANS toucher aux types d'évaluations)\n\n";

// ÉTAPE 1: État actuel
echo "=== ÉTAPE 1: ÉTAT AVANT CORRECTION ===\n";

$totalGrades = DB::table('grades')->count();
$nullSeqBefore = DB::table('grades')->whereNull('sequence_id')->count();

echo "Total grades: {$totalGrades}\n";
echo "Grades avec sequence_id=NULL: {$nullSeqBefore} (" . round(($nullSeqBefore/$totalGrades)*100,1) . "%)\n\n";

// Vérifier le ratio Seq1/Seq2 AVANT correction
$seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();
$compoEvals = DB::table('evaluations')->where('type', 'composition')->whereNull('sequence_id')->count();

echo "ÉVALUATIONS (types originaux préservés):\n";
echo "  Séquence 1: {$seq1Evals}\n";
echo "  Séquence 2: {$seq2Evals}\n";
echo "  Compositions (seq=NULL): {$compoEvals}\n";
$ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratio}%\n\n";

// ÉTAPE 2: Corriger UNIQUEMENT les grades de compositions
echo "=== ÉTAPE 2: CORRECTION DES GRADES ===\n";

// Trouver les grades qui ont un sequence_id mais dont l'évaluation est une composition
$problematicGrades = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->where('e.type', 'composition')
    ->whereNotNull('g.sequence_id')
    ->count();

echo "Grades de compositions avec sequence_id NON-NULL: {$problematicGrades}\n";

if ($problematicGrades > 0) {
    echo "🔄 Correction en cours...\n";

    // Mettre à NULL les sequence_id des grades dont l'évaluation est une composition
    DB::statement("
        UPDATE grades g
        INNER JOIN evaluations e ON g.evaluation_id = e.id
        SET g.sequence_id = NULL
        WHERE e.type = 'composition'
          AND g.sequence_id IS NOT NULL
    ");

    echo "✅ Correction effectuée\n";
} else {
    echo "✅ Aucune correction nécessaire\n";
}

// ÉTAPE 3: Vérification finale
echo "\n=== ÉTAPE 3: VÉRIFICATION FINALE ===\n";

$nullSeqAfter = DB::table('grades')->whereNull('sequence_id')->count();
$corrected = $nullSeqAfter - $nullSeqBefore;

echo "Grades avec sequence_id=NULL maintenant: {$nullSeqAfter} (" . round(($nullSeqAfter/$totalGrades)*100,1) . "%)\n";
echo "Grades corrigés: {$corrected}\n\n";

// Vérifier qu'on n'a corrigé QUE des compositions
$remainingProblematic = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->where('e.type', 'composition')
    ->whereNotNull('g.sequence_id')
    ->count();

if ($remainingProblematic == 0) {
    echo "✅ PARFAIT! Tous les grades de compositions ont sequence_id=NULL\n";
} else {
    echo "⚠️  Il reste {$remainingProblematic} grades de compositions avec sequence_id NON-NULL\n";
}

// Vérifier qu'on n'a PAS touché aux grades de DS
$dsGradesWithNull = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->where('e.type', '!=', 'composition')
    ->whereNull('g.sequence_id')
    ->count();

if ($dsGradesWithNull > 0) {
    echo "⚠️  ATTENTION: {$dsGradesWithNull} grades de DS ont sequence_id=NULL (ne devrait pas arriver)\n";
} else {
    echo "✅ PARFAIT! Aucun grade de DS n'a été affecté\n";
}

// Vérification du ratio (devrait rester identique)
echo "\nÉVALUATIONS (vérification qu'elles n'ont PAS changé):\n";
$seq1EvalsAfter = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2EvalsAfter = DB::table('evaluations')->where('sequence_id', 2)->count();
echo "  Séquence 1: {$seq1EvalsAfter} (avant: {$seq1Evals})\n";
echo "  Séquence 2: {$seq2EvalsAfter} (avant: {$seq2Evals})\n";

if ($seq1EvalsAfter == $seq1Evals && $seq2EvalsAfter == $seq2Evals) {
    echo "✅ Les évaluations n'ont PAS été modifiées (PARFAIT!)\n";
} else {
    echo "⚠️  Les évaluations ont changé (ne devrait pas arriver)\n";
}

echo "\n🎉 CORRECTION TERMINÉE!\n";
echo "💡 Cette correction était minimale et sûre - elle n'a touché QUE les grades de compositions.\n";
