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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom du département (ex: Mathématiques, SVT, Français)
            $table->string('code', 10)->unique(); // Code court (ex: MATH, SVT, FR)
            $table->text('description')->nullable(); // Description du département
            $table->string('color', 7)->default('#6c757d'); // Couleur pour l'interface (hex color)
            $table->foreignId('head_teacher_id')->nullable()->constrained('teachers')->onDelete('set null'); // Chef de département
            $table->boolean('is_active')->default(true); // Département actif/inactif
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['is_active']);
            $table->index(['order']);
            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
