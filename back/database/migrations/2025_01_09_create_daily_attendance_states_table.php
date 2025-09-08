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
        Schema::create('daily_attendance_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_series_id');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->date('attendance_date');
            $table->enum('entry_state', ['not_done', 'in_progress', 'completed'])->default('not_done');
            $table->enum('exit_state', ['not_done', 'in_progress', 'completed'])->default('not_done');
            $table->timestamp('entry_completed_at')->nullable();
            $table->timestamp('exit_completed_at')->nullable();
            $table->unsignedBigInteger('school_year_id');
            $table->timestamps();

            // Index et contraintes
            $table->foreign('class_series_id')->references('id')->on('class_series')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            
            // Contrainte unique pour une seule entrée par série par jour
            $table->unique(['class_series_id', 'attendance_date', 'school_year_id'], 'unique_daily_attendance_state');
            
            // Index pour optimiser les requêtes
            $table->index(['attendance_date', 'school_year_id']);
            $table->index(['class_series_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendance_states');
    }
};