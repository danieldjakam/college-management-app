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
        Schema::create('uniform_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uniform_order_id')->constrained('uniform_orders')->onDelete('cascade');
            $table->string('receipt_number', 50)->unique(); // Ex: REC-UNIF-2025-0001
            $table->foreignId('student_id')->constrained('students');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check', 'other'])->default('cash');
            $table->string('payment_reference')->nullable(); // Référence de transaction
            $table->foreignId('processed_by')->constrained('users'); // Comptable qui a traité
            $table->timestamp('sale_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_sales');
    }
};
