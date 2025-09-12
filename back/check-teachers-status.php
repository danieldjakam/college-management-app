<?php

/**
 * Script pour vérifier le statut actuel des enseignants dans la base de données
 * Affiche les enseignants dans users et dans teachers pour analyse
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Teacher;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n===========================================\n";
echo "  VÉRIFICATION DU STATUT DES ENSEIGNANTS\n";
echo "===========================================\n\n";

try {
    // 1. Enseignants dans la table users
    echo "📋 ENSEIGNANTS DANS LA TABLE 'users':\n";
    echo "=====================================\n\n";
    
    $teacherUsers = User::where('role', 'teacher')->get();
    
    if ($teacherUsers->count() > 0) {
        echo "Nombre total: " . $teacherUsers->count() . "\n\n";
        
        foreach ($teacherUsers as $user) {
            echo "ID: {$user->id}\n";
            echo "Nom: {$user->name}\n";
            echo "Email: {$user->email}\n";
            echo "Contact: " . ($user->contact ?? 'Non défini') . "\n";
            echo "Actif: " . ($user->is_active ? 'Oui' : 'Non') . "\n";
            echo "Créé le: " . $user->created_at . "\n";
            echo "-----------------------------------\n";
        }
    } else {
        echo "❌ Aucun enseignant trouvé dans la table users.\n\n";
    }
    
    // 2. Enseignants dans la table teachers
    echo "\n📋 ENSEIGNANTS DANS LA TABLE 'teachers':\n";
    echo "========================================\n\n";
    
    $teachers = Teacher::all();
    
    if ($teachers->count() > 0) {
        echo "Nombre total: " . $teachers->count() . "\n\n";
        
        foreach ($teachers as $teacher) {
            echo "ID: {$teacher->id}\n";
            echo "Teacher ID: {$teacher->teacher_id}\n";
            echo "Nom: {$teacher->first_name} {$teacher->last_name}\n";
            echo "Email: {$teacher->email}\n";
            echo "Téléphone: " . ($teacher->phone_number ?? 'Non défini') . "\n";
            echo "Actif: " . ($teacher->is_active ? 'Oui' : 'Non') . "\n";
            echo "Date d'embauche: " . $teacher->date_joined . "\n";
            echo "-----------------------------------\n";
        }
    } else {
        echo "❌ Aucun enseignant trouvé dans la table teachers.\n\n";
    }
    
    // 3. Analyse des doublons potentiels
    echo "\n📊 ANALYSE DES DOUBLONS:\n";
    echo "========================\n\n";
    
    $duplicates = 0;
    foreach ($teacherUsers as $user) {
        $existingTeacher = Teacher::where('email', $user->email)->first();
        if ($existingTeacher) {
            $duplicates++;
            echo "⚠️  Doublon détecté: {$user->email}\n";
            echo "   - Dans users (ID: {$user->id})\n";
            echo "   - Dans teachers (ID: {$existingTeacher->id})\n\n";
        }
    }
    
    if ($duplicates === 0) {
        echo "✅ Aucun doublon détecté.\n";
    } else {
        echo "Total de doublons: {$duplicates}\n";
    }
    
    // 4. Résumé
    echo "\n===========================================\n";
    echo "📊 RÉSUMÉ\n";
    echo "===========================================\n";
    echo "Enseignants dans 'users': " . $teacherUsers->count() . "\n";
    echo "Enseignants dans 'teachers': " . $teachers->count() . "\n";
    echo "Doublons détectés: {$duplicates}\n";
    echo "Enseignants à migrer: " . ($teacherUsers->count() - $duplicates) . "\n";
    
    // 5. Vérifier les rôles disponibles
    echo "\n📋 RÔLES DISPONIBLES DANS 'users':\n";
    echo "===================================\n";
    $roles = User::select('role', DB::raw('count(*) as count'))
        ->groupBy('role')
        ->get();
    
    foreach ($roles as $role) {
        echo "- {$role->role}: {$role->count} utilisateur(s)\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Vérification terminée.\n\n";