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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->date('attendance_date');
            $table->boolean('is_present');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('attendance_type', ['manual', 'qr_scan', 'automatic'])->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances des requêtes
            $table->index(['student_id', 'attendance_date']);
            $table->index(['school_class_id', 'attendance_date']);
            $table->index(['school_year_id', 'attendance_date']);
            $table->index('attendance_type');

            // Contrainte unique pour éviter les doublons
            $table->unique(['student_id', 'attendance_date', 'school_year_id'], 'student_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
