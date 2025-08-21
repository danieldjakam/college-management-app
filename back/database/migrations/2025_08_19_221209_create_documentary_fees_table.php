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
        Schema::create('documentary_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->enum('fee_type', ['frais_dossier'])->default('frais_dossier');
            $table->string('description')->nullable();
            $table->decimal('fee_amount', 10, 2)->comment('Montant des frais de dossier');
            $table->decimal('penalty_amount', 10, 2)->default(0)->comment('Montant de la pénalité');
            $table->decimal('total_amount', 10, 2)->comment('Montant total (fee_amount + penalty_amount)');
            $table->date('payment_date');
            $table->date('versement_date')->nullable();
            $table->datetime('validation_date')->nullable();
            $table->enum('payment_method', ['cash', 'cheque', 'transfer', 'mobile_money'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->unique();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index pour les recherches fréquentes
            $table->index(['student_id', 'school_year_id']);
            $table->index(['fee_type', 'status']);
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentary_fees');
    }
};