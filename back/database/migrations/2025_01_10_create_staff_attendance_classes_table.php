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
        // Créer la table de liaison entre staff_attendances et school_classes
        if (!Schema::hasTable('staff_attendance_classes')) {
            Schema::create('staff_attendance_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_attendance_id')
                    ->constrained('staff_attendances')
                    ->onDelete('cascade');
                $table->foreignId('school_class_id')
                    ->constrained('school_classes')
                    ->onDelete('cascade');
                $table->timestamps();
                
                // Index pour améliorer les performances
                $table->index(['staff_attendance_id', 'school_class_id'], 'staff_attendance_class_index');
                
                // Empêcher les doublons
                $table->unique(['staff_attendance_id', 'school_class_id'], 'unique_staff_attendance_class');
            });
        }
        
        // Ajouter la colonne class_id dans staff_attendances si elle n'existe pas (pour compatibilité)
        if (!Schema::hasColumn('staff_attendances', 'class_id')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('class_id')->nullable()->after('staff_type');
                $table->foreign('class_id')->references('id')->on('school_classes')->onDelete('set null');
                $table->index('class_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la table de liaison
        Schema::dropIfExists('staff_attendance_classes');
        
        // Supprimer la colonne class_id de staff_attendances
        if (Schema::hasColumn('staff_attendances', 'class_id')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            });
        }
    }
};
