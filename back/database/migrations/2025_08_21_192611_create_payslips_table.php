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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('salaire_base', 10, 0)->default(0);
            $table->decimal('primes_mensuelles', 10, 0)->default(0);
            $table->decimal('deductions_mensuelles', 10, 0)->default(0);
            $table->decimal('montant_coupures', 10, 0)->default(0);
            $table->decimal('salaire_brut', 10, 0)->default(0);
            $table->decimal('salaire_net', 10, 0)->default(0);
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement']);
            $table->enum('statut', ['brouillon', 'valide', 'paye'])->default('brouillon');
            $table->boolean('retire')->default(false);
            $table->date('date_retrait')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees_payroll')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
            $table->unique(['employee_id', 'period_id']);
            $table->index(['statut', 'period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};