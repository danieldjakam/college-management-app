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
        // Vérifier et supprimer la clé étrangère si elle existe
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'main_teachers'
              AND TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'school_class_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($foreignKeys)) {
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE main_teachers DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }
        }

        Schema::table('main_teachers', function (Blueprint $table) {
            // Supprimer l'index unique qui utilise school_class_id
            $table->dropUnique('unique_main_teacher_per_class');

            // Supprimer l'ancienne colonne school_class_id
            $table->dropColumn('school_class_id');

            // Ajouter la nouvelle colonne class_series_id
            $table->unsignedBigInteger('class_series_id')->after('teacher_id');
            $table->foreign('class_series_id')->references('id')->on('class_series')->onDelete('cascade');

            // Recréer l'index unique avec la nouvelle colonne
            $table->unique(['class_series_id', 'school_year_id'], 'unique_main_teacher_per_class_series');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_teachers', function (Blueprint $table) {
            // Supprimer l'index unique
            $table->dropUnique('unique_main_teacher_per_class_series');

            // Supprimer la clé étrangère et la colonne class_series_id
            $table->dropForeign(['class_series_id']);
            $table->dropColumn('class_series_id');

            // Restaurer l'ancienne colonne school_class_id
            $table->unsignedBigInteger('school_class_id')->after('teacher_id');

            // Recréer l'index unique original
            $table->unique(['school_class_id', 'school_year_id'], 'unique_main_teacher_per_class');
        });
    }
};
