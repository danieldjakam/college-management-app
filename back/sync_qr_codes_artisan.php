<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:sync-qr-codes {--force : Force l\'exécution sans confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronise les codes QR manquants pour tous les utilisateurs (format STAFF_[ID])';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==========================================');
        $this->info('SYNCHRONISATION DES CODES QR');
        $this->info('==========================================');
        $this->newLine();

        try {
            // 1. Analyser les utilisateurs sans codes QR
            $this->info('1. Analyse des utilisateurs sans codes QR...');
            $this->line('--------------------------------------------');
            
            $usersWithoutQR = DB::table('users')
                ->select('id', 'name', 'role')
                ->whereNull('qr_code')
                ->orWhere('qr_code', '')
                ->orderBy('id')
                ->get();
            
            if ($usersWithoutQR->count() === 0) {
                $this->info('✅ Tous les utilisateurs ont déjà des codes QR.');
                $this->info('Aucune mise à jour nécessaire.');
                $this->newLine();
                
                // Afficher les statistiques
                $this->showStatistics();
                return 0;
            }
            
            $this->warn("Utilisateurs sans codes QR trouvés: " . $usersWithoutQR->count());
            $this->newLine();
            
            // Afficher les utilisateurs qui seront mis à jour
            $headers = ['ID', 'Nom', 'Rôle', 'Nouveau QR Code'];
            $rows = [];
            
            foreach ($usersWithoutQR as $user) {
                $rows[] = [
                    $user->id,
                    substr($user->name, 0, 30) . (strlen($user->name) > 30 ? '...' : ''),
                    $user->role,
                    "STAFF_{$user->id}"
                ];
            }
            
            $this->table($headers, array_slice($rows, 0, 10)); // Afficher les 10 premiers
            
            if (count($rows) > 10) {
                $this->line("... et " . (count($rows) - 10) . " autres utilisateurs");
            }
            
            $this->newLine();
            
            // 2. Confirmation
            if (!$this->option('force')) {
                $this->warn('⚠️  ATTENTION: Cette opération va modifier la base de données.');
                
                if (!$this->confirm('Voulez-vous continuer ?')) {
                    $this->error('❌ Opération annulée par l\'utilisateur.');
                    return 1;
                }
            }
            
            // 3. Mise à jour avec barre de progression
            $this->info('2. Mise à jour en cours...');
            $this->line('-------------------------');
            
            DB::beginTransaction();
            
            try {
                $bar = $this->output->createProgressBar($usersWithoutQR->count());
                $bar->start();
                
                $updated = DB::table('users')
                    ->whereNull('qr_code')
                    ->orWhere('qr_code', '')
                    ->update([
                        'qr_code' => DB::raw('CONCAT("STAFF_", id)'),
                        'updated_at' => now()
                    ]);
                
                $bar->advance($usersWithoutQR->count());
                $bar->finish();
                $this->newLine(2);
                
                DB::commit();
                
                $this->info("✅ Mise à jour réussie: {$updated} utilisateurs mis à jour");
                $this->newLine();
                
                // 4. Vérification
                $this->info('3. Vérification des résultats...');
                $this->line('--------------------------------');
                
                $verificationSample = DB::table('users')
                    ->select('id', 'name', 'qr_code')
                    ->whereIn('id', $usersWithoutQR->pluck('id')->take(5)->toArray())
                    ->get();
                
                $verificationHeaders = ['ID', 'Nom', 'Code QR'];
                $verificationRows = [];
                
                foreach ($verificationSample as $user) {
                    $verificationRows[] = [
                        $user->id,
                        substr($user->name, 0, 25) . (strlen($user->name) > 25 ? '...' : ''),
                        $user->qr_code
                    ];
                }
                
                $this->table($verificationHeaders, $verificationRows);
                
                // 5. Statistiques finales
                $this->info('4. Statistiques finales...');
                $this->line('--------------------------');
                $this->showStatistics();
                
                $finalUsersWithoutQR = DB::table('users')
                    ->whereNull('qr_code')
                    ->orWhere('qr_code', '')
                    ->count();
                
                if ($finalUsersWithoutQR === 0) {
                    $this->info('🎉 SUCCÈS: Tous les utilisateurs ont maintenant des codes QR!');
                }
                
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ ERREUR: ' . $e->getMessage());
            $this->error('La mise à jour a été annulée.');
            return 1;
        }
        
        $this->newLine();
        $this->info('==========================================');
        $this->info('SYNCHRONISATION TERMINÉE AVEC SUCCÈS');
        $this->info('==========================================');
        
        return 0;
    }
    
    /**
     * Afficher les statistiques des utilisateurs
     */
    private function showStatistics()
    {
        $totalUsers = DB::table('users')->count();
        $usersWithQR = DB::table('users')
            ->whereNotNull('qr_code')
            ->where('qr_code', '!=', '')
            ->count();
        $usersWithoutQR = $totalUsers - $usersWithQR;
        
        $this->line("Total utilisateurs: {$totalUsers}");
        $this->line("Avec codes QR: {$usersWithQR}");
        $this->line("Sans codes QR: {$usersWithoutQR}");
        $this->newLine();
    }
}