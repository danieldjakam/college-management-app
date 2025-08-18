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
        Schema::table('teachers', function (Blueprint $table) {
            $table->enum('type_personnel', ['V', 'SP', 'P'])
                  ->default('V')
                  ->after('is_active')
                  ->comment('Type de personnel: V=Vacataire, SP=Semi-Permanent, P=Permanent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('type_personnel');
        });
    }
};
