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
        Schema::table('needs', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte CASCADE dangereuse
            $table->dropForeign(['user_id']);
            
            // Modifier la colonne user_id pour accepter NULL
            $table->foreignId('user_id')->nullable()->change();
            
            // Recréer la contrainte avec SET NULL pour préserver les données
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('needs_user_id_foreign_safe');
                  
            // Supprimer l'ancienne contrainte sur approved_by aussi si elle existe
            $table->dropForeign(['approved_by']);
            
            // Recréer la contrainte approved_by avec SET NULL (déjà nullable)
            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('needs_approved_by_foreign_safe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table) {
            // Supprimer les nouvelles contraintes
            $table->dropForeign(['user_id']);
            $table->dropForeign(['approved_by']);
            
            // Remettre les anciennes contraintes CASCADE (non recommandé)
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
};
