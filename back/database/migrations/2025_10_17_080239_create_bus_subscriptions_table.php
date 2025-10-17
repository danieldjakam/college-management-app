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
        Schema::create('bus_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->integer('start_month'); // Mois de début (1-12)
            $table->integer('start_year'); // Année de début
            $table->integer('months_count')->default(1); // Nombre de mois achetés
            $table->decimal('monthly_price', 10, 2); // Prix mensuel au moment de l'achat
            $table->decimal('total_amount', 10, 2); // Montant total payé
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->date('purchase_date'); // Date d'achat
            $table->foreignId('purchased_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a enregistré l'achat
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['student_id', 'start_year', 'start_month']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_subscriptions');
    }
};
