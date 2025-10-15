<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanDuplicateEvaluations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluations:clean-duplicates {--dry-run : Afficher ce qui serait supprimé sans vraiment supprimer} {--force : Forcer la suppression sans confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les évaluations vides et les doublons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🧹 NETTOYAGE DES ÉVALUATIONS');
        $this->info('============================');
        $this->newLine();

        // 1. Trouver les évaluations vides
        $this->info('📋 Étape 1 : Identification des évaluations vides...');
        $emptyEvaluations = DB::table('evaluations')
            ->leftJoin('grades', 'evaluations.id', '=', 'grades.evaluation_id')
            ->whereNull('grades.id')
            ->select('evaluations.*')
            ->get();

        $this->line("   Trouvé : {$emptyEvaluations->count()} évaluations vides");
        
        if ($emptyEvaluations->count() > 0 && !$dryRun) {
            $this->newLine();
            $this->warn('   Exemples d\'évaluations vides à supprimer :');
            foreach ($emptyEvaluations->take(5) as $eval) {
                $this->line("   - ID {$eval->id}: {$eval->name}");
            }
        }

        // 2. Trouver les doublons
        $this->newLine();
        $this->info('📋 Étape 2 : Identification des doublons...');
        
        $duplicates = DB::select("
            SELECT 
                class_series_subject_id, 
                sequence_id, 
                teacher_id, 
                COUNT(*) as count,
                GROUP_CONCAT(id ORDER BY id) as eval_ids
            FROM evaluations
            GROUP BY class_series_subject_id, sequence_id, teacher_id
            HAVING COUNT(*) > 1
        ");

        $this->line("   Trouvé : " . count($duplicates) . " combinaisons en double");

        $evaluationsToDelete = collect();
        $evaluationsWithDataSkipped = collect();

        foreach ($duplicates as $dup) {
            $evalIds = explode(',', $dup->eval_ids);

            // Compter les notes pour chaque évaluation du groupe
            $evalGrades = [];
            foreach ($evalIds as $evalId) {
                $gradesCount = DB::table('grades')->where('evaluation_id', $evalId)->count();
                $evalGrades[$evalId] = $gradesCount;
            }

            // Trouver celle avec le plus de notes
            $bestEval = array_keys($evalGrades, max($evalGrades))[0];
            $maxGrades = $evalGrades[$bestEval];

            // SÉCURITÉ : Ne supprimer QUE les doublons VIDES (0 notes)
            foreach ($evalIds as $evalId) {
                if ($evalId != $bestEval) {
                    $gradesCount = $evalGrades[$evalId];

                    if ($gradesCount === 0) {
                        // Sûr de supprimer : aucune note
                        $evaluationsToDelete->push($evalId);
                    } else {
                        // ⚠️ DANGER : Cette évaluation a des notes - NE PAS SUPPRIMER
                        $evaluationsWithDataSkipped->push([
                            'id' => $evalId,
                            'grades_count' => $gradesCount
                        ]);

                        if (!$dryRun) {
                            $this->warn("   ⚠️  IGNORÉ: Éval ID {$evalId} ({$gradesCount} notes) - CONSERVÉE pour éviter perte de données");
                        }
                    }
                }
            }

            if (!$dryRun) {
                $this->line("   ✓ Garder évaluation ID {$bestEval} ({$maxGrades} notes)");
            }
        }

        // Total à supprimer
        $totalToDelete = $emptyEvaluations->count() + $evaluationsToDelete->count();

        $this->newLine();
        $this->info('📊 RÉSUMÉ:');
        $this->line("   Évaluations vides : {$emptyEvaluations->count()}");
        $this->line("   Doublons vides à supprimer : {$evaluationsToDelete->count()}");
        $this->line("   TOTAL À SUPPRIMER : {$totalToDelete}");

        if ($evaluationsWithDataSkipped->count() > 0) {
            $this->newLine();
            $this->warn("   ⚠️  ÉVALUATIONS DUPLIQUÉES CONSERVÉES (contiennent des notes) : {$evaluationsWithDataSkipped->count()}");
            $totalNotesPreserved = $evaluationsWithDataSkipped->sum('grades_count');
            $this->warn("   📝 Total de notes préservées : {$totalNotesPreserved}");
            $this->newLine();
            $this->comment("   💡 Ces doublons contiennent des notes et ne seront PAS supprimés automatiquement.");
            $this->comment("   💡 Vous devrez les fusionner manuellement si nécessaire.");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  MODE DRY-RUN : Aucune suppression effectuée');
            $this->info('Exécutez sans --dry-run pour supprimer réellement');
            return Command::SUCCESS;
        }

        // Confirmation
        if (!$force) {
            $this->newLine();
            if (!$this->confirm("Voulez-vous vraiment supprimer {$totalToDelete} évaluations ?", false)) {
                $this->info('❌ Opération annulée');
                return Command::SUCCESS;
            }
        }

        // Suppression
        $this->newLine();
        $this->info('🗑️  Suppression en cours...');

        $deletedCount = 0;

        // Supprimer les évaluations vides
        foreach ($emptyEvaluations as $eval) {
            DB::table('evaluations')->where('id', $eval->id)->delete();
            $deletedCount++;
        }

        // Supprimer les doublons
        foreach ($evaluationsToDelete as $evalId) {
            DB::table('evaluations')->where('id', $evalId)->delete();
            $deletedCount++;
        }

        $this->newLine();
        $this->info("✅ {$deletedCount} évaluations supprimées avec succès !");

        if ($evaluationsWithDataSkipped->count() > 0) {
            $totalNotesPreserved = $evaluationsWithDataSkipped->sum('grades_count');
            $this->newLine();
            $this->warn("⚠️  {$evaluationsWithDataSkipped->count()} doublons avec notes ont été CONSERVÉS ({$totalNotesPreserved} notes préservées)");
        }

        // Log
        Log::info('Nettoyage des évaluations effectué', [
            'deleted_count' => $deletedCount,
            'empty_count' => $emptyEvaluations->count(),
            'empty_duplicates_count' => $evaluationsToDelete->count(),
            'skipped_with_data_count' => $evaluationsWithDataSkipped->count(),
            'notes_preserved' => $evaluationsWithDataSkipped->sum('grades_count')
        ]);

        return Command::SUCCESS;
    }
}
