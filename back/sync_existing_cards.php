<?php
/**
 * Script de synchronisation pour les cartes déjà imprimées
 *
 * Ce script permet de :
 * 1. Vérifier les QR codes existants
 * 2. Synchroniser staff_identifier avec qr_code
 * 3. Générer des QR codes manquants
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Teacher;

// Charger Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SYNCHRONISATION DES CARTES EXISTANTES ===\n\n";

try {
    echo "1. ANALYSE DES UTILISATEURS...\n";

    $users = User::whereIn('role', ['vacataire', 'SP', 'P', 'teacher', 'enseignant', 'bibliothecaire', 'secretaire'])->get();

    echo "Utilisateurs trouvés: " . $users->count() . "\n\n";

    $updated = 0;
    $generated = 0;

    foreach ($users as $user) {
        $needsUpdate = false;
        $updates = [];

        // Cas 1: L'utilisateur a un staff_identifier mais pas de qr_code
        if ($user->staff_identifier && !$user->qr_code) {
            $updates['qr_code'] = $user->staff_identifier;
            $needsUpdate = true;
            echo "✅ Sync QR: {$user->name} -> QR: {$user->staff_identifier}\n";
        }

        // Cas 2: L'utilisateur a un qr_code mais pas de staff_identifier
        elseif ($user->qr_code && !$user->staff_identifier) {
            $updates['staff_identifier'] = $user->qr_code;
            $needsUpdate = true;
            echo "✅ Sync ID: {$user->name} -> ID: {$user->qr_code}\n";
        }

        // Cas 3: L'utilisateur n'a ni qr_code ni staff_identifier
        elseif (!$user->qr_code && !$user->staff_identifier) {
            $newId = 'STAFF_' . $user->id;
            $updates['qr_code'] = $newId;
            $updates['staff_identifier'] = $newId;
            $needsUpdate = true;
            $generated++;
            echo "🆕 Nouveau: {$user->name} -> ID/QR: {$newId}\n";
        }

        // Cas 4: Les deux existent mais sont différents
        elseif ($user->qr_code && $user->staff_identifier && $user->qr_code !== $user->staff_identifier) {
            // Vérifier si le staff_identifier est déjà utilisé comme qr_code par quelqu'un d'autre
            $existingUser = User::where('qr_code', $user->staff_identifier)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                echo "⚠️  Conflit détecté: {$user->name} -> {$user->staff_identifier} déjà utilisé par {$existingUser->name}\n";
                echo "   Génération d'un nouveau QR: ";
                $newQr = $user->staff_identifier . '_U' . $user->id;
                $updates['qr_code'] = $newQr;
                echo "{$newQr}\n";
            } else {
                // Prioriser le staff_identifier (ID Personnel)
                $updates['qr_code'] = $user->staff_identifier;
                echo "🔄 Correction: {$user->name} -> QR mis à jour vers {$user->staff_identifier}\n";
            }
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            try {
                $user->update($updates);
                $updated++;
            } catch (Exception $e) {
                echo "❌ Erreur pour {$user->name}: " . $e->getMessage() . "\n";
                // Générer un QR unique en cas d'échec
                try {
                    $fallbackQr = 'USER_' . $user->id . '_' . time();
                    $user->update(['qr_code' => $fallbackQr]);
                    echo "   Fallback QR généré: {$fallbackQr}\n";
                    $updated++;
                } catch (Exception $e2) {
                    echo "   ❌ Fallback échoué aussi: " . $e2->getMessage() . "\n";
                }
            }
        }
    }

    echo "\n2. ANALYSE DES ENSEIGNANTS (table teachers)...\n";

    $teachers = Teacher::all();
    echo "Enseignants trouvés: " . $teachers->count() . "\n\n";

    foreach ($teachers as $teacher) {
        $needsUpdate = false;
        $updates = [];

        // Même logique pour les enseignants
        if ($teacher->staff_identifier && !$teacher->qr_code) {
            $updates['qr_code'] = $teacher->staff_identifier;
            $needsUpdate = true;
            echo "✅ Sync QR Teacher: {$teacher->first_name} {$teacher->last_name} -> QR: {$teacher->staff_identifier}\n";
        }
        elseif ($teacher->qr_code && !$teacher->staff_identifier) {
            $updates['staff_identifier'] = $teacher->qr_code;
            $needsUpdate = true;
            echo "✅ Sync ID Teacher: {$teacher->first_name} {$teacher->last_name} -> ID: {$teacher->qr_code}\n";
        }
        elseif (!$teacher->qr_code && !$teacher->staff_identifier) {
            $newId = 'TEACH_' . $teacher->id;
            $updates['qr_code'] = $newId;
            $updates['staff_identifier'] = $newId;
            $needsUpdate = true;
            $generated++;
            echo "🆕 Nouveau Teacher: {$teacher->first_name} {$teacher->last_name} -> ID/QR: {$newId}\n";
        }
        elseif ($teacher->qr_code && $teacher->staff_identifier && $teacher->qr_code !== $teacher->staff_identifier) {
            $updates['qr_code'] = $teacher->staff_identifier;
            $needsUpdate = true;
            echo "🔄 Correction Teacher: {$teacher->first_name} {$teacher->last_name} -> QR mis à jour vers {$teacher->staff_identifier}\n";
        }

        if ($needsUpdate) {
            $teacher->update($updates);
            $updated++;
        }
    }

    echo "\n=== RÉSUMÉ ===\n";
    echo "Utilisateurs mis à jour: {$updated}\n";
    echo "Nouveaux ID/QR générés: {$generated}\n";

    echo "\n=== VÉRIFICATION FINALE ===\n";

    $usersWithBoth = User::whereNotNull('qr_code')
        ->whereNotNull('staff_identifier')
        ->where('qr_code', '!=', '')
        ->where('staff_identifier', '!=', '')
        ->count();

    $teachersWithBoth = Teacher::whereNotNull('qr_code')
        ->whereNotNull('staff_identifier')
        ->where('qr_code', '!=', '')
        ->where('staff_identifier', '!=', '')
        ->count();

    echo "Utilisateurs avec ID et QR synchronisés: {$usersWithBoth}\n";
    echo "Enseignants avec ID et QR synchronisés: {$teachersWithBoth}\n";

    echo "\n✅ SYNCHRONISATION TERMINÉE\n";
    echo "Vos cartes existantes fonctionneront maintenant avec les QR codes !\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}