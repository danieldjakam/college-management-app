<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔄 CRÉATION DU BACKUP DE LA BASE DE DONNÉES...\n\n";

$backupFile = 'backup_evaluations_grades_' . date('Y-m-d_H-i-s') . '.sql';

// Export des tables evaluations et grades uniquement (les plus importantes)
$tables = ['evaluations', 'grades'];

$sql = "-- Backup créé le " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Tables: " . implode(', ', $tables) . "\n\n";

foreach ($tables as $table) {
    echo "📦 Export de la table '{$table}'...\n";

    // Récupérer la structure de la table
    $createTable = DB::select("SHOW CREATE TABLE {$table}");
    $sql .= "\n-- Structure de la table {$table}\n";
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

    // Récupérer toutes les données
    $rows = DB::table($table)->get();
    $count = count($rows);

    if ($count > 0) {
        $sql .= "-- Données de la table {$table} ({$count} lignes)\n";

        // Obtenir les colonnes
        $columns = array_keys((array)$rows[0]);
        $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";

        foreach ($rows as $index => $row) {
            $values = [];
            foreach ($columns as $col) {
                $value = $row->$col;
                if (is_null($value)) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            $sql .= "(" . implode(', ', $values) . ")";
            $sql .= ($index < $count - 1) ? ",\n" : ";\n\n";
        }
    }

    echo "  ✅ {$count} lignes exportées\n";
}

// Écrire dans le fichier
file_put_contents($backupFile, $sql);

$fileSize = round(filesize($backupFile) / 1024 / 1024, 2);

echo "\n✅ BACKUP CRÉÉ AVEC SUCCÈS!\n";
echo "📁 Fichier: {$backupFile}\n";
echo "📊 Taille: {$fileSize} MB\n";
echo "\n💡 Pour restaurer: mysql -u root c0admin < {$backupFile}\n";
