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
        // Changer staff_type de ENUM vers VARCHAR pour accepter n'importe quel type
        DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type VARCHAR(50) NOT NULL");
        
        // Optionnel: Ajouter un index pour les performances
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->index('staff_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retourner à l'ENUM original (attention: peut perdre des données si de nouveaux types existent)
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropIndex(['staff_type']);
        });
        
        DB::statement("ALTER TABLE staff_attendances MODIFY COLUMN staff_type ENUM('teacher', 'accountant', 'supervisor', 'admin', 'bibliothecaire', 'permanent', 'vacataire', 'semi_permanent')");
    }
};
