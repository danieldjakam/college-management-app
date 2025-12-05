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
        Schema::table('subjects', function (Blueprint $table) {
            // Change group column from ENUM to VARCHAR to allow unlimited groups
            DB::statement("ALTER TABLE subjects MODIFY COLUMN `group` VARCHAR(50) NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Revert back to ENUM with original values
            DB::statement("ALTER TABLE subjects MODIFY COLUMN `group` ENUM('A','B','C','D') NULL");
        });
    }
};
