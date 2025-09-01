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
        Schema::table('staff_attendances', function (Blueprint $table) {
            // Ajouter 'bibliothecaire' au enum staff_type
            DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type ENUM('teacher', 'accountant', 'supervisor', 'admin', 'secretaire', 'bibliothecaire') COMMENT 'Type de personnel'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            // Retirer 'bibliothecaire' du enum staff_type
            DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type ENUM('teacher', 'accountant', 'supervisor', 'admin', 'secretaire') COMMENT 'Type de personnel'");
        });
    }
};
