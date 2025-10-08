#!/usr/bin/env php
<?php

/**
 * Script de nettoyage des affectations dupliquées
 * Une matière dans une classe ne peut être enseignée que par UN SEUL enseignant
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Nettoyage des affectations dupliquées\n";
echo "========================================\n\n";

try {
    DB::beginTransaction();

    // 1. Trouver les doublons (même class_series_subject_id pour la même année scolaire)
    echo "1. Recherche des doublons...\n";

    $duplicates = DB::select("
        SELECT
            class_series_subject_id,
            school_year_id,
            COUNT(*) as count,
            GROUP_CONCAT(id ORDER BY created_at) as ids,
            GROUP_CONCAT(teacher_id ORDER BY created_at) as teacher_ids
        FROM teacher_assignments
        WHERE is_active = 1
        GROUP BY class_series_subject_id, school_year_id
        HAVING COUNT(*) > 1
    ");

    if (empty($duplicates)) {
        echo "   ✓ Aucun doublon trouvé.\n\n";
        DB::commit();
        exit(0);
    }

    echo "   ⚠ " . count($duplicates) . " doublon(s) trouvé(s).\n\n";

    // 2. Afficher les détails des doublons
    echo "2. Détails des doublons :\n";
    foreach ($duplicates as $dup) {
        $ids = explode(',', $dup->ids);
        $teacherIds = explode(',', $dup->teacher_ids);

        // Récupérer les infos détaillées
        $assignment = DB::table('teacher_assignments as ta')
            ->join('class_series_subjects as css', 'ta.class_series_subject_id', '=', 'css.id')
            ->join('subjects as s', 'css.subject_id', '=', 's.id')
            ->join('class_series as cs', 'css.class_series_id', '=', 'cs.id')
            ->where('ta.id', $ids[0])
            ->select('s.name as subject', 'cs.name as class')
            ->first();

        echo "\n   Matière: {$assignment->subject} - Classe: {$assignment->class}\n";
        echo "   Nombre d'enseignants: {$dup->count}\n";

        foreach ($ids as $index => $id) {
            $teacher = DB::table('teachers')->find($teacherIds[$index]);
            $isKept = $index === 0 ? ' ← CONSERVÉ' : ' ← À SUPPRIMER';
            echo "     - Enseignant ID {$teacherIds[$index]}: {$teacher->first_name} {$teacher->last_name}{$isKept}\n";
        }
    }

    // 3. Demander confirmation (ou forcer en production)
    echo "\n3. Nettoyage...\n";
    echo "   Stratégie: Conserver la première affectation (la plus ancienne) et supprimer les autres.\n\n";

    $totalDeleted = 0;
    foreach ($duplicates as $dup) {
        $ids = explode(',', $dup->ids);
        // Garder le premier (index 0), supprimer les autres
        $idsToDelete = array_slice($ids, 1);

        foreach ($idsToDelete as $id) {
            DB::table('teacher_assignments')->where('id', $id)->delete();
            $totalDeleted++;
            echo "   ✓ Supprimé l'affectation ID: {$id}\n";
        }
    }

    echo "\n   ✓ Total supprimé: {$totalDeleted} affectation(s)\n";

    DB::commit();

    echo "\n========================================\n";
    echo "✓ Nettoyage terminé avec succès!\n";
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
