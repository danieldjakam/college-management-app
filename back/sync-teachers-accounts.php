<?php

/**
 * Script pour synchroniser les comptes enseignants
 * 
 * Ce script va:
 * 1. Vérifier les enseignants dans la table teachers qui n'ont pas de compte user
 * 2. Vérifier les users avec role='teacher' qui n'ont pas de profil teacher
 * 3. Proposer de créer les liens manquants
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n===========================================\n";
echo "  SYNCHRONISATION DES COMPTES ENSEIGNANTS\n";
echo "===========================================\n\n";

try {
    // 1. Analyser la situation actuelle
    echo "📊 ANALYSE DE LA SITUATION:\n";
    echo "===========================\n\n";
    
    // Enseignants dans teachers
    $allTeachers = Teacher::all();
    $teachersWithAccount = Teacher::whereNotNull('user_id')->count();
    $teachersWithoutAccount = Teacher::whereNull('user_id')->get();
    
    echo "📚 Table 'teachers':\n";
    echo "   - Total: " . $allTeachers->count() . "\n";
    echo "   - Avec compte utilisateur: {$teachersWithAccount}\n";
    echo "   - Sans compte utilisateur: " . $teachersWithoutAccount->count() . "\n\n";
    
    // Users avec role teacher
    $teacherUsers = User::where('role', 'teacher')->get();
    $usersLinkedToTeacher = 0;
    $orphanUsers = [];
    
    foreach ($teacherUsers as $user) {
        $linkedTeacher = Teacher::where('user_id', $user->id)->first();
        if ($linkedTeacher) {
            $usersLinkedToTeacher++;
        } else {
            $orphanUsers[] = $user;
        }
    }
    
    echo "👥 Table 'users' (role=teacher):\n";
    echo "   - Total: " . $teacherUsers->count() . "\n";
    echo "   - Liés à un profil teacher: {$usersLinkedToTeacher}\n";
    echo "   - Orphelins (sans profil): " . count($orphanUsers) . "\n\n";
    
    // 2. Afficher les enseignants sans compte
    if ($teachersWithoutAccount->count() > 0) {
        echo "⚠️  ENSEIGNANTS SANS COMPTE UTILISATEUR:\n";
        echo "=========================================\n\n";
        
        foreach ($teachersWithoutAccount as $teacher) {
            echo "ID: {$teacher->id} | {$teacher->teacher_id}\n";
            echo "Nom: {$teacher->first_name} {$teacher->last_name}\n";
            echo "Email: " . ($teacher->email ?: 'Non défini') . "\n";
            echo "-----------------------------------\n";
        }
        echo "\n";
    }
    
    // 3. Afficher les comptes orphelins
    if (count($orphanUsers) > 0) {
        echo "⚠️  COMPTES UTILISATEURS ORPHELINS:\n";
        echo "====================================\n\n";
        
        foreach ($orphanUsers as $user) {
            echo "ID: {$user->id}\n";
            echo "Nom: {$user->name}\n";
            echo "Email: {$user->email}\n";
            echo "Username: {$user->username}\n";
            
            // Essayer de trouver une correspondance par email
            $possibleTeacher = Teacher::where('email', $user->email)->first();
            if ($possibleTeacher) {
                echo "🔍 Correspondance possible: Teacher ID {$possibleTeacher->id} ({$possibleTeacher->first_name} {$possibleTeacher->last_name})\n";
            }
            echo "-----------------------------------\n";
        }
        echo "\n";
    }
    
    // 4. Proposer des actions
    echo "🔧 ACTIONS DISPONIBLES:\n";
    echo "======================\n\n";
    echo "1. Lier automatiquement les comptes orphelins aux enseignants (par email)\n";
    echo "2. Créer des comptes utilisateurs pour les enseignants qui n'en ont pas\n";
    echo "3. Afficher uniquement le rapport (aucune modification)\n";
    echo "4. Quitter\n\n";
    
    echo "Choisissez une option (1-4): ";
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);
    
    switch ($choice) {
        case '1':
            // Lier automatiquement par email
            echo "\n🔗 LIAISON AUTOMATIQUE PAR EMAIL:\n";
            echo "==================================\n\n";
            
            DB::beginTransaction();
            $linkedCount = 0;
            
            foreach ($orphanUsers as $user) {
                $teacher = Teacher::where('email', $user->email)->first();
                if ($teacher && !$teacher->user_id) {
                    $teacher->update(['user_id' => $user->id]);
                    echo "✅ Lié: {$user->name} → {$teacher->first_name} {$teacher->last_name}\n";
                    $linkedCount++;
                }
            }
            
            if ($linkedCount > 0) {
                echo "\nConfirmer la liaison de {$linkedCount} compte(s)? (yes/no): ";
                $handle = fopen("php://stdin", "r");
                $confirm = trim(fgets($handle));
                fclose($handle);
                
                if ($confirm === 'yes' || $confirm === 'y') {
                    DB::commit();
                    echo "✅ Liaisons enregistrées!\n";
                } else {
                    DB::rollBack();
                    echo "❌ Liaisons annulées.\n";
                }
            } else {
                DB::rollBack();
                echo "Aucune correspondance trouvée par email.\n";
            }
            break;
            
        case '2':
            // Créer des comptes pour les enseignants
            echo "\n👤 CRÉATION DE COMPTES UTILISATEURS:\n";
            echo "====================================\n\n";
            
            if ($teachersWithoutAccount->count() === 0) {
                echo "Tous les enseignants ont déjà un compte!\n";
                break;
            }
            
            echo "Mot de passe par défaut pour tous les comptes: ";
            $handle = fopen("php://stdin", "r");
            $defaultPassword = trim(fgets($handle));
            fclose($handle);
            
            if (strlen($defaultPassword) < 6) {
                echo "❌ Le mot de passe doit faire au moins 6 caractères.\n";
                break;
            }
            
            DB::beginTransaction();
            $createdCount = 0;
            
            foreach ($teachersWithoutAccount as $teacher) {
                // Générer un username basé sur le nom
                $baseUsername = strtolower(substr($teacher->first_name, 0, 1) . $teacher->last_name);
                $username = $baseUsername;
                $counter = 1;
                
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                // Email ou générer un email temporaire
                $email = $teacher->email ?: $username . '@cpb-douala.local';
                
                // Vérifier que l'email n'existe pas déjà
                if (User::where('email', $email)->exists()) {
                    echo "⚠️  Email déjà utilisé pour {$teacher->first_name} {$teacher->last_name}: {$email}\n";
                    continue;
                }
                
                $user = User::create([
                    'name' => $teacher->first_name . ' ' . $teacher->last_name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make($defaultPassword),
                    'role' => 'teacher',
                    'contact' => $teacher->phone_number,
                    'is_active' => $teacher->is_active
                ]);
                
                $teacher->update(['user_id' => $user->id]);
                
                echo "✅ Compte créé: {$username} pour {$teacher->first_name} {$teacher->last_name}\n";
                $createdCount++;
            }
            
            if ($createdCount > 0) {
                echo "\nConfirmer la création de {$createdCount} compte(s)? (yes/no): ";
                $handle = fopen("php://stdin", "r");
                $confirm = trim(fgets($handle));
                fclose($handle);
                
                if ($confirm === 'yes' || $confirm === 'y') {
                    DB::commit();
                    echo "✅ Comptes créés avec succès!\n";
                    echo "⚠️  N'oubliez pas de communiquer les identifiants aux enseignants.\n";
                } else {
                    DB::rollBack();
                    echo "❌ Création annulée.\n";
                }
            } else {
                DB::rollBack();
                echo "Aucun compte créé.\n";
            }
            break;
            
        case '3':
            echo "\n✅ Rapport affiché. Aucune modification effectuée.\n";
            break;
            
        case '4':
        default:
            echo "\n👋 Au revoir!\n";
            break;
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Script terminé.\n\n";