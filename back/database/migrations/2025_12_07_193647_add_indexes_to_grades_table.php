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
            // Index composite pour les requêtes de bulletins (les plus fréquentes)
            // Utilisé dans: BulletinService->getGradesForStudent()
            $table->index(['student_id', 'trimester_id', 'sequence_id'], 'idx_grades_student_trimester_sequence');

            // Index pour les requêtes par évaluation (saisie de notes)
            // Utilisé dans: GradeController->getByEvaluation()
            $table->index(['evaluation_id'], 'idx_grades_evaluation');

            // Index pour les requêtes par matière et période
            // Utilisé dans: BulletinService->calculateSubjectAverage()
            $table->index(['class_series_subject_id', 'trimester_id'], 'idx_grades_subject_trimester');

            // Index simple sur student_id (pour les requêtes de listing)
            $table->index(['student_id'], 'idx_grades_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // Supprimer les index dans l'ordre inverse
            $table->dropIndex('idx_grades_student');
            $table->dropIndex('idx_grades_subject_trimester');
            $table->dropIndex('idx_grades_evaluation');
            $table->dropIndex('idx_grades_student_trimester_sequence');
        });
    }
};
