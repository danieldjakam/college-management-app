<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ANALYSE DES ÉVALUATIONS MAL TYPÉES\n";
echo "======================================\n\n";

// Toutes les évaluations typées comme 'composition'
$compositions = DB::table('evaluations')
    ->where('type', 'composition')
    ->get();

echo "Total d'évaluations typées 'composition': " . count($compositions) . "\n\n";

// Analyser les noms pour détecter les fausses compositions
$realCompositions = [];
$fakeCompositions = [];

$dsPatterns = [
    'DS', 'ds', 'Ds',
    'devoir', 'Devoir', 'DEVOIR',
    'No ', 'N°', 'no ',
    'seq', 'Seq', 'SEQ',
    'sequence', 'Sequence', 'SEQUENCE',
    'évaluation', 'Évaluation', 'ÉVALUATION',
    'evaluation', 'Evaluation', 'EVALUATION'
];

foreach ($compositions as $eval) {
    $isFake = false;
    $name = $eval->name;

    // Vérifier si le nom contient des indicateurs de DS/Séquence
    foreach ($dsPatterns as $pattern) {
        if (stripos($name, $pattern) !== false) {
            // Mais exclure les vrais noms de composition
            if (!preg_match('/^Composition \d+/i', $name) &&
                !preg_match('/Compo \d+/i', $name)) {
                $isFake = true;
                break;
            }
        }
    }

    if ($isFake) {
        $fakeCompositions[] = $eval;
    } else {
        $realCompositions[] = $eval;
    }
}

echo "RÉSULTATS DE L'ANALYSE:\n";
echo "  Vraies compositions: " . count($realCompositions) . "\n";
echo "  Fausses compositions (DS mal typés): " . count($fakeCompositions) . "\n\n";

// Afficher les fausses compositions avec détails
echo "=== FAUSSES COMPOSITIONS DÉTECTÉES ===\n";
echo "Ces évaluations sont typées 'composition' mais sont en fait des DS/Séquences:\n\n";

$fakeByTrimester = [1 => [], 2 => [], 3 => []];
foreach ($fakeCompositions as $eval) {
    $fakeByTrimester[$eval->trimester_id][] = $eval;
}

foreach ([1, 2, 3] as $trimId) {
    echo "TRIMESTRE {$trimId}:\n";
    $trimesters = $fakeByTrimester[$trimId];

    if (empty($trimesters)) {
        echo "  Aucune\n\n";
        continue;
    }

    foreach ($trimesters as $eval) {
        $gradeCount = DB::table('grades')->where('evaluation_id', $eval->id)->count();
        echo "  ID {$eval->id}: \"{$eval->name}\" ({$gradeCount} grades)\n";
    }
    echo "\n";
}

// Statistiques par pattern détecté
echo "=== PATTERNS DÉTECTÉS DANS LES FAUSSES COMPOSITIONS ===\n";
$patternCounts = [];
foreach ($fakeCompositions as $eval) {
    $name = $eval->name;
    if (stripos($name, 'DS') !== false || stripos($name, 'ds') !== false) {
        $patternCounts['DS'] = ($patternCounts['DS'] ?? 0) + 1;
    }
    if (stripos($name, 'seq') !== false) {
        $patternCounts['seq'] = ($patternCounts['seq'] ?? 0) + 1;
    }
    if (stripos($name, 'devoir') !== false) {
        $patternCounts['devoir'] = ($patternCounts['devoir'] ?? 0) + 1;
    }
    if (stripos($name, 'No ') !== false || stripos($name, 'N°') !== false) {
        $patternCounts['No/N°'] = ($patternCounts['No/N°'] ?? 0) + 1;
    }
    if (stripos($name, 'évaluation') !== false || stripos($name, 'evaluation') !== false) {
        $patternCounts['évaluation'] = ($patternCounts['évaluation'] ?? 0) + 1;
    }
}

foreach ($patternCounts as $pattern => $count) {
    echo "  {$pattern}: {$count} évaluations\n";
}
echo "\n";

// Calculer l'impact sur les grades
echo "=== IMPACT SUR LES GRADES ===\n";
$fakeIds = array_map(fn($e) => $e->id, $fakeCompositions);
$affectedGrades = DB::table('grades')
    ->whereIn('evaluation_id', $fakeIds)
    ->count();

echo "Grades affectés par les fausses compositions: {$affectedGrades}\n";
echo "Total grades dans la base: " . DB::table('grades')->count() . "\n";
echo "Pourcentage: " . round(($affectedGrades / DB::table('grades')->count()) * 100, 1) . "%\n\n";

// Déterminer le bon sequence_id pour chaque fausse composition
echo "=== PROPOSITION DE CORRECTION ===\n";
echo "Pour chaque fausse composition, on va déterminer le bon sequence_id:\n\n";

$corrections = [];
foreach ($fakeCompositions as $eval) {
    $name = $eval->name;
    $trimId = $eval->trimester_id;
    $newSeqId = null;

    // Logique de détection du bon sequence_id
    if (preg_match('/DS\s*2|ds\s*2|Ds\s*2|devoir\s*2|Devoir\s*2|No\s*2|N°\s*2/i', $name)) {
        $newSeqId = 2;
    } elseif (preg_match('/DS\s*1|ds\s*1|Ds\s*1|devoir\s*1|Devoir\s*1|seq\s*1|Seq\s*1|No\s*1|N°\s*1/i', $name)) {
        $newSeqId = 1;
    } elseif (preg_match('/DS\s*3|ds\s*3|Ds\s*3|devoir\s*3|Devoir\s*3|seq\s*3|Seq\s*3|No\s*3|N°\s*3/i', $name)) {
        $newSeqId = 3;
    } elseif (preg_match('/DS\s*4|ds\s*4|Ds\s*4|devoir\s*4|Devoir\s*4|seq\s*4|Seq\s*4|No\s*4|N°\s*4/i', $name)) {
        $newSeqId = 4;
    } elseif ($trimId == 1) {
        // Par défaut pour trimestre 1: séquence 1
        $newSeqId = 1;
    } elseif ($trimId == 2) {
        // Par défaut pour trimestre 2: séquence 3
        $newSeqId = 3;
    }

    $corrections[] = [
        'eval_id' => $eval->id,
        'name' => $name,
        'trim' => $trimId,
        'old_seq' => $eval->sequence_id,
        'new_seq' => $newSeqId,
        'grade_count' => DB::table('grades')->where('evaluation_id', $eval->id)->count()
    ];
}

// Afficher échantillon de corrections
echo "Échantillon de corrections proposées (20 premières):\n";
foreach (array_slice($corrections, 0, 20) as $c) {
    echo sprintf(
        "  ID %d: \"%s\" (trim=%d) → sequence_id=%s (%d grades)\n",
        $c['eval_id'],
        substr($c['name'], 0, 50),
        $c['trim'],
        $c['new_seq'] ?? 'NULL',
        $c['grade_count']
    );
}

echo "\n💡 CONCLUSION:\n";
echo "La base de données a " . count($fakeCompositions) . " évaluations mal typées comme 'composition'.\n";
echo "Ces évaluations affectent {$affectedGrades} grades.\n";
echo "Les migrations ont incorrectement mis leur sequence_id à NULL.\n";
echo "Il faut:\n";
echo "  1. Changer le type de 'composition' vers 'ds' pour ces évaluations\n";
echo "  2. Restaurer le bon sequence_id pour les évaluations\n";
echo "  3. Restaurer le bon sequence_id pour les grades correspondants\n";
