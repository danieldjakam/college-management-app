<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyzeSuspiciousAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:analyze-suspicious
        {--fix : Corriger automatiquement les problèmes détectés}
        {--user-id= : Analyser un utilisateur spécifique}
        {--date= : Analyser une date spécifique (YYYY-MM-DD)}
        {--delete-duplicates : Supprimer les sorties qui ont la même heure que l\'entrée}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyser et corriger les badgeages suspects (entrée/sortie à la même heure)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldFix = $this->option('fix');
        $userId = $this->option('user-id');
        $date = $this->option('date');
        $deleteDuplicates = $this->option('delete-duplicates');

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  ANALYSE DES BADGEAGES SUSPECTS');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if (!$shouldFix && !$deleteDuplicates) {
            $this->warn('MODE ANALYSE SEULEMENT: Aucune modification ne sera appliquée');
            $this->warn('Utilisez --delete-duplicates pour supprimer les doublons');
            $this->newLine();
        }

        // Requête pour trouver les cas suspects
        $query = $this->buildSuspiciousQuery($userId, $date);

        $suspiciousCases = $query->get();

        if ($suspiciousCases->isEmpty()) {
            $this->info('✅ Aucun badgeage suspect trouvé!');
            return 0;
        }

        $this->info("📊 {$suspiciousCases->count()} cas suspect(s) trouvé(s)");
        $this->newLine();

        // Grouper par utilisateur et date
        $groupedCases = $this->groupSuspiciousCases($suspiciousCases);

        $this->displaySuspiciousCases($groupedCases);

        if ($deleteDuplicates) {
            $this->newLine();
            $deleted = $this->deleteSuspiciousExits($groupedCases);
            $this->info("✅ {$deleted} sortie(s) supprimée(s)");
        }

        return 0;
    }

    /**
     * Construire la requête pour trouver les cas suspects
     */
    private function buildSuspiciousQuery($userId = null, $date = null)
    {
        // Sous-requête pour trouver les sorties qui ont exactement la même heure qu'une entrée
        $query = StaffAttendance::select('staff_attendances.*')
            ->where('event_type', 'exit')
            ->whereExists(function($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('staff_attendances as sa2')
                    ->whereColumn('sa2.user_id', 'staff_attendances.user_id')
                    ->whereColumn('sa2.attendance_date', 'staff_attendances.attendance_date')
                    ->where('sa2.event_type', 'entry')
                    ->whereColumn('sa2.scanned_at', 'staff_attendances.scanned_at');
            })
            ->with('user')
            ->orderBy('attendance_date', 'desc')
            ->orderBy('scanned_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($date) {
            $query->where('attendance_date', $date);
        }

        return $query;
    }

    /**
     * Grouper les cas suspects par utilisateur et date
     */
    private function groupSuspiciousCases($suspiciousCases)
    {
        $grouped = [];

        foreach ($suspiciousCases as $case) {
            $key = $case->user_id . '_' . $case->attendance_date;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'user' => $case->user,
                    'date' => $case->attendance_date,
                    'cases' => []
                ];
            }

            $grouped[$key]['cases'][] = $case;
        }

        return $grouped;
    }

    /**
     * Afficher les cas suspects
     */
    private function displaySuspiciousCases($groupedCases)
    {
        $headers = ['Utilisateur', 'Date', 'Heure', 'Type', 'ID', 'Action suggérée'];
        $rows = [];

        foreach ($groupedCases as $group) {
            $userName = $group['user']->name;
            $date = $group['date'];

            foreach ($group['cases'] as $case) {
                $rows[] = [
                    $userName,
                    $date,
                    $case->scanned_at->format('H:i:s'),
                    $case->event_type === 'entry' ? 'Entrée' : 'Sortie',
                    $case->id,
                    '🗑️ Supprimer la sortie'
                ];
            }

            // Ajouter la ligne d'entrée correspondante pour contexte
            $entry = StaffAttendance::where('user_id', $group['user']->id)
                ->where('attendance_date', $date)
                ->where('event_type', 'entry')
                ->where('scanned_at', $group['cases'][0]->scanned_at)
                ->first();

            if ($entry) {
                $rows[] = [
                    '↳ ' . $userName,
                    $date,
                    $entry->scanned_at->format('H:i:s'),
                    'Entrée',
                    $entry->id,
                    '✓ Garder l\'entrée'
                ];
            }

            $rows[] = ['---', '---', '---', '---', '---', '---'];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('💡 Explication:');
        $this->line('Ces cas montrent des sorties qui ont exactement la même heure que l\'entrée.');
        $this->line('Cela indique probablement une erreur de badgeage (double scan rapide).');
        $this->line('La solution recommandée est de supprimer les sorties et garder uniquement les entrées.');
    }

    /**
     * Supprimer les sorties suspectes
     */
    private function deleteSuspiciousExits($groupedCases)
    {
        $deleted = 0;

        DB::beginTransaction();

        try {
            foreach ($groupedCases as $group) {
                foreach ($group['cases'] as $case) {
                    // Supprimer la sortie
                    $case->delete();
                    $deleted++;

                    Log::info('Sortie suspecte supprimée', [
                        'id' => $case->id,
                        'user_id' => $case->user_id,
                        'user_name' => $group['user']->name,
                        'date' => $case->attendance_date,
                        'time' => $case->scanned_at->format('H:i:s')
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Erreur lors de la suppression: ' . $e->getMessage());
            return 0;
        }

        return $deleted;
    }
}
