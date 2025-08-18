<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use Carbon\Carbon;

class TestMultipleEntries extends Command
{
    protected $signature = 'test:multiple-entries {user_id}';
    protected $description = 'Tester le système de multiples entrées/sorties';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("Utilisateur avec ID {$userId} non trouvé");
            return 1;
        }

        $schoolYear = SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) {
            $this->error("Aucune année scolaire active");
            return 1;
        }

        $today = Carbon::now()->toDateString();
        
        // Trouver un superviseur valide
        $supervisor = User::whereIn('role', ['admin', 'surveillant_general'])->first();
        if (!$supervisor) {
            $this->error("Aucun superviseur trouvé");
            return 1;
        }

        $this->info("Test des multiples entrées/sorties pour {$user->name}");
        $this->info("Date: {$today}");
        $this->info("Superviseur: {$supervisor->name}");
        
        // Simuler les mouvements de John comme dans votre exemple
        $movements = [
            ['time' => '08:15', 'type' => 'entry'],
            ['time' => '09:00', 'type' => 'exit'],
            ['time' => '10:00', 'type' => 'entry'],
            ['time' => '11:00', 'type' => 'exit'],
        ];

        // Nettoyer les anciens enregistrements de test
        StaffAttendance::where('user_id', $userId)
            ->where('attendance_date', $today)
            ->delete();

        $this->info("\nCréation des mouvements de test...");
        
        foreach ($movements as $movement) {
            $dateTime = Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . $movement['time']);
            
            StaffAttendance::create([
                'user_id' => $userId,
                'supervisor_id' => $supervisor->id,
                'school_year_id' => $schoolYear->id,
                'attendance_date' => $today,
                'scanned_at' => $dateTime,
                'is_present' => $movement['type'] === 'entry',
                'event_type' => $movement['type'],
                'staff_type' => 'teacher',
                'late_minutes' => $movement['type'] === 'entry' && $movement['time'] === '08:15' ? 15 : 0
            ]);
            
            $this->info("{$movement['time']} - {$user->name} " . ($movement['type'] === 'entry' ? 'ENTRE' : 'SORT'));
        }

        // Calculer le temps de travail total
        $totalWorkTime = StaffAttendance::calculateDailyWorkTime($userId, $today);
        
        $this->info("\n📊 RÉSULTATS:");
        $this->info("Temps total travaillé: {$totalWorkTime}h");
        $this->info("Détail: 45min (08:15-09:00) + 1h (10:00-11:00) = 1h45 = 1.75h");
        
        // Afficher tous les mouvements
        $movements = StaffAttendance::getDailyMovements($userId, $today);
        $this->info("\n📋 MOUVEMENTS DU JOUR:");
        
        foreach ($movements as $movement) {
            $this->info("  {$movement['time']} - {$movement['type_label']}" . 
                       ($movement['late_minutes'] > 0 ? " (Retard: {$movement['late_minutes']}min)" : ""));
        }
        
        return 0;
    }
}