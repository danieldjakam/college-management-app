<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\SchoolClass;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer quelques classes pour les emplois du temps
        $classes = SchoolClass::limit(3)->get();
        
        if ($classes->isEmpty()) {
            $this->command->info('Aucune classe trouvée. Veuillez d\'abord créer des classes.');
            return;
        }

        $subjects = [
            'Mathématiques',
            'Français',
            'Anglais',
            'Histoire-Géographie',
            'Sciences Physiques',
            'SVT',
            'EPS',
            'Informatique',
            'Arts Plastiques',
            'Musique'
        ];

        $teachers = [
            'M. Dupont',
            'Mme Martin',
            'M. Bernard',
            'Mme Leroy',
            'M. Moreau',
            'Mme Simon',
            'M. Laurent',
            'Mme Michel'
        ];

        $rooms = ['A101', 'A102', 'A103', 'B201', 'B202', 'B203', 'C301', 'C302', 'Gymnase', 'Labo'];

        $timeSlots = [
            ['08:00', '09:00'],
            ['09:00', '10:00'],
            ['10:00', '11:00'],
            ['11:00', '12:00'],
            ['14:00', '15:00'],
            ['15:00', '16:00'],
            ['16:00', '17:00']
        ];

        foreach ($classes as $class) {
            $this->command->info("Création de l'emploi du temps pour la classe: {$class->name}");
            
            // Pour chaque jour de la semaine (Lundi = 1 à Samedi = 6)
            for ($day = 1; $day <= 6; $day++) {
                // Samedi - seulement le matin
                $daySlots = ($day == 6) ? array_slice($timeSlots, 0, 4) : $timeSlots;
                
                // Créer 3-4 cours par jour
                $numCourses = ($day == 6) ? rand(2, 3) : rand(3, 5);
                $selectedSlots = array_rand($daySlots, min($numCourses, count($daySlots)));
                
                if (!is_array($selectedSlots)) {
                    $selectedSlots = [$selectedSlots];
                }
                
                foreach ($selectedSlots as $slotIndex) {
                    $slot = $daySlots[$slotIndex];
                    
                    Schedule::create([
                        'class_id' => $class->id,
                        'day_of_week' => $day,
                        'start_time' => $slot[0],
                        'end_time' => $slot[1],
                        'subject' => $subjects[array_rand($subjects)],
                        'teacher_name' => $teachers[array_rand($teachers)],
                        'room' => $rooms[array_rand($rooms)],
                        'academic_year' => '2024-2025'
                    ]);
                }
            }
        }

        $this->command->info('Emplois du temps créés avec succès!');
    }
}