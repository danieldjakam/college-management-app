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
        Schema::create('uniform_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique(); // Ex: ORD-2025-0001
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('status', ['pending', 'ready', 'fulfilled', 'cancelled'])->default('pending');
            // pending: En attente de disponibilité
            // ready: Disponible pour retrait
            // fulfilled: Retiré et payé
            // cancelled: Annulé
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('placed_by')->nullable()->constrained('users'); // Qui a placé la commande
            $table->timestamp('placed_at')->useCurrent();
            $table->foreignId('ready_by')->nullable()->constrained('users'); // Qui a marqué comme prêt
            $table->timestamp('ready_at')->nullable();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users'); // Qui a traité le retrait
            $table->timestamp('fulfilled_at')->nullable();
            $table->boolean('student_notified')->default(false); // Élève notifié pour retrait?
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_orders');
    }
};
