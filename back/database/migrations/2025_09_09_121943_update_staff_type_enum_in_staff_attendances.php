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
        // Utiliser une requête SQL directe pour modifier l'ENUM
        DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type ENUM('teacher', 'accountant', 'supervisor', 'admin', 'bibliothecaire', 'permanent', 'vacataire', 'semi_permanent')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retourner à l'ancien ENUM
        DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type ENUM('teacher', 'accountant', 'supervisor', 'admin', 'bibliothecaire')");
    }
};
