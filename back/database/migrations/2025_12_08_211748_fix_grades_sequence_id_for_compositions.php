<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ✅ CORRECTION: Mettre à jour grades.sequence_id=NULL pour toutes les notes de compositions
     *
     * PROBLÈME IDENTIFIÉ:
     * - evaluations.sequence_id a été corrigé (1402 compositions avec sequence_id=NULL) ✅
     * - MAIS grades.sequence_id contient encore des valeurs NON-NULL pour les compositions ❌
     *
     * IMPACT:
     * - Les bulletins affichent des doublons (Compo = Seq2) car le système cherche les notes
     *   avec sequence_id=2, et trouve les notes de compositions qui ont grade.sequence_id=2
     *
     * SOLUTION:
     * - Mettre à jour grades.sequence_id=NULL pour toutes les notes liées à des évaluations
     *   de type 'composition'
     */
    public function up(): void
    {
        echo "🔄 Correction des notes de compositions dans la table grades...\n";

        // Compter avant correction
        $beforeCount = DB::table('grades as g')
            ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
            ->where('e.type', 'composition')
            ->whereNotNull('g.sequence_id')
            ->count();

        echo "❌ Grades de compositions avec sequence_id NON-NULL: $beforeCount\n";

        // ÉTAPE 1: Modifier le schéma de la colonne sequence_id pour autoriser NULL
        echo "📝 Modification du schéma: grades.sequence_id → nullable\n";
        Schema::table('grades', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_id')->nullable()->change();
        });

        // ÉTAPE 2: Mettre à jour grades.sequence_id=NULL pour toutes les notes de compositions
        echo "🔄 Mise à jour des notes de compositions...\n";
        DB::statement("
            UPDATE grades g
            INNER JOIN evaluations e ON g.evaluation_id = e.id
            SET g.sequence_id = NULL
            WHERE e.type = 'composition'
        ");

        // Compter après correction
        $afterCount = DB::table('grades as g')
            ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
            ->where('e.type', 'composition')
            ->whereNull('g.sequence_id')
            ->count();

        echo "✅ $afterCount notes de compositions corrigées (sequence_id=NULL)\n";

        // Vérification finale
        $remaining = DB::table('grades as g')
            ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
            ->where('e.type', 'composition')
            ->whereNotNull('g.sequence_id')
            ->count();

        if ($remaining > 0) {
            echo "⚠️  ATTENTION: Il reste encore $remaining notes de compositions avec sequence_id NON-NULL\n";
        } else {
            echo "🎉 SUCCÈS TOTAL: Toutes les notes de compositions ont maintenant sequence_id=NULL\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne peut pas restaurer les anciennes valeurs de sequence_id
        // car on ne les a pas sauvegardées
        echo "⚠️  IMPOSSIBLE DE RESTAURER: Les anciennes valeurs de sequence_id n'ont pas été sauvegardées\n";
    }
};
