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
        // Vérifier si la colonne class_series_id existe déjà
        $columns = Schema::getColumnListing('main_teachers');

        if (in_array('class_series_id', $columns)) {
            // La colonne existe déjà, vérifier s'il faut nettoyer
            echo "La colonne class_series_id existe déjà.\n";

            // Supprimer les enregistrements orphelins
            DB::statement("
                DELETE FROM main_teachers
                WHERE class_series_id NOT IN (
                    SELECT id FROM class_series
                )
            ");

            // Supprimer school_class_id si elle existe encore
            if (in_array('school_class_id', $columns)) {
                // Supprimer les clés étrangères sur school_class_id
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = 'main_teachers'
                      AND TABLE_SCHEMA = DATABASE()
                      AND COLUMN_NAME = 'school_class_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                foreach ($foreignKeys as $fk) {
                    DB::statement("ALTER TABLE main_teachers DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }

                // Supprimer l'index unique s'il existe
                $indexes = DB::select("SHOW INDEX FROM main_teachers WHERE Key_name = 'unique_main_teacher_per_class'");
                if (!empty($indexes)) {
                    DB::statement("ALTER TABLE main_teachers DROP INDEX unique_main_teacher_per_class");
                }

                Schema::table('main_teachers', function (Blueprint $table) {
                    $table->dropColumn('school_class_id');
                });
            }

            // Vérifier si la clé étrangère existe
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'main_teachers'
                  AND TABLE_SCHEMA = DATABASE()
                  AND COLUMN_NAME = 'class_series_id'
                  AND CONSTRAINT_NAME = 'main_teachers_class_series_id_foreign'
            ");

            if (empty($fkExists)) {
                Schema::table('main_teachers', function (Blueprint $table) {
                    $table->foreign('class_series_id')->references('id')->on('class_series')->onDelete('cascade');
                });
            }

            // Vérifier si l'index unique existe
            $uniqueExists = DB::select("SHOW INDEX FROM main_teachers WHERE Key_name = 'unique_main_teacher_per_class_series'");
            if (empty($uniqueExists)) {
                DB::statement("ALTER TABLE main_teachers ADD UNIQUE unique_main_teacher_per_class_series (class_series_id, school_year_id)");
            }

            return;
        }

        // Migration normale : school_class_id vers class_series_id
        if (!in_array('school_class_id', $columns)) {
            throw new Exception("Ni school_class_id ni class_series_id n'existent dans la table main_teachers");
        }

        // Supprimer tous les enregistrements de main_teachers (en production il n'y en a probablement pas ou peu)
        DB::table('main_teachers')->truncate();

        // Vérifier et supprimer la clé étrangère si elle existe
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'main_teachers'
              AND TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'school_class_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE main_teachers DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }

        // Supprimer l'index unique s'il existe
        $indexes = DB::select("SHOW INDEX FROM main_teachers WHERE Key_name = 'unique_main_teacher_per_class'");
        if (!empty($indexes)) {
            DB::statement("ALTER TABLE main_teachers DROP INDEX unique_main_teacher_per_class");
        }

        Schema::table('main_teachers', function (Blueprint $table) {
            // Supprimer l'ancienne colonne school_class_id
            $table->dropColumn('school_class_id');

            // Ajouter la nouvelle colonne class_series_id
            $table->unsignedBigInteger('class_series_id')->after('teacher_id');
            $table->foreign('class_series_id')->references('id')->on('class_series')->onDelete('cascade');
        });

        // Ajouter l'index unique
        DB::statement("ALTER TABLE main_teachers ADD UNIQUE unique_main_teacher_per_class_series (class_series_id, school_year_id)");
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
