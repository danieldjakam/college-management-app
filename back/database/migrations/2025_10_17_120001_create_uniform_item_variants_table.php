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
        Schema::create('uniform_item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uniform_item_id')->constrained('uniform_items')->onDelete('cascade');
            $table->string('size'); // XS, S, M, L, XL, XXL
            $table->decimal('price_adjustment', 10, 2)->default(0); // Ajustement de prix par taille
            $table->string('sku', 50)->unique(); // Ex: SPORT-001-M
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0); // Quantité réservée (commandes en attente)
            $table->integer('available_quantity')->virtualAs('stock_quantity - reserved_quantity'); // Stock disponible
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_item_variants');
    }
};
