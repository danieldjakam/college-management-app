<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_manual_discounts')) {
            return;
        }

        Schema::create('student_manual_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('created_by')->nullable();

            // Montant fixe à réduire sur la scolarité (jamais sur l'inscription)
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reason', 1000)->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'school_year_id']);
            $table->index(['school_year_id']);

            // FKs (si contraintes disponibles)
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_manual_discounts');
    }
};
