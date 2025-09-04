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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Composition Mathématiques"
            $table->enum('type', ['interrogation', 'devoir', 'composition', 'tp', 'controle']);
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->foreignId('trimester_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('series_subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->decimal('max_score', 5, 2)->default(20.00); // Note maximale
            $table->decimal('coefficient', 3, 2)->default(1.00); // Coefficient de l'évaluation
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index(['sequence_id', 'series_subject_id']);
            $table->index(['trimester_id', 'type']);
            $table->index(['school_year_id', 'date']);
            $table->index(['teacher_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};