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
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('class_series_id')->constrained('class_series')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('entered_by')->nullable()->constrained('users')->onDelete('set null');

            // Période (Trimestre)
            $table->tinyInteger('trimester')->comment('1, 2, or 3'); // Trimestre 1, 2, ou 3

            // Type d'évaluation
            $table->enum('evaluation_type', [
                'eval1',    // 1ère évaluation du trimestre
                'eval2',    // 2ème évaluation du trimestre
                'comp'      // Composition du trimestre
            ]);

            // Note
            $table->decimal('score', 5, 2)->nullable()->comment('Note sur 20');
            $table->boolean('is_absent')->default(false)->comment('Élève absent à cette évaluation');

            // Métadonnées
            $table->text('notes')->nullable()->comment('Observations ou notes complémentaires');
            $table->timestamp('entered_at')->nullable();

            $table->timestamps();

            // Index pour performance
            $table->index(['student_id', 'subject_id', 'trimester']);
            $table->index(['class_series_id', 'subject_id', 'trimester']);

            // Contrainte unique : un élève ne peut avoir qu'une seule note par évaluation
            $table->unique([
                'student_id',
                'subject_id',
                'school_year_id',
                'trimester',
                'evaluation_type'
            ], 'unique_student_evaluation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};
