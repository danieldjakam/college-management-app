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
        Schema::table('staff_attendances', function (Blueprint $table) {
            // Supprimer la contrainte unique qui empêche les multiples entrées/sorties
            $table->dropUnique('unique_user_date_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            // Remettre la contrainte unique si on rollback
            $table->unique(['user_id', 'attendance_date', 'event_type'], 'unique_user_date_event');
        });
    }
};
