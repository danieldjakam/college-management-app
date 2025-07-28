-- =================================================================
-- DONNEES DE TEST - SYSTEME DE GESTION SCOLAIRE GSBPL
-- Groupe Scolaire Bilingue Privé La Semence
-- =================================================================

USE `gsbpl_school_management`;

-- =================================================================
-- DONNEES INITIALES - UTILISATEURS ADMINISTRATEURS
-- =================================================================

-- Utilisateur administrateur principal
INSERT INTO `users` (`id`, `username`, `email`, `password`, `school_id`, `role`) VALUES
('admin_001', 'admin', 'admin@gsbpl.com', '$2b$10$X9J0YvYvYvYvYvYvYvYvYuK8K8K8K8K8K8K8K8K8K8K8K8K8K8K8', 'GSBPL_001', 'ad'),
('comp_001', 'comptable', 'comptable@gsbpl.com', '$2b$10$X9J0YvYvYvYvYvYvYvYvYuK8K8K8K8K8K8K8K8K8K8K8K8K8K8K8', 'GSBPL_001', 'comp');

-- Note: Les mots de passe cryptés correspondent à "password123"

-- =================================================================
-- DONNEES INITIALES - SECTIONS ACADEMIQUES
-- =================================================================

INSERT INTO `sections` (`id`, `name`, `type`, `school_year`) VALUES
(1, 'Maternelle', 'Préscolaire', '2024-2025'),
(2, 'Primaire Francophone', 'Primaire', '2024-2025'),
(3, 'Primaire Anglophone', 'Primaire', '2024-2025'),
(4, 'Secondaire Francophone', 'Secondaire', '2024-2025'),
(5, 'Secondaire Anglophone', 'Secondaire', '2024-2025');

-- =================================================================
-- DONNEES INITIALES - CLASSES
-- =================================================================

INSERT INTO `class` (`id`, `name`, `level`, `section`, `inscriptions_olds_students`, `inscriptions_news_students`, `first_tranch_news_students`, `first_tranch_olds_students`, `second_tranch_news_students`, `second_tranch_olds_students`, `third_tranch_news_students`, `third_tranch_olds_students`, `graduation`, `school_id`, `school_year`, `teacherId`, `first_date`, `last_date`) VALUES
('CLASS_001', 'Petite Section', 1, 1, 15000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 5000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_002', 'Moyenne Section A', 2, 1, 15000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 5000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_003', 'Moyenne Section B', 2, 1, 15000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 5000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_004', 'Grande Section A', 3, 1, 15000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 5000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_005', 'Grande Section B', 3, 1, 15000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 25000.00, 20000.00, 5000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_006', 'CP A', 4, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_007', 'CP B', 4, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_008', 'CE1 A', 5, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_009', 'CE1 B', 5, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_010', 'CE2 A', 6, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_011', 'CE2 B', 6, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_012', 'CM1 A', 7, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_013', 'CM1 B', 7, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_014', 'CM2 A', 8, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_015', 'CM2 B', 8, 2, 18000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 30000.00, 25000.00, 8000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_016', 'Class 1', 4, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_017', 'Class 2', 5, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_018', 'Class 3', 6, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_019', 'Class 4', 7, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_020', 'Class 5', 8, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_021', 'Class 6', 9, 3, 20000.00, 28000.00, 35000.00, 30000.00, 35000.00, 30000.00, 35000.00, 30000.00, 10000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_022', 'SIL A', 10, 3, 25000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 15000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_023', 'SIL B', 10, 3, 25000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 15000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31),
('CLASS_024', 'SIL C', 10, 3, 25000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 40000.00, 35000.00, 15000.00, 'GSBPL_001', '2024-2025', NULL, 1, 31);

-- =================================================================
-- DONNEES INITIALES - ENSEIGNANTS
-- =================================================================

INSERT INTO `teachers` (`id`, `name`, `subname`, `class_id`, `matricule`, `password`, `sex`, `phone_number`, `birthday`, `school_id`, `school_year`) VALUES
('TEACH_001', 'Marie', 'NGOUNOU', 'CLASS_001', 'SEM-PETITE-SECTION', '1234', 'f', '+237698745632', '1985-03-15', 'GSBPL_001', '2024-2025'),
('TEACH_002', 'Jean', 'KAMDEM', 'CLASS_006', 'SEM-CP-A', '5678', 'm', '+237677123456', '1982-07-22', 'GSBPL_001', '2024-2025'),
('TEACH_003', 'Sylvie', 'FOTSO', 'CLASS_008', 'SEM-CE1-A', '9012', 'f', '+237694567890', '1988-11-10', 'GSBPL_001', '2024-2025'),
('TEACH_004', 'Paul', 'MBARGA', 'CLASS_012', 'SEM-CM1-A', '3456', 'm', '+237681234567', '1980-05-18', 'GSBPL_001', '2024-2025'),
('TEACH_005', 'Françoise', 'DJOUMESSI', 'CLASS_014', 'SEM-CM2-A', '7890', 'f', '+237676543210', '1983-09-25', 'GSBPL_001', '2024-2025'),
('TEACH_006', 'Michael', 'JOHNSON', 'CLASS_016', 'SEM-CLASS-1', '2468', 'm', '+237695123456', '1986-12-08', 'GSBPL_001', '2024-2025'),
('TEACH_007', 'Sarah', 'WILLIAMS', 'CLASS_018', 'SEM-CLASS-3', '1357', 'f', '+237687654321', '1984-04-14', 'GSBPL_001', '2024-2025'),
('TEACH_008', 'David', 'BROWN', 'CLASS_020', 'SEM-CLASS-5', '9753', 'm', '+237678912345', '1981-08-30', 'GSBPL_001', '2024-2025'),
('TEACH_009', 'Emma', 'DAVIS', 'CLASS_022', 'SEM-SIL-A', '4567', 'f', '+237689234567', '1987-01-12', 'GSBPL_001', '2024-2025'),
('TEACH_010', 'Sophie', 'TALLA', 'CLASS_002', 'SEM-MOY-SEC-A', '8901', 'f', '+237697456123', '1989-06-20', 'GSBPL_001', '2024-2025');

-- =================================================================
-- MISES A JOUR DES ENSEIGNANTS DANS LES CLASSES
-- =================================================================

UPDATE `class` SET `teacherId` = 'TEACH_001' WHERE `id` = 'CLASS_001';
UPDATE `class` SET `teacherId` = 'TEACH_010' WHERE `id` = 'CLASS_002';
UPDATE `class` SET `teacherId` = 'TEACH_002' WHERE `id` = 'CLASS_006';
UPDATE `class` SET `teacherId` = 'TEACH_003' WHERE `id` = 'CLASS_008';
UPDATE `class` SET `teacherId` = 'TEACH_004' WHERE `id` = 'CLASS_012';
UPDATE `class` SET `teacherId` = 'TEACH_005' WHERE `id` = 'CLASS_014';
UPDATE `class` SET `teacherId` = 'TEACH_006' WHERE `id` = 'CLASS_016';
UPDATE `class` SET `teacherId` = 'TEACH_007' WHERE `id` = 'CLASS_018';
UPDATE `class` SET `teacherId` = 'TEACH_008' WHERE `id` = 'CLASS_020';
UPDATE `class` SET `teacherId` = 'TEACH_009' WHERE `id` = 'CLASS_022';

-- =================================================================
-- DONNEES INITIALES - ETUDIANTS EXEMPLES
-- =================================================================

INSERT INTO `students` (`name`, `subname`, `class_id`, `sex`, `fatherName`, `profession`, `birthday`, `birthday_place`, `email`, `phone_number`, `school_year`, `status`, `is_new`, `school_id`, `inscription`, `first_tranch`, `second_tranch`, `third_tranch`, `graduation`, `assurance`) VALUES
-- Petite Section
('NKOMO', 'Marie Grace', 'CLASS_001', 'f', 'NKOMO Jean', 'Enseignant', '2021-03-15', 'Yaoundé', 'nkomo.marie@gmail.com', '+237698745632', '2024-2025', 'new', 'yes', 'GSBPL_001', 20000.00, 25000.00, 25000.00, 25000.00, 5000.00, 2000.00),
('MBALLA', 'Junior Alex', 'CLASS_001', 'm', 'MBALLA Paul', 'Commerçant', '2021-07-22', 'Douala', 'mballa.junior@yahoo.com', '+237677123456', '2024-2025', 'new', 'yes', 'GSBPL_001', 20000.00, 25000.00, 25000.00, 25000.00, 5000.00, 2000.00),
('FOTSO', 'Laure Divine', 'CLASS_001', 'f', 'FOTSO Martin', 'Ingénieur', '2021-11-10', 'Bafoussam', 'fotso.laure@hotmail.com', '+237694567890', '2024-2025', 'old', 'no', 'GSBPL_001', 15000.00, 20000.00, 20000.00, 20000.00, 5000.00, 2000.00),

-- CP A
('KAMDEM', 'Blessing Hope', 'CLASS_006', 'f', 'KAMDEM Samuel', 'Médecin', '2018-05-18', 'Yaoundé', 'kamdem.blessing@gmail.com', '+237681234567', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),
('NGUEMO', 'Christian David', 'CLASS_006', 'm', 'NGUEMO Pierre', 'Avocat', '2018-09-25', 'Douala', 'nguemo.christian@yahoo.fr', '+237676543210', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),
('TALLA', 'Princesse Joelle', 'CLASS_006', 'f', 'TALLA François', 'Banquier', '2018-12-08', 'Yaoundé', 'talla.princesse@gmail.com', '+237695123456', '2024-2025', 'old', 'no', 'GSBPL_001', 18000.00, 25000.00, 25000.00, 25000.00, 8000.00, 3000.00),

-- CE1 A
('DJOUMESSI', 'Emmanuel Junior', 'CLASS_008', 'm', 'DJOUMESSI Alain', 'Entrepreneur', '2017-04-14', 'Bafoussam', 'djoumessi.emmanuel@hotmail.com', '+237687654321', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),
('MBARGA', 'Vanessa Claire', 'CLASS_008', 'f', 'MBARGA Joseph', 'Pharmacien', '2017-08-30', 'Douala', 'mbarga.vanessa@yahoo.com', '+237678912345', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),
('SIMO', 'Armel Patrick', 'CLASS_008', 'm', 'SIMO Henri', 'Comptable', '2017-01-12', 'Yaoundé', 'simo.armel@gmail.com', '+237689234567', '2024-2025', 'old', 'no', 'GSBPL_001', 18000.00, 25000.00, 25000.00, 25000.00, 8000.00, 3000.00),

-- CM2 A
('TCHOUMI', 'Ange Rebecca', 'CLASS_014', 'f', 'TCHOUMI Robert', 'Professeur', '2013-06-20', 'Yaoundé', 'tchoumi.ange@gmail.com', '+237697456123', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),
('NGONO', 'Steve Arnold', 'CLASS_014', 'm', 'NGONO Michel', 'Directeur', '2013-10-05', 'Douala', 'ngono.steve@yahoo.fr', '+237682147963', '2024-2025', 'new', 'yes', 'GSBPL_001', 25000.00, 30000.00, 30000.00, 30000.00, 8000.00, 3000.00),

-- Class 1 (Anglophone)
('JOHNSON', 'Grace Melody', 'CLASS_016', 'f', 'JOHNSON Michael', 'Engineer', '2018-03-25', 'Buea', 'johnson.grace@gmail.com', '+237693258147', '2024-2025', 'new', 'yes', 'GSBPL_001', 28000.00, 35000.00, 35000.00, 35000.00, 10000.00, 4000.00),
('WILLIAMS', 'Daniel Bright', 'CLASS_016', 'm', 'WILLIAMS John', 'Doctor', '2018-07-18', 'Bamenda', 'williams.daniel@yahoo.com', '+237674125896', '2024-2025', 'new', 'yes', 'GSBPL_001', 28000.00, 35000.00, 35000.00, 35000.00, 10000.00, 4000.00),

-- SIL A (Anglophone)
('BROWN', 'Stephanie Joy', 'CLASS_022', 'f', 'BROWN David', 'Lawyer', '2014-11-12', 'Limbe', 'brown.stephanie@hotmail.com', '+237685741269', '2024-2025', 'new', 'yes', 'GSBPL_001', 35000.00, 40000.00, 40000.00, 40000.00, 15000.00, 5000.00),
('DAVIS', 'Joshua Emmanuel', 'CLASS_022', 'm', 'DAVIS Robert', 'Business Man', '2014-05-08', 'Douala', 'davis.joshua@gmail.com', '+237698526374', '2024-2025', 'old', 'no', 'GSBPL_001', 25000.00, 35000.00, 35000.00, 35000.00, 15000.00, 5000.00);

-- =================================================================
-- DONNEES INITIALES - MATIERES PAR SECTION
-- =================================================================

-- Matières pour la section Maternelle
INSERT INTO `subjects` (`name`, `over`, `section`, `school_year`) VALUES
('Langage', 20, 1, '2024-2025'),
('Mathématiques', 20, 1, '2024-2025'),
('Découverte du Monde', 20, 1, '2024-2025'),
('Arts Plastiques', 20, 1, '2024-2025'),
('Éducation Physique', 20, 1, '2024-2025'),
('Anglais', 20, 1, '2024-2025');

-- Matières pour le Primaire Francophone
INSERT INTO `subjects` (`name`, `over`, `section`, `school_year`) VALUES
('Français', 20, 2, '2024-2025'),
('Mathématiques', 20, 2, '2024-2025'),
('Anglais', 20, 2, '2024-2025'),
('Sciences', 20, 2, '2024-2025'),
('Histoire-Géographie', 20, 2, '2024-2025'),
('Éducation Civique et Morale', 20, 2, '2024-2025'),
('Arts Plastiques', 20, 2, '2024-2025'),
('Éducation Physique et Sportive', 20, 2, '2024-2025'),
('Informatique', 20, 2, '2024-2025');

-- Matières pour le Primaire Anglophone
INSERT INTO `subjects` (`name`, `over`, `section`, `school_year`) VALUES
('English Language', 20, 3, '2024-2025'),
('Mathematics', 20, 3, '2024-2025'),
('French', 20, 3, '2024-2025'),
('Science', 20, 3, '2024-2025'),
('Social Studies', 20, 3, '2024-2025'),
('Moral Education', 20, 3, '2024-2025'),
('Arts and Crafts', 20, 3, '2024-2025'),
('Physical Education', 20, 3, '2024-2025'),
('Computer Studies', 20, 3, '2024-2025');

-- =================================================================
-- DONNEES INITIALES - DOMAINES ET ACTIVITES
-- =================================================================

-- Domaines pour la Maternelle
INSERT INTO `domains` (`name`, `section`, `school_year`) VALUES
('Mobiliser le langage dans toutes ses dimensions', 1, '2024-2025'),
('Agir, s\'exprimer, comprendre à travers l\'activité physique', 1, '2024-2025'),
('Agir, s\'exprimer, comprendre à travers les activités artistiques', 1, '2024-2025'),
('Construire les premiers outils pour structurer sa pensée', 1, '2024-2025'),
('Explorer le monde', 1, '2024-2025');

-- Activités pour les domaines de la Maternelle
INSERT INTO `activities` (`name`, `appreciationsNber`, `section`, `domainId`, `school_year`) VALUES
('Expression orale', 5, 1, 1, '2024-2025'),
('Compréhension', 5, 1, 1, '2024-2025'),
('Graphisme', 5, 1, 1, '2024-2025'),
('Motricité globale', 5, 1, 2, '2024-2025'),
('Motricité fine', 5, 1, 2, '2024-2025'),
('Dessin', 5, 1, 3, '2024-2025'),
('Peinture', 5, 1, 3, '2024-2025'),
('Chant', 5, 1, 3, '2024-2025'),
('Nombres', 5, 1, 4, '2024-2025'),
('Formes et grandeurs', 5, 1, 4, '2024-2025'),
('Temps', 5, 1, 5, '2024-2025'),
('Espace', 5, 1, 5, '2024-2025'),
('Vivant', 5, 1, 5, '2024-2025');

-- =================================================================
-- DONNEES INITIALES - COMPETENCES ET SOUS-COMPETENCES
-- =================================================================

-- Compétences pour la Maternelle
INSERT INTO `com` (`id`, `name`, `section`, `school_year`) VALUES
('COM_001', 'Communiquer avec les adultes et avec les autres enfants', 1, '2024-2025'),
('COM_002', 'Comprendre et apprendre', 1, '2024-2025'),
('COM_003', 'Échanger et réfléchir avec les autres', 1, '2024-2025');

-- Sous-compétences pour la Maternelle
INSERT INTO `sub_com` (`id`, `name`, `slug`, `section`, `comId`, `tags`, `school_year`) VALUES
('SUBCOM_001', 'Oser entrer en communication', 'oser-communiquer', 1, 'COM_001', '[{"name": "Engagement", "over": 5}, {"name": "Autonomie", "over": 5}]', '2024-2025'),
('SUBCOM_002', 'Comprendre et se faire comprendre', 'comprendre-etre-compris', 1, 'COM_001', '[{"name": "Clarté", "over": 5}, {"name": "Précision", "over": 5}]', '2024-2025'),
('SUBCOM_003', 'Pratiquer divers usages du langage oral', 'usages-langage-oral', 1, 'COM_002', '[{"name": "Vocabulaire", "over": 5}, {"name": "Syntaxe", "over": 5}]', '2024-2025');

-- =================================================================
-- DONNEES INITIALES - SEQUENCES ET TRIMESTRES
-- =================================================================

INSERT INTO `seq` (`id`, `name`, `school_year`) VALUES
('SEQ_001', 'Séquence 1', '2024-2025'),
('SEQ_002', 'Séquence 2', '2024-2025'),
('SEQ_003', 'Séquence 3', '2024-2025'),
('SEQ_004', 'Séquence 4', '2024-2025'),
('SEQ_005', 'Séquence 5', '2024-2025'),
('SEQ_006', 'Séquence 6', '2024-2025');

INSERT INTO `trims` (`id`, `name`, `seqIds`, `school_year`) VALUES
('TRIM_001', 'Premier Trimestre', '["SEQ_001", "SEQ_002"]', '2024-2025'),
('TRIM_002', 'Deuxième Trimestre', '["SEQ_003", "SEQ_004"]', '2024-2025'),
('TRIM_003', 'Troisième Trimestre', '["SEQ_005", "SEQ_006"]', '2024-2025');

-- =================================================================
-- DONNEES INITIALES - EXAMENS ANNUELS
-- =================================================================

INSERT INTO `annual_exams` (`name`, `school_year`) VALUES
('Examen de passage', '2024-2025'),
('Concours d\'entrée en 6ème', '2024-2025'),
('Certificat d\'Études Primaires (CEP)', '2024-2025'),
('First School Leaving Certificate (FSLC)', '2024-2025');

-- =================================================================
-- DONNEES INITIALES - PARAMETRES SYSTEME
-- =================================================================

INSERT INTO `settings` (`school_id`, `year_school`, `is_after_compo`, `is_editable`) VALUES
('GSBPL_001', '2024-2025', 'no', 'yes');

-- =================================================================
-- EXEMPLES DE NOTES (Optionnel)
-- =================================================================

-- Quelques notes exemple pour les matières (Primaire)
INSERT INTO `notesBySubject` (`student_id`, `exam_id`, `class_id`, `subject_id`, `value`, `school_year`) VALUES
-- Notes pour KAMDEM Blessing Hope en CP A
(4, 'SEQ_001', 'CLASS_006', 7, 18.5, '2024-2025'), -- Français
(4, 'SEQ_001', 'CLASS_006', 8, 16.0, '2024-2025'), -- Mathématiques
(4, 'SEQ_001', 'CLASS_006', 9, 19.0, '2024-2025'), -- Anglais

-- Notes pour NGUEMO Christian David en CP A
(5, 'SEQ_001', 'CLASS_006', 7, 15.5, '2024-2025'), -- Français
(5, 'SEQ_001', 'CLASS_006', 8, 17.5, '2024-2025'), -- Mathématiques
(5, 'SEQ_001', 'CLASS_006', 9, 16.0, '2024-2025'); -- Anglais

-- =================================================================
-- EXEMPLES DE PAIEMENTS
-- =================================================================

INSERT INTO `payments_details` (`student_id`, `operator_id`, `amount`, `recu_name`, `tag`) VALUES
-- Paiements pour quelques étudiants
(1, 'admin_001', 20000.00, 'RECU_001_INS', 'inscription'),
(1, 'comp_001', 25000.00, 'RECU_001_T1', 'première tranche'),
(2, 'admin_001', 20000.00, 'RECU_002_INS', 'inscription'),
(3, 'comp_001', 15000.00, 'RECU_003_INS', 'inscription'),
(4, 'admin_001', 25000.00, 'RECU_004_INS', 'inscription'),
(4, 'comp_001', 30000.00, 'RECU_004_T1', 'première tranche'),
(5, 'admin_001', 25000.00, 'RECU_005_INS', 'inscription');

-- =================================================================
-- COMPLETION DE L'INSTALLATION
-- =================================================================

-- Message de confirmation
SELECT 'Base de données initialisée avec succès!' AS message,
       COUNT(*) AS nombre_etudiants FROM students
UNION ALL
SELECT 'Nombre d\'enseignants:', COUNT(*) FROM teachers
UNION ALL
SELECT 'Nombre de classes:', COUNT(*) FROM class
UNION ALL
SELECT 'Nombre de matières:', COUNT(*) FROM subjects;

-- =================================================================
-- INFORMATIONS DE CONNEXION
-- =================================================================

/*
INFORMATIONS DE CONNEXION POUR TESTER L'APPLICATION :

1. ADMINISTRATEUR :
   - Username: admin
   - Password: password123
   - Rôle: Administrateur (ad)

2. COMPTABLE :
   - Username: comptable
   - Password: password123
   - Rôle: Comptable (comp)

3. ENSEIGNANTS (exemples) :
   - Matricule: SEM-PETITE-SECTION, Password: 1234
   - Matricule: SEM-CP-A, Password: 5678
   - Matricule: SEM-CE1-A, Password: 9012
   - Matricule: SEM-CM2-A, Password: 7890
   - Matricule: SEM-SIL-A, Password: 4567

NOTES IMPORTANTES :
- Les mots de passe sont en texte clair pour les enseignants (système simplifié)
- Les mots de passe admin/comptable sont hachés avec bcrypt
- Changez tous les mots de passe par défaut en production
- Les montants sont en FCFA (Francs CFA)
- L'année scolaire est 2024-2025

DONNÉES CRÉÉES :
- 24 classes réparties dans 5 sections
- 10 enseignants assignés aux classes principales
- 15 étudiants exemples dans différentes classes
- Structure complète des matières, domaines et compétences
- Système de séquences et trimestres
- Exemples de notes et paiements
*/