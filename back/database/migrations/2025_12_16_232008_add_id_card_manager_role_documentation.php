<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cette migration documente l'ajout du nouveau rôle 'id_card_manager'
     *
     * Rôle: id_card_manager (Gestionnaire de Cartes d'Identité)
     * Permissions:
     * - Voir toutes les classes
     * - Voir tous les élèves
     * - Modifier les photos des élèves
     * - Prévisualiser les cartes d'identité
     * - Générer/Imprimer les cartes d'identité (individuelles et par classe)
     *
     * Restrictions:
     * - Ne peut PAS modifier d'autres informations des élèves
     * - Ne peut PAS accéder aux notes, paiements, bulletins
     * - Ne peut PAS gérer les enseignants ou le personnel
     */
    public function up(): void
    {
        // Le champ 'role' existe déjà dans la table 'users' en tant que string
        // Aucune modification de schéma n'est nécessaire
        // Le nouveau rôle 'id_card_manager' sera ajouté via le middleware et les routes

        // Note: Pour créer un utilisateur avec ce rôle, utilisez:
        // User::create([
        //     'name' => 'Gestionnaire Cartes',
        //     'username' => 'card_manager',
        //     'email' => 'cards@cpbdouala.cm',
        //     'role' => 'id_card_manager',
        //     'password' => bcrypt('password')
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire - le rôle est simplement une valeur string
        // Les utilisateurs avec ce rôle peuvent être supprimés manuellement si nécessaire
    }
};
