<?php

require_once __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Hash;

// Configuration de la base de données
$capsule = new DB;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== Script de mise à jour des mots de passe - Bibliothécaires ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Nouveau mot de passe à attribuer
    $newPassword = 'Password123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    echo "Nouveau mot de passe: $newPassword\n";
    echo "Hash généré: $hashedPassword\n\n";

    // Rechercher tous les utilisateurs avec le rôle 'Bibliothécaire'
    echo "Recherche des utilisateurs avec le rôle 'Bibliothécaire'...\n";

    $bibliothecaires = DB::table('users')
        ->where('role', 'Bibliothécaire')
        ->where('is_active', 1)
        ->get(['id', 'name', 'username', 'email', 'role']);

    if ($bibliothecaires->isEmpty()) {
        echo "❌ Aucun bibliothécaire trouvé dans la base de données.\n";
        exit(1);
    }

    echo "✅ " . count($bibliothecaires) . " bibliothécaire(s) trouvé(s):\n\n";

    // Afficher la liste des bibliothécaires
    foreach ($bibliothecaires as $index => $bibliothecaire) {
        echo ($index + 1) . ". ID: {$bibliothecaire->id}\n";
        echo "   Nom: {$bibliothecaire->name}\n";
        echo "   Username: {$bibliothecaire->username}\n";
        echo "   Email: {$bibliothecaire->email}\n";
        echo "   Rôle: {$bibliothecaire->role}\n\n";
    }

    // Demander confirmation
    echo "⚠️  ATTENTION: Cette opération va modifier le mot de passe de " . count($bibliothecaires) . " utilisateur(s).\n";
    echo "Voulez-vous continuer? (oui/non): ";

    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);

    if (strtolower($confirmation) !== 'oui') {
        echo "❌ Opération annulée par l'utilisateur.\n";
        exit(0);
    }

    echo "\n🔄 Mise à jour des mots de passe en cours...\n\n";

    $successCount = 0;
    $errorCount = 0;

    // Commencer une transaction
    DB::beginTransaction();

    foreach ($bibliothecaires as $bibliothecaire) {
        try {
            $updated = DB::table('users')
                ->where('id', $bibliothecaire->id)
                ->where('role', 'Bibliothécaire')
                ->update([
                    'password' => $hashedPassword,
                    'updated_at' => now()
                ]);

            if ($updated) {
                echo "✅ Mot de passe mis à jour pour: {$bibliothecaire->name} (ID: {$bibliothecaire->id})\n";
                $successCount++;
            } else {
                echo "❌ Échec de la mise à jour pour: {$bibliothecaire->name} (ID: {$bibliothecaire->id})\n";
                $errorCount++;
            }

        } catch (Exception $e) {
            echo "❌ Erreur lors de la mise à jour pour {$bibliothecaire->name}: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }

    // Confirmer ou annuler la transaction
    if ($errorCount === 0) {
        DB::commit();
        echo "\n✅ Transaction confirmée. Tous les mots de passe ont été mis à jour avec succès.\n";
    } else {
        DB::rollback();
        echo "\n❌ Transaction annulée en raison d'erreurs. Aucune modification n'a été appliquée.\n";
    }

    // Résumé final
    echo "\n=== RÉSUMÉ ===\n";
    echo "Utilisateurs traités: " . count($bibliothecaires) . "\n";
    echo "Mises à jour réussies: $successCount\n";
    echo "Erreurs: $errorCount\n";
    echo "Nouveau mot de passe: $newPassword\n";

    if ($successCount > 0 && $errorCount === 0) {
        echo "\n🎉 Opération terminée avec succès!\n";
        echo "Tous les bibliothécaires peuvent maintenant se connecter avec le mot de passe: $newPassword\n";
    }

} catch (Exception $e) {
    DB::rollback();
    echo "❌ Erreur critique: " . $e->getMessage() . "\n";
    echo "Aucune modification n'a été appliquée.\n";
    exit(1);
}

// Fonction helper pour now() si elle n'existe pas
if (!function_exists('now')) {
    function now() {
        return date('Y-m-d H:i:s');
    }
}

echo "\nScript terminé à " . date('Y-m-d H:i:s') . "\n";

?>