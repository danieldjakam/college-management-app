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
        Schema::create('uniform_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Tenue de sport, Tenue de mercredi
            $table->string('code', 20)->unique(); // Ex: SPORT-001, MERCREDI-001
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2); // Prix de base
            $table->string('category')->nullable(); // sport, mercredi, autre
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('reorder_level')->default(10); // Seuil d'alerte stock
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_items');
    }
};
