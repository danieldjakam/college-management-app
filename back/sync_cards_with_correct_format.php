<?php
/**
 * Script de synchronisation des cartes avec formats corrects :
 * - TCH_ID pour les enseignants
 * - STAF_ID pour le personnel administratif
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Teacher;

// Charger Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SYNCHRONISATION AVEC FORMATS CORRECTS ===\n";
echo "📝 TCH_ID pour enseignants | STAF_ID pour personnel administratif\n\n";

try {
    $updated = 0;

    echo "1. TRAITEMENT DES ENSEIGNANTS (Users avec rôle enseignant)...\n";

    $teacherUsers = User::whereIn('role', ['teacher', 'enseignant'])->get();
    echo "Enseignants (users) trouvés: " . $teacherUsers->count() . "\n";

    foreach ($teacherUsers as $user) {
        $correctId = 'TCH_' . $user->id;
        $needsUpdate = false;
        $updates = [];

        // Vérifier si l'ID actuel ne respecte pas le format TCH_
        if (!$user->staff_identifier || !str_starts_with($user->staff_identifier, 'TCH_')) {
            $updates['staff_identifier'] = $correctId;
            $needsUpdate = true;
            echo "🔧 Format ID: {$user->name} -> {$correctId}\n";
        }

        // Synchroniser le QR code
        if ($user->qr_code !== $user->staff_identifier) {
            $updates['qr_code'] = $updates['staff_identifier'] ?? $user->staff_identifier;
            $needsUpdate = true;
            echo "🔗 Sync QR: {$user->name} -> {$updates['qr_code']}\n";
        }

        if ($needsUpdate) {
            $user->update($updates);
            $updated++;
        }
    }

    echo "\n2. TRAITEMENT DU PERSONNEL ADMINISTRATIF...\n";

    $adminUsers = User::whereIn('role', ['vacataire', 'SP', 'P', 'bibliothecaire', 'secretaire', 'comptable', 'surveillant_general', 'principal'])->get();
    echo "Personnel administratif trouvé: " . $adminUsers->count() . "\n";

    foreach ($adminUsers as $user) {
        $correctId = 'STAF_' . $user->id;
        $needsUpdate = false;
        $updates = [];

        // Vérifier si l'ID actuel ne respecte pas le format STAF_
        if (!$user->staff_identifier || !str_starts_with($user->staff_identifier, 'STAF_')) {
            $updates['staff_identifier'] = $correctId;
            $needsUpdate = true;
            echo "🔧 Format ID: {$user->name} -> {$correctId}\n";
        }

        // Synchroniser le QR code
        if ($user->qr_code !== $user->staff_identifier) {
            $updates['qr_code'] = $updates['staff_identifier'] ?? $user->staff_identifier;
            $needsUpdate = true;
            echo "🔗 Sync QR: {$user->name} -> {$updates['qr_code']}\n";
        }

        if ($needsUpdate) {
            $user->update($updates);
            $updated++;
        }
    }

    echo "\n3. TRAITEMENT DES ENSEIGNANTS (Table teachers)...\n";

    $teachers = Teacher::all();
    echo "Enseignants (teachers) trouvés: " . $teachers->count() . "\n";

    foreach ($teachers as $teacher) {
        $correctId = 'TCH_' . $teacher->id;
        $needsUpdate = false;
        $updates = [];

        // Vérifier si l'ID actuel ne respecte pas le format TCH_
        if (!$teacher->staff_identifier || !str_starts_with($teacher->staff_identifier, 'TCH_')) {
            $updates['staff_identifier'] = $correctId;
            $needsUpdate = true;
            echo "🔧 Format ID Teacher: {$teacher->first_name} {$teacher->last_name} -> {$correctId}\n";
        }

        // Synchroniser le QR code
        if ($teacher->qr_code !== $teacher->staff_identifier) {
            $updates['qr_code'] = $updates['staff_identifier'] ?? $teacher->staff_identifier;
            $needsUpdate = true;
            echo "🔗 Sync QR Teacher: {$teacher->first_name} {$teacher->last_name} -> {$updates['qr_code']}\n";
        }

        if ($needsUpdate) {
            $teacher->update($updates);
            $updated++;
        }
    }

    echo "\n=== RÉSUMÉ DE LA SYNCHRONISATION ===\n";
    echo "Total mis à jour: {$updated}\n";

    // Vérification finale
    echo "\n=== VÉRIFICATION FINALE ===\n";

    $teachersWithTCH = User::whereIn('role', ['teacher', 'enseignant'])
        ->where('staff_identifier', 'LIKE', 'TCH_%')
        ->where('qr_code', '=', DB::raw('staff_identifier'))
        ->count();

    $staffWithSTAF = User::whereIn('role', ['vacataire', 'SP', 'P', 'bibliothecaire', 'secretaire'])
        ->where('staff_identifier', 'LIKE', 'STAF_%')
        ->where('qr_code', '=', DB::raw('staff_identifier'))
        ->count();

    $teachersTableWithTCH = Teacher::where('staff_identifier', 'LIKE', 'TCH_%')
        ->where('qr_code', '=', DB::raw('staff_identifier'))
        ->count();

    echo "✅ Enseignants (users) avec TCH_ synchronisés: {$teachersWithTCH}\n";
    echo "✅ Personnel admin avec STAF_ synchronisés: {$staffWithSTAF}\n";
    echo "✅ Teachers table avec TCH_ synchronisés: {$teachersTableWithTCH}\n";

    echo "\n🎉 SYNCHRONISATION TERMINÉE !\n";
    echo "📋 Vos cartes existantes sont maintenant compatibles avec le système de scan.\n";
    echo "💡 Format enseignants: TCH_ID | Format personnel: STAF_ID\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}