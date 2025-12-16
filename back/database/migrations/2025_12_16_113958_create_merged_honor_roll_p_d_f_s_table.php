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
        Schema::create('merged_honor_roll_p_d_f_s', function (Blueprint $table) {
            $table->id();

            // Références aux filtres utilisés pour générer (sans contraintes FK)
            $table->unsignedBigInteger('trimester_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('level_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('series_id')->nullable();

            // Informations sur le fichier
            $table->string('file_path');
            $table->string('filename');
            $table->integer('certificate_count')->default(0);
            $table->bigInteger('file_size')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merged_honor_roll_p_d_f_s');
    }
};
