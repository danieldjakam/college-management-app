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
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "1ère Séquence", "2ème Séquence"
            $table->integer('number'); // 1, 2, 3, 4, 5, 6
            $table->foreignId('trimester_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index(['trimester_id', 'number']);
            $table->index(['school_year_id', 'number']);
            $table->index(['is_current', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};