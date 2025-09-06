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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->integer('day_of_week'); // 1 = Lundi, 2 = Mardi, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->string('subject');
            $table->string('teacher_name')->nullable();
            $table->string('room')->nullable();
            $table->string('academic_year')->default('2024-2025');
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['class_id', 'day_of_week']);
            $table->index('academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};