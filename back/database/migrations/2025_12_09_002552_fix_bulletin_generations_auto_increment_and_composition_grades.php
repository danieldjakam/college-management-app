<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ✅ CORRECTION: Ajouter AUTO_INCREMENT sur bulletin_generations.id
     *
     * PROBLÈME: La colonne id n'a pas AUTO_INCREMENT, provoquant l'erreur:
     * "SQLSTATE[HY000]: General error: 1364 Field 'id' doesn't have a default value"
     *
     * SOLUTION: Modifier la colonne pour ajouter AUTO_INCREMENT
     *
     * ⚠️ SÉCURITÉ: Aucune donnée supprimée, seulement modification de schéma
     *
     * NOTE: Cette migration corrige le schéma de la table bulletin_generations.
     * La correction principale des compositions se trouve dans BulletinService.php
     */
    public function up(): void
    {
        echo "🔄 Vérification et correction de la table bulletin_generations...\n";

        // Vérifier si la table existe
        if (!Schema::hasTable('bulletin_generations')) {
            echo "⚠️  Table bulletin_generations n'existe pas, création...\n";
            Schema::create('bulletin_generations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->string('bulletin_type'); // sequence, trimester
                $table->string('period_identifier'); // seq1, trim1, etc.
                $table->string('file_path');
                $table->timestamp('generated_at');
                $table->timestamps();

                $table->index(['student_id', 'bulletin_type', 'period_identifier']);
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
            echo "✅ Table bulletin_generations créée avec succès\n";
            return;
        }

        // Compter les enregistrements AVANT modification
        $countBefore = DB::table('bulletin_generations')->count();
        echo "📊 Enregistrements AVANT modification: $countBefore\n";

        // Vérifier si la colonne id a déjà AUTO_INCREMENT
        $columnInfo = DB::select("SHOW COLUMNS FROM bulletin_generations WHERE Field = 'id'");
        if (!empty($columnInfo)) {
            $extra = $columnInfo[0]->Extra ?? '';
            if (stripos($extra, 'auto_increment') !== false) {
                echo "✅ La colonne id a déjà AUTO_INCREMENT, aucune modification nécessaire\n";
                return;
            }
        }

        // Modifier la colonne id pour ajouter AUTO_INCREMENT
        echo "🔧 Ajout de AUTO_INCREMENT sur la colonne id...\n";
        try {
            DB::statement('ALTER TABLE bulletin_generations MODIFY COLUMN id bigint(20) unsigned NOT NULL AUTO_INCREMENT');
            echo "✅ AUTO_INCREMENT ajouté avec succès\n";
        } catch (\Exception $e) {
            echo "⚠️  Erreur lors de la modification: " . $e->getMessage() . "\n";
            throw $e;
        }

        // Vérifier après modification
        $countAfter = DB::table('bulletin_generations')->count();
        echo "📊 Enregistrements APRÈS modification: $countAfter\n";

        if ($countBefore === $countAfter) {
            echo "✅ SUCCÈS: Aucune donnée perdue ($countAfter enregistrements)\n";
        } else {
            echo "⚠️  ATTENTION: Nombre d'enregistrements différent!\n";
            echo "   Avant: $countBefore\n";
            echo "   Après: $countAfter\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne peut pas vraiment restaurer AUTO_INCREMENT de manière sûre
        echo "⚠️  ROLLBACK: Retrait de AUTO_INCREMENT (non recommandé)\n";

        if (Schema::hasTable('bulletin_generations')) {
            try {
                DB::statement('ALTER TABLE bulletin_generations MODIFY COLUMN id bigint(20) unsigned NOT NULL');
                echo "✅ AUTO_INCREMENT retiré\n";
            } catch (\Exception $e) {
                echo "⚠️  Erreur lors du rollback: " . $e->getMessage() . "\n";
            }
        }
    }
};
