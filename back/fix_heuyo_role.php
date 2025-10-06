<?php

/**
 * Script pour changer le rôle de M. HEUYO Patrice
 * De surveillant_general à teacher (enseignant permanent)
 * Sans affecter ses présences et autres données
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Teacher;
use App\Models\StaffAttendance;
use Illuminate\Support\Facades\DB;

echo "=== CHANGEMENT DE RÔLE : M. HEUYO Patrice ===" . PHP_EOL . PHP_EOL;

try {
    DB::beginTransaction();

    // 1. Récupérer l'utilisateur
    $user = User::where('id', 61)->first();

    if (!$user) {
        throw new Exception("Utilisateur ID 61 (M. HEUYO Patrice) non trouvé");
    }

    echo "✅ Utilisateur trouvé:" . PHP_EOL;
    echo "   ID: " . $user->id . PHP_EOL;
    echo "   Nom: " . $user->name . PHP_EOL;
    echo "   Rôle actuel: " . $user->role . PHP_EOL;
    echo "   Email: " . $user->email . PHP_EOL;
    echo "   QR Code: " . $user->qr_code . PHP_EOL . PHP_EOL;

    // 2. Vérifier s'il existe déjà dans la table teachers
    $existingTeacher = Teacher::where('user_id', 61)->first();

    if ($existingTeacher) {
        echo "ℹ️  Déjà présent dans la table teachers (ID: {$existingTeacher->id})" . PHP_EOL;
        echo "   Mise à jour des informations..." . PHP_EOL . PHP_EOL;

        $existingTeacher->update([
            'type_personnel' => 'P', // P = Permanent
            'is_active' => true,
            'qr_code' => $user->qr_code,
            'email' => $user->email,
            'phone_number' => $user->contact
        ]);

        $teacher = $existingTeacher;
    } else {
        // 3. Créer une entrée dans la table teachers
        echo "📝 Création de l'entrée dans la table teachers..." . PHP_EOL;

        // Séparer le nom et prénom
        $nameParts = explode(' ', trim(str_replace('M.', '', $user->name)));
        $firstName = end($nameParts); // Patrice
        $lastName = implode(' ', array_slice($nameParts, 0, -1)); // HEUYO

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone_number' => $user->contact,
            'qr_code' => $user->qr_code,
            'type_personnel' => 'P', // P = Permanent
            'is_active' => true,
            'gender' => 'M',
            'hired_date' => now()->toDateString(),
        ]);

        echo "   ✅ Teacher créé avec ID: " . $teacher->id . PHP_EOL . PHP_EOL;
    }

    // 4. Mettre à jour le rôle dans users
    echo "🔄 Changement du rôle de '{$user->role}' à 'teacher'..." . PHP_EOL;
    $oldRole = $user->role;
    $user->role = 'teacher';
    $user->save();
    echo "   ✅ Rôle mis à jour" . PHP_EOL . PHP_EOL;

    // 5. Mettre à jour le staff_type dans les présences
    echo "📊 Mise à jour des présences (staff_type)..." . PHP_EOL;
    $attendanceCount = StaffAttendance::where('user_id', $user->id)->count();
    echo "   Total présences trouvées: {$attendanceCount}" . PHP_EOL;

    if ($attendanceCount > 0) {
        StaffAttendance::where('user_id', $user->id)
            ->update(['staff_type' => 'permanent']);
        echo "   ✅ Toutes les présences mises à jour avec staff_type='permanent'" . PHP_EOL . PHP_EOL;
    }

    // 6. Vérification finale
    echo "=== VÉRIFICATION FINALE ===" . PHP_EOL . PHP_EOL;

    $user->refresh();
    $teacher->refresh();

    echo "👤 USER:" . PHP_EOL;
    echo "   ID: " . $user->id . PHP_EOL;
    echo "   Nom: " . $user->name . PHP_EOL;
    echo "   Rôle: " . $user->role . " (ancien: {$oldRole})" . PHP_EOL;
    echo "   QR Code: " . $user->qr_code . PHP_EOL;
    echo "   Active: " . ($user->is_active ? 'OUI' : 'NON') . PHP_EOL . PHP_EOL;

    echo "👨‍🏫 TEACHER:" . PHP_EOL;
    echo "   ID: " . $teacher->id . PHP_EOL;
    echo "   Prénom: " . $teacher->first_name . PHP_EOL;
    echo "   Nom: " . $teacher->last_name . PHP_EOL;
    echo "   Type Personnel: " . $teacher->type_personnel . " (P = Permanent)" . PHP_EOL;
    echo "   User ID: " . $teacher->user_id . PHP_EOL;
    echo "   QR Code: " . $teacher->qr_code . PHP_EOL;
    echo "   Active: " . ($teacher->is_active ? 'OUI' : 'NON') . PHP_EOL . PHP_EOL;

    echo "📅 PRÉSENCES:" . PHP_EOL;
    $recentAttendances = StaffAttendance::where('user_id', $user->id)
        ->orderBy('scanned_at', 'desc')
        ->limit(5)
        ->get();

    foreach ($recentAttendances as $att) {
        echo "   - " . $att->scanned_at->format('Y-m-d H:i:s') . " | ";
        echo $att->event_type . " | ";
        echo "staff_type: " . $att->staff_type . " | ";
        echo "Retard: " . ($att->late_minutes ?? 0) . " min" . PHP_EOL;
    }

    // Commit de la transaction
    DB::commit();

    echo PHP_EOL . "✅ ✅ ✅ SUCCÈS ! Toutes les modifications ont été appliquées !" . PHP_EOL;
    echo PHP_EOL . "📋 RÉSUMÉ DES CHANGEMENTS:" . PHP_EOL;
    echo "   1. Rôle changé de '{$oldRole}' à 'teacher'" . PHP_EOL;
    echo "   2. Entrée teacher créée/mise à jour (ID: {$teacher->id})" . PHP_EOL;
    echo "   3. {$attendanceCount} présences mises à jour (staff_type='permanent')" . PHP_EOL;
    echo "   4. Aucune donnée perdue - tout est préservé !" . PHP_EOL;

} catch (Exception $e) {
    DB::rollBack();
    echo PHP_EOL . "❌ ERREUR : " . $e->getMessage() . PHP_EOL;
    echo "Aucune modification n'a été appliquée (rollback effectué)" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== FIN DU SCRIPT ===" . PHP_EOL;
