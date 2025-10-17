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
        Schema::create('bus_ticket_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('bus_ticket_batches')->onDelete('cascade'); // Lien vers le lot
            $table->string('ticket_number', 50); // Numéro du ticket (ex: T-A-001)
            $table->foreignId('student_id')->constrained('students'); // Élève
            $table->enum('ticket_type', ['aller', 'retour']); // Type de ticket
            $table->decimal('price', 10, 2); // Prix payé
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'other'])->default('cash'); // Méthode de paiement
            $table->foreignId('sold_by')->constrained('users'); // Qui a vendu
            $table->timestamp('sold_at'); // Date et heure de vente
            $table->string('qr_code_data', 500)->nullable(); // Données du QR code pour validation
            $table->boolean('is_used')->default(false); // Ticket utilisé ou non
            $table->timestamp('used_at')->nullable(); // Quand il a été utilisé
            $table->text('notes')->nullable(); // Notes optionnelles
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index('ticket_number');
            $table->index('batch_id');
            $table->index('student_id');
            $table->index(['batch_id', 'is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_ticket_sales');
    }
};
