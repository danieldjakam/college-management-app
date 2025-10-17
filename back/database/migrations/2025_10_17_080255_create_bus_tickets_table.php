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
        Schema::create('bus_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_subscription_id')->constrained('bus_subscriptions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->integer('month'); // Mois du ticket (1-12)
            $table->integer('year'); // Année du ticket
            $table->integer('day_number'); // Numéro du jour (1-31)
            $table->date('ticket_date'); // Date complète du ticket
            $table->string('qr_code_data', 500); // Données du QR code (nom, matricule, date)
            $table->string('ticket_color', 20); // Couleur du ticket pour ce mois
            $table->boolean('is_used')->default(false); // Ticket utilisé ou non
            $table->timestamp('used_at')->nullable(); // Quand le ticket a été utilisé
            $table->timestamps();

            // Index
            $table->index(['student_id', 'year', 'month']);
            $table->index('ticket_date');
            $table->index('is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_tickets');
    }
};
