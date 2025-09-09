-- Script SQL simple pour synchroniser les codes QR
-- Usage: Connectez-vous à MySQL et exécutez ce script

-- 1. D'abord, voir les utilisateurs qui vont être mis à jour
SELECT 
    id,
    name,
    role,
    qr_code AS ancien_qr,
    CONCAT('STAFF_', id) AS nouveau_qr
FROM users 
WHERE qr_code IS NULL OR qr_code = ''
ORDER BY id;

-- 2. Compter combien d'utilisateurs seront mis à jour
SELECT COUNT(*) as utilisateurs_a_mettre_a_jour
FROM users 
WHERE qr_code IS NULL OR qr_code = '';

-- 3. Effectuer la mise à jour (DÉCOMMENTEZ LA LIGNE SUIVANTE POUR EXÉCUTER)
-- UPDATE users SET qr_code = CONCAT('STAFF_', id), updated_at = NOW() WHERE qr_code IS NULL OR qr_code = '';

-- 4. Vérifier le résultat après mise à jour
-- SELECT id, name, role, qr_code FROM users WHERE qr_code LIKE 'STAFF_%' ORDER BY id;

-- 5. Statistiques finales
-- SELECT 
--     COUNT(*) as total_utilisateurs,
--     SUM(CASE WHEN qr_code IS NOT NULL AND qr_code != '' THEN 1 ELSE 0 END) as avec_qr,
--     SUM(CASE WHEN qr_code IS NULL OR qr_code = '' THEN 1 ELSE 0 END) as sans_qr
-- FROM users;