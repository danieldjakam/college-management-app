<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTeacherPhoneNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:sync-phone-numbers {--dry-run : Afficher les modifications sans les appliquer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les numéros de téléphone des enseignants vers le champ contact des users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 MODE DRY-RUN : Aucune modification ne sera appliquée');
            $this->newLine();
        }

        $this->info('🔄 Synchronisation des numéros de téléphone des enseignants...');
        $this->newLine();

        // Récupérer tous les enseignants avec leur utilisateur
        $teachers = Teacher::with('user')
            ->whereNotNull('user_id')
            ->whereNotNull('phone_number')
            ->get();

        $totalTeachers = $teachers->count();
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->withProgressBar($teachers, function ($teacher) use (&$updatedCount, &$skippedCount, &$errorCount, $isDryRun) {
            if (!$teacher->user) {
                $skippedCount++;
                return;
            }

            $teacherPhone = $this->normalizePhoneNumber($teacher->phone_number);
            $userContact = $this->normalizePhoneNumber($teacher->user->contact ?? '');

            // Si les numéros sont déjà identiques (après normalisation), skip
            if ($teacherPhone === $userContact && !empty($userContact)) {
                $skippedCount++;
                return;
            }

            try {
                if (!$isDryRun) {
                    // Mettre à jour le contact de l'utilisateur
                    $teacher->user->update([
                        'contact' => $teacher->phone_number
                    ]);
                }

                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\n❌ Erreur pour {$teacher->first_name} {$teacher->last_name}: " . $e->getMessage());
            }
        });

        $this->newLine(2);

        // Afficher un résumé détaillé
        $this->info('📊 RÉSUMÉ DE LA SYNCHRONISATION');
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['Total enseignants', $totalTeachers],
                ['✅ Mis à jour', $updatedCount],
                ['⏭️  Ignorés (déjà à jour)', $skippedCount],
                ['❌ Erreurs', $errorCount],
            ]
        );

        if ($isDryRun && $updatedCount > 0) {
            $this->newLine();
            $this->warn("⚠️  Mode dry-run : Les {$updatedCount} modifications n'ont PAS été appliquées.");
            $this->info("💡 Exécutez sans l'option --dry-run pour appliquer les changements.");
        } elseif ($updatedCount > 0) {
            $this->newLine();
            $this->info("✅ {$updatedCount} numéros de téléphone synchronisés avec succès !");
        } else {
            $this->newLine();
            $this->info('✅ Tous les numéros de téléphone sont déjà synchronisés.');
        }

        return Command::SUCCESS;
    }

    /**
     * Normaliser un numéro de téléphone pour la comparaison
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Supprimer tous les espaces, tirets, parenthèses, points
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // Supprimer le préfixe +237 ou 237 pour la comparaison
        $phone = preg_replace('/^\+?237/', '', $phone);

        return $phone;
    }
}
