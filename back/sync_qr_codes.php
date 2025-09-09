<?php
/**
 * Script de synchronisation des codes QR pour la production
 * Usage: php sync_qr_codes.php
 * 
 * Ce script met à jour tous les utilisateurs qui n'ont pas de code QR
 * en leur assignant le format STAFF_[ID]
 */

// Inclure l'autoloader de Laravel
require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "==========================================\n";
echo "SCRIPT DE SYNCHRONISATION DES CODES QR\n";
echo "==========================================\n\n";

try {
    // Vérifier la connexion à la base de données
    DB::connection()->getPdo();
    echo "✅ Connexion à la base de données établie\n\n";
    
    // 1. Lister les utilisateurs sans codes QR
    echo "1. Analyse des utilisateurs sans codes QR...\n";
    echo "--------------------------------------------\n";
    
    $usersWithoutQR = DB::table('users')
        ->select('id', 'name', 'role')
        ->whereNull('qr_code')
        ->orWhere('qr_code', '')
        ->orderBy('id')
        ->get();
    
    if ($usersWithoutQR->count() === 0) {
        echo "✅ Tous les utilisateurs ont déjà des codes QR.\n";
        echo "Aucune mise à jour nécessaire.\n\n";
        
        // Afficher les statistiques
        $totalUsers = DB::table('users')->count();
        $usersWithQR = DB::table('users')
            ->whereNotNull('qr_code')
            ->where('qr_code', '!=', '')
            ->count();
        
        echo "STATISTIQUES:\n";
        echo "=============\n";
        echo "Total utilisateurs: {$totalUsers}\n";
        echo "Avec codes QR: {$usersWithQR}\n";
        echo "Sans codes QR: 0\n\n";
        
        exit(0);
    }
    
    echo "Utilisateurs sans codes QR trouvés: " . $usersWithoutQR->count() . "\n\n";
    
    foreach ($usersWithoutQR as $user) {
        echo "• ID {$user->id}: {$user->name} ({$user->role}) -> STAFF_{$user->id}\n";
    }
    
    echo "\n2. Confirmation de mise à jour...\n";
    echo "----------------------------------\n";
    echo "⚠️  ATTENTION: Cette opération va modifier la base de données de production.\n";
    echo "Voulez-vous continuer ? (tapez 'OUI' en majuscules pour confirmer): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation !== 'OUI') {
        echo "❌ Opération annulée par l'utilisateur.\n";
        exit(1);
    }
    
    // 3. Effectuer la mise à jour
    echo "\n3. Mise à jour en cours...\n";
    echo "-------------------------\n";
    
    DB::beginTransaction();
    
    try {
        $updated = DB::table('users')
            ->whereNull('qr_code')
            ->orWhere('qr_code', '')
            ->update([
                'qr_code' => DB::raw('CONCAT("STAFF_", id)'),
                'updated_at' => now()
            ]);
        
        DB::commit();
        
        echo "✅ Mise à jour réussie: {$updated} utilisateurs mis à jour\n\n";
        
        // 4. Vérification post-mise à jour
        echo "4. Vérification des résultats...\n";
        echo "--------------------------------\n";
        
        $sampleUsers = DB::table('users')
            ->select('id', 'name', 'qr_code')
            ->whereIn('id', $usersWithoutQR->pluck('id')->take(5)->toArray())
            ->get();
        
        echo "Échantillon des utilisateurs mis à jour:\n";
        foreach ($sampleUsers as $user) {
            echo "✅ ID {$user->id}: {$user->name} -> {$user->qr_code}\n";
        }
        
        // Statistiques finales
        echo "\n5. Statistiques finales...\n";
        echo "--------------------------\n";
        
        $totalUsers = DB::table('users')->count();
        $usersWithQR = DB::table('users')
            ->whereNotNull('qr_code')
            ->where('qr_code', '!=', '')
            ->count();
        $usersWithoutQR = $totalUsers - $usersWithQR;
        
        echo "Total utilisateurs: {$totalUsers}\n";
        echo "Avec codes QR: {$usersWithQR}\n";
        echo "Sans codes QR: {$usersWithoutQR}\n\n";
        
        if ($usersWithoutQR === 0) {
            echo "🎉 SUCCÈS: Tous les utilisateurs ont maintenant des codes QR!\n";
        }
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "La mise à jour a été annulée.\n";
    exit(1);
}

echo "\n==========================================\n";
echo "SYNCHRONISATION TERMINÉE AVEC SUCCÈS\n";
echo "==========================================\n";