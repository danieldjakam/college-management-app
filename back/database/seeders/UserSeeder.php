<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur
        User::create([
            'name' => 'Administrateur',
            'username' => 'admin',
            'email' => 'admin@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Créer un enseignant
        User::create([
            'name' => 'Professeur Martin',
            'username' => 'prof.martin',
            'email' => 'martin@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        // Créer un comptable
        User::create([
            'name' => 'Comptable Dupont',
            'username' => 'comptable',
            'email' => 'comptable@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'accountant',
        ]);

        // Créer un utilisateur standard
        User::create([
            'name' => 'Utilisateur Test',
            'username' => 'user.test',
            'email' => 'user@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        // Créer quelques enseignants supplémentaires
        User::create([
            'name' => 'Professeur Dubois',
            'username' => 'prof.dubois',
            'email' => 'dubois@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        User::create([
            'name' => 'Professeur Ngomo',
            'username' => 'prof.ngomo',
            'email' => 'ngomo@gsbpl.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        echo "✅ Utilisateurs créés avec succès !\n";
        echo "==========================================\n";
        echo "Admin:      username: admin       | password: password123\n";
        echo "Enseignant: username: prof.martin | password: password123\n";
        echo "Comptable:  username: comptable   | password: password123\n";
        echo "Utilisateur: username: user.test  | password: password123\n";
        echo "==========================================\n";
    }
}