<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ✅ CORRECTION: Permettre sequence_id=NULL pour les compositions
     * Les compositions sont liées au TRIMESTRE, pas à une séquence
     * Seules les évaluations de séquence (DS, TP) doivent avoir un sequence_id
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Modifier la colonne sequence_id pour autoriser NULL
            $table->unsignedBigInteger('sequence_id')->nullable()->change();
        });

        // Après modification de la colonne, corriger les anciennes compositions
        DB::statement("UPDATE evaluations SET sequence_id = NULL WHERE type = 'composition'");

        echo "✅ " . DB::table('evaluations')->where('type', 'composition')->whereNull('sequence_id')->count() . " compositions corrigées\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Restaurer la contrainte NOT NULL (attention: ne peut pas être fait si des NULL existent)
            $table->unsignedBigInteger('sequence_id')->nullable(false)->change();
        });
    }
};
