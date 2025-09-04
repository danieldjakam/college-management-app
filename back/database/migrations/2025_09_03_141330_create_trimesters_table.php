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
        Schema::create('trimesters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "1er Trimestre", "2ème Trimestre", "3ème Trimestre"
            $table->integer('number'); // 1, 2, 3
            $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index(['school_year_id', 'number']);
            $table->index(['is_current', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trimesters');
    }
};