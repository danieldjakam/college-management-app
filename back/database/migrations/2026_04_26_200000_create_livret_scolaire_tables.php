<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pour les notes ajustees du livret scolaire
        if (!Schema::hasTable('livret_scolaire_grades')) {
            Schema::create('livret_scolaire_grades', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_series_subject_id');
                $table->unsignedBigInteger('school_year_id');
                $table->unsignedBigInteger('modified_by')->nullable();

                // Notes ajustees par trimestre
                $table->decimal('trim1', 5, 2)->nullable();
                $table->decimal('trim2', 5, 2)->nullable();
                $table->decimal('trim3', 5, 2)->nullable();

                $table->timestamps();

                $table->unique(['student_id', 'class_series_subject_id', 'school_year_id'], 'livret_unique');
                $table->index(['school_year_id']);
                $table->index(['student_id']);
            });
        }

        // Table pour marquer les classes d'examen
        if (!Schema::hasTable('exam_classes')) {
            Schema::create('exam_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_series_id');
                $table->unsignedBigInteger('school_year_id');
                $table->timestamps();

                $table->unique(['class_series_id', 'school_year_id']);
                $table->index(['school_year_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('livret_scolaire_grades');
        Schema::dropIfExists('exam_classes');
    }
};
