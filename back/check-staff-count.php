<?php

/**
 * Script pour vérifier le nombre réel de personnel en base de données
 * 
 * Ce script va:
 * 1. Compter les utilisateurs dans la table users
 * 2. Compter les enseignants dans la table teachers
 * 3. Analyser les doublons (enseignants qui ont un compte utilisateur)
 * 4. Donner le nombre réel de personnes uniques
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Teacher;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n===============================================\n";
echo "  VÉRIFICATION DU NOMBRE DE PERSONNEL EN BD\n";
echo "===============================================\n\n";

try {
    
    // 1. Analyser la table USERS
    echo "📊 ANALYSE DE LA TABLE 'users':\n";
    echo "===============================\n\n";
    
    $totalUsers = User::count();
    echo "Total utilisateurs: {$totalUsers}\n\n";
    
    // Compter par rôle
    $usersByRole = User::select('role', DB::raw('count(*) as count'))
        ->groupBy('role')
        ->get();
    
    echo "Répartition par rôle:\n";
    $adminCount = 0;
    $teacherUsersCount = 0;
    foreach ($usersByRole as $roleData) {
        echo "- {$roleData->role}: {$roleData->count}\n";
        
        if (in_array($roleData->role, ['admin', 'secretaire', 'comptable_superieur', 'surveillant_general', 'surveillant_secteur', 'accountant', 'comptable'])) {
            $adminCount += $roleData->count;
        }
        
        if ($roleData->role === 'teacher') {
            $teacherUsersCount = $roleData->count;
        }
    }
    
    echo "\n📊 Résumé users:\n";
    echo "- Personnel administratif (avec compte): {$adminCount}\n";
    echo "- Enseignants (avec compte): {$teacherUsersCount}\n";
    echo "- Total users: {$totalUsers}\n";
    
    // 2. Analyser la table TEACHERS
    echo "\n\n📚 ANALYSE DE LA TABLE 'teachers':\n";
    echo "==================================\n\n";
    
    $totalTeachers = Teacher::count();
    echo "Total enseignants: {$totalTeachers}\n\n";
    
    // Compter par type
    $teachersByType = Teacher::select('type_personnel', DB::raw('count(*) as count'))
        ->groupBy('type_personnel')
        ->get();
    
    echo "Répartition par type:\n";
    foreach ($teachersByType as $typeData) {
        $typeLabel = match($typeData->type_personnel) {
            'P' => 'Permanent',
            'S' => 'Semi-permanent', 
            'V' => 'Vacataire',
            null => 'Non défini',
            default => $typeData->type_personnel
        };
        echo "- {$typeLabel} ({$typeData->type_personnel}): {$typeData->count}\n";
    }
    
    // Analyser les comptes utilisateurs
    $teachersWithAccount = Teacher::whereNotNull('user_id')->count();
    $teachersWithoutAccount = Teacher::whereNull('user_id')->count();
    
    echo "\nComptes utilisateurs:\n";
    echo "- Avec compte utilisateur: {$teachersWithAccount}\n";
    echo "- Sans compte utilisateur: {$teachersWithoutAccount}\n";
    
    // 3. Analyser les doublons
    echo "\n\n🔍 ANALYSE DES DOUBLONS:\n";
    echo "========================\n\n";
    
    // Enseignants qui ont un compte utilisateur
    $teachersWithUserAccount = Teacher::whereNotNull('user_id')
        ->with('user')
        ->get();
    
    echo "Enseignants avec compte utilisateur:\n";
    $validLinks = 0;
    foreach ($teachersWithUserAccount as $teacher) {
        if ($teacher->user) {
            $validLinks++;
            echo "- {$teacher->first_name} {$teacher->last_name} → User: {$teacher->user->name} (role: {$teacher->user->role})\n";
        } else {
            echo "- {$teacher->first_name} {$teacher->last_name} → ❌ User ID {$teacher->user_id} introuvable\n";
        }
    }
    echo "\nLiens valides: {$validLinks}/{$teachersWithAccount}\n";
    
    // Vérifier les emails en doublon
    echo "\nVérification doublons par email:\n";
    $teacherEmails = Teacher::whereNotNull('email')->pluck('email')->toArray();
    $userEmails = User::whereNotNull('email')->pluck('email')->toArray();
    $emailDuplicates = array_intersect($teacherEmails, $userEmails);
    
    echo "- Emails en doublon: " . count($emailDuplicates) . "\n";
    if (count($emailDuplicates) > 0) {
        foreach ($emailDuplicates as $email) {
            echo "  * {$email}\n";
        }
    }
    
    // 4. CALCUL FINAL
    echo "\n\n🎯 CALCUL FINAL DU PERSONNEL UNIQUE:\n";
    echo "====================================\n\n";
    
    // Personnel unique = Tous les users + enseignants sans compte
    $uniqueStaffCount = $totalUsers + $teachersWithoutAccount;
    
    echo "Méthode de calcul:\n";
    echo "- Tous les utilisateurs (users): {$totalUsers}\n";
    echo "- Enseignants sans compte: {$teachersWithoutAccount}\n";
    echo "- TOTAL PERSONNEL UNIQUE: {$uniqueStaffCount}\n\n";
    
    // Vérification alternative
    $alternativeCount = $adminCount + $teacherUsersCount + $teachersWithoutAccount;
    echo "Vérification alternative:\n";
    echo "- Personnel admin avec compte: {$adminCount}\n";
    echo "- Enseignants avec compte: {$teacherUsersCount}\n";
    echo "- Enseignants sans compte: {$teachersWithoutAccount}\n";
    echo "- TOTAL ALTERNATIF: {$alternativeCount}\n\n";
    
    // Diagnostic des écarts
    if ($uniqueStaffCount != 203) {
        echo "⚠️ ÉCART DÉTECTÉ !\n";
        echo "- Attendu: 203 personnes\n";
        echo "- Trouvé: {$uniqueStaffCount} personnes\n";
        echo "- Différence: " . (203 - $uniqueStaffCount) . "\n\n";
        
        if ($uniqueStaffCount < 203) {
            echo "Causes possibles:\n";
            echo "- Moins d'utilisateurs créés que prévu\n";
            echo "- Certains enseignants supprimés\n";
            echo "- Estimation initiale incorrecte\n";
        } else {
            echo "Causes possibles:\n";
            echo "- Plus d'utilisateurs créés depuis l'estimation\n";
            echo "- Doublons non détectés\n";
        }
    } else {
        echo "✅ PARFAIT ! Le compte correspond exactement aux 203 personnes attendues.\n";
    }
    
    // 5. RECOMMANDATIONS
    echo "\n📝 RECOMMANDATIONS POUR LE GÉNÉRATEUR DE CARTES:\n";
    echo "=================================================\n\n";
    
    echo "Pour charger TOUT le personnel ({$uniqueStaffCount} personnes):\n";
    echo "1. Charger TOUS les users: {$totalUsers} personnes\n";
    echo "2. Charger les teachers sans compte: {$teachersWithoutAccount} personnes\n";
    echo "3. Total: {$uniqueStaffCount} cartes à générer\n\n";
    
    echo "Requêtes SQL équivalentes:\n";
    echo "- SELECT * FROM users; -- {$totalUsers} résultats\n";
    echo "- SELECT * FROM teachers WHERE user_id IS NULL; -- {$teachersWithoutAccount} résultats\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Analyse terminée.\n\n";