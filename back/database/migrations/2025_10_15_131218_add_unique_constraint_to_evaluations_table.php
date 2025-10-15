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
            // Ajouter une contrainte unique pour empêcher les doublons
            // Une évaluation unique = même matière + même séquence + même enseignant
            $table->unique(
                ['class_series_subject_id', 'sequence_id', 'teacher_id'],
                'unique_evaluation_per_teacher_subject_sequence'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Supprimer la contrainte unique
            $table->dropUnique('unique_evaluation_per_teacher_subject_sequence');
        });
    }
};
