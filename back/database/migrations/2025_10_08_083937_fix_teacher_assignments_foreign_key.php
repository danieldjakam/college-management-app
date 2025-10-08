<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Corriger la clé étrangère de teacher_assignments qui pointe vers l'ancienne table series_subjects
     */
    public function up(): void
    {
        // Vérifier si la contrainte existe
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'teacher_assignments'
              AND TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'class_series_subject_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        // Supprimer toutes les clés étrangères existantes sur cette colonne
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE teacher_assignments DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            echo "Supprimé la clé étrangère : {$fk->CONSTRAINT_NAME}\n";
        }

        // Ajouter la nouvelle clé étrangère correcte vers class_series_subjects
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->foreign('class_series_subject_id')
                  ->references('id')
                  ->on('class_series_subjects')
                  ->onDelete('cascade');
        });

        echo "Nouvelle clé étrangère ajoutée vers class_series_subjects\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropForeign(['class_series_subject_id']);
        });
    }
};
