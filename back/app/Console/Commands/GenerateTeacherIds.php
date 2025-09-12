<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher;

class GenerateTeacherIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:generate-ids {--force : Regenerate IDs even for teachers who already have one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate unique teacher IDs for all teachers without one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        // Get teachers without teacher_id or all if force is used
        $query = Teacher::query();
        if (!$force) {
            $query->whereNull('teacher_id');
        }
        
        $teachers = $query->orderBy('id')->get();
        
        if ($teachers->isEmpty()) {
            $this->info('Aucun enseignant trouvé sans ID.');
            return;
        }
        
        $this->info("Génération d'IDs pour " . $teachers->count() . " enseignant(s)...");
        
        $bar = $this->output->createProgressBar($teachers->count());
        $bar->start();
        
        $counter = 1;
        $updated = 0;
        
        foreach ($teachers as $teacher) {
            // Générer un ID unique
            do {
                $teacherId = 'TCH_' . str_pad($counter, 3, '0', STR_PAD_LEFT);
                $counter++;
            } while (Teacher::where('teacher_id', $teacherId)->exists());
            
            // Mettre à jour l'enseignant
            $teacher->update(['teacher_id' => $teacherId]);
            $updated++;
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✅ {$updated} enseignant(s) mis à jour avec succès !");
        
        // Afficher quelques exemples
        $examples = Teacher::whereNotNull('teacher_id')
            ->latest('updated_at')
            ->take(5)
            ->get(['teacher_id', 'first_name', 'last_name']);
            
        if ($examples->isNotEmpty()) {
            $this->newLine();
            $this->info("Exemples d'IDs générés :");
            foreach ($examples as $example) {
                $this->line("  {$example->teacher_id} → {$example->first_name} {$example->last_name}");
            }
        }
    }
}
