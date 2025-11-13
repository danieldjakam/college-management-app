<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subject_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A, B, C, D
            $table->string('name'); // Nom du groupe (ex: MATIÈRES LITTÉRAIRES)
            $table->string('name_en')->nullable(); // Nom anglais
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insérer les groupes par défaut
        DB::table('subject_groups')->insert([
            [
                'code' => 'A',
                'name' => 'MATIÈRES LITTÉRAIRES',
                'name_en' => 'LITERARY SUBJECTS',
                'description' => 'Groupe A: Français, Anglais, Histoire, Géographie, etc.',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'B',
                'name' => 'MATIÈRES SCIENTIFIQUES',
                'name_en' => 'SCIENTIFIC SUBJECTS',
                'description' => 'Groupe B: Mathématiques, Physique, Chimie, SVT',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'C',
                'name' => 'MATIÈRES PRATIQUES',
                'name_en' => 'PRACTICAL SUBJECTS',
                'description' => 'Groupe C: Informatique, EPS, Travaux manuels, Arts',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'D',
                'name' => 'AUTRES MATIÈRES',
                'name_en' => 'OTHER SUBJECTS',
                'description' => 'Groupe D: Éducation civique, Culture nationale, etc.',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_groups');
    }
};
