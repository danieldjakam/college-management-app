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
        Schema::create('uniform_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uniform_order_id')->constrained('uniform_orders')->onDelete('cascade');
            $table->foreignId('uniform_item_id')->constrained('uniform_items')->onDelete('cascade');
            $table->foreignId('uniform_item_variant_id')->constrained('uniform_item_variants')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Prix au moment de la commande
            $table->decimal('subtotal', 10, 2); // quantity * unit_price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_order_items');
    }
};
