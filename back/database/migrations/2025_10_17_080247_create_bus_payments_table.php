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
        Schema::create('bus_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_subscription_id')->constrained('bus_subscriptions')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Montant payé
            $table->date('payment_date'); // Date du paiement
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'other'])->default('cash');
            $table->string('transaction_reference')->nullable(); // Référence de transaction
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null'); // Qui a reçu le paiement
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index
            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_payments');
    }
};
