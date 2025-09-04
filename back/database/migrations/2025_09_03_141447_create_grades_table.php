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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('evaluation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->foreignId('trimester_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('series_subject_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 5, 2)->nullable(); // Note obtenue
            $table->decimal('max_score', 5, 2)->default(20.00); // Note maximale
            $table->decimal('coefficient', 3, 2)->default(1.00); // Coefficient
            $table->decimal('weighted_score', 8, 2)->nullable(); // Note pondérée (score * coefficient)
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_excused')->default(false);
            $table->text('comment')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index(['student_id', 'sequence_id']);
            $table->index(['student_id', 'trimester_id']);
            $table->index(['student_id', 'school_year_id']);
            $table->index(['evaluation_id', 'student_id']);
            $table->index(['series_subject_id', 'sequence_id']);
            
            // Contrainte d'unicité
            $table->unique(['student_id', 'evaluation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};