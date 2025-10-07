-- Script pour corriger la table main_teachers en production
-- À exécuter manuellement dans MySQL

-- 1. Vérifier la structure actuelle de la table
SHOW CREATE TABLE main_teachers;

-- 2. Lister toutes les clés étrangères
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'main_teachers'
  AND TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 3. Lister tous les index
SHOW INDEX FROM main_teachers;

-- 4. Supprimer la clé étrangère sur school_class_id (si elle existe)
-- Remplacez 'nom_de_la_foreign_key' par le nom réel trouvé à l'étape 2
-- Exemple: ALTER TABLE main_teachers DROP FOREIGN KEY main_teachers_school_class_id_foreign;

-- 5. Supprimer l'index unique
ALTER TABLE main_teachers DROP INDEX unique_main_teacher_per_class;

-- 6. Supprimer la colonne school_class_id
ALTER TABLE main_teachers DROP COLUMN school_class_id;

-- 7. Ajouter la nouvelle colonne class_series_id
ALTER TABLE main_teachers ADD COLUMN class_series_id BIGINT UNSIGNED NOT NULL AFTER teacher_id;

-- 8. Ajouter la clé étrangère
ALTER TABLE main_teachers
ADD CONSTRAINT main_teachers_class_series_id_foreign
FOREIGN KEY (class_series_id) REFERENCES class_series(id) ON DELETE CASCADE;

-- 9. Recréer l'index unique
ALTER TABLE main_teachers
ADD UNIQUE unique_main_teacher_per_class_series (class_series_id, school_year_id);

-- 10. Vérifier le résultat final
SHOW CREATE TABLE main_teachers;
