<?php

/**
 * DATABASE OPTIMIZATION AUDIT SCRIPT
 *
 * Ce script analyse la base de données pour identifier:
 * 1. Les tables sans index sur les clés étrangères
 * 2. Les requêtes lentes potentielles
 * 3. Les tables volumineuses sans index appropriés
 *
 * Usage: php database_optimization_audit.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "========================================\n";
echo "DATABASE OPTIMIZATION AUDIT\n";
echo "========================================\n\n";

// Tables critiques à analyser (celles qui causent le plus de problèmes de performance)
$criticalTables = [
    'grades',
    'students',
    'evaluations',
    'payments',
    'payment_details',
    'teacher_assignments',
    'sequences',
    'trimesters',
    'subjects',
    'class_series_subjects',
    'bulletin_generations',
    'attendance',
    'users',
    'teachers',
    'school_classes',
];

echo "📊 ANALYSE DES TABLES CRITIQUES\n";
echo "--------------------------------\n\n";

foreach ($criticalTables as $table) {
    if (!Schema::hasTable($table)) {
        echo "⚠️  Table '$table' n'existe pas\n\n";
        continue;
    }

    // Compter les lignes
    $count = DB::table($table)->count();

    // Obtenir les index existants
    $indexes = DB::select("SHOW INDEX FROM $table");

    echo "📋 Table: $table\n";
    echo "   Lignes: " . number_format($count) . "\n";
    echo "   Index existants:\n";

    $indexNames = [];
    foreach ($indexes as $index) {
        if (!in_array($index->Key_name, $indexNames)) {
            $indexNames[] = $index->Key_name;
            echo "   - {$index->Key_name}";
            if ($index->Key_name === 'PRIMARY') {
                echo " (PRIMARY KEY)";
            } elseif ($index->Non_unique == 0) {
                echo " (UNIQUE)";
            }
            echo "\n";
        }
    }

    echo "\n";
}

echo "\n========================================\n";
echo "🔍 ANALYSE DES INDEX MANQUANTS\n";
echo "========================================\n\n";

// Vérifier les colonnes de clés étrangères qui pourraient manquer d'index
$foreignKeyColumns = [
    'grades' => [
        'student_id' => 'EXISTS',
        'evaluation_id' => 'EXISTS',
        'sequence_id' => 'EXISTS',
        'trimester_id' => 'EXISTS',
        'school_year_id' => 'EXISTS',
        'series_subject_id' => 'EXISTS',
        ['student_id', 'sequence_id', 'trimester_id'] => 'NEEDED - Requêtes de bulletins',
        ['series_subject_id', 'student_id', 'sequence_id'] => 'NEEDED - Calcul de moyennes',
    ],
    'students' => [
        'class_series_id' => 'EXISTS',
        'is_active' => 'NEEDED - Filtrage étudiants actifs',
        ['class_series_id', 'is_active', 'status'] => 'NEEDED - Requêtes fréquentes',
    ],
    'evaluations' => [
        'sequence_id' => 'EXISTS',
        'trimester_id' => 'EXISTS',
        'teacher_id' => 'EXISTS',
        'series_subject_id' => 'NEEDED - Recherche par matière',
        ['sequence_id', 'trimester_id', 'series_subject_id'] => 'NEEDED - Génération bulletins',
    ],
    'payments' => [
        'student_id' => 'EXISTS',
        'school_year_id' => 'EXISTS',
        'payment_date' => 'EXISTS',
        'created_by_user_id' => 'NEEDED - Historique utilisateur',
    ],
    'payment_details' => [
        'payment_id' => 'NEEDED - Détails de paiement',
        'student_id' => 'NEEDED - Historique étudiant',
        'fee_type_id' => 'NEEDED - Filtrage par type',
        ['payment_id', 'fee_type_id'] => 'NEEDED - Rapports financiers',
    ],
    'teacher_assignments' => [
        'teacher_id' => 'EXISTS (unique)',
        'series_subject_id' => 'EXISTS (unique)',
        'school_year_id' => 'EXISTS (unique)',
        'is_active' => 'NEEDED - Filtrage actifs',
    ],
    'bulletin_generations' => [
        'student_id' => 'NEEDED - Recherche par étudiant',
        'bulletin_type' => 'NEEDED - Recherche par type',
        'period_identifier' => 'NEEDED - Recherche par période',
        ['student_id', 'bulletin_type', 'period_identifier'] => 'CRITICAL - Éviter duplications',
    ],
    'attendance' => [
        'student_id' => 'NEEDED',
        'date' => 'NEEDED',
        'school_year_id' => 'NEEDED',
        ['student_id', 'date', 'school_year_id'] => 'NEEDED - Requêtes fréquentes',
    ],
];

foreach ($foreignKeyColumns as $table => $columns) {
    if (!Schema::hasTable($table)) continue;

    echo "📋 $table:\n";
    foreach ($columns as $column => $status) {
        if (is_array($column)) {
            echo "   ⚠️  INDEX COMPOSITE MANQUANT: " . implode(' + ', $column) . " - $status\n";
        } else {
            $hasIndex = DB::select("SHOW INDEX FROM $table WHERE Column_name = ?", [$column]);
            if (empty($hasIndex) && strpos($status, 'NEEDED') !== false) {
                echo "   ❌ MANQUANT: $column - $status\n";
            } elseif (!empty($hasIndex)) {
                echo "   ✅ OK: $column\n";
            }
        }
    }
    echo "\n";
}

echo "\n========================================\n";
echo "📈 RECOMMANDATIONS D'OPTIMISATION\n";
echo "========================================\n\n";

echo "1. AJOUT D'INDEX COMPOSITES (PRIORITÉ HAUTE)\n";
echo "   - grades: (student_id, sequence_id, trimester_id)\n";
echo "   - grades: (series_subject_id, student_id, sequence_id)\n";
echo "   - evaluations: (sequence_id, trimester_id, series_subject_id)\n";
echo "   - bulletin_generations: (student_id, bulletin_type, period_identifier)\n";
echo "   - attendance: (student_id, date, school_year_id)\n\n";

echo "2. AJOUT D'INDEX SIMPLES (PRIORITÉ MOYENNE)\n";
echo "   - students: (is_active)\n";
echo "   - payment_details: (payment_id), (student_id), (fee_type_id)\n";
echo "   - teacher_assignments: (is_active)\n\n";

echo "3. OPTIMISATIONS DE CODE (PRIORITÉ HAUTE)\n";
echo "   - Utiliser eager loading dans BulletinController\n";
echo "   - Ajouter ->select() pour limiter les colonnes chargées\n";
echo "   - Implémenter le cache pour les calculs de bulletins\n";
echo "   - Paginer les résultats volumineux\n\n";

echo "4. CONFIGURATION MYSQL (PRIORITÉ MOYENNE)\n";
echo "   - Augmenter innodb_buffer_pool_size\n";
echo "   - Augmenter max_execution_time PHP (300s)\n";
echo "   - Activer le query cache MySQL\n\n";

$totalCount = 0;
foreach ($criticalTables as $table) {
    if (Schema::hasTable($table)) {
        $totalCount += DB::table($table)->count();
    }
}

echo "📊 STATISTIQUES GLOBALES\n";
echo "   Total des lignes dans tables critiques: " . number_format($totalCount) . "\n";
echo "   Tables analysées: " . count($criticalTables) . "\n\n";

echo "✅ Audit terminé!\n";
echo "   Prochaine étape: Exécuter la migration d'optimisation\n";
echo "   Commande: php artisan migrate --path=/database/migrations/XXXX_optimize_database_indexes.php\n\n";
