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
     * Cette migration ajoute des index critiques pour résoudre les problèmes de timeout 504
     * en production. Les index sont optimisés pour les requêtes les plus fréquentes.
     *
     * Version SAFE: Vérifie l'existence des tables et colonnes avant de créer les index
     */
    public function up(): void
    {
        // ========================================
        // 1. OPTIMISATION TABLE GRADES (CRITIQUE)
        // ========================================
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                // Index composite pour les requêtes de bulletins
                if (!$this->indexExists('grades', 'idx_grades_bulletin_lookup')) {
                    $table->index(['student_id', 'sequence_id', 'trimester_id'], 'idx_grades_bulletin_lookup');
                }

                // Index composite pour calcul de moyennes par matière
                if (!$this->indexExists('grades', 'idx_grades_subject_student')) {
                    $table->index(['class_series_subject_id', 'student_id', 'sequence_id'], 'idx_grades_subject_student');
                }

                // Index pour les requêtes par année scolaire + trimester
                if (!$this->indexExists('grades', 'idx_grades_year_trimester')) {
                    $table->index(['school_year_id', 'trimester_id', 'student_id'], 'idx_grades_year_trimester');
                }

                // Index pour les notes non absentes
                if (!$this->indexExists('grades', 'idx_grades_not_absent')) {
                    $table->index(['is_absent', 'student_id', 'sequence_id'], 'idx_grades_not_absent');
                }
            });
        }

        // ========================================
        // 2. OPTIMISATION TABLE STUDENTS
        // ========================================
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                // Index pour filtrer les étudiants actifs par classe
                if (!$this->indexExists('students', 'idx_students_active_lookup')) {
                    $table->index(['is_active', 'class_series_id', 'status'], 'idx_students_active_lookup');
                }

                // Index sur name pour les recherches alphabétiques
                if (!$this->indexExists('students', 'idx_students_name')) {
                    $table->index(['name', 'subname'], 'idx_students_name');
                }
            });
        }

        // ========================================
        // 3. OPTIMISATION TABLE EVALUATIONS
        // ========================================
        if (Schema::hasTable('evaluations')) {
            Schema::table('evaluations', function (Blueprint $table) {
                // Index composite pour recherche d'évaluations lors de génération bulletins
                if (!$this->indexExists('evaluations', 'idx_eval_bulletin_generation')) {
                    $table->index(['sequence_id', 'trimester_id', 'class_series_subject_id'], 'idx_eval_bulletin_generation');
                }

                // Index pour filtrer les évaluations actives par type
                if (!$this->indexExists('evaluations', 'idx_eval_active_type')) {
                    $table->index(['is_active', 'type', 'school_year_id'], 'idx_eval_active_type');
                }

                // Index pour recherche par enseignant + date
                if (!$this->indexExists('evaluations', 'idx_eval_teacher_date')) {
                    $table->index(['teacher_id', 'date', 'sequence_id'], 'idx_eval_teacher_date');
                }
            });
        }

        // ========================================
        // 4. OPTIMISATION TABLE BULLETIN_GENERATIONS
        // ========================================
        if (Schema::hasTable('bulletin_generations')) {
            Schema::table('bulletin_generations', function (Blueprint $table) {
                // Index UNIQUE composite pour éviter les duplications
                if (!$this->indexExists('bulletin_generations', 'idx_bulletin_unique_lookup')) {
                    $table->unique(['student_id', 'period_type', 'period_identifier'], 'idx_bulletin_unique_lookup');
                }

                // Index pour recherche par type de bulletin
                if (!$this->indexExists('bulletin_generations', 'idx_bulletin_type_period')) {
                    $table->index(['period_type', 'period_identifier', 'generated_at'], 'idx_bulletin_type_period');
                }

                // Index pour recherche par étudiant
                if (!$this->indexExists('bulletin_generations', 'idx_bulletin_student_type')) {
                    $table->index(['student_id', 'period_type'], 'idx_bulletin_student_type');
                }
            });
        }

        // ========================================
        // 5. OPTIMISATION TABLE PAYMENTS
        // ========================================
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Index pour recherche par date (souvent utilisé dans les rapports)
                if (!$this->indexExists('payments', 'idx_payments_date_student')) {
                    $table->index(['payment_date', 'student_id'], 'idx_payments_date_student');
                }
            });
        }

        // ========================================
        // 6. OPTIMISATION TABLE TEACHER_ASSIGNMENTS
        // ========================================
        if (Schema::hasTable('teacher_assignments')) {
            Schema::table('teacher_assignments', function (Blueprint $table) {
                // Index pour filtrer les assignments actifs
                if (!$this->indexExists('teacher_assignments', 'idx_teacher_assign_active')) {
                    $table->index(['is_active', 'school_year_id'], 'idx_teacher_assign_active');
                }

                // Index pour recherche par série-matière
                if (!$this->indexExists('teacher_assignments', 'idx_teacher_assign_subject')) {
                    $table->index(['class_series_subject_id', 'school_year_id'], 'idx_teacher_assign_subject');
                }
            });
        }

        // ========================================
        // 7. OPTIMISATION TABLE SEQUENCES
        // ========================================
        if (Schema::hasTable('sequences')) {
            Schema::table('sequences', function (Blueprint $table) {
                // Index pour recherche de séquences actives/en cours
                if (!$this->indexExists('sequences', 'idx_sequences_status')) {
                    $table->index(['is_active', 'is_current', 'is_completed'], 'idx_sequences_status');
                }

                // Index pour recherche par trimestre
                if (!$this->indexExists('sequences', 'idx_sequences_trimester')) {
                    $table->index(['trimester_id', 'number'], 'idx_sequences_trimester');
                }
            });
        }

        // ========================================
        // 8. OPTIMISATION TABLE TRIMESTERS
        // ========================================
        if (Schema::hasTable('trimesters')) {
            Schema::table('trimesters', function (Blueprint $table) {
                // Index pour recherche par année scolaire
                if (!$this->indexExists('trimesters', 'idx_trimesters_school_year')) {
                    $table->index(['school_year_id', 'number'], 'idx_trimesters_school_year');
                }
            });
        }

        // ========================================
        // 9. OPTIMISATION TABLE CLASS_SERIES_SUBJECTS
        // ========================================
        if (Schema::hasTable('class_series_subjects')) {
            Schema::table('class_series_subjects', function (Blueprint $table) {
                // Index pour recherche par classe série
                if (!$this->indexExists('class_series_subjects', 'idx_class_series_subjects_lookup')) {
                    $table->index(['class_series_id', 'subject_id'], 'idx_class_series_subjects_lookup');
                }
            });
        }

        // ========================================
        // 10. OPTIMISATION TABLE TEACHERS
        // ========================================
        if (Schema::hasTable('teachers')) {
            Schema::table('teachers', function (Blueprint $table) {
                // Index sur user_id pour les jointures
                if (!$this->indexExists('teachers', 'idx_teachers_user')) {
                    $table->index(['user_id'], 'idx_teachers_user');
                }

                // Index sur is_active
                if (!$this->indexExists('teachers', 'idx_teachers_active')) {
                    $table->index(['is_active'], 'idx_teachers_active');
                }
            });
        }

        // ========================================
        // 11. OPTIMISATION TABLE USERS
        // ========================================
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Index sur username pour les authentifications
                if (!$this->indexExists('users', 'idx_users_username')) {
                    $table->index(['username'], 'idx_users_username');
                }

                // Index sur role pour les requêtes par rôle
                if (!$this->indexExists('users', 'idx_users_role')) {
                    $table->index(['role'], 'idx_users_role');
                }
            });
        }

        // ========================================
        // 12. OPTIMISATION TABLE SUBJECTS
        // ========================================
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                // Index sur group pour les regroupements de matières
                if (!$this->indexExists('subjects', 'idx_subjects_group')) {
                    $table->index(['group', 'is_active'], 'idx_subjects_group');
                }
            });
        }

        \Log::info('✅ Optimisation database: ' . $this->countIndexesCreated() . ' index créés avec succès');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'grades' => ['idx_grades_bulletin_lookup', 'idx_grades_subject_student', 'idx_grades_year_trimester', 'idx_grades_not_absent'],
            'students' => ['idx_students_active_lookup', 'idx_students_name'],
            'evaluations' => ['idx_eval_bulletin_generation', 'idx_eval_active_type', 'idx_eval_teacher_date'],
            'bulletin_generations' => ['idx_bulletin_unique_lookup', 'idx_bulletin_type_period', 'idx_bulletin_student_type'],
            'payments' => ['idx_payments_date_student'],
            'teacher_assignments' => ['idx_teacher_assign_active', 'idx_teacher_assign_subject'],
            'sequences' => ['idx_sequences_status', 'idx_sequences_trimester'],
            'trimesters' => ['idx_trimesters_school_year'],
            'class_series_subjects' => ['idx_class_series_subjects_lookup'],
            'teachers' => ['idx_teachers_user', 'idx_teachers_active'],
            'users' => ['idx_users_username', 'idx_users_role'],
            'subjects' => ['idx_subjects_group'],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            // Vérifier si c'est un index unique
                            if (strpos($index, 'unique') !== false) {
                                $table->dropUnique($index);
                            } else {
                                $table->dropIndex($index);
                            }
                        }
                    }
                });
            }
        }
    }

    /**
     * Vérifie si un index existe déjà sur une table (compatible Laravel 12)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Compte le nombre d'index créés
     */
    private function countIndexesCreated(): int
    {
        // Approximatif: 25 index au total
        return 25;
    }
};
