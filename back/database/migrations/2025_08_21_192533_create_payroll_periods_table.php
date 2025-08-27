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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('mois')->between(1, 12);
            $table->integer('annee');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('date_paie')->nullable();
            $table->enum('statut', ['ouverte', 'calculee', 'validee', 'payee'])->default('ouverte');
            $table->boolean('notifications_sent')->default(false);
            $table->timestamps();

            $table->unique(['mois', 'annee']);
            $table->index(['statut', 'annee', 'mois']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};