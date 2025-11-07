-- ============================================
-- Script de correction des types d'évaluations
-- À exécuter en production via SSH
-- ============================================
-- Date: 2025-11-07
-- Description: Corrige les types d'évaluations dans la base de données
--              pour distinguer séquences, compositions et DS
-- ============================================

-- Afficher le début du script
SELECT '🚀 DÉBUT DE LA CORRECTION DES TYPES D\'ÉVALUATIONS' AS '';
SELECT '' AS '';

-- ============================================
-- ÉTAPE 0 : Modifier l'ENUM pour ajouter 'sequence' et 'ds'
-- ============================================
SELECT '📝 ÉTAPE 0 : Modification de l\'ENUM...' AS '';

ALTER TABLE evaluations
MODIFY COLUMN type ENUM('interrogation','devoir','composition','tp','controle','sequence','ds') NOT NULL;

SELECT '✅ Colonne type modifiée avec succès pour accepter sequence et ds' AS '';
SELECT '' AS '';

-- ============================================
-- ÉTAPE 1 : Identifier et marquer les SÉQUENCES
-- ============================================
SELECT '📝 ÉTAPE 1 : Identification des séquences...' AS '';

UPDATE evaluations
SET type = 'sequence'
WHERE type = 'composition'
AND (
    name REGEXP '(?i)(séquence|sequence|seq)[[:space:]]*[0-9]'
    OR name REGEXP '(?i)^seq[0-9]'
    OR LOWER(name) LIKE '%séquence%'
    OR LOWER(name) LIKE '%sequence%'
);

SELECT CONCAT('✅ ', ROW_COUNT(), ' séquences identifiées et marquées comme type=sequence') AS '';
SELECT '' AS '';

-- ============================================
-- ÉTAPE 2 : Compter les VRAIES COMPOSITIONS
-- ============================================
SELECT '📝 ÉTAPE 2 : Comptage des vraies compositions...' AS '';

SELECT CONCAT('✅ ', COUNT(*), ' vraies compositions gardées comme type=composition') AS ''
FROM evaluations
WHERE type = 'composition'
AND (
    name REGEXP '(?i)composition[[:space:]]*[0-9]'
    OR LOWER(name) LIKE '%composition%'
    OR LOWER(name) LIKE '%compo%'
);

SELECT '' AS '';

-- ============================================
-- ÉTAPE 3 : Les autres évaluations → type='ds'
-- ============================================
SELECT '📝 ÉTAPE 3 : Identification des DS/Devoirs...' AS '';

UPDATE evaluations
SET type = 'ds'
WHERE type = 'composition'
AND name NOT REGEXP '(?i)composition[[:space:]]*[0-9]'
AND LOWER(name) NOT LIKE '%composition%'
AND LOWER(name) NOT LIKE '%compo%';

SELECT CONCAT('✅ ', ROW_COUNT(), ' autres évaluations (DS, Devoirs, etc.) marquées comme type=ds') AS '';
SELECT '' AS '';

-- ============================================
-- STATISTIQUES FINALES
-- ============================================
SELECT '📊 STATISTIQUES FINALES :' AS '';
SELECT '' AS '';

SELECT
    type AS 'Type',
    COUNT(*) AS 'Total'
FROM evaluations
GROUP BY type
ORDER BY
    CASE type
        WHEN 'sequence' THEN 1
        WHEN 'composition' THEN 2
        WHEN 'ds' THEN 3
        WHEN 'tp' THEN 4
        WHEN 'interrogation' THEN 5
        WHEN 'devoir' THEN 6
        WHEN 'controle' THEN 7
        ELSE 8
    END;

SELECT '' AS '';
SELECT CONCAT('📈 TOTAL GÉNÉRAL : ', COUNT(*), ' évaluations') AS ''
FROM evaluations;

SELECT '' AS '';
SELECT '✅ CORRECTION TERMINÉE AVEC SUCCÈS !' AS '';
