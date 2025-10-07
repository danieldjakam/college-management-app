#!/usr/bin/env php
<?php

/**
 * Script de correction de la table main_teachers
 * À exécuter depuis la racine du projet Laravel : php fix_main_teachers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "========================================\n";
echo "Script de correction de main_teachers\n";
echo "========================================\n\n";

try {
    // 1. Vérifier la structure actuelle
    echo "1. Vérification de la structure actuelle...\n";
    $columns = Schema::getColumnListing('main_teachers');
    echo "   Colonnes actuelles: " . implode(', ', $columns) . "\n\n";

    $hasSchoolClassId = in_array('school_class_id', $columns);
    $hasClassSeriesId = in_array('class_series_id', $columns);

    echo "   - school_class_id existe: " . ($hasSchoolClassId ? 'OUI' : 'NON') . "\n";
    echo "   - class_series_id existe: " . ($hasClassSeriesId ? 'OUI' : 'NON') . "\n\n";

    // 2. Vérifier les clés étrangères
    echo "2. Vérification des clés étrangères...\n";
    $foreignKeys = DB::select("
        SELECT CONSTRAINT_NAME, COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'main_teachers'
          AND TABLE_SCHEMA = DATABASE()
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");

    foreach ($foreignKeys as $fk) {
        echo "   - {$fk->CONSTRAINT_NAME} sur {$fk->COLUMN_NAME}\n";
    }
    echo "\n";

    // 3. Vérifier les index
    echo "3. Vérification des index...\n";
    $indexes = DB::select("SHOW INDEX FROM main_teachers");
    $uniqueIndexes = [];
    foreach ($indexes as $index) {
        if ($index->Key_name !== 'PRIMARY') {
            $uniqueIndexes[$index->Key_name][] = $index->Column_name;
        }
    }
    foreach ($uniqueIndexes as $name => $cols) {
        echo "   - {$name} sur (" . implode(', ', $cols) . ")\n";
    }
    echo "\n";

    // 4. Appliquer les corrections nécessaires
    echo "4. Application des corrections...\n";

    DB::beginTransaction();

    // Si class_series_id existe déjà, on a juste besoin de marquer la migration
    if ($hasClassSeriesId && !$hasSchoolClassId) {
        echo "   ✓ La colonne class_series_id existe déjà.\n";
        echo "   → Marquage de la migration comme exécutée...\n";

        // Vérifier si la migration est déjà enregistrée
        $migrationExists = DB::table('migrations')
            ->where('migration', '2025_10_07_201543_update_main_teachers_to_use_class_series_id')
            ->exists();

        if (!$migrationExists) {
            $maxBatch = DB::table('migrations')->max('batch') ?? 0;
            DB::table('migrations')->insert([
                'migration' => '2025_10_07_201543_update_main_teachers_to_use_class_series_id',
                'batch' => $maxBatch + 1
            ]);
            echo "   ✓ Migration marquée comme exécutée.\n";
        } else {
            echo "   ✓ Migration déjà enregistrée.\n";
        }
    }
    // Si school_class_id existe encore, faire la migration complète
    elseif ($hasSchoolClassId && !$hasClassSeriesId) {
        echo "   → Migration de school_class_id vers class_series_id...\n";

        // Supprimer les clés étrangères sur school_class_id
        foreach ($foreignKeys as $fk) {
            if ($fk->COLUMN_NAME === 'school_class_id') {
                echo "   → Suppression de la clé étrangère {$fk->CONSTRAINT_NAME}...\n";
                DB::statement("ALTER TABLE main_teachers DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }
        }

        // Supprimer l'index unique s'il existe
        if (isset($uniqueIndexes['unique_main_teacher_per_class'])) {
            echo "   → Suppression de l'index unique_main_teacher_per_class...\n";
            DB::statement("ALTER TABLE main_teachers DROP INDEX unique_main_teacher_per_class");
        }

        // Supprimer la colonne school_class_id
        echo "   → Suppression de la colonne school_class_id...\n";
        Schema::table('main_teachers', function ($table) {
            $table->dropColumn('school_class_id');
        });

        // Ajouter la nouvelle colonne class_series_id
        echo "   → Ajout de la colonne class_series_id...\n";
        DB::statement("ALTER TABLE main_teachers ADD COLUMN class_series_id BIGINT UNSIGNED NOT NULL AFTER teacher_id");

        // Ajouter la clé étrangère
        echo "   → Ajout de la clé étrangère...\n";
        Schema::table('main_teachers', function ($table) {
            $table->foreign('class_series_id')
                  ->references('id')
                  ->on('class_series')
                  ->onDelete('cascade');
        });

        // Ajouter l'index unique
        echo "   → Ajout de l'index unique...\n";
        DB::statement("ALTER TABLE main_teachers ADD UNIQUE unique_main_teacher_per_class_series (class_series_id, school_year_id)");

        // Marquer la migration comme exécutée
        $migrationExists = DB::table('migrations')
            ->where('migration', '2025_10_07_201543_update_main_teachers_to_use_class_series_id')
            ->exists();

        if (!$migrationExists) {
            $maxBatch = DB::table('migrations')->max('batch') ?? 0;
            DB::table('migrations')->insert([
                'migration' => '2025_10_07_201543_update_main_teachers_to_use_class_series_id',
                'batch' => $maxBatch + 1
            ]);
            echo "   ✓ Migration marquée comme exécutée.\n";
        }

        echo "   ✓ Migration complétée avec succès.\n";
    }
    // Si les deux colonnes existent, c'est un état incohérent
    elseif ($hasSchoolClassId && $hasClassSeriesId) {
        echo "   ⚠ ATTENTION: Les deux colonnes existent!\n";
        echo "   → Suppression de school_class_id (ancienne colonne)...\n";

        // Supprimer les clés étrangères sur school_class_id
        foreach ($foreignKeys as $fk) {
            if ($fk->COLUMN_NAME === 'school_class_id') {
                echo "   → Suppression de la clé étrangère {$fk->CONSTRAINT_NAME}...\n";
                DB::statement("ALTER TABLE main_teachers DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }
        }

        // Supprimer l'ancien index unique s'il existe
        if (isset($uniqueIndexes['unique_main_teacher_per_class'])) {
            echo "   → Suppression de l'index unique_main_teacher_per_class...\n";
            DB::statement("ALTER TABLE main_teachers DROP INDEX unique_main_teacher_per_class");
        }

        // Supprimer la colonne school_class_id
        Schema::table('main_teachers', function ($table) {
            $table->dropColumn('school_class_id');
        });

        echo "   ✓ Colonne school_class_id supprimée.\n";

        // Marquer la migration
        $migrationExists = DB::table('migrations')
            ->where('migration', '2025_10_07_201543_update_main_teachers_to_use_class_series_id')
            ->exists();

        if (!$migrationExists) {
            $maxBatch = DB::table('migrations')->max('batch') ?? 0;
            DB::table('migrations')->insert([
                'migration' => '2025_10_07_201543_update_main_teachers_to_use_class_series_id',
                'batch' => $maxBatch + 1
            ]);
            echo "   ✓ Migration marquée comme exécutée.\n";
        }
    }
    // Si aucune des deux n'existe
    else {
        echo "   ⚠ ATTENTION: Ni school_class_id ni class_series_id n'existent!\n";
        echo "   → Ajout de la colonne class_series_id...\n";

        DB::statement("ALTER TABLE main_teachers ADD COLUMN class_series_id BIGINT UNSIGNED NOT NULL AFTER teacher_id");

        Schema::table('main_teachers', function ($table) {
            $table->foreign('class_series_id')
                  ->references('id')
                  ->on('class_series')
                  ->onDelete('cascade');
        });

        DB::statement("ALTER TABLE main_teachers ADD UNIQUE unique_main_teacher_per_class_series (class_series_id, school_year_id)");

        $migrationExists = DB::table('migrations')
            ->where('migration', '2025_10_07_201543_update_main_teachers_to_use_class_series_id')
            ->exists();

        if (!$migrationExists) {
            $maxBatch = DB::table('migrations')->max('batch') ?? 0;
            DB::table('migrations')->insert([
                'migration' => '2025_10_07_201543_update_main_teachers_to_use_class_series_id',
                'batch' => $maxBatch + 1
            ]);
        }

        echo "   ✓ Colonne class_series_id ajoutée.\n";
    }

    DB::commit();

    echo "\n5. Vérification finale...\n";
    $finalColumns = Schema::getColumnListing('main_teachers');
    echo "   Colonnes finales: " . implode(', ', $finalColumns) . "\n";

    echo "\n========================================\n";
    echo "✓ SUCCÈS: Correction terminée!\n";
    echo "========================================\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n========================================\n";
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
    echo "========================================\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
