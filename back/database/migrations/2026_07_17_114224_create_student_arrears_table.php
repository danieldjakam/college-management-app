<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_arrears', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('source_school_year_id');
            $table->unsignedBigInteger('target_school_year_id');
            $table->decimal('total_required', 12, 2)->comment('Montant total requis annee source');
            $table->decimal('total_paid', 12, 2)->comment('Montant total paye annee source');
            $table->decimal('arrear_amount', 12, 2)->comment('Montant impaye (dette)');
            $table->string('source_class_name')->nullable()->comment('Classe de l\'eleve annee source');
            $table->enum('status', ['pending', 'partially_paid', 'paid', 'waived'])->default('pending');
            $table->decimal('amount_paid_on_arrear', 12, 2)->default(0)->comment('Montant deja rembourse sur cette dette');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'source_school_year_id'], 'unique_student_arrear_per_year');
            $table->index(['target_school_year_id', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_arrears');
    }
};
