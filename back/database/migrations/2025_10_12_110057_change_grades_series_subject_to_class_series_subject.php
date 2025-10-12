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
        Schema::table('grades', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte de clé étrangère d'abord
            $table->dropForeign(['series_subject_id']);

            // Supprimer les index existants
            $table->dropIndex(['series_subject_id', 'sequence_id']);

            // Renommer la colonne
            $table->renameColumn('series_subject_id', 'class_series_subject_id');
        });

        // Ajouter la nouvelle contrainte et les index dans une deuxième étape
        Schema::table('grades', function (Blueprint $table) {
            $table->foreign('class_series_subject_id')
                  ->references('id')
                  ->on('class_series_subjects')
                  ->onDelete('cascade');

            // Recréer l'index avec le nouveau nom de colonne
            $table->index(['class_series_subject_id', 'sequence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte d'abord
            $table->dropForeign(['class_series_subject_id']);

            // Supprimer les index
            $table->dropIndex(['class_series_subject_id', 'sequence_id']);

            // Renommer la colonne
            $table->renameColumn('class_series_subject_id', 'series_subject_id');
        });

        // Rétablir l'ancienne contrainte et les index
        Schema::table('grades', function (Blueprint $table) {
            $table->foreign('series_subject_id')
                  ->references('id')
                  ->on('series_subjects')
                  ->onDelete('cascade');

            $table->index(['series_subject_id', 'sequence_id']);
        });
    }
};
