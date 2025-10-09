<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixTeacherUserMismatch extends Command
{
    protected $signature = 'fix:teacher-user-mismatch {teacher_id?} {--all}';
    protected $description = 'Corriger les incohérences entre les teachers et leurs users liés';

    public function handle()
    {
        $this->info('🔧 === CORRECTION DES INCOHÉRENCES TEACHER-USER ===');
        $this->line('');

        if ($this->option('all')) {
            return $this->fixAllMismatches();
        }

        $teacherId = $this->argument('teacher_id');

        if (!$teacherId) {
            // Si aucun ID n'est fourni, on cherche toutes les incohérences
            return $this->detectAndFixMismatches();
        }

        return $this->fixSpecificTeacher($teacherId);
    }

    protected function fixSpecificTeacher($teacherId)
    {
        $teacher = Teacher::find($teacherId);

        if (!$teacher) {
            $this->error("❌ Teacher ID {$teacherId} introuvable");
            return 1;
        }

        $this->info("👨‍🏫 Teacher trouvé:");
        $this->line("   ID: {$teacher->id}");
        $this->line("   Nom: {$teacher->first_name} {$teacher->last_name}");
        $this->line("   QR Code: {$teacher->qr_code}");
        $this->line("   User ID lié: {$teacher->user_id}");
        $this->line('');

        if (!$teacher->user_id) {
            $this->warn("⚠️  Ce teacher n'a pas de user lié");
            return 0;
        }

        $user = User::find($teacher->user_id);

        if (!$user) {
            $this->error("❌ User ID {$teacher->user_id} introuvable");
            return 1;
        }

        $this->info("👤 User lié:");
        $this->line("   ID: {$user->id}");
        $this->line("   Nom: {$user->name}");
        $this->line("   QR Code: {$user->qr_code}");
        $this->line('');

        // Vérifier si les noms correspondent
        $teacherFullName = trim("{$teacher->first_name} {$teacher->last_name}");
        $userName = trim($user->name);

        if ($teacherFullName === $userName && $teacher->qr_code === $user->qr_code) {
            $this->info("✅ Aucune incohérence détectée");
            return 0;
        }

        $this->warn("❌ INCOHÉRENCE DÉTECTÉE:");
        $this->line("   Teacher dit: {$teacherFullName}");
        $this->line("   User dit: {$userName}");
        $this->line('');

        if (!$this->confirm("Voulez-vous corriger le User pour qu'il corresponde au Teacher ?", true)) {
            $this->info("Operation annulée");
            return 0;
        }

        try {
            DB::beginTransaction();

            $oldName = $user->name;
            $oldQR = $user->qr_code;

            $user->name = $teacherFullName;
            $user->qr_code = $teacher->qr_code;
            $user->save();

            DB::commit();

            $this->info("✅ Correction effectuée avec succès!");
            $this->line('');
            $this->info("📝 Modifications:");
            $this->line("   Nom: {$oldName} → {$teacherFullName}");
            $this->line("   QR Code: {$oldQR} → {$teacher->qr_code}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }

    protected function detectAndFixMismatches()
    {
        $this->info("🔍 Recherche des incohérences...");
        $this->line('');

        $teachers = Teacher::with('user')->whereNotNull('user_id')->get();
        $mismatches = [];

        foreach ($teachers as $teacher) {
            if (!$teacher->user) {
                continue;
            }

            $teacherFullName = trim("{$teacher->first_name} {$teacher->last_name}");
            $userName = trim($teacher->user->name);

            if ($teacherFullName !== $userName || $teacher->qr_code !== $teacher->user->qr_code) {
                $mismatches[] = $teacher;
            }
        }

        if (empty($mismatches)) {
            $this->info("✅ Aucune incohérence trouvée!");
            return 0;
        }

        $this->warn("❌ " . count($mismatches) . " incohérence(s) trouvée(s):");
        $this->line('');

        $this->table(
            ['Teacher ID', 'Teacher Name', 'Teacher QR', 'User Name', 'User QR'],
            array_map(function($teacher) {
                $teacherFullName = trim("{$teacher->first_name} {$teacher->last_name}");
                return [
                    $teacher->id,
                    $teacherFullName,
                    $teacher->qr_code,
                    $teacher->user->name,
                    $teacher->user->qr_code
                ];
            }, $mismatches)
        );

        $this->line('');
        $this->info("💡 Pour corriger une incohérence spécifique:");
        $this->line("   php artisan fix:teacher-user-mismatch <teacher_id>");
        $this->line('');
        $this->info("💡 Pour corriger toutes les incohérences:");
        $this->line("   php artisan fix:teacher-user-mismatch --all");

        return 0;
    }

    protected function fixAllMismatches()
    {
        $this->info("🔍 Recherche et correction de toutes les incohérences...");
        $this->line('');

        $teachers = Teacher::with('user')->whereNotNull('user_id')->get();
        $fixed = 0;
        $errors = 0;

        foreach ($teachers as $teacher) {
            if (!$teacher->user) {
                continue;
            }

            $teacherFullName = trim("{$teacher->first_name} {$teacher->last_name}");
            $userName = trim($teacher->user->name);

            if ($teacherFullName !== $userName || $teacher->qr_code !== $teacher->user->qr_code) {
                try {
                    DB::beginTransaction();

                    $oldName = $teacher->user->name;
                    $oldQR = $teacher->user->qr_code;

                    $teacher->user->name = $teacherFullName;
                    $teacher->user->qr_code = $teacher->qr_code;
                    $teacher->user->save();

                    DB::commit();

                    $this->info("✅ Teacher {$teacher->id}: {$oldName} → {$teacherFullName}");
                    $fixed++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("❌ Teacher {$teacher->id}: Erreur - {$e->getMessage()}");
                    $errors++;
                }
            }
        }

        $this->line('');
        $this->info("📊 Résumé:");
        $this->line("   ✅ Corrigés: {$fixed}");
        $this->line("   ❌ Erreurs: {$errors}");

        return 0;
    }
}
