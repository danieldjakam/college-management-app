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
        // Vérifier si la table existe déjà
        if (!Schema::hasTable('staff_attendance_classes')) {
            Schema::create('staff_attendance_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_attendance_id')->constrained()->onDelete('cascade');
                $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
                $table->timestamps();

                // Index pour les requêtes fréquentes
                $table->index(['staff_attendance_id', 'school_class_id'], 'idx_attendance_class');
                
                // Éviter les doublons
                $table->unique(['staff_attendance_id', 'school_class_id'], 'uk_attendance_class');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_classes');
    }
};
