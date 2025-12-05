<?php
/**
 * Script de correction pour les séquences en production
 *
 * ⚠️ ATTENTION: Ce script modifie la base de données!
 * Exécutez d'abord diagnose_sequences_production.php pour voir les problèmes
 *
 * Usage:
 * php artisan tinker < scripts/fix_sequences_production.php
 *
 * Ou copiez-collez le contenu dans tinker en production
 */

echo "==========================================\n";
echo "CORRECTION DES SÉQUENCES EN PRODUCTION\n";
echo "==========================================\n\n";

// Sauvegarder l'état actuel
echo "📸 Sauvegarde de l'état actuel...\n\n";
$backup = DB::table('sequences')->get();
echo "✅ " . $backup->count() . " séquences sauvegardées en mémoire\n\n";

$changes = [];
$errors = [];

// 1. Corriger les compositions avec mauvais flag ou number
echo "🔧 CORRECTION 1: Compositions mal configurées\n\n";

$compositions = DB::table('sequences')
    ->where(function($query) {
        $query->where('name', 'LIKE', '%Composition%')
              ->orWhere('name', 'LIKE', '%composition%');
    })
    ->get();

foreach ($compositions as $comp) {
    $updates = [];

    if ($comp->is_composition != 1) {
        $updates['is_composition'] = 1;
        $changes[] = "→ Séquence ID {$comp->id} ({$comp->name}): is_composition mis à 1";
    }

    if ($comp->number != 0) {
        $updates['number'] = 0;
        $changes[] = "→ Séquence ID {$comp->id} ({$comp->name}): number mis à 0";
    }

    if (!empty($updates)) {
        try {
            DB::table('sequences')->where('id', $comp->id)->update($updates);
            echo "✅ Séquence ID {$comp->id} corrigée\n";
        } catch (\Exception $e) {
            $errors[] = "❌ Erreur lors de la correction de la séquence ID {$comp->id}: " . $e->getMessage();
            echo "❌ Erreur: " . $e->getMessage() . "\n";
        }
    }
}

if (empty($changes)) {
    echo "✅ Aucune correction nécessaire pour les compositions\n";
}

echo "\n";

// 2. Vérifier et supprimer les doublons
echo "🔧 CORRECTION 2: Suppression des doublons\n\n";

$duplicates = DB::table('sequences')
    ->select('trimester_id', 'number', DB::raw('GROUP_CONCAT(id) as ids'), DB::raw('COUNT(*) as count'))
    ->groupBy('trimester_id', 'number')
    ->having('count', '>', 1)
    ->get();

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup->ids);
    echo "⚠️ Doublon détecté: Trimestre {$dup->trimester_id}, Number {$dup->number}\n";
    echo "   IDs: " . implode(', ', $ids) . "\n";

    // Garder la séquence avec is_composition=1 si c'est une composition
    $sequences = DB::table('sequences')->whereIn('id', $ids)->get();

    $keepId = null;
    $deleteIds = [];

    // Trouver laquelle garder
    foreach ($sequences as $seq) {
        if ($seq->is_composition == 1 && $keepId === null) {
            $keepId = $seq->id;
        } elseif ($keepId === null) {
            $keepId = $seq->id; // Garder la première par défaut
        } else {
            $deleteIds[] = $seq->id;
        }
    }

    echo "   → Garder: ID {$keepId}\n";
    echo "   → Supprimer: IDs " . implode(', ', $deleteIds) . "\n";

    // Migrer les évaluations vers la séquence à garder
    foreach ($deleteIds as $deleteId) {
        $evalCount = DB::table('evaluations')->where('sequence_id', $deleteId)->count();

        if ($evalCount > 0) {
            echo "   → Migration de {$evalCount} évaluations de ID {$deleteId} vers ID {$keepId}\n";

            try {
                // Vérifier s'il y a des doublons après migration
                $existingEvals = DB::table('evaluations')
                    ->where('sequence_id', $keepId)
                    ->pluck('id')
                    ->toArray();

                // Migrer les évaluations uniques
                DB::table('evaluations')
                    ->where('sequence_id', $deleteId)
                    ->whereNotIn('id', $existingEvals)
                    ->update(['sequence_id' => $keepId]);

                // Supprimer les doublons
                DB::table('evaluations')
                    ->where('sequence_id', $deleteId)
                    ->delete();

                $changes[] = "→ {$evalCount} évaluations migrées de séquence {$deleteId} vers {$keepId}";
            } catch (\Exception $e) {
                $errors[] = "❌ Erreur lors de la migration des évaluations: " . $e->getMessage();
                echo "   ❌ Erreur: " . $e->getMessage() . "\n";
                continue; // Ne pas supprimer si la migration a échoué
            }
        }

        // Supprimer la séquence dupliquée
        try {
            DB::table('sequences')->where('id', $deleteId)->delete();
            $changes[] = "→ Séquence dupliquée ID {$deleteId} supprimée";
            echo "   ✅ Séquence ID {$deleteId} supprimée\n";
        } catch (\Exception $e) {
            $errors[] = "❌ Erreur lors de la suppression de la séquence ID {$deleteId}: " . $e->getMessage();
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

if (empty($duplicates)) {
    echo "✅ Aucun doublon détecté\n\n";
}

// 3. Résumé
echo "==========================================\n";
echo "RÉSUMÉ DES MODIFICATIONS\n";
echo "==========================================\n\n";

if (!empty($changes)) {
    echo "✅ MODIFICATIONS EFFECTUÉES (" . count($changes) . "):\n\n";
    foreach ($changes as $change) {
        echo "$change\n";
    }
    echo "\n";
} else {
    echo "✅ Aucune modification nécessaire\n\n";
}

if (!empty($errors)) {
    echo "❌ ERREURS RENCONTRÉES (" . count($errors) . "):\n\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
    echo "\n";
    echo "⚠️ Certaines corrections ont échoué. Vérifiez les erreurs ci-dessus.\n\n";
}

// État final
echo "📊 ÉTAT FINAL DES SÉQUENCES:\n\n";
$final_sequences = DB::table('sequences')
    ->orderBy('trimester_id')
    ->orderBy('number')
    ->get(['id', 'name', 'number', 'trimester_id', 'is_composition']);

foreach ($final_sequences as $seq) {
    $type = $seq->is_composition ? '🎯 COMPOSITION' : '📝 SÉQUENCE';
    echo "ID {$seq->id} | {$seq->name} | Number: {$seq->number} | Trim {$seq->trimester_id} | {$type}\n";
}

echo "\n==========================================\n";
echo "FIN DE LA CORRECTION\n";
echo "==========================================\n\n";

echo "💡 Prochaines étapes:\n";
echo "1. Vérifiez que les PV se génèrent correctement\n";
echo "2. Testez la génération de bulletins\n";
echo "3. Si tout fonctionne, c'est terminé!\n";
