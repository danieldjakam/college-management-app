<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$backupFile = '/Users/redwolf-dark/Downloads/c0admin (22).sql';

echo "🔄 IMPORTATION DU BACKUP DU 5 DÉCEMBRE\n";
echo "======================================\n\n";

if (!file_exists($backupFile)) {
    die("❌ Fichier de backup introuvable: {$backupFile}\n");
}

$fileSize = round(filesize($backupFile) / 1024 / 1024, 2);
echo "📁 Fichier: {$backupFile}\n";
echo "📊 Taille: {$fileSize} MB\n\n";

echo "⚠️  ATTENTION: Cette opération va REMPLACER toute la base de données actuelle!\n";
echo "⏳ Cela peut prendre quelques minutes...\n\n";

// Obtenir les informations de connexion
$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$database = env('DB_DATABASE', 'c0admin');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');

// Construire la commande mysql
$mysqlPaths = [
    '/usr/local/bin/mysql',
    '/usr/local/mysql/bin/mysql',
    '/opt/homebrew/bin/mysql',
    '/Applications/XAMPP/xamppfiles/bin/mysql',
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
    die("❌ Commande mysql introuvable. Veuillez l'importer manuellement.\n");
}

echo "🔧 Utilisation de: {$mysqlCmd}\n\n";

// Construire la commande d'importation
$passwordArg = $password ? "-p'{$password}'" : '';
$command = "{$mysqlCmd} -h {$host} -P {$port} -u {$username} {$passwordArg} {$database} < \"{$backupFile}\" 2>&1";

echo "▶️  Démarrage de l'importation...\n";
$startTime = microtime(true);

// Exécuter la commande
exec($command, $output, $returnCode);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($returnCode !== 0) {
    echo "\n❌ ERREUR lors de l'importation!\n";
    echo "Code retour: {$returnCode}\n";
    echo "Sortie:\n" . implode("\n", $output) . "\n";
} else {
    echo "\n✅ IMPORTATION RÉUSSIE!\n";
    echo "⏱️  Durée: {$duration} secondes\n\n";

    // Vérifier l'état de la base
    echo "=== VÉRIFICATION ===\n";
    $totalGrades = DB::table('grades')->count();
    $seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
    $seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();
    $compoEvals = DB::table('evaluations')->where('type', 'composition')->where('trimester_id', 1)->count();

    echo "Total grades: {$totalGrades}\n";
    echo "Évaluations Seq1: {$seq1Evals}\n";
    echo "Évaluations Seq2: {$seq2Evals}\n";
    echo "Compositions T1: {$compoEvals}\n";

    $ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
    echo "Ratio Seq2/Seq1: {$ratio}%\n";

    echo "\n🎉 Base de données restaurée avec succès!\n";
}
