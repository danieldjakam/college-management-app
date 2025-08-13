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
        // D'abord, corriger les rôles invalides existants
        DB::statement("UPDATE users SET role = 'accountant' WHERE role NOT IN ('admin', 'teacher', 'accountant', 'user', 'surveillant_general')");
        
        // Ensuite, modifier la contrainte ENUM pour inclure 'general_accountant'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'accountant', 'user', 'surveillant_general', 'general_accountant') NOT NULL DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'general_accountant' de l'enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'accountant', 'user', 'surveillant_general') NOT NULL DEFAULT 'user'");
    }
};
