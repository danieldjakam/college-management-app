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
        Schema::create('bus_ticket_batches', function (Blueprint $table) {
            $table->id();
            $table->date('batch_date'); // Date de génération du lot
            $table->enum('ticket_type', ['aller', 'retour']); // Type de ticket
            $table->integer('quantity_generated')->default(0); // Nombre généré
            $table->integer('quantity_sold')->default(0); // Nombre vendu
            $table->integer('quantity_remaining')->default(0); // Nombre restant
            $table->decimal('price_per_ticket', 10, 2); // Prix unitaire
            $table->string('batch_prefix', 20); // Préfixe (ex: T-A, T-R)
            $table->integer('start_number'); // Numéro de début (ex: 001)
            $table->integer('end_number'); // Numéro de fin (ex: 030)
            $table->foreignId('generated_by')->constrained('users'); // Qui a généré
            $table->boolean('is_active')->default(true); // Actif ou non
            $table->text('notes')->nullable(); // Notes optionnelles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_ticket_batches');
    }
};
