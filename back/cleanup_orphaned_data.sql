-- Script de nettoyage des données orphelines en production
-- À exécuter AVANT de lancer php artisan migrate

-- 1. Vérifier combien d'enregistrements orphelins existent
SELECT COUNT(*) as orphaned_count
FROM teacher_assignments
WHERE class_series_subject_id NOT IN (
    SELECT id FROM class_series_subjects
);

-- 2. Afficher les enregistrements orphelins (pour vérification)
SELECT ta.id, ta.teacher_id, ta.class_series_subject_id, ta.school_year_id
FROM teacher_assignments ta
WHERE ta.class_series_subject_id NOT IN (
    SELECT id FROM class_series_subjects
);

-- 3. Supprimer les enregistrements orphelins
DELETE FROM teacher_assignments
WHERE class_series_subject_id NOT IN (
    SELECT id FROM class_series_subjects
);

-- 4. Vérifier qu'il n'en reste plus
SELECT COUNT(*) as remaining_orphaned_count
FROM teacher_assignments
WHERE class_series_subject_id NOT IN (
    SELECT id FROM class_series_subjects
);
