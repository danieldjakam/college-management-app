<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demandes_explication', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emetteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('destinataire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            
            $table->string('sujet');
            $table->text('message');
            $table->enum('priorite', ['basse', 'normale', 'haute', 'urgente'])->default('normale');
            $table->enum('statut', ['brouillon', 'envoyee', 'lue', 'repondue', 'cloturee'])->default('brouillon');
            $table->enum('type', ['financier', 'absence', 'retard', 'disciplinaire', 'autre'])->default('autre');
            
            $table->datetime('date_envoi')->nullable();
            $table->datetime('date_lecture')->nullable();
            $table->datetime('date_reponse')->nullable();
            $table->datetime('date_cloture')->nullable();
            
            $table->text('reponse')->nullable();
            $table->json('pieces_jointes')->nullable(); // Stockage des noms de fichiers joints
            $table->text('notes_internes')->nullable(); // Notes privées pour l'émetteur
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['emetteur_id', 'statut']);
            $table->index(['destinataire_id', 'statut']);
            $table->index(['school_year_id', 'date_envoi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_explication');
    }
};