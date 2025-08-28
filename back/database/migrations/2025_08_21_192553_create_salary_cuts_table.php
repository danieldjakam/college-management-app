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
        Schema::create('salary_cuts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('montant_coupe', 10, 0);
            $table->text('motif');
            $table->date('date_coupure');
            $table->unsignedBigInteger('created_by');
            $table->enum('statut', ['active', 'annulee'])->default('active');
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees_payroll')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['employee_id', 'period_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_cuts');
    }
};