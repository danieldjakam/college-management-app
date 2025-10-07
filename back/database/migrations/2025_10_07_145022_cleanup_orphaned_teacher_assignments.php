<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer les affectations d'enseignants qui référencent des class_series_subjects inexistants
        DB::statement('
            DELETE FROM teacher_assignments
            WHERE class_series_subject_id NOT IN (
                SELECT id FROM class_series_subjects
            )
        ');

        // Optionnel: Afficher le nombre d'enregistrements supprimés
        $deletedCount = DB::table('teacher_assignments')
            ->whereNotIn('class_series_subject_id', function($query) {
                $query->select('id')->from('class_series_subjects');
            })
            ->count();

        if ($deletedCount > 0) {
            echo "Nettoyage: {$deletedCount} affectation(s) orpheline(s) supprimée(s)\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Aucune action nécessaire pour le rollback
        // Les données orphelines ne peuvent pas être restaurées
    }
};
