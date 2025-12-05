<?php
/**
 * Script de diagnostic pour vérifier les séquences en production
 *
 * Usage:
 * php artisan tinker < scripts/diagnose_sequences_production.php
 *
 * Ou copiez-collez le contenu dans tinker en production
 */

echo "==========================================\n";
echo "DIAGNOSTIC DES SÉQUENCES EN PRODUCTION\n";
echo "==========================================\n\n";

// 1. Vérifier toutes les séquences
echo "📋 LISTE DES SÉQUENCES:\n\n";
$sequences = DB::table('sequences')
    ->orderBy('trimester_id')
    ->orderBy('number')
    ->get(['id', 'name', 'number', 'trimester_id', 'is_composition', 'is_active', 'is_completed']);

foreach ($sequences as $seq) {
    $type = $seq->is_composition ? '🎯 COMPOSITION' : '📝 SÉQUENCE';
    $status = $seq->is_active ? '✅ Active' : '⏸️ Inactive';
    $complete = $seq->is_completed ? '✔️ Complété' : '⏳ En cours';

    echo "ID {$seq->id} | {$seq->name}\n";
    echo "  └─ Number: {$seq->number} | Trimestre: {$seq->trimester_id}\n";
    echo "  └─ Type: {$type} | Status: {$status} | {$complete}\n\n";
}

// 2. Chercher des anomalies
echo "\n🔍 RECHERCHE D'ANOMALIES:\n\n";

$anomalies = [];

// Anomalie 1: Séquence avec number=3 dans trimestre 1
$seq3_trim1 = DB::table('sequences')
    ->where('trimester_id', 1)
    ->where('number', 3)
    ->first();

if ($seq3_trim1) {
    $anomalies[] = "❌ ANOMALIE: Séquence avec number=3 trouvée dans Trimestre 1";
    $anomalies[] = "   → ID {$seq3_trim1->id}: {$seq3_trim1->name}";
    $anomalies[] = "   → is_composition: {$seq3_trim1->is_composition}";
    $anomalies[] = "   → Cette séquence devrait être une composition avec number=0\n";
}

// Anomalie 2: Composition avec number != 0
$comps_wrong_number = DB::table('sequences')
    ->where('is_composition', 1)
    ->where('number', '!=', 0)
    ->get();

foreach ($comps_wrong_number as $comp) {
    $anomalies[] = "❌ ANOMALIE: Composition avec number != 0";
    $anomalies[] = "   → ID {$comp->id}: {$comp->name} (number={$comp->number})";
    $anomalies[] = "   → Les compositions devraient avoir number=0\n";
}

// Anomalie 3: Séquence normale (non-composition) avec is_composition=0 mais nom contenant "Composition"
$false_sequences = DB::table('sequences')
    ->where('is_composition', 0)
    ->where('name', 'LIKE', '%Composition%')
    ->get();

foreach ($false_sequences as $seq) {
    $anomalies[] = "❌ ANOMALIE: Séquence avec nom 'Composition' mais is_composition=0";
    $anomalies[] = "   → ID {$seq->id}: {$seq->name}";
    $anomalies[] = "   → Le flag is_composition devrait être à 1\n";
}

// Anomalie 4: Duplicatas de séquences
$duplicates = DB::table('sequences')
    ->select('trimester_id', 'number', DB::raw('COUNT(*) as count'))
    ->groupBy('trimester_id', 'number')
    ->having('count', '>', 1)
    ->get();

foreach ($duplicates as $dup) {
    $anomalies[] = "❌ ANOMALIE: Séquence dupliquée";
    $anomalies[] = "   → Trimestre {$dup->trimester_id}, Number {$dup->number}: {$dup->count} occurrences\n";
}

if (empty($anomalies)) {
    echo "✅ Aucune anomalie détectée! La structure des séquences est correcte.\n\n";
} else {
    echo "⚠️ ANOMALIES DÉTECTÉES:\n\n";
    foreach ($anomalies as $anomaly) {
        echo "$anomaly\n";
    }
}

// 3. Statistiques des évaluations
echo "\n📊 STATISTIQUES DES ÉVALUATIONS PAR SÉQUENCE:\n\n";
$eval_stats = DB::table('evaluations')
    ->select('sequence_id', 'trimester_id', DB::raw('COUNT(*) as eval_count'))
    ->groupBy('sequence_id', 'trimester_id')
    ->orderBy('trimester_id')
    ->orderBy('sequence_id')
    ->get();

foreach ($eval_stats as $stat) {
    $seq = DB::table('sequences')->find($stat->sequence_id);
    $seqName = $seq ? $seq->name : "Séquence inconnue";
    echo "Séquence ID {$stat->sequence_id} ({$seqName}) - Trimestre {$stat->trimester_id}: {$stat->eval_count} évaluations\n";
}

echo "\n==========================================\n";
echo "FIN DU DIAGNOSTIC\n";
echo "==========================================\n";
