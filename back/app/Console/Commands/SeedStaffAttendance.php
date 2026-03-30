<?php

namespace App\Console\Commands;

use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SeedStaffAttendance extends Command
{
    protected $signature = 'attendance:seed
        {name : Nom (partiel) de l\'employé à rechercher}
        {--month=3 : Mois (1-12)}
        {--year=2026 : Année}
        {--entry-base=07:00 : Heure d\'entrée de base (HH:MM)}
        {--exit-base=18:00 : Heure de sortie de base (HH:MM)}
        {--vary=30 : Variation max en minutes (+/-)}
        {--dry-run : Simuler sans insérer}';

    protected $description = 'Insérer des données de présence pour un employé (lundi-samedi, tout le mois)';

    public function handle()
    {
        $searchName = $this->argument('name');
        $month = (int) $this->option('month');
        $year = (int) $this->option('year');
        $entryBase = $this->option('entry-base');
        $exitBase = $this->option('exit-base');
        $vary = (int) $this->option('vary');
        $dryRun = $this->option('dry-run');

        // Chercher l'utilisateur
        $users = User::where('name', 'like', "%{$searchName}%")->get();

        if ($users->isEmpty()) {
            $this->error("Aucun utilisateur trouvé avec \"{$searchName}\"");
            return 1;
        }

        if ($users->count() > 1) {
            $this->warn("Plusieurs utilisateurs trouvés :");
            foreach ($users as $u) {
                $this->line("  ID {$u->id} - {$u->name} ({$u->role})");
            }
            $userId = $this->ask('Entrez l\'ID de l\'utilisateur souhaité');
            $user = User::find($userId);
            if (!$user) {
                $this->error("Utilisateur ID {$userId} introuvable");
                return 1;
            }
        } else {
            $user = $users->first();
        }

        $this->info("Employé : {$user->name} (ID: {$user->id}, Rôle: {$user->role})");

        // Année scolaire
        $schoolYear = SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) {
            $this->error("Aucune année scolaire active");
            return 1;
        }
        $this->info("Année scolaire : {$schoolYear->name} (ID: {$schoolYear->id})");

        // Déterminer le staff_type
        $staffType = $this->getStaffType($user);
        $this->info("Type personnel : {$staffType}");

        // Générer les jours (lundi à samedi)
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $days = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            // Lundi=1 ... Samedi=6, Dimanche=0
            if ($current->dayOfWeek >= 1 && $current->dayOfWeek <= 6) {
                $days[] = $current->copy();
            }
            $current->addDay();
        }

        $this->info("Jours à remplir : " . count($days) . " (lun-sam)");

        if ($dryRun) {
            $this->warn("MODE DRY-RUN - Aucune insertion");
        }

        if (!$dryRun && !$this->confirm("Insérer " . (count($days) * 2) . " enregistrements de présence ?")) {
            $this->info("Annulé.");
            return 0;
        }

        // Vérifier les enregistrements existants
        $existing = StaffAttendance::where('user_id', $user->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->count();

        if ($existing > 0) {
            $this->warn("{$existing} enregistrements existants pour ce mois.");
            if (!$dryRun && !$this->confirm("Supprimer les existants et recommencer ?")) {
                $this->info("Annulé.");
                return 0;
            }
            if (!$dryRun) {
                StaffAttendance::where('user_id', $user->id)
                    ->whereYear('attendance_date', $year)
                    ->whereMonth('attendance_date', $month)
                    ->delete();
                $this->info("Anciens enregistrements supprimés.");
            }
        }

        $inserted = 0;
        $table = [];

        foreach ($days as $day) {
            // Varier l'heure d'entrée : entre (base - 30min) et (base + 30min)
            $entryVariation = rand(-$vary, $vary);
            $exitVariation = rand(-15, 15);

            $entryTime = Carbon::parse($day->format('Y-m-d') . ' ' . $entryBase)
                ->addMinutes($entryVariation);

            $exitTime = Carbon::parse($day->format('Y-m-d') . ' ' . $exitBase)
                ->addMinutes($exitVariation);

            // Calculer le retard (référence : 07:30)
            $referenceEntry = Carbon::parse($day->format('Y-m-d') . ' 07:30:00');
            $lateMinutes = 0;
            if ($entryTime->gt($referenceEntry)) {
                $lateMinutes = $entryTime->diffInMinutes($referenceEntry);
            }

            $entryData = [
                'user_id' => $user->id,
                'supervisor_id' => 1,
                'school_year_id' => $schoolYear->id,
                'attendance_date' => $day->format('Y-m-d'),
                'scanned_at' => $entryTime,
                'is_present' => true,
                'event_type' => 'entry',
                'staff_type' => $staffType,
                'late_minutes' => $lateMinutes,
                'notes' => 'Généré par commande attendance:seed',
            ];

            $exitData = [
                'user_id' => $user->id,
                'supervisor_id' => 1,
                'school_year_id' => $schoolYear->id,
                'attendance_date' => $day->format('Y-m-d'),
                'scanned_at' => $exitTime,
                'is_present' => false,
                'event_type' => 'exit',
                'staff_type' => $staffType,
                'late_minutes' => 0,
                'notes' => 'Généré par commande attendance:seed',
            ];

            $table[] = [
                $day->format('D d/m'),
                $entryTime->format('H:i'),
                $exitTime->format('H:i'),
                $lateMinutes > 0 ? "{$lateMinutes} min" : '-',
            ];

            if (!$dryRun) {
                StaffAttendance::create($entryData);
                StaffAttendance::create($exitData);
                $inserted += 2;
            }
        }

        $this->table(['Jour', 'Entrée', 'Sortie', 'Retard'], $table);

        if ($dryRun) {
            $this->warn("DRY-RUN terminé. " . count($days) . " jours simulés.");
        } else {
            $this->info("{$inserted} enregistrements insérés pour {$user->name}.");
        }

        return 0;
    }

    private function getStaffType(User $user): string
    {
        if ($user->role === 'teacher') {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher && $teacher->type_personnel) {
                return $teacher->type_personnel;
            }
            return 'permanent';
        }

        $map = [
            'admin' => 'admin',
            'accountant' => 'accountant',
            'comptable_superieur' => 'accountant',
            'secretaire' => 'secretaire',
            'surveillant_general' => 'supervisor',
            'principal' => 'admin',
            'censeur' => 'admin',
            'bibliothecaire' => 'bibliothecaire',
        ];

        return $map[$user->role] ?? $user->role;
    }
}
