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
        Schema::create('evaluation_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade');
            $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
            $table->enum('evaluation_mode', ['1ds_1comp', '2ds_1comp']);
            $table->decimal('ds1_percentage', 5, 2);
            $table->decimal('ds2_percentage', 5, 2)->default(0);
            $table->decimal('composition_percentage', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index pour la recherche
            $table->index(['school_year_id', 'level_id']);
            
            // Contrainte : une seule config active par niveau/année
            $table->unique(['school_year_id', 'level_id', 'is_active'], 'unique_active_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_configs');
    }
};
