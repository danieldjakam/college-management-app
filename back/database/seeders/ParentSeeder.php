<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\ParentNotification;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Créer des parents de test
        $parent1 = ParentGuardian::create([
            'first_name' => 'Marie',
            'last_name' => 'DUPONT',
            'email' => 'marie.dupont@example.com',
            'phone' => '6123456789',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('1234'),
            'address' => 'Akwa, Douala',
            'profession' => 'Infirmière',
            'emergency_contact' => '6987654321',
            'is_active' => true
        ]);

        $parent2 = ParentGuardian::create([
            'first_name' => 'Jean',
            'last_name' => 'MARTIN',
            'email' => 'jean.martin@example.com', 
            'phone' => '6234567890',
            'password' => Hash::make('martin2024'),
            'pin_code' => Hash::make('5678'),
            'address' => 'Bonanjo, Douala',
            'profession' => 'Ingénieur',
            'emergency_contact' => '6876543210',
            'is_active' => true
        ]);

        $parent3 = ParentGuardian::create([
            'first_name' => 'Alice',
            'last_name' => 'NGUYEN',
            'email' => 'alice.nguyen@example.com',
            'phone' => '6345678901',
            'password' => Hash::make('alice456'),
            'pin_code' => Hash::make('9999'),
            'address' => 'Bonapriso, Douala',
            'profession' => 'Enseignante',
            'emergency_contact' => '6765432109',
            'is_active' => true
        ]);

        // Récupérer quelques étudiants existants pour créer des relations
        $students = Student::limit(6)->get();
        
        if ($students->count() >= 3) {
            // Associer des enfants aux parents
            
            // Parent 1 (Marie) - 2 enfants
            $parent1->children()->attach($students[0]->id, [
                'relationship_type' => 'mother',
                'is_primary_contact' => true,
                'can_pick_up' => true,
                'emergency_contact' => true
            ]);

            $parent1->children()->attach($students[1]->id, [
                'relationship_type' => 'mother',
                'is_primary_contact' => true,
                'can_pick_up' => true,
                'emergency_contact' => true
            ]);

            // Parent 2 (Jean) - 1 enfant
            $parent2->children()->attach($students[2]->id, [
                'relationship_type' => 'father',
                'is_primary_contact' => true,
                'can_pick_up' => true,
                'emergency_contact' => true
            ]);

            // Parent 3 (Alice) - 2 enfants
            if ($students->count() >= 5) {
                $parent3->children()->attach($students[3]->id, [
                    'relationship_type' => 'mother',
                    'is_primary_contact' => true,
                    'can_pick_up' => true,
                    'emergency_contact' => false
                ]);

                $parent3->children()->attach($students[4]->id, [
                    'relationship_type' => 'mother',
                    'is_primary_contact' => false,
                    'can_pick_up' => true,
                    'emergency_contact' => true
                ]);
            }
        }

        // Créer quelques notifications de test
        if ($students->count() > 0) {
            // Notifications pour Marie DUPONT
            ParentNotification::create([
                'parent_id' => $parent1->id,
                'student_id' => $students[0]->id ?? null,
                'type' => 'attendance',
                'priority' => 'urgent',
                'title' => 'Absence non justifiée',
                'message' => 'Votre enfant ' . ($students[0]->first_name ?? 'Jean') . ' était absent aujourd\'hui sans justification. Veuillez contacter l\'établissement.',
                'data' => json_encode(['date' => now()->format('Y-m-d'), 'subject' => 'Mathématiques']),
                'is_read' => false
            ]);

            ParentNotification::create([
                'parent_id' => $parent1->id,
                'student_id' => $students[1]->id ?? null,
                'type' => 'grade',
                'priority' => 'normal',
                'title' => 'Nouvelle note disponible',
                'message' => 'Une nouvelle note de Français a été saisie pour ' . ($students[1]->first_name ?? 'Sophie') . '. Note: 15/20',
                'data' => json_encode(['subject' => 'Français', 'grade' => 15, 'max_grade' => 20]),
                'is_read' => false
            ]);

            // Notifications pour Jean MARTIN
            ParentNotification::create([
                'parent_id' => $parent2->id,
                'student_id' => $students[2]->id ?? null,
                'type' => 'behavior',
                'priority' => 'high',
                'title' => 'Comportement exemplaire',
                'message' => 'Félicitations ! ' . ($students[2]->first_name ?? 'Paul') . ' a été félicité pour son excellent comportement cette semaine.',
                'data' => json_encode(['week' => now()->weekOfYear, 'teacher' => 'Mme TANGA']),
                'is_read' => true,
                'read_at' => now()->subHour()
            ]);

            // Notification générale
            ParentNotification::create([
                'parent_id' => $parent1->id,
                'student_id' => null,
                'type' => 'general',
                'priority' => 'normal',
                'title' => 'Réunion parents-professeurs',
                'message' => 'La réunion parents-professeurs aura lieu le 15 septembre 2024 à 14h en salle de conférence.',
                'data' => json_encode(['date' => '2024-09-15', 'time' => '14:00', 'location' => 'Salle de conférence']),
                'is_read' => false
            ]);

            // Notification de paiement
            ParentNotification::create([
                'parent_id' => $parent3->id,
                'student_id' => $students[3]->id ?? null,
                'type' => 'payment',
                'priority' => 'urgent',
                'title' => 'Rappel de paiement',
                'message' => 'Le paiement de la scolarité pour le mois de septembre est en retard. Montant dû: 75 000 FCFA',
                'data' => json_encode(['amount' => 75000, 'currency' => 'FCFA', 'due_date' => '2024-09-10']),
                'is_read' => false
            ]);
        }

        $this->command->info('✅ 3 parents créés avec succès :');
        $this->command->info('1. Marie DUPONT - Email: marie.dupont@example.com - Mot de passe: password123 - PIN: 1234');
        $this->command->info('2. Jean MARTIN - Email: jean.martin@example.com - Mot de passe: martin2024 - PIN: 5678');
        $this->command->info('3. Alice NGUYEN - Email: alice.nguyen@example.com - Mot de passe: alice456 - PIN: 9999');
        $this->command->info('📱 Tu peux tester la connexion avec email/téléphone + mot de passe/PIN');
    }
}