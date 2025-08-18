<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Teacher;

class GenerateStaffQR extends Command
{
    protected $signature = 'generate:staff-qr {user_id}';
    protected $description = 'Générer un code QR pour un membre du personnel';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Utilisateur avec ID {$userId} non trouvé");
            return 1;
        }
        
        // Vérifier que c'est bien un membre du personnel
        $staffRoles = ['teacher', 'accountant', 'admin', 'surveillant_general', 'comptable_superieur', 'general_accountant', 'secretaire'];
        
        if (!in_array($user->role, $staffRoles)) {
            $this->error("Cet utilisateur n'est pas un membre du personnel (rôle: {$user->role})");
            return 1;
        }
        
        $this->info("Génération du QR code pour: {$user->name} ({$user->role})");
        
        // Générer un code QR unique simple
        $qrCode = 'STAFF_' . $user->id;
        
        $this->info("Code QR généré: {$qrCode}");
        
        // Mettre à jour l'utilisateur avec le nouveau QR code
        $user->update(['qr_code' => $qrCode]);
        
        // Si c'est un enseignant, mettre à jour aussi dans la table teachers
        if ($user->role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $teacher->update(['qr_code' => $qrCode]);
                $this->info("QR code également mis à jour dans la table teachers");
            } else {
                $this->warn("Aucun enregistrement Teacher trouvé pour cet utilisateur");
            }
        }
        
        // Vérifier que l'enregistrement a bien été fait
        $user = $user->fresh();
        if ($user->qr_code === $qrCode) {
            $this->info("✅ QR code enregistré avec succès dans la base de données");
        } else {
            $this->error("❌ Échec de l'enregistrement du QR code");
            return 1;
        }
        
        // Test de recherche
        $foundUser = User::where('qr_code', $qrCode)
                       ->whereIn('role', $staffRoles)
                       ->where('is_active', true)
                       ->first();
        
        if ($foundUser) {
            $this->info("✅ QR code trouvé par recherche - scan QR devrait fonctionner");
        } else {
            $this->error("❌ QR code NON trouvé par recherche - problème de configuration");
        }
        
        return 0;
    }
}