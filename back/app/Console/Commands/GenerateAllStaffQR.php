<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Teacher;

class GenerateAllStaffQR extends Command
{
    protected $signature = 'generate:all-staff-qr {--force : Régénérer même pour ceux qui ont déjà un QR code}';
    protected $description = 'Générer des codes QR pour tout le personnel qui n\'en a pas';

    public function handle()
    {
        $force = $this->option('force');
        
        $staffRoles = ['teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire'];
        
        $query = User::whereIn('role', $staffRoles)->where('is_active', true);
        
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('qr_code')->orWhere('qr_code', '');
            });
        }
        
        $users = $query->get();
        
        if ($users->count() === 0) {
            $this->info("Aucun utilisateur à traiter.");
            return 0;
        }
        
        $this->info("Génération des QR codes pour " . $users->count() . " utilisateur(s)...");
        
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();
        
        $generated = 0;
        $errors = 0;
        
        foreach ($users as $user) {
            try {
                // Générer un code QR unique simple
                $qrCode = 'STAFF_' . $user->id;
                
                // Mettre à jour l'utilisateur avec le nouveau QR code
                $user->update(['qr_code' => $qrCode]);
                
                // Si c'est un enseignant, mettre à jour aussi dans la table teachers
                if ($user->role === 'teacher') {
                    $teacher = Teacher::where('user_id', $user->id)->first();
                    if ($teacher) {
                        $teacher->update(['qr_code' => $qrCode]);
                    }
                }
                
                $generated++;
                
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Erreur pour {$user->name}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✅ {$generated} QR codes générés avec succès");
        if ($errors > 0) {
            $this->warn("⚠️ {$errors} erreurs rencontrées");
        }
        
        return 0;
    }
}