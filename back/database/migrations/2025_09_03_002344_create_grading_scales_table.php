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
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('level_id')->nullable()->constrained('levels')->onDelete('cascade'); // null = pour tous les niveaux
            $table->string('grade_code', 5); // TB, B, AB, P, M, I
            $table->string('grade_label', 50); // Très Bien, Bien, etc.
            $table->decimal('min_score', 5, 2); // Note minimale
            $table->decimal('max_score', 5, 2); // Note maximale
            $table->text('appreciation'); // Appréciation automatique
            $table->string('color_code', 7)->default('#6c757d'); // Couleur pour l'affichage
            $table->decimal('passing_threshold', 5, 2)->nullable(); // Seuil de passage (si applicable)
            $table->boolean('is_passing_grade')->default(true); // Si cette note permet le passage
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            // Index
            $table->index(['school_year_id', 'level_id']);
            $table->index(['grade_code', 'school_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
