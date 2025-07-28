-- =================================================================
-- BASE DE DONNEES - SYSTEME DE GESTION SCOLAIRE GSBPL
-- Groupe Scolaire Bilingue Privé La Semence
-- =================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =================================================================
-- CREATION DE LA BASE DE DONNEES
-- =================================================================

CREATE DATABASE IF NOT EXISTS `gsbpl_school_management` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `gsbpl_school_management`;

-- =================================================================
-- TABLE DES UTILISATEURS ADMINISTRATEURS ET COMPTABLES
-- =================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `username` VARCHAR(30) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `school_id` VARCHAR(255) NOT NULL,
  `role` ENUM('ad', 'comp') NOT NULL DEFAULT 'comp',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES SECTIONS ACADEMIQUES
-- =================================================================

CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_year` (`school_year`),
  INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES CLASSES
-- =================================================================

CREATE TABLE IF NOT EXISTS `class` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `level` INT(11) NOT NULL,
  `section` INT(11) NOT NULL,
  `inscriptions_olds_students` DECIMAL(10,2) DEFAULT 0.00,
  `inscriptions_news_students` DECIMAL(10,2) DEFAULT 0.00,
  `first_tranch_news_students` DECIMAL(10,2) DEFAULT 0.00,
  `first_tranch_olds_students` DECIMAL(10,2) DEFAULT 0.00,
  `second_tranch_news_students` DECIMAL(10,2) DEFAULT 0.00,
  `second_tranch_olds_students` DECIMAL(10,2) DEFAULT 0.00,
  `third_tranch_news_students` DECIMAL(10,2) DEFAULT 0.00,
  `third_tranch_olds_students` DECIMAL(10,2) DEFAULT 0.00,
  `graduation` DECIMAL(10,2) DEFAULT 0.00,
  `school_id` VARCHAR(255) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `teacherId` VARCHAR(255) DEFAULT NULL,
  `first_date` INT(11) DEFAULT NULL,
  `last_date` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_school_year` (`school_year`),
  INDEX `idx_section` (`section`),
  INDEX `idx_teacher` (`teacherId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES ENSEIGNANTS
-- =================================================================

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `subname` VARCHAR(255) NOT NULL,
  `class_id` VARCHAR(255) DEFAULT NULL,
  `matricule` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(10) NOT NULL,
  `sex` ENUM('f', 'm') NOT NULL,
  `phone_number` VARCHAR(20) DEFAULT NULL,
  `birthday` DATE DEFAULT NULL,
  `school_id` VARCHAR(255) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE SET NULL,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_school_year` (`school_year`),
  INDEX `idx_matricule` (`matricule`),
  INDEX `idx_class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES ETUDIANTS
-- =================================================================

CREATE TABLE IF NOT EXISTS `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `subname` VARCHAR(255) NOT NULL,
  `class_id` VARCHAR(255) NOT NULL,
  `sex` ENUM('f', 'm') NOT NULL,
  `fatherName` VARCHAR(255) DEFAULT NULL,
  `profession` VARCHAR(255) DEFAULT NULL,
  `birthday` DATE DEFAULT NULL,
  `birthday_place` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone_number` VARCHAR(20) DEFAULT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `status` ENUM('new', 'old') NOT NULL DEFAULT 'new',
  `is_new` ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
  `school_id` VARCHAR(255) NOT NULL,
  `inscription` DECIMAL(10,2) DEFAULT 0.00,
  `first_tranch` DECIMAL(10,2) DEFAULT 0.00,
  `second_tranch` DECIMAL(10,2) DEFAULT 0.00,
  `third_tranch` DECIMAL(10,2) DEFAULT 0.00,
  `graduation` DECIMAL(10,2) DEFAULT 0.00,
  `assurance` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_school_year` (`school_year`),
  INDEX `idx_class_id` (`class_id`),
  INDEX `idx_name_subname` (`name`, `subname`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES MATIERES/SUJETS
-- =================================================================

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `over` INT(11) NOT NULL DEFAULT 20,
  `section` INT(11) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  INDEX `idx_section` (`section`),
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES DOMAINES
-- =================================================================

CREATE TABLE IF NOT EXISTS `domains` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `section` INT(11) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  INDEX `idx_section` (`section`),
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES ACTIVITES
-- =================================================================

CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `appreciationsNber` INT(11) NOT NULL DEFAULT 5,
  `section` INT(11) NOT NULL,
  `domainId` INT(11) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`domainId`) REFERENCES `domains`(`id`) ON DELETE CASCADE,
  INDEX `idx_section` (`section`),
  INDEX `idx_domain` (`domainId`),
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES COMPETENCES
-- =================================================================

CREATE TABLE IF NOT EXISTS `com` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `section` INT(11) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  INDEX `idx_section` (`section`),
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES SOUS-COMPETENCES
-- =================================================================

CREATE TABLE IF NOT EXISTS `sub_com` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `section` INT(11) NOT NULL,
  `comId` VARCHAR(255) NOT NULL,
  `tags` JSON DEFAULT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`comId`) REFERENCES `com`(`id`) ON DELETE CASCADE,
  INDEX `idx_section` (`section`),
  INDEX `idx_com` (`comId`),
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES SEQUENCES
-- =================================================================

CREATE TABLE IF NOT EXISTS `seq` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES TRIMESTRES
-- =================================================================

CREATE TABLE IF NOT EXISTS `trims` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `seqIds` JSON DEFAULT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES EXAMENS ANNUELS
-- =================================================================

CREATE TABLE IF NOT EXISTS `annual_exams` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_year` (`school_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES NOTES PAR COMPETENCES
-- =================================================================

CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT(11) NOT NULL,
  `exam_id` VARCHAR(255) NOT NULL,
  `class_id` VARCHAR(255) NOT NULL,
  `sub_com_id` VARCHAR(255) NOT NULL,
  `tag_name` VARCHAR(255) NOT NULL,
  `value` DECIMAL(5,2) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sub_com_id`) REFERENCES `sub_com`(`id`) ON DELETE CASCADE,
  INDEX `idx_student` (`student_id`),
  INDEX `idx_exam` (`exam_id`),
  INDEX `idx_class` (`class_id`),
  INDEX `idx_sub_com` (`sub_com_id`),
  INDEX `idx_school_year` (`school_year`),
  UNIQUE KEY `unique_note` (`student_id`, `exam_id`, `sub_com_id`, `tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES NOTES PAR MATIERE
-- =================================================================

CREATE TABLE IF NOT EXISTS `notesBySubject` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT(11) NOT NULL,
  `exam_id` VARCHAR(255) NOT NULL,
  `class_id` VARCHAR(255) NOT NULL,
  `subject_id` INT(11) NOT NULL,
  `value` DECIMAL(5,2) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  INDEX `idx_student` (`student_id`),
  INDEX `idx_exam` (`exam_id`),
  INDEX `idx_class` (`class_id`),
  INDEX `idx_subject` (`subject_id`),
  INDEX `idx_school_year` (`school_year`),
  UNIQUE KEY `unique_note_subject` (`student_id`, `exam_id`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES NOTES PAR DOMAINE
-- =================================================================

CREATE TABLE IF NOT EXISTS `notesByDomain` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT(11) NOT NULL,
  `exam_id` VARCHAR(255) NOT NULL,
  `class_id` VARCHAR(255) NOT NULL,
  `domain_id` INT(11) NOT NULL,
  `activitieId` INT(11) NOT NULL,
  `value` DECIMAL(5,2) NOT NULL,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`activitieId`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
  INDEX `idx_student` (`student_id`),
  INDEX `idx_exam` (`exam_id`),
  INDEX `idx_class` (`class_id`),
  INDEX `idx_domain` (`domain_id`),
  INDEX `idx_activity` (`activitieId`),
  INDEX `idx_school_year` (`school_year`),
  UNIQUE KEY `unique_note_domain` (`student_id`, `exam_id`, `domain_id`, `activitieId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES STATISTIQUES GENERALES
-- =================================================================

CREATE TABLE IF NOT EXISTS `stats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT(11) NOT NULL,
  `class_id` VARCHAR(255) NOT NULL,
  `exam_id` VARCHAR(255) NOT NULL,
  `totalPoints` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `school_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `class`(`id`) ON DELETE CASCADE,
  INDEX `idx_student` (`student_id`),
  INDEX `idx_class` (`class_id`),
  INDEX `idx_exam` (`exam_id`),
  INDEX `idx_school_year` (`school_year`),
  UNIQUE KEY `unique_stat` (`student_id`, `class_id`, `exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES PAIEMENTS
-- =================================================================

CREATE TABLE IF NOT EXISTS `payments_details` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT(11) NOT NULL,
  `operator_id` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `recu_name` VARCHAR(255) DEFAULT NULL,
  `tag` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`operator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_student` (`student_id`),
  INDEX `idx_operator` (`operator_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABLE DES PARAMETRES SYSTEME
-- =================================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` VARCHAR(255) NOT NULL,
  `year_school` VARCHAR(20) NOT NULL,
  `is_after_compo` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
  `is_editable` ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_year_school` (`year_school`),
  UNIQUE KEY `unique_school_year` (`school_id`, `year_school`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- CONTRAINTES DE CLES ETRANGERES SUPPLEMENTAIRES
-- =================================================================

-- Ajouter la contrainte pour teachers.class_id après création de toutes les tables
ALTER TABLE `class` 
ADD CONSTRAINT `fk_class_teacher` 
FOREIGN KEY (`teacherId`) REFERENCES `teachers`(`id`) ON DELETE SET NULL;

-- =================================================================
-- VUES UTILES POUR LES RAPPORTS
-- =================================================================

-- Vue pour les totaux de classe
CREATE VIEW `class_totals` AS
SELECT 
    c.id,
    c.name,
    c.section,
    s.name as section_name,
    COUNT(st.id) as total_students,
    SUM(CASE WHEN st.is_new = 'yes' THEN 1 ELSE 0 END) as new_students,
    SUM(CASE WHEN st.is_new = 'no' THEN 1 ELSE 0 END) as old_students,
    t.name as teacher_name,
    t.subname as teacher_subname
FROM `class` c
LEFT JOIN `sections` s ON c.section = s.id
LEFT JOIN `students` st ON c.id = st.class_id
LEFT JOIN `teachers` t ON c.teacherId = t.id
GROUP BY c.id, c.name, c.section, s.name, t.name, t.subname;

-- Vue pour les totaux de paiements par étudiant
CREATE VIEW `student_payments_summary` AS
SELECT 
    s.id,
    s.name,
    s.subname,
    s.class_id,
    c.name as class_name,
    s.inscription,
    s.first_tranch,
    s.second_tranch,
    s.third_tranch,
    s.graduation,
    s.assurance,
    (s.inscription + s.first_tranch + s.second_tranch + s.third_tranch + s.graduation + s.assurance) as total_expected,
    COALESCE(SUM(pd.amount), 0) as total_paid,
    ((s.inscription + s.first_tranch + s.second_tranch + s.third_tranch + s.graduation + s.assurance) - COALESCE(SUM(pd.amount), 0)) as balance
FROM `students` s
LEFT JOIN `class` c ON s.class_id = c.id
LEFT JOIN `payments_details` pd ON s.id = pd.student_id
GROUP BY s.id, s.name, s.subname, s.class_id, c.name, s.inscription, s.first_tranch, s.second_tranch, s.third_tranch, s.graduation, s.assurance;

COMMIT;

-- =================================================================
-- NOTES D'INSTALLATION
-- =================================================================

/* 
Instructions d'installation :

1. Créer la base de données MySQL/MariaDB
2. Exécuter ce script SQL complet
3. Créer un utilisateur administrateur initial :
   - username: admin
   - password: admin123 (à changer immédiatement)
   - role: ad (administrateur)

4. Configurer les paramètres de connexion dans le backend
5. Importer les données de test si nécessaire

Sécurité :
- Changer le mot de passe par défaut de l'administrateur
- Configurer des sauvegardes régulières
- Limiter les accès réseau à la base de données
- Utiliser SSL/TLS pour les connexions

Multi-tenancy :
- Chaque école a son propre school_id
- Les données sont séparées par school_year
- Utiliser des index sur ces colonnes pour les performances
*/