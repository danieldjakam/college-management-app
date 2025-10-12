<?php

namespace App\Console\Commands;

use App\Models\Sequence;
use Illuminate\Console\Command;

class UnlockCompositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compositions:unlock {--all : Déverrouiller toutes les compositions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Déverrouiller les compositions pour permettre aux enseignants de saisir les notes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔓 Déverrouillage des compositions...');

        if ($this->option('all')) {
            // Déverrouiller toutes les compositions
            $count = Sequence::where('is_composition', true)
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            $this->info("✅ {$count} composition(s) déverrouillée(s)");
        } else {
            // Déverrouiller seulement les compositions actives
            $count = Sequence::where('is_composition', true)
                ->where('is_active', true)
                ->where('is_locked', true)
                ->update(['is_locked' => false]);

            $this->info("✅ {$count} composition(s) active(s) déverrouillée(s)");
        }

        // Afficher les compositions déverrouillées
        $unlockedCompositions = Sequence::where('is_composition', true)
            ->where('is_locked', false)
            ->get(['id', 'name', 'is_active']);

        if ($unlockedCompositions->isNotEmpty()) {
            $this->newLine();
            $this->info('📋 Compositions déverrouillées:');

            foreach ($unlockedCompositions as $composition) {
                $status = $composition->is_active ? '✅ Active' : '⏸️ Inactive';
                $this->line("  - {$composition->name} [{$status}]");
            }
        }

        return 0;
    }
}
