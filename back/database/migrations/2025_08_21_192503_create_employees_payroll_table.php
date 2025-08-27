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
        Schema::create('employees_payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('poste');
            $table->string('department')->nullable();
            $table->decimal('salaire_base', 10, 0)->default(0);
            $table->decimal('primes_fixes', 10, 0)->default(0);
            $table->decimal('deductions_fixes', 10, 0)->default(0);
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement'])->default('especes');
            $table->string('telephone_whatsapp')->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'conge'])->default('actif');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['statut', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees_payroll');
    }
};