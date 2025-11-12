<?php

/**
 * Script de correction des usernames avec espaces
 * À exécuter en production pour corriger tous les usernames qui contiennent des espaces
 *
 * Usage:
 *   php fix_usernames_production.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "==============================================\n";
echo "  CORRECTION DES USERNAMES AVEC ESPACES\n";
echo "==============================================\n\n";

// Récupérer tous les utilisateurs avec des espaces dans le username
$usersWithSpaces = User::whereRaw("username LIKE '% %'")->get();

if ($usersWithSpaces->isEmpty()) {
    echo "✅ Aucun username avec espace trouvé. Base de données OK!\n";
    exit(0);
}

echo "Trouvé " . $usersWithSpaces->count() . " utilisateur(s) avec des espaces:\n\n";

$corrected = 0;
$errors = 0;

foreach ($usersWithSpaces as $user) {
    $oldUsername = $user->username;
    $newUsername = str_replace(' ', '', $oldUsername); // Enlever tous les espaces

    // Vérifier si le nouveau username existe déjà
    $exists = User::where('username', $newUsername)->where('id', '!=', $user->id)->exists();

    if ($exists) {
        echo "❌ ERREUR: '{$oldUsername}' -> '{$newUsername}' (déjà utilisé)\n";
        $errors++;
        continue;
    }

    try {
        $user->username = $newUsername;
        $user->save();
        echo "✅ '{$oldUsername}' -> '{$newUsername}' (ID: {$user->id}, {$user->name})\n";
        $corrected++;
    } catch (\Exception $e) {
        echo "❌ ERREUR: '{$oldUsername}' - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n==============================================\n";
echo "  RÉSUMÉ\n";
echo "==============================================\n";
echo "✅ Corrigés:   {$corrected}\n";
echo "❌ Erreurs:    {$errors}\n";
echo "📊 Total:      " . ($corrected + $errors) . "\n";
echo "==============================================\n";

if ($errors > 0) {
    echo "\n⚠️  ATTENTION: Des erreurs se sont produites. Vérifiez les logs ci-dessus.\n";
    exit(1);
}

echo "\n🎉 Tous les usernames ont été corrigés avec succès!\n";
exit(0);
