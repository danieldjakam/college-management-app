<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Console\Command;

class SyncUserTeacherId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-teacher-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser le champ teacher_id dans la table users basé sur la relation teacher.user_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation des teacher_id dans la table users...');

        // Récupérer tous les enseignants qui ont un user_id
        $teachers = Teacher::whereNotNull('user_id')->get();

        $bar = $this->output->createProgressBar($teachers->count());
        $bar->start();

        $synced = 0;
        $errors = 0;

        foreach ($teachers as $teacher) {
            try {
                $user = User::find($teacher->user_id);

                if ($user) {
                    // Vérifier si le teacher_id est différent
                    if ($user->teacher_id !== $teacher->id) {
                        $user->update(['teacher_id' => $teacher->id]);
                        $synced++;
                    }
                } else {
                    $this->newLine();
                    $this->warn("⚠️ User #{$teacher->user_id} introuvable pour teacher #{$teacher->id}");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Erreur pour teacher #{$teacher->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ {$synced} teacher_id synchronisé(s)");

        if ($errors > 0) {
            $this->warn("⚠️ {$errors} erreur(s) rencontrée(s)");
        }

        return 0;
    }
}
