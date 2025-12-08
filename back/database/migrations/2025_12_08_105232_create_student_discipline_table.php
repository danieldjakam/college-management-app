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
        Schema::create('student_discipline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('sequence_id')->nullable()->constrained('sequences')->onDelete('cascade');
            $table->foreignId('trimester_id')->nullable()->constrained('trimesters')->onDelete('cascade');

            // Retards (heures)
            $table->integer('delays_justified')->default(0)->comment('Retards justifiés (heures)');
            $table->integer('delays_unjustified')->default(0)->comment('Retards non justifiés (heures)');

            // Absences (heures)
            $table->integer('absences_justified')->default(0)->comment('Absences justifiées (heures)');
            $table->integer('absences_unjustified')->default(0)->comment('Absences non justifiées (heures)');

            // Blâmes
            $table->integer('blame_conduct')->default(0)->comment('Blâmes conduite');
            $table->integer('blame_work')->default(0)->comment('Blâmes travail');

            // Avertissements
            $table->integer('warning_conduct')->default(0)->comment('Avertissements conduite');
            $table->integer('warning_work')->default(0)->comment('Avertissements travail');

            // Consignes (heures)
            $table->integer('detention_hours')->default(0)->comment('Consignes (heures)');

            // Exclusions (jours)
            $table->integer('exclusion_days')->default(0)->comment('Exclusions (jours)');

            $table->text('observations')->nullable()->comment('Observations générales');
            $table->timestamps();

            // Index pour performance
            $table->index(['student_id', 'sequence_id']);
            $table->index(['student_id', 'trimester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_discipline');
    }
};
