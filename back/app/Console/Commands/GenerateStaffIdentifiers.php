<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GenerateStaffIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staff:generate-identifiers {--force : Force la régénération pour tous les utilisateurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Générer les identifiants personnel (STAF_ID ou TCH_ID) pour tous les utilisateurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Génération des identifiants personnel...');

        $forceRegenerate = $this->option('force');
        
        $query = User::where('is_active', true);
        
        if (!$forceRegenerate) {
            $query->whereNull('staff_identifier');
        }
        
        $users = $query->get();
        
        if ($users->isEmpty()) {
            $this->info('Aucun utilisateur nécessite la génération d\'un identifiant.');
            return 0;
        }

        $this->info("Traitement de {$users->count()} utilisateur(s)...");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $generated = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                $prefix = $user->role === 'teacher' ? 'TCH_' : 'STAF_';
                $identifier = $prefix . $user->id;
                
                $user->update(['staff_identifier' => $identifier]);
                $generated++;
                
                $this->info("\n✅ {$user->name} → {$identifier}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("\n❌ Erreur pour {$user->name}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("✅ Génération terminée!");
        $this->info("• Identifiants générés: {$generated}");
        if ($errors > 0) {
            $this->error("• Erreurs: {$errors}");
        }

        return 0;
    }
}
