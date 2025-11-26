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
        // 1. Modifier la table subjects pour rendre le champ group VARCHAR au lieu de ENUM
        Schema::table('subjects', function (Blueprint $table) {
            // Supprimer l'ancienne colonne ENUM et la remplacer par VARCHAR
            $table->dropColumn('group');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('group', 50)->nullable()->after('code');
            $table->foreign('group')->references('code')->on('subject_groups')->onDelete('set null');
        });

        // 2. Ajouter des colonnes pour les couleurs personnalisables dans subject_groups
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->string('color', 20)->default('#6b7280')->after('description');
            $table->string('bg_color', 20)->default('#f9fafb')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes de couleur
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn(['color', 'bg_color']);
        });

        // Remettre le champ group en ENUM
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['group']);
            $table->dropColumn('group');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('group', ['A', 'B', 'C', 'D'])->nullable()->after('code');
        });
    }
};
