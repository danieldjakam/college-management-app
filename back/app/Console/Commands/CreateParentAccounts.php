<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\ParentGuardian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateParentAccounts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'parents:create-accounts {--dry-run : Exécuter en mode test sans créer les comptes}';

    /**
     * The description of the console command.
     */
    protected $description = 'Créer des comptes parents automatiquement basés sur les numéros de téléphone des élèves avec PIN 1234 par défaut';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Mode DRY-RUN activé - Aucun compte ne sera créé');
        }

        $this->info('🚀 Début de la création des comptes parents...');
        
        // Récupérer tous les élèves avec des numéros de téléphone parents
        $students = Student::whereNotNull('parent_phone')
                          ->where('parent_phone', '!=', '')
                          ->orWhere(function($query) {
                              $query->whereNotNull('mother_phone')
                                    ->where('mother_phone', '!=', '');
                          })
                          ->get();

        $this->info("📊 Nombre d'élèves avec numéros parents: {$students->count()}");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($students as $student) {
            try {
                // Traiter le téléphone du père/tuteur principal
                if (!empty($student->parent_phone)) {
                    $result = $this->createParentAccount(
                        $student->parent_phone, 
                        $student->parent_name ?? 'Parent', 
                        $student, 
                        'father',
                        $dryRun
                    );
                    
                    if ($result === 'created') $created++;
                    elseif ($result === 'skipped') $skipped++;
                    elseif ($result === 'error') $errors++;
                }

                // Traiter le téléphone de la mère
                if (!empty($student->mother_phone) && $student->mother_phone !== $student->parent_phone) {
                    $result = $this->createParentAccount(
                        $student->mother_phone, 
                        $student->mother_name ?? 'Mère', 
                        $student, 
                        'mother',
                        $dryRun
                    );
                    
                    if ($result === 'created') $created++;
                    elseif ($result === 'skipped') $skipped++;
                    elseif ($result === 'error') $errors++;
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur pour l'élève {$student->full_name}: " . $e->getMessage());
                $errors++;
            }
        }

        // Afficher le résumé
        $this->newLine();
        $this->info('📈 RÉSUMÉ:');
        $this->line("✅ Comptes créés: $created");
        $this->line("⏭️  Comptes existants (ignorés): $skipped");
        $this->line("❌ Erreurs: $errors");
        
        if ($dryRun) {
            $this->warn('🔍 Mode DRY-RUN - Aucun compte n\'a été réellement créé');
            $this->info('💡 Exécutez sans --dry-run pour créer les comptes');
        } else {
            $this->info('✨ Création des comptes parents terminée!');
        }

        return 0;
    }

    /**
     * Créer un compte parent basé sur un numéro de téléphone
     */
    private function createParentAccount($phone, $name, $student, $relationshipType, $dryRun = false)
    {
        // Nettoyer le numéro de téléphone
        $cleanPhone = $this->cleanPhoneNumber($phone);
        
        if (empty($cleanPhone)) {
            return 'error';
        }

        // Vérifier si un parent avec ce numéro existe déjà
        $existingParent = ParentGuardian::where('phone', $cleanPhone)->first();
        
        if ($existingParent) {
            // Parent existe déjà, créer seulement la relation avec l'enfant
            if (!$dryRun) {
                $this->createParentChildRelationship($existingParent, $student, $relationshipType);
            }
            
            $this->line("⏭️  Parent existe déjà: $cleanPhone -> {$student->full_name}");
            return 'skipped';
        }

        // Extraire prénom et nom depuis le nom complet
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        if (!$dryRun) {
            DB::beginTransaction();
            
            try {
                // Créer le compte parent
                $parent = ParentGuardian::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $cleanPhone . '@parent.cpbd.local', // Email généré basé sur le téléphone
                    'phone' => $cleanPhone,
                    'password' => Hash::make('password123'), // Mot de passe temporaire
                    'pin_code' => Hash::make('1234'), // PIN par défaut
                    'is_active' => true
                ]);

                // Créer la relation parent-enfant
                $this->createParentChildRelationship($parent, $student, $relationshipType);

                DB::commit();
                
                $this->line("✅ Créé: $cleanPhone -> {$student->full_name} ($relationshipType)");
                return 'created';
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Erreur création parent $cleanPhone: " . $e->getMessage());
                return 'error';
            }
        } else {
            $this->line("🔍 [DRY-RUN] Créerait: $cleanPhone -> {$student->full_name} ($relationshipType)");
            return 'created';
        }
    }

    /**
     * Créer la relation parent-enfant
     */
    private function createParentChildRelationship($parent, $student, $relationshipType)
    {
        // Vérifier si la relation existe déjà
        $existingRelation = $parent->children()->where('student_id', $student->id)->first();
        
        if (!$existingRelation) {
            $parent->children()->attach($student->id, [
                'relationship_type' => $relationshipType,
                'is_primary_contact' => $relationshipType === 'father',
                'can_pick_up' => true,
                'emergency_contact' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Nettoyer et valider le numéro de téléphone
     */
    private function cleanPhoneNumber($phone)
    {
        // Supprimer les espaces, tirets, parenthèses
        $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Supprimer les caractères non numériques sauf le +
        $clean = preg_replace('/[^0-9+]/', '', $clean);
        
        // Si commence par +237, le garder, sinon ajouter
        if (!preg_match('/^\+237/', $clean)) {
            // Si commence par 237, ajouter le +
            if (preg_match('/^237/', $clean)) {
                $clean = '+' . $clean;
            }
            // Si commence par 6 ou 2, ajouter +237
            elseif (preg_match('/^[62]/', $clean)) {
                $clean = '+237' . $clean;
            }
        }
        
        // Vérifier que le format final est correct (+237XXXXXXXXX)
        if (preg_match('/^\+237[62]\d{8}$/', $clean)) {
            return $clean;
        }
        
        return null; // Numéro invalide
    }
}