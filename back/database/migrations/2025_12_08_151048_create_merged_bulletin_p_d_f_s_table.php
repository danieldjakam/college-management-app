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
        Schema::create('merged_bulletin_p_d_f_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_series_id')->constrained('class_series')->onDelete('cascade');
            $table->string('period_type'); // 'sequence' ou 'trimester'
            $table->string('period_identifier'); // 'seq1', 'trim2', etc.
            $table->string('file_path');
            $table->string('filename');
            $table->integer('bulletin_count')->default(0);
            $table->bigInteger('file_size')->nullable(); // Taille en bytes
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['class_series_id', 'period_type', 'period_identifier'], 'merged_bulletins_search_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merged_bulletin_p_d_f_s');
    }
};
