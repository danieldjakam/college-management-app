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
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('set null')
                  ->comment('Classe où l\'enseignant va donner cours (pour Vacataire/Semi-Permanent)');
            
            // Ajouter un index pour optimiser les requêtes
            $table->index(['class_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropIndex(['class_id', 'attendance_date']);
            $table->dropColumn('class_id');
        });
    }
};
