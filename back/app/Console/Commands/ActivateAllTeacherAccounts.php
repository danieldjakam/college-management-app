<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ActivateAllTeacherAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:activate-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activer et créer des comptes utilisateurs pour tous les enseignants (vacataires, semi-permanents, permanents) avec mot de passe par défaut';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎓 Activation des comptes enseignants...');
        $this->info('========================================');

        $defaultPassword = 'password123';
        $teachers = Teacher::all();

        if ($teachers->isEmpty()) {
            $this->warn('Aucun enseignant trouvé dans la base de données.');
            return;
        }

        $this->info("Total d'enseignants: " . $teachers->count());
        $this->newLine();

        $created = 0;
        $updated = 0;
        $activated = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($teachers->count());
        $progressBar->start();

        foreach ($teachers as $teacher) {
            try {
                // Si l'enseignant a déjà un compte utilisateur
                if ($teacher->user_id && $teacher->user) {
                    $user = $teacher->user;

                    // Activer le compte s'il est inactif
                    if (!$user->is_active) {
                        $user->update(['is_active' => true]);
                        $activated++;
                    }

                    // Réinitialiser le mot de passe au mot de passe par défaut
                    $user->update(['password' => Hash::make($defaultPassword)]);
                    $updated++;

                } else {
                    // Créer un nouveau compte utilisateur
                    $username = $this->generateUsername($teacher);

                    $user = User::create([
                        'name' => $teacher->full_name,
                        'username' => $username,
                        'email' => $teacher->email ?? "{$username}@cpb-douala.com",
                        'password' => Hash::make($defaultPassword),
                        'role' => 'teacher',
                        'is_active' => true,
                        'staff_identifier' => $teacher->staff_identifier
                    ]);

                    // Lier l'utilisateur à l'enseignant
                    $teacher->update(['user_id' => $user->id]);
                    $created++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("\nErreur pour {$teacher->full_name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher les résultats
        $this->info('========================================');
        $this->info('✅ RÉSUMÉ');
        $this->info('========================================');
        $this->info("✨ Comptes créés: {$created}");
        $this->info("🔄 Mots de passe réinitialisés: {$updated}");
        $this->info("🔓 Comptes activés: {$activated}");

        if ($errors > 0) {
            $this->warn("⚠️  Erreurs: {$errors}");
        }

        $this->newLine();
        $this->info('🔑 Mot de passe par défaut pour tous: password123');
        $this->info('========================================');
    }

    /**
     * Générer un nom d'utilisateur unique
     */
    protected function generateUsername($teacher)
    {
        $firstName = $this->removeAccents(strtolower($teacher->first_name));
        $lastName = $this->removeAccents(strtolower($teacher->last_name));
        $baseUsername = "{$firstName}.{$lastName}";

        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = "{$baseUsername}{$counter}";
            $counter++;
        }

        return $username;
    }

    /**
     * Supprimer les accents
     */
    protected function removeAccents($string)
    {
        $unwanted_array = [
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
            'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U',
            'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
            'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
        ];
        return strtr($string, $unwanted_array);
    }
}
