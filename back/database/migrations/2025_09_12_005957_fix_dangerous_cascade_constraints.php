<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration corrige les contraintes CASCADE dangereuses qui supprimaient 
     * automatiquement des données importantes lors de la suppression d'utilisateurs.
     */
    public function up(): void
    {
        // Fixer les contraintes dans la table payments (paiements)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by_user_id']);
                    $table->foreign('created_by_user_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('payments_created_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table documentary_fees (frais de dossiers)
        if (Schema::hasTable('documentary_fees')) {
            Schema::table('documentary_fees', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by_user_id']);
                    $table->foreign('created_by_user_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('documentary_fees_created_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table tasks (tâches)
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('tasks_created_by_safe');
                          
                    $table->dropForeign(['assigned_to']);
                    $table->foreign('assigned_to')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('tasks_assigned_to_safe');
                          
                    $table->dropForeign(['assigned_by']);
                    $table->foreign('assigned_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('tasks_assigned_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si les contraintes n'existent pas
                }
            });
        }

        // Fixer les contraintes dans la table supervisor_class_assignments
        if (Schema::hasTable('supervisor_class_assignments')) {
            Schema::table('supervisor_class_assignments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['supervisor_id']);
                    $table->foreign('supervisor_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('supervisor_assignments_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table teacher_attendances
        if (Schema::hasTable('teacher_attendances')) {
            Schema::table('teacher_attendances', function (Blueprint $table) {
                try {
                    $table->dropForeign(['supervisor_id']);
                    $table->foreign('supervisor_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('teacher_attendances_supervisor_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table staff_attendances
        if (Schema::hasTable('staff_attendances')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                try {
                    // user_id peut rester cascade car c'est leur propre présence
                    // supervisor_id doit être SET NULL
                    $table->dropForeign(['supervisor_id']);
                    $table->foreign('supervisor_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('staff_attendances_supervisor_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table attendances (présences étudiants)
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                try {
                    $table->dropForeign(['supervisor_id']);
                    $table->foreign('supervisor_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('attendances_supervisor_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table document_folders
        if (Schema::hasTable('document_folders')) {
            Schema::table('document_folders', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('document_folders_created_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table documents
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['uploaded_by']);
                    $table->foreign('uploaded_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('documents_uploaded_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table employees_payroll
        if (Schema::hasTable('employees_payroll')) {
            Schema::table('employees_payroll', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                    $table->foreign('user_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('employees_payroll_user_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table salary_cuts
        if (Schema::hasTable('salary_cuts')) {
            Schema::table('salary_cuts', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null')
                          ->name('salary_cuts_created_by_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Fixer les contraintes dans la table document_permissions
        if (Schema::hasTable('document_permissions')) {
            Schema::table('document_permissions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                    $table->foreign('user_id')
                          ->references('id')
                          ->on('users')
                          ->onDelete('cascade') // Ici cascade est OK car ce sont juste des permissions
                          ->name('document_permissions_user_safe');
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Dans un environnement de production, il est recommandé de ne PAS
        // reverser cette migration car elle protège l'intégrité des données.
        // Les anciennes contraintes CASCADE étaient dangereuses.
        
        // Si vraiment nécessaire, vous pouvez décommenter et adapter le code ci-dessous
        // mais cela remettra les contraintes dangereuses en place.
        
        /*
        // Remettre les anciennes contraintes CASCADE (NON RECOMMANDÉ)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['created_by_user_id']);
                $table->foreign('created_by_user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            });
        }
        
        // ... répéter pour les autres tables si nécessaire
        */
    }
};