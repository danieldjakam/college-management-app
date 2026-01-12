<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sur certaines bases importées, la colonne `students.id` peut perdre AUTO_INCREMENT.
        // Cela empêche la création d'élèves (MySQL: Field 'id' doesn't have a default value).

        $row = DB::selectOne("SHOW COLUMNS FROM students WHERE Field = 'id'");
        if (!$row) {
            return;
        }

        $extra = strtolower((string) ($row->Extra ?? ''));
        if (str_contains($extra, 'auto_increment')) {
            return; // déjà OK
        }

        // Forcer AUTO_INCREMENT
        DB::statement('ALTER TABLE students MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // On ne retire pas AUTO_INCREMENT en down (risque de casser les inserts)
    }
};
