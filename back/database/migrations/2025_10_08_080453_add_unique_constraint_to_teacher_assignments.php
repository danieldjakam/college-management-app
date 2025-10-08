<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute une contrainte unique pour s'assurer qu'une matière dans une classe
     * ne peut être enseignée que par UN SEUL enseignant pour une année scolaire donnée.
     */
    public function up(): void
    {
        // D'abord, supprimer les doublons avant d'ajouter la contrainte
        echo "Nettoyage des doublons avant d'ajouter la contrainte...\n";

        // Trouver et supprimer les doublons (garder le plus ancien)
        DB::statement("
            DELETE t1 FROM teacher_assignments t1
            INNER JOIN teacher_assignments t2
            WHERE t1.id > t2.id
              AND t1.class_series_subject_id = t2.class_series_subject_id
              AND t1.school_year_id = t2.school_year_id
              AND t1.is_active = t2.is_active
        ");

        Schema::table('teacher_assignments', function (Blueprint $table) {
            // Ajouter une contrainte unique composite
            // Une matière dans une classe ne peut avoir qu'un seul enseignant actif par année scolaire
            $table->unique(
                ['class_series_subject_id', 'school_year_id', 'is_active'],
                'unique_subject_class_year_active'
            );
        });

        echo "Contrainte unique ajoutée avec succès.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_subject_class_year_active');
        });
    }
};
