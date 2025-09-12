<?php

/**
 * Script de migration pour déplacer les enseignants de la table users vers la table teachers
 * 
 * Ce script va:
 * 1. Récupérer tous les utilisateurs avec le rôle 'teacher'
 * 2. Créer une entrée correspondante dans la table teachers
 * 3. Optionnellement supprimer ou désactiver l'utilisateur de la table users
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Teacher;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n===========================================\n";
echo "  MIGRATION DES ENSEIGNANTS\n";
echo "===========================================\n\n";

try {
    DB::beginTransaction();
    
    // 1. Récupérer tous les utilisateurs avec le rôle 'teacher'
    $teacherUsers = User::where('role', 'teacher')->get();
    
    echo "📊 Nombre d'enseignants trouvés dans la table users: " . $teacherUsers->count() . "\n\n";
    
    if ($teacherUsers->count() === 0) {
        echo "❌ Aucun enseignant trouvé dans la table users.\n";
        DB::rollBack();
        exit(0);
    }
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($teacherUsers as $user) {
        echo "👤 Traitement de: {$user->name} (ID: {$user->id})\n";
        
        try {
            // Vérifier si l'enseignant existe déjà
            $existingTeacher = Teacher::where('email', $user->email)->first();
            
            if ($existingTeacher) {
                echo "   ⚠️  Enseignant déjà existant dans la table teachers (ID: {$existingTeacher->id})\n";
                $skipCount++;
                
                // Optionnel: Mettre à jour les informations si nécessaire
                $existingTeacher->update([
                    'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                    'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                    'phone_number' => $user->contact,
                    'address' => $user->address ?? '',
                    'is_active' => $user->is_active ?? true,
                ]);
                echo "   ✅ Informations mises à jour\n";
                
            } else {
                // Générer un teacher_id unique si nécessaire
                $lastTeacher = Teacher::orderBy('id', 'desc')->first();
                $nextId = $lastTeacher ? $lastTeacher->id + 1 : 1;
                $teacherId = 'TCH_' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                
                // Créer le nouvel enseignant
                $newTeacher = Teacher::create([
                    'teacher_id' => $teacherId,
                    'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                    'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                    'email' => $user->email,
                    'phone_number' => $user->contact ?? '',
                    'address' => $user->address ?? '',
                    'date_of_birth' => $user->date_of_birth ?? null,
                    'gender' => $user->gender ?? 'male',
                    'qualification' => $user->qualification ?? '',
                    'specialization' => $user->specialization ?? '',
                    'years_of_experience' => 0,
                    'date_joined' => $user->created_at ?? now(),
                    'is_active' => $user->is_active ?? true,
                    'photo' => $user->photo ?? null,
                    'password' => $user->password, // Conserver le même mot de passe hashé
                ]);
                
                echo "   ✅ Enseignant créé avec succès (ID: {$newTeacher->id}, Teacher_ID: {$teacherId})\n";
                $successCount++;
            }
            
            // Optionnel: Désactiver ou supprimer l'utilisateur de la table users
            // Option 1: Désactiver l'utilisateur
            $user->update(['is_active' => false, 'role' => 'migrated_teacher']);
            echo "   🔄 Utilisateur marqué comme migré\n";
            
            // Option 2: Supprimer l'utilisateur (décommentez si vous voulez supprimer)
            // $user->delete();
            // echo "   🗑️  Utilisateur supprimé de la table users\n";
            
        } catch (\Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            $errorCount++;
            Log::error("Erreur migration enseignant {$user->id}: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    echo "===========================================\n";
    echo "📊 RÉSUMÉ DE LA MIGRATION\n";
    echo "===========================================\n";
    echo "✅ Enseignants migrés avec succès: {$successCount}\n";
    echo "⚠️  Enseignants déjà existants: {$skipCount}\n";
    echo "❌ Erreurs rencontrées: {$errorCount}\n";
    echo "\n";
    
    // Demander confirmation avant de valider
    echo "Voulez-vous valider ces changements? (yes/no) [no]: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $response = trim($line);
    fclose($handle);
    
    if ($response === 'yes' || $response === 'y') {
        DB::commit();
        echo "\n✅ Migration validée et enregistrée avec succès!\n";
        
        // Afficher les statistiques finales
        $totalTeachers = Teacher::count();
        $activeTeachers = Teacher::where('is_active', true)->count();
        echo "\n📊 Statistiques finales:\n";
        echo "   - Total enseignants dans la table teachers: {$totalTeachers}\n";
        echo "   - Enseignants actifs: {$activeTeachers}\n";
        
    } else {
        DB::rollBack();
        echo "\n❌ Migration annulée. Aucun changement n'a été enregistré.\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "La migration a été annulée.\n";
    Log::error("Erreur critique migration enseignants: " . $e->getMessage());
    exit(1);
}

echo "\n✨ Script terminé.\n\n";