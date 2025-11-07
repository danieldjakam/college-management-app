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
        // Étape 0 : Modifier l'ENUM pour ajouter 'sequence' et 'ds'
        DB::statement("
            ALTER TABLE evaluations
            MODIFY COLUMN type ENUM('interrogation','devoir','composition','tp','controle','sequence','ds') NOT NULL
        ");

        echo "✅ Colonne 'type' modifiée pour accepter 'sequence' et 'ds'\n";

        // Étape 1 : Identifier et marquer les SÉQUENCES (Sequence 1, Sequence 2, seq1, seq2, etc.)
        $sequencesUpdated = DB::table('evaluations')
            ->where('type', 'composition')
            ->where(function($query) {
                $query->where('name', 'REGEXP', '(?i)(séquence|sequence|seq)[[:space:]]*[0-9]')
                      ->orWhere('name', 'REGEXP', '(?i)^seq[0-9]')
                      ->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%séquence%')
                      ->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%sequence%');
            })
            ->update(['type' => 'sequence']);

        echo "✅ {$sequencesUpdated} séquences identifiées et marquées comme type='sequence'\n";

        // Étape 2 : Compter les VRAIES COMPOSITIONS (celles qui contiennent "composition" dans le nom)
        $compositionsCount = DB::table('evaluations')
            ->where('type', 'composition')
            ->where(function($query) {
                $query->where('name', 'REGEXP', '(?i)composition[[:space:]]*[0-9]')
                      ->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%composition%')
                      ->orWhere(DB::raw('LOWER(name)'), 'LIKE', '%compo%');
            })
            ->count();

        echo "✅ {$compositionsCount} vraies compositions gardées comme type='composition'\n";

        // Étape 3 : Les autres évaluations (DS, Devoirs, Evaluations) → type='ds'
        $dsUpdated = DB::table('evaluations')
            ->where('type', 'composition')
            ->where('name', 'NOT REGEXP', '(?i)composition[[:space:]]*[0-9]')
            ->where(DB::raw('LOWER(name)'), 'NOT LIKE', '%composition%')
            ->where(DB::raw('LOWER(name)'), 'NOT LIKE', '%compo%')
            ->update(['type' => 'ds']);

        echo "✅ {$dsUpdated} autres évaluations (DS, Devoirs, etc.) marquées comme type='ds'\n";

        // Statistiques finales
        $sequences = DB::table('evaluations')->where('type', 'sequence')->count();
        $compositions = DB::table('evaluations')->where('type', 'composition')->count();
        $ds = DB::table('evaluations')->where('type', 'ds')->count();
        $tp = DB::table('evaluations')->where('type', 'tp')->count();
        $interrogation = DB::table('evaluations')->where('type', 'interrogation')->count();
        $devoir = DB::table('evaluations')->where('type', 'devoir')->count();
        $controle = DB::table('evaluations')->where('type', 'controle')->count();

        echo "\n📊 STATISTIQUES FINALES :\n";
        echo "   - Séquences (sequence) : {$sequences}\n";
        echo "   - Compositions (composition) : {$compositions}\n";
        echo "   - DS/Devoirs (ds) : {$ds}\n";
        echo "   - TP (tp) : {$tp}\n";
        echo "   - Interrogation : {$interrogation}\n";
        echo "   - Devoir : {$devoir}\n";
        echo "   - Contrôle : {$controle}\n";
        echo "   - TOTAL : " . ($sequences + $compositions + $ds + $tp + $interrogation + $devoir + $controle) . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre tous les types à leur état d'origine
        DB::table('evaluations')
            ->whereIn('type', ['sequence', 'ds'])
            ->update(['type' => 'composition']);

        echo "✅ Types 'sequence' et 'ds' remis à 'composition'\n";

        // Retirer 'sequence' et 'ds' de l'ENUM
        DB::statement("
            ALTER TABLE evaluations
            MODIFY COLUMN type ENUM('interrogation','devoir','composition','tp','controle') NOT NULL
        ");

        echo "✅ Colonne 'type' restaurée à son état initial\n";
    }
};
