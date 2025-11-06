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
        Schema::create('subject_competences', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('class_series_id')->constrained('class_series')->onDelete('cascade');
            $table->foreignId('class_series_subject_id')->constrained('class_series_subjects')->onDelete('cascade');
            $table->foreignId('trimester_id')->constrained('trimesters')->onDelete('cascade');

            // Les 2 compétences évaluées (texte libre)
            $table->text('competence_1')->nullable();
            $table->text('competence_2')->nullable();

            $table->timestamps();

            // Index unique: un enseignant ne peut avoir qu'une seule paire de compétences par matière/classe/trimestre
            $table->unique(['teacher_id', 'class_series_subject_id', 'trimester_id'], 'unique_competence_per_subject_trimester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_competences');
    }
};
