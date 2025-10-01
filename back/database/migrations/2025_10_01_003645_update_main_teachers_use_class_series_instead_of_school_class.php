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
        // Vérifier si la colonne class_series_id existe
        $hasClassSeriesId = Schema::hasColumn('main_teachers', 'class_series_id');

        if ($hasClassSeriesId) {
            // Nettoyer les données invalides dans main_teachers
            // (class_series_id contient des school_class.id au lieu de class_series.id)
            DB::statement('DELETE FROM main_teachers WHERE class_series_id NOT IN (SELECT id FROM class_series)');

            // Vérifier si la foreign key existe avant de la supprimer
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'main_teachers'
                AND COLUMN_NAME = 'class_series_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($foreignKeys)) {
                Schema::table('main_teachers', function (Blueprint $table) {
                    $table->dropForeign(['class_series_id']);
                });
            }

            // Ajouter la foreign key vers class_series
            Schema::table('main_teachers', function (Blueprint $table) {
                $table->foreign('class_series_id')
                      ->references('id')
                      ->on('class_series')
                      ->onDelete('cascade');
            });

            // Vérifier et mettre à jour la contrainte unique
            $uniqueConstraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_NAME = 'main_teachers'
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_SCHEMA = DATABASE()
            ");

            // Supprimer les anciennes contraintes uniques
            foreach ($uniqueConstraints as $constraint) {
                if (str_contains($constraint->CONSTRAINT_NAME, 'class_series') ||
                    str_contains($constraint->CONSTRAINT_NAME, 'school_class')) {
                    DB::statement("ALTER TABLE main_teachers DROP INDEX {$constraint->CONSTRAINT_NAME}");
                }
            }

            // Ajouter la nouvelle contrainte unique
            Schema::table('main_teachers', function (Blueprint $table) {
                $table->unique(['class_series_id', 'school_year_id'], 'main_teachers_class_series_year_unique');
            });
        } else {
            // Si la colonne n'existe pas encore, c'est une nouvelle installation
            // Ne rien faire, la structure sera créée par la migration de création initiale
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_teachers', function (Blueprint $table) {
            // Supprimer la contrainte unique
            $table->dropUnique('main_teachers_class_series_year_unique');

            // Supprimer la foreign key
            $table->dropForeign(['class_series_id']);

            // Renommer la colonne
            $table->renameColumn('class_series_id', 'school_class_id');

            // Restaurer l'ancienne foreign key
            $table->foreign('school_class_id')
                  ->references('id')
                  ->on('school_classes')
                  ->onDelete('cascade');
        });

        // Restaurer l'ancienne contrainte unique
        Schema::table('main_teachers', function (Blueprint $table) {
            $table->unique(['school_class_id', 'school_year_id']);
        });
    }
};
