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
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_scholarship_id')->constrained('class_scholarships')->onDelete('cascade');
            $table->foreignId('payment_tranche_id')->constrained('payment_tranches')->onDelete('cascade');
            $table->boolean('is_used')->default(false)->comment('Si la bourse a été utilisée dans un paiement');
            $table->dateTime('used_at')->nullable()->comment('Date d\'utilisation de la bourse');
            $table->decimal('amount_used', 10, 2)->default(0)->comment('Montant de bourse utilisé');
            $table->text('notes')->nullable()->comment('Notes sur l\'attribution de la bourse');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['student_id', 'payment_tranche_id']);
            $table->index(['student_id', 'is_used']);
            
            // Contrainte : un étudiant ne peut avoir qu'une seule bourse par tranche
            $table->unique(['student_id', 'payment_tranche_id'], 'unique_student_tranche_scholarship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_scholarships');
    }
};
