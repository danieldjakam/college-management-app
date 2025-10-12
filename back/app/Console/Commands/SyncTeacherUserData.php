<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Console\Command;

class SyncTeacherUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teachers:sync-data {--teacher_id= : Synchroniser un enseignant spécifique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les noms et QR codes entre les tables teachers et users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation des données enseignants...');

        if ($this->option('teacher_id')) {
            // Synchroniser un enseignant spécifique
            $this->syncTeacher($this->option('teacher_id'));
        } else {
            // Synchroniser tous les enseignants
            $teachers = Teacher::whereNotNull('user_id')->get();

            $bar = $this->output->createProgressBar($teachers->count());
            $bar->start();

            $synced = 0;
            foreach ($teachers as $teacher) {
                if ($this->syncTeacher($teacher->id, false)) {
                    $synced++;
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ {$synced} enseignant(s) synchronisé(s)");
        }

        return 0;
    }

    /**
     * Synchroniser un enseignant spécifique
     */
    private function syncTeacher($teacherId, $verbose = true)
    {
        $teacher = Teacher::find($teacherId);

        if (!$teacher) {
            if ($verbose) {
                $this->error("❌ Enseignant #{$teacherId} introuvable");
            }
            return false;
        }

        if (!$teacher->user_id) {
            if ($verbose) {
                $this->warn("⚠️ Enseignant #{$teacherId} n'a pas de compte utilisateur lié");
            }
            return false;
        }

        $user = User::find($teacher->user_id);

        if (!$user) {
            if ($verbose) {
                $this->error("❌ Utilisateur #{$teacher->user_id} introuvable");
            }
            return false;
        }

        // Construire le nom complet depuis teacher
        $teacherFullName = trim($teacher->first_name . ' ' . $teacher->last_name);

        $changes = [];

        // Vérifier si le nom doit être synchronisé
        if ($user->name !== $teacherFullName) {
            $changes['name'] = [
                'from' => $user->name,
                'to' => $teacherFullName
            ];
        }

        // Vérifier si le QR code doit être synchronisé
        if ($teacher->qr_code && $user->qr_code !== $teacher->qr_code) {
            $changes['qr_code'] = [
                'from' => $user->qr_code ?? 'VIDE',
                'to' => $teacher->qr_code
            ];
        }

        // Appliquer les changements
        if (!empty($changes)) {
            $updateData = [];

            if (isset($changes['name'])) {
                $updateData['name'] = $teacherFullName;
            }

            if (isset($changes['qr_code'])) {
                $updateData['qr_code'] = $teacher->qr_code;
            }

            $user->update($updateData);

            if ($verbose) {
                $this->info("✅ Enseignant #{$teacherId} ({$teacherFullName}):");
                foreach ($changes as $field => $change) {
                    $this->line("   {$field}: {$change['from']} → {$change['to']}");
                }
            }

            return true;
        } else {
            if ($verbose) {
                $this->info("✓ Enseignant #{$teacherId} ({$teacherFullName}) - Déjà synchronisé");
            }
            return false;
        }
    }
}
