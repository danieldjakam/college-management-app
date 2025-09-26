-- =============================================
-- SCRIPT DE CORRECTION DES BOURSES MULTIPLES
-- =============================================
-- PROBLÈME: Certains étudiants ont reçu plusieurs fois la bourse de 20,000 FCFA
-- SOLUTION: Garder seulement la première bourse chronologiquement, supprimer les autres
--
-- ⚠️  IMPORTANT: Faire un backup de la base avant d'exécuter ce script!
-- =============================================

-- Étape 1: Identifier les étudiants avec bourses multiples (pour vérification)
SELECT
    p.student_id,
    s.name as student_name,
    s.student_number,
    COUNT(*) as scholarship_count,
    SUM(p.scholarship_amount) as total_scholarship_received,
    GROUP_CONCAT(p.id ORDER BY p.created_at ASC) as payment_ids,
    GROUP_CONCAT(p.scholarship_amount ORDER BY p.created_at ASC) as amounts,
    GROUP_CONCAT(DATE(p.created_at) ORDER BY p.created_at ASC) as dates
FROM payments p
JOIN students s ON p.student_id = s.id
WHERE p.has_scholarship = 1
AND p.scholarship_amount > 0
AND p.school_year_id = 1
GROUP BY p.student_id, s.name, s.student_number
HAVING COUNT(*) > 1
ORDER BY scholarship_count DESC;

-- Étape 2: Sauvegarder les données originales (optionnel - pour rollback)
CREATE TEMPORARY TABLE backup_payments_scholarships AS
SELECT * FROM payments
WHERE has_scholarship = 1
AND scholarship_amount > 0
AND school_year_id = 1;

-- Étape 3: Correction pour l'étudiant KANDIS MARIVONE (ID: 215) - 3 bourses → 1
-- Garder le premier paiement (ID: 235), corriger les 2 autres

-- Paiement ID: 1198 (17/09/2025) - Supprimer la bourse
UPDATE payments
SET
    has_scholarship = 0,
    scholarship_amount = 0.00,
    total_amount = total_amount + 20000.00,  -- Ajouter la bourse au montant payé
    updated_at = NOW()
WHERE id = 1198
AND student_id = 215
AND has_scholarship = 1;

-- Paiement ID: 1478 (26/09/2025) - Supprimer la bourse
UPDATE payments
SET
    has_scholarship = 0,
    scholarship_amount = 0.00,
    total_amount = total_amount + 20000.00,  -- Ajouter la bourse au montant payé
    updated_at = NOW()
WHERE id = 1478
AND student_id = 215
AND has_scholarship = 1;

-- Étape 4: Correction pour l'étudiant DAVID EMMANUEL (ID: 101) - 2 bourses → 1
-- Garder le premier paiement (ID: 119), corriger le second

-- Paiement ID: 120 (20/08/2025) - Supprimer la bourse
UPDATE payments
SET
    has_scholarship = 0,
    scholarship_amount = 0.00,
    total_amount = total_amount + 20000.00,  -- Ajouter la bourse au montant payé
    updated_at = NOW()
WHERE id = 120
AND student_id = 101
AND has_scholarship = 1;

-- Étape 5: Correction pour l'étudiant PAUL CYRIL (ID: 51) - 2 bourses → 1
-- Garder le premier paiement (ID: 70), corriger le second

-- Paiement ID: 1117 (16/09/2025) - Supprimer la bourse
UPDATE payments
SET
    has_scholarship = 0,
    scholarship_amount = 0.00,
    total_amount = total_amount + 20000.00,  -- Ajouter la bourse au montant payé
    updated_at = NOW()
WHERE id = 1117
AND student_id = 51
AND has_scholarship = 1;

-- Étape 6: Vérification après correction
-- Cette requête ne doit retourner AUCUN résultat si la correction a réussi
SELECT
    p.student_id,
    s.name as student_name,
    COUNT(*) as scholarship_count,
    SUM(p.scholarship_amount) as total_scholarship_received
FROM payments p
JOIN students s ON p.student_id = s.id
WHERE p.has_scholarship = 1
AND p.scholarship_amount > 0
AND p.school_year_id = 1
GROUP BY p.student_id, s.name
HAVING COUNT(*) > 1;

-- Étape 7: Rapport final - Statistiques après correction
SELECT
    'Correction terminée' as status,
    COUNT(*) as total_payments_with_scholarships,
    COUNT(DISTINCT student_id) as unique_students_with_scholarships,
    SUM(scholarship_amount) as total_scholarship_amount,
    AVG(scholarship_amount) as average_scholarship_amount
FROM payments
WHERE has_scholarship = 1
AND scholarship_amount > 0
AND school_year_id = 1;

-- Étape 8: Vérification détaillée des 3 étudiants corrigés
SELECT
    p.student_id,
    s.name as student_name,
    s.student_number,
    p.id as payment_id,
    p.total_amount,
    p.has_scholarship,
    p.scholarship_amount,
    DATE(p.created_at) as payment_date
FROM payments p
JOIN students s ON p.student_id = s.id
WHERE p.student_id IN (215, 101, 51)
AND p.school_year_id = 1
ORDER BY p.student_id, p.created_at;

-- =============================================
-- NOTES DE ROLLBACK (en cas de problème)
-- =============================================
-- Si quelque chose ne va pas, restaurer depuis la table temporaire:
--
-- UPDATE payments p
-- JOIN backup_payments_scholarships b ON p.id = b.id
-- SET
--     p.has_scholarship = b.has_scholarship,
--     p.scholarship_amount = b.scholarship_amount,
--     p.total_amount = b.total_amount,
--     p.updated_at = NOW()
-- WHERE p.id IN (1198, 1478, 120, 1117);
--
-- DROP TEMPORARY TABLE backup_payments_scholarships;
-- =============================================

-- Montants corrigés attendus après le script:
--
-- KANDIS MARIVONE (215):
--   - Paiement 235: 42,000 FCFA + bourse 20,000 = GARDÉ
--   - Paiement 1198: 25,000 + 20,000 = 45,000 FCFA (bourse supprimée)
--   - Paiement 1478: 19,000 + 20,000 = 39,000 FCFA (bourse supprimée)
--   Total payé: 126,000 FCFA + 1 bourse 20,000 = 146,000 FCFA
--
-- DAVID EMMANUEL (101):
--   - Paiement 119: 31,000 FCFA + bourse 20,000 = GARDÉ
--   - Paiement 120: 42,000 + 20,000 = 62,000 FCFA (bourse supprimée)
--   Total payé: 93,000 FCFA + 1 bourse 20,000 = 113,000 FCFA
--
-- PAUL CYRIL (51):
--   - Paiement 70: 50,000 FCFA + bourse 20,000 = GARDÉ
--   - Paiement 1117: 20,000 + 20,000 = 40,000 FCFA (bourse supprimée)
--   Total payé: 90,000 FCFA + 1 bourse 20,000 = 110,000 FCFA