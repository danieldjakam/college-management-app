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
        Schema::create('bus_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_price', 10, 2)->default(0); // Prix mensuel du bus
            $table->boolean('is_active')->default(true); // Service de bus actif ou non
            $table->text('description')->nullable(); // Description/notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_settings');
    }
};
