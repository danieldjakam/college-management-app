<?php

/**
 * SCRIPT DE TEST DE PERFORMANCE
 *
 * Ce script teste les performances des opérations critiques
 * avant et après l'optimisation de la base de données.
 *
 * Usage: php test_performance.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Evaluation;
use App\Models\BulletinGeneration;

echo "========================================\n";
echo "TEST DE PERFORMANCE - BASE DE DONNÉES\n";
echo "========================================\n\n";

// Activer le comptage de requêtes
DB::enableQueryLog();

// ==========================================
// TEST 1: Récupération des notes d'un élève
// ==========================================
echo "📊 TEST 1: Récupération des notes pour génération bulletin\n";
echo "-----------------------------------------------------------\n";

$startTime = microtime(true);
$queryCountStart = count(DB::getQueryLog());

$student = Student::where('is_active', true)->first();

if ($student) {
    $grades = Grade::where('student_id', $student->id)
                  ->where('sequence_id', 1)
                  ->where('trimester_id', 1)
                  ->with([
                      'classSeriesSubject.subject',
                      'evaluation'
                  ])
                  ->get();

    $endTime = microtime(true);
    $queryCountEnd = count(DB::getQueryLog());
    $executionTime = ($endTime - $startTime) * 1000;
    $queryCount = $queryCountEnd - $queryCountStart;

    echo "   ✅ Étudiant: {$student->name}\n";
    echo "   ✅ Notes récupérées: " . $grades->count() . "\n";
    echo "   ⏱️  Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";
    echo "   📊 Nombre de requêtes SQL: {$queryCount}\n";
    echo "   🎯 Objectif: < 100ms, < 5 requêtes\n";

    if ($executionTime < 100 && $queryCount < 5) {
        echo "   ✅ PASSÉ\n";
    } else {
        echo "   ⚠️  ATTENTION: Performance sous-optimale\n";
    }
} else {
    echo "   ⚠️  Aucun étudiant trouvé\n";
}

echo "\n";

// Réinitialiser le log
DB::flushQueryLog();

// ==========================================
// TEST 2: Liste des paiements avec pagination
// ==========================================
echo "💰 TEST 2: Liste des paiements paginés\n";
echo "---------------------------------------\n";

$startTime = microtime(true);
$queryCountStart = count(DB::getQueryLog());

$payments = Payment::with([
                'student:id,name,class_series_id',
                'student.classSeries:id,name'
            ])
            ->orderBy('payment_date', 'desc')
            ->limit(50)
            ->get();

$endTime = microtime(true);
$queryCountEnd = count(DB::getQueryLog());
$executionTime = ($endTime - $startTime) * 1000;
$queryCount = $queryCountEnd - $queryCountStart;

echo "   ✅ Paiements récupérés: " . $payments->count() . "\n";
echo "   ⏱️  Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";
echo "   📊 Nombre de requêtes SQL: {$queryCount}\n";
echo "   🎯 Objectif: < 200ms, < 10 requêtes\n";

if ($executionTime < 200 && $queryCount < 10) {
    echo "   ✅ PASSÉ\n";
} else {
    echo "   ⚠️  ATTENTION: Performance sous-optimale\n";
}

echo "\n";

// Réinitialiser le log
DB::flushQueryLog();

// ==========================================
// TEST 3: Recherche d'évaluations
// ==========================================
echo "📝 TEST 3: Recherche d'évaluations par séquence/trimestre\n";
echo "----------------------------------------------------------\n";

$startTime = microtime(true);
$queryCountStart = count(DB::getQueryLog());

$evaluations = Evaluation::where('sequence_id', 1)
                        ->where('trimester_id', 1)
                        ->where('is_active', true)
                        ->with(['teacher', 'classSeriesSubject.subject'])
                        ->get();

$endTime = microtime(true);
$queryCountEnd = count(DB::getQueryLog());
$executionTime = ($endTime - $startTime) * 1000;
$queryCount = $queryCountEnd - $queryCountStart;

echo "   ✅ Évaluations trouvées: " . $evaluations->count() . "\n";
echo "   ⏱️  Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";
echo "   📊 Nombre de requêtes SQL: {$queryCount}\n";
echo "   🎯 Objectif: < 100ms, < 5 requêtes\n";

if ($executionTime < 100 && $queryCount < 5) {
    echo "   ✅ PASSÉ\n";
} else {
    echo "   ⚠️  ATTENTION: Performance sous-optimale\n";
}

echo "\n";

// Réinitialiser le log
DB::flushQueryLog();

// ==========================================
// TEST 4: Vérification de l'existence d'un bulletin
// ==========================================
echo "📄 TEST 4: Vérification existence bulletin (avec index unique)\n";
echo "---------------------------------------------------------------\n";

$startTime = microtime(true);
$queryCountStart = count(DB::getQueryLog());

if ($student) {
    $bulletin = BulletinGeneration::where('student_id', $student->id)
                                 ->where('bulletin_type', 'sequence')
                                 ->where('period_identifier', 'seq1')
                                 ->first();

    $endTime = microtime(true);
    $queryCountEnd = count(DB::getQueryLog());
    $executionTime = ($endTime - $startTime) * 1000;
    $queryCount = $queryCountEnd - $queryCountStart;

    echo "   ✅ Bulletin trouvé: " . ($bulletin ? "Oui" : "Non") . "\n";
    echo "   ⏱️  Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";
    echo "   📊 Nombre de requêtes SQL: {$queryCount}\n";
    echo "   🎯 Objectif: < 10ms, 1 requête (utilise index unique)\n";

    if ($executionTime < 10 && $queryCount == 1) {
        echo "   ✅ PASSÉ (index optimisé)\n";
    } else {
        echo "   ⚠️  ATTENTION: Index unique peut-être manquant\n";
    }
}

echo "\n";

// Réinitialiser le log
DB::flushQueryLog();

// ==========================================
// TEST 5: Agrégations pour statistiques
// ==========================================
echo "📈 TEST 5: Calcul de statistiques avec agrégations SQL\n";
echo "-------------------------------------------------------\n";

$startTime = microtime(true);
$queryCountStart = count(DB::getQueryLog());

$stats = Payment::selectRaw('
                COUNT(*) as total_payments,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_payment,
                MIN(payment_date) as first_payment,
                MAX(payment_date) as last_payment
            ')
            ->first();

$endTime = microtime(true);
$queryCountEnd = count(DB::getQueryLog());
$executionTime = ($endTime - $startTime) * 1000;
$queryCount = $queryCountEnd - $queryCountStart;

echo "   ✅ Total paiements: " . number_format($stats->total_payments ?? 0) . "\n";
echo "   ✅ Revenu total: " . number_format($stats->total_revenue ?? 0, 0, ',', ' ') . " FCFA\n";
echo "   ⏱️  Temps d'exécution: " . number_format($executionTime, 2) . " ms\n";
echo "   📊 Nombre de requêtes SQL: {$queryCount}\n";
echo "   🎯 Objectif: < 100ms, 1 requête\n";

if ($executionTime < 100 && $queryCount == 1) {
    echo "   ✅ PASSÉ\n";
} else {
    echo "   ⚠️  ATTENTION: Agrégations lentes\n";
}

echo "\n";

// ==========================================
// STATISTIQUES GLOBALES
// ==========================================
echo "========================================\n";
echo "📊 STATISTIQUES GLOBALES DE LA BASE\n";
echo "========================================\n\n";

$tables = [
    'students',
    'grades',
    'evaluations',
    'payments',
    'payment_details',
    'bulletin_generations',
    'attendance',
    'teacher_assignments'
];

foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        echo "   📋 {$table}: " . number_format($count) . " lignes\n";
    } catch (\Exception $e) {
        echo "   ⚠️  {$table}: Erreur lecture\n";
    }
}

echo "\n========================================\n";
echo "📌 RECOMMANDATIONS\n";
echo "========================================\n\n";

echo "Si les tests échouent:\n";
echo "1. Vérifier que la migration d'index a été appliquée:\n";
echo "   php artisan migrate:status\n\n";
echo "2. Vérifier les index créés:\n";
echo "   php artisan tinker\n";
echo "   >>> DB::select(\"SHOW INDEX FROM grades\");\n\n";
echo "3. Optimiser les tables:\n";
echo "   ANALYZE TABLE grades;\n";
echo "   OPTIMIZE TABLE grades;\n\n";

echo "Si les tests réussissent:\n";
echo "✅ La base de données est correctement optimisée!\n";
echo "✅ Les index sont actifs et performants\n";
echo "✅ Prêt pour la production\n\n";

echo "========================================\n";
echo "✅ Tests terminés!\n";
echo "========================================\n";
