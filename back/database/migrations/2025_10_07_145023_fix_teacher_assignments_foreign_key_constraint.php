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
        Schema::table('teacher_assignments', function (Blueprint $table) {
            // Ajouter la nouvelle contrainte pointant vers class_series_subjects
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
        Schema::table('teacher_assignments', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte
            $table->dropForeign(['class_series_subject_id']);

            // Restaurer l'ancienne contrainte pointant vers series_subjects
            $table->foreign('class_series_subject_id', 'teacher_assignments_series_subject_id_foreign')
                  ->references('id')
                  ->on('series_subjects')
                  ->onDelete('cascade');
        });
    }
};
