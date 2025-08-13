<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateGeneralAccountant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:general-accountant 
                            {name : Nom complet du comptable général}
                            {username : Nom d\'utilisateur}
                            {email : Adresse email}
                            {--password= : Mot de passe (généré automatiquement si non fourni)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un nouvel utilisateur avec le rôle de Comptable Général';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $username = $this->argument('username');
        $email = $this->argument('email');
        $password = $this->option('password') ?? $this->generateRandomPassword();

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error('Un utilisateur avec cette adresse email existe déjà.');
            return 1;
        }

        if (User::where('username', $username)->exists()) {
            $this->error('Un utilisateur avec ce nom d\'utilisateur existe déjà.');
            return 1;
        }

        try {
            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'general_accountant',
                'is_active' => true,
            ]);

            $this->info('✅ Comptable Général créé avec succès !');
            $this->line('');
            $this->line('📋 Détails du compte :');
            $this->line("   👤 Nom: {$user->name}");
            $this->line("   🏷️  Nom d'utilisateur: {$user->username}");
            $this->line("   📧 Email: {$user->email}");
            $this->line("   🔐 Mot de passe: {$password}");
            $this->line("   👑 Rôle: Comptable Général");
            $this->line('');
            $this->warn('⚠️  Veuillez noter le mot de passe et le communiquer de manière sécurisée à l\'utilisateur.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur lors de la création du comptable général: ' . $e->getMessage());
            return 1;
        }
    }

    private function generateRandomPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
}
