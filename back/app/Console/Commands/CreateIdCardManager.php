<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateIdCardManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-id-card-manager
                          {username? : Le nom d\'utilisateur}
                          {--email= : L\'adresse email}
                          {--name= : Le nom complet}
                          {--password= : Le mot de passe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un compte utilisateur avec le rôle id_card_manager pour la gestion des cartes d\'identité';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('  Création d\'un Gestionnaire de Cartes');
        $this->info('==============================================');
        $this->line('');

        // Récupérer ou demander le nom d'utilisateur
        $username = $this->argument('username') ?? $this->ask('Nom d\'utilisateur', 'card_manager');

        // Vérifier si l'utilisateur existe déjà
        if (User::where('username', $username)->exists()) {
            $this->error("❌ L'utilisateur '{$username}' existe déjà!");
            return Command::FAILURE;
        }

        // Récupérer ou demander l'email
        $email = $this->option('email') ?? $this->ask('Email', 'cards@cpbdouala.cm');

        // Vérifier si l'email existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error("❌ L'email '{$email}' est déjà utilisé!");
            return Command::FAILURE;
        }

        // Récupérer ou demander le nom complet
        $name = $this->option('name') ?? $this->ask('Nom complet', 'Gestionnaire de Cartes');

        // Récupérer ou demander le mot de passe
        $password = $this->option('password') ?? $this->secret('Mot de passe (min. 6 caractères)');

        // Valider le mot de passe
        if (strlen($password) < 6) {
            $this->error("❌ Le mot de passe doit contenir au moins 6 caractères!");
            return Command::FAILURE;
        }

        // Confirmer la création
        $this->line('');
        $this->info('Informations du compte:');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Nom d\'utilisateur', $username],
                ['Email', $email],
                ['Nom complet', $name],
                ['Rôle', 'id_card_manager'],
            ]
        );

        if (!$this->confirm('Voulez-vous créer ce compte?', true)) {
            $this->warn('❌ Création annulée.');
            return Command::FAILURE;
        }

        try {
            // Créer l'utilisateur
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'name' => $name,
                'role' => 'id_card_manager',
                'password' => Hash::make($password),
            ]);

            $this->line('');
            $this->info('✅ Compte créé avec succès!');
            $this->line('');
            $this->info('==============================================');
            $this->info('  Détails du compte');
            $this->info('==============================================');
            $this->table(
                ['ID', 'Username', 'Email', 'Nom', 'Rôle'],
                [
                    [
                        $user->id,
                        $user->username,
                        $user->email,
                        $user->name,
                        $user->role
                    ]
                ]
            );

            $this->line('');
            $this->info('🔐 Ce compte a les permissions suivantes:');
            $this->line('  - Voir toutes les classes et élèves');
            $this->line('  - Modifier les photos des élèves');
            $this->line('  - Prévisualiser les cartes d\'identité');
            $this->line('  - Générer et imprimer les cartes (individuelles ou par classe)');
            $this->line('');
            $this->info('🌐 URL de connexion: ' . (config('app.url') ?: 'http://localhost:3000') . '/login');
            $this->line('');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la création du compte:');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
