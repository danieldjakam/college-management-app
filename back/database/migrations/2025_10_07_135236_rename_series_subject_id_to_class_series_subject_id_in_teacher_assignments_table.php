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
        Schema::table('teacher_assignments', function (Blueprint $table) {
            // Renommer la colonne series_subject_id en class_series_subject_id
            $table->renameColumn('series_subject_id', 'class_series_subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            // Restaurer le nom original de la colonne
            $table->renameColumn('class_series_subject_id', 'series_subject_id');
        });
    }
};
