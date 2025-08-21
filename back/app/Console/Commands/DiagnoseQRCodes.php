<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Teacher;

class DiagnoseQRCodes extends Command
{
    protected $signature = 'diagnose:qr-codes {--user-id=}';
    protected $description = 'Diagnostiquer les codes QR du personnel';

    public function handle()
    {
        $userId = $this->option('user-id');
        
        if ($userId) {
            $this->diagnoseSpecificUser($userId);
        } else {
            $this->diagnoseAllStaff();
        }
        
        return 0;
    }
    
    private function diagnoseSpecificUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("Utilisateur avec ID {$userId} non trouvé");
            return;
        }
        
        $this->info("=== Diagnostic pour {$user->name} (ID: {$user->id}) ===");
        $this->info("Email: {$user->email}");
        $this->info("Rôle: {$user->role}");
        $this->info("Actif: " . ($user->is_active ? 'Oui' : 'Non'));
        $this->info("QR Code: " . ($user->qr_code ?: 'Aucun'));
        
        if ($user->role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $this->info("Teacher QR Code: " . ($teacher->qr_code ?: 'Aucun'));
            } else {
                $this->warn("Aucun enregistrement Teacher trouvé");
            }
        }
        
        // Test de recherche
        if ($user->qr_code) {
            $foundUser = User::where('qr_code', $user->qr_code)
                           ->whereIn('role', ['teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire'])
                           ->where('is_active', true)
                           ->first();
            
            if ($foundUser) {
                $this->info("✅ QR Code trouvé par recherche");
            } else {
                $this->error("❌ QR Code NON trouvé par recherche");
            }
        }
    }
    
    private function diagnoseAllStaff()
    {
        $this->info("=== Diagnostic des codes QR du personnel ===");
        
        $staffRoles = ['teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire'];
        
        $users = User::whereIn('role', $staffRoles)
                    ->where('is_active', true)
                    ->get();
        
        $this->info("Total personnel actif: " . $users->count());
        
        $withQR = $users->filter(function($user) {
            return !empty($user->qr_code);
        });
        
        $withoutQR = $users->filter(function($user) {
            return empty($user->qr_code);
        });
        
        $this->info("Avec QR Code: " . $withQR->count());
        $this->info("Sans QR Code: " . $withoutQR->count());
        
        $this->info("\n=== Détails par rôle ===");
        foreach ($staffRoles as $role) {
            $roleUsers = $users->where('role', $role);
            $roleWithQR = $roleUsers->filter(function($user) {
                return !empty($user->qr_code);
            });
            
            $this->info("{$role}: {$roleWithQR->count()}/{$roleUsers->count()} avec QR");
        }
        
        if ($withoutQR->count() > 0) {
            $this->warn("\n=== Personnel sans QR Code ===");
            foreach ($withoutQR as $user) {
                $this->warn("- {$user->name} ({$user->role}) - ID: {$user->id}");
            }
        }
        
        // Vérifier les doublons
        $this->info("\n=== Vérification des doublons ===");
        $qrCodes = $users->pluck('qr_code')->filter()->toArray();
        $duplicates = array_count_values($qrCodes);
        $duplicates = array_filter($duplicates, function($count) {
            return $count > 1;
        });
        
        if (count($duplicates) > 0) {
            $this->error("⚠️  Codes QR dupliqués trouvés:");
            foreach ($duplicates as $qrCode => $count) {
                $this->error("- {$qrCode}: {$count} utilisateurs");
            }
        } else {
            $this->info("✅ Aucun code QR dupliqué");
        }
    }
}