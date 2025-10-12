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
        Schema::table('evaluations', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte de clé étrangère
            $table->dropForeign(['series_subject_id']);

            // Renommer la colonne
            $table->renameColumn('series_subject_id', 'class_series_subject_id');
        });

        // Ajouter la nouvelle contrainte dans une deuxième étape
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('class_series_subject_id')
                  ->references('id')
                  ->on('class_series_subjects')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte
            $table->dropForeign(['class_series_subject_id']);

            // Renommer la colonne
            $table->renameColumn('class_series_subject_id', 'series_subject_id');
        });

        // Rétablir l'ancienne contrainte
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('series_subject_id')
                  ->references('id')
                  ->on('series_subjects')
                  ->onDelete('cascade');
        });
    }
};
