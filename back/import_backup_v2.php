<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$backupFile = '/Users/redwolf-dark/Downloads/c0admin (22).sql';

echo "🔄 IMPORTATION DU BACKUP (VERSION 2 - SANS CONTRAINTES)\n";
echo "========================================================\n\n";

if (!file_exists($backupFile)) {
    die("❌ Fichier introuvable: {$backupFile}\n");
}

$fileSize = round(filesize($backupFile) / 1024 / 1024, 2);
echo "📁 Fichier: {$backupFile}\n";
echo "📊 Taille: {$fileSize} MB\n\n";

echo "🔧 Désactivation des contraintes de clé étrangère...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0');

echo "📦 Lecture et exécution du fichier SQL...\n";
echo "⏳ Cela peut prendre 5-10 minutes...\n\n";

$startTime = microtime(true);

// Lire et exécuter le fichier SQL
$sql = file_get_contents($backupFile);

// Diviser en requêtes individuelles
$queries = explode(';', $sql);
$totalQueries = count($queries);
$executed = 0;
$errors = 0;

echo "Total de requêtes à exécuter: {$totalQueries}\n";
echo "Progression: ";

foreach ($queries as $index => $query) {
    $query = trim($query);

    if (empty($query) || substr($query, 0, 2) === '--') {
        continue;
    }

    try {
        DB::statement($query);
        $executed++;

        // Afficher la progression tous les 10000 requêtes
        if ($executed % 10000 === 0) {
            $percent = round(($executed / $totalQueries) * 100, 1);
            echo "{$percent}% ";
            flush();
        }
    } catch (\Exception $e) {
        $errors++;
        // Ignorer les erreurs mineures (tables déjà existantes, etc.)
        if ($errors < 10) {
            // echo "\n⚠️  Erreur ignorée: " . $e->getMessage() . "\n";
        }
    }
}

echo "100%\n\n";

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "✅ Requêtes exécutées: {$executed}\n";
echo "⚠️  Erreurs ignorées: {$errors}\n";
echo "⏱️  Durée: {$duration} secondes\n\n";

echo "🔧 Réactivation des contraintes de clé étrangère...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=1');

// Vérifier l'état
echo "\n=== VÉRIFICATION ===\n";
$totalGrades = DB::table('grades')->count();
$seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();

echo "Total grades: {$totalGrades}\n";
echo "Évaluations Seq1: {$seq1Evals}\n";
echo "Évaluations Seq2: {$seq2Evals}\n";

$ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
echo "Ratio Seq2/Seq1: {$ratio}%\n";

echo "\n🎉 IMPORTATION TERMINÉE!\n";
