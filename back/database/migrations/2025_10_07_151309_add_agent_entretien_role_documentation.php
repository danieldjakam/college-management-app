<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cette migration documente l'ajout du rôle 'agent_entretien' (Agents d'entretien)
     *
     * Rôles disponibles dans le système:
     * - admin: Administrateur
     * - principal: Directeur
     * - secretaire: Secrétaire
     * - accountant: Comptable
     * - comptable_superieur: Comptable supérieur
     * - teacher: Enseignant
     * - student: Étudiant
     * - parent: Parent
     * - agent_entretien: Agent d'entretien (NOUVEAU)
     *
     * Permissions du rôle agent_entretien:
     * - Consultation des données de base de l'établissement
     * - Gestion de son propre profil
     * - Aucune permission sur les données académiques ou financières
     */
    public function up(): void
    {
        // Cette migration est documentaire uniquement
        // Le rôle est stocké comme string dans users.role
        // Aucune modification de schéma n'est nécessaire
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire - migration documentaire uniquement
    }
};
