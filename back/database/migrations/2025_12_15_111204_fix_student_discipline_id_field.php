<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop the table and recreate it with correct structure
        Schema::dropIfExists('student_discipline');

        Schema::create('student_discipline', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('sequence_id')->nullable();
            $table->unsignedBigInteger('trimester_id')->nullable();

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

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration drops and recreates the table, so down() should do nothing
        // as the original structure was already incorrect
    }
};
