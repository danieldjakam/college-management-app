<?php
/**
 * Script pour vérifier et réparer le compte admin
 * Usage: php check_admin.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "===========================================\n";
echo "VÉRIFICATION DU COMPTE ADMIN\n";
echo "===========================================\n\n";

// Rechercher le compte admin
$admin = User::where('username', 'admin')->first();

if (!$admin) {
    echo "❌ ERREUR: Aucun compte admin trouvé!\n";
    echo "Création d'un nouveau compte admin...\n";
    
    $admin = User::create([
        'name' => 'Administrateur',
        'username' => 'admin',
        'email' => 'admin@cpb-douala.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => 1
    ]);
    
    echo "✅ Compte admin créé avec succès!\n";
    echo "   Username: admin\n";
    echo "   Password: password123\n";
} else {
    echo "✅ Compte admin trouvé:\n";
    echo "   ID: {$admin->id}\n";
    echo "   Username: {$admin->username}\n";
    echo "   Email: {$admin->email}\n";
    echo "   Role: {$admin->role}\n";
    echo "   Actif: " . ($admin->is_active ? 'OUI' : 'NON') . "\n";
    echo "   Créé le: {$admin->created_at}\n\n";
    
    // Vérifier si le compte est actif
    if (!$admin->is_active) {
        echo "⚠️ Le compte est DÉSACTIVÉ. Activation...\n";
        $admin->is_active = 1;
        $admin->save();
        echo "✅ Compte activé!\n";
    }
    
    // Proposer de réinitialiser le mot de passe
    echo "\nVoulez-vous réinitialiser le mot de passe? (o/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) == 'o' || trim($line) == 'O') {
        $admin->password = Hash::make('password123');
        $admin->save();
        echo "✅ Mot de passe réinitialisé à: password123\n";
    }
}

// Tester la connexion
echo "\n===========================================\n";
echo "TEST DE CONNEXION\n";
echo "===========================================\n";

$credentials = [
    'username' => 'admin',
    'password' => 'password123'
];

if (Auth::attempt($credentials)) {
    echo "✅ Connexion réussie avec les identifiants!\n";
} else {
    echo "❌ ERREUR: Impossible de se connecter avec ces identifiants\n";
    echo "   Vérifiez la configuration JWT ou la base de données\n";
}

// Vérifier les autres comptes
echo "\n===========================================\n";
echo "AUTRES COMPTES UTILISATEURS\n";
echo "===========================================\n";

$users = User::where('role', '!=', 'admin')
    ->where('is_active', 1)
    ->limit(5)
    ->get(['id', 'username', 'role', 'is_active']);

foreach ($users as $user) {
    echo "   - {$user->username} ({$user->role}) - Actif: " . ($user->is_active ? 'OUI' : 'NON') . "\n";
}

echo "\n✅ Vérification terminée!\n";