<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔄 NETTOYAGE ET RÉIMPORTATION DE LA BASE DE DONNÉES\n";
echo "====================================================\n\n";

echo "ÉTAPE 1: Désactivation des contraintes...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0');

echo "ÉTAPE 2: Suppression de toutes les tables...\n";
$tables = DB::select('SHOW TABLES');
$dbName = env('DB_DATABASE', 'c0admin');
$count = 0;

foreach ($tables as $table) {
    $tableName = $table->{"Tables_in_{$dbName}"};
    echo "  Suppression de {$tableName}...\n";
    DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
    $count++;
}

echo "  ✅ {$count} tables supprimées\n\n";

echo "ÉTAPE 3: Réactivation des contraintes...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "✅ BASE DE DONNÉES NETTOYÉE!\n\n";

echo "ÉTAPE 4: Importation du backup du 5 décembre...\n";
$backupFile = '/Users/redwolf-dark/Downloads/c0admin (25).sql';

if (!file_exists($backupFile)) {
    die("❌ Fichier introuvable: {$backupFile}\n");
}

$fileSize = round(filesize($backupFile) / 1024 / 1024, 2);
echo "📁 Fichier: {$backupFile}\n";
echo "📊 Taille: {$fileSize} MB\n";
echo "⏳ Import en cours (peut prendre 5-10 minutes)...\n\n";

$startTime = microtime(true);

// Construire la commande mysql
$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$database = env('DB_DATABASE', 'c0admin');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');

$mysqlPaths = [
    '/usr/local/mysql/bin/mysql',
    '/usr/local/bin/mysql',
    '/opt/homebrew/bin/mysql',
    'mysql'
];

$mysqlCmd = null;
foreach ($mysqlPaths as $path) {
    if (file_exists($path) || $path === 'mysql') {
        $mysqlCmd = $path;
        break;
    }
}

if (!$mysqlCmd) {
    die("❌ Commande mysql introuvable\n");
}

$passwordArg = $password ? "-p'{$password}'" : '';
$command = "{$mysqlCmd} -h {$host} -P {$port} -u {$username} {$passwordArg} {$database} < \"{$backupFile}\" 2>&1";

exec($command, $output, $returnCode);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($returnCode !== 0) {
    echo "\n⚠️  Import terminé avec des avertissements\n";
    echo "Code retour: {$returnCode}\n";
    $errors = implode("\n", array_slice($output, -10));
    echo "Dernières lignes:\n{$errors}\n\n";
} else {
    echo "\n✅ IMPORT RÉUSSI!\n";
}

echo "⏱️  Durée: {$duration} secondes\n\n";

// Vérifier l'état
echo "=== VÉRIFICATION ===\n";
$totalGrades = DB::table('grades')->count();
$nullSeq = DB::table('grades')->whereNull('sequence_id')->count();
$seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();

echo "Total grades: {$totalGrades}\n";
echo "Grades avec sequence_id=NULL: {$nullSeq} (" . round(($nullSeq/$totalGrades)*100,1) . "%)\n";
echo "Évaluations Seq1: {$seq1Evals}\n";
echo "Évaluations Seq2: {$seq2Evals}\n";

$ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
echo "Ratio Seq2/Seq1: {$ratio}%\n";

echo "\n🎉 BASE DE DONNÉES PRÊTE POUR LES TESTS!\n";
echo "💡 Maintenant on peut appliquer les migrations problématiques pour voir ce qui se passe.\n";
