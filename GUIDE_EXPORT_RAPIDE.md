# 🚀 GUIDE RAPIDE - Export Base de Données Production

## 📋 Contexte
Vous avez un problème d'**erreur de calcul** (bulletins, paiements, etc.) en production et nous devons exporter la base de données pour analyse et correction.

---

## ⚡ Méthode Recommandée (PLUS RAPIDE)

### Option 1: Via SSH avec le script Bash

**Depuis votre serveur de production via SSH:**

```bash
# 1. Se connecter au serveur
ssh user@votre-serveur-production.com

# 2. Aller dans le dossier du projet
cd /path/to/college-management-app

# 3. Lancer le script d'export
./export_production_db.sh

# Suivre les instructions à l'écran
```

Le script va:
- ✅ Charger automatiquement les credentials depuis `back/.env`
- ✅ Vous demander si vous voulez exclure les tables temporaires (cache, sessions)
- ✅ Compresser automatiquement le backup (.gz)
- ✅ Vous montrer la taille finale et le chemin du fichier
- ✅ Vérifier l'intégrité du fichier

**Télécharger le backup sur votre machine locale:**

```bash
# Depuis votre machine locale (PAS depuis le serveur)
scp user@serveur:/path/to/college-management-app/backups/backup_production_*.sql.gz ./
```

---

### Option 2: Via Commande Laravel Artisan

**Depuis votre serveur de production via SSH:**

```bash
# 1. Se connecter au serveur
ssh user@votre-serveur-production.com

# 2. Aller dans le dossier backend
cd /path/to/college-management-app/back

# 3. Lancer la commande Artisan
php artisan db:backup --compress

# Le backup sera créé dans: storage/app/backups/
```

**Télécharger le backup:**

```bash
# Depuis votre machine locale
scp user@serveur:/path/to/back/storage/app/backups/backup_c0admin_*.sql.gz ./
```

---

### Option 3: Export mysqldump direct

**Depuis votre serveur de production via SSH:**

```bash
# 1. Se connecter au serveur
ssh user@votre-serveur-production.com

# 2. Exporter avec compression (adaptez les credentials)
mysqldump -u DB_USER -p \
  --single-transaction \
  --quick \
  --skip-comments \
  c0admin | gzip -9 > backup_$(date +%Y%m%d).sql.gz

# 3. Télécharger sur votre machine
# (depuis votre machine locale)
scp user@serveur:~/backup_*.sql.gz ./
```

---

## 📥 Restauration en Local

### Méthode 1: Import direct

```bash
# Décompresser et importer en une seule commande
gunzip -c backup_production_20260125.sql.gz | mysql -u root -p c0admin

# Ou avec zcat (sur macOS/Linux)
zcat backup_production_20260125.sql.gz | mysql -u root -p c0admin
```

### Méthode 2: Import en deux étapes

```bash
# 1. Décompresser d'abord
gunzip backup_production_20260125.sql.gz

# 2. Importer ensuite
mysql -u root -p c0admin < backup_production_20260125.sql
```

### Méthode 3: Via phpMyAdmin (si fichier pas trop gros)

1. Décompresser le fichier `.sql.gz` → `.sql`
2. Ouvrir phpMyAdmin
3. Sélectionner la base `c0admin`
4. Onglet "Importer"
5. Sélectionner le fichier `.sql`
6. Cliquer "Exécuter"

---

## 🔍 Vérifications Après Import

### 1. Vérifier le nombre de tables

```bash
mysql -u root -p c0admin -e "SHOW TABLES;" | wc -l
```

Vous devriez avoir **environ 70-80 tables**.

### 2. Vérifier les données critiques

```bash
mysql -u root -p c0admin
```

```sql
-- Nombre d'étudiants
SELECT COUNT(*) FROM students;

-- Nombre de classes
SELECT COUNT(*) FROM school_classes;

-- Nombre de notes
SELECT COUNT(*) FROM grades;

-- Nombre de paiements
SELECT COUNT(*) FROM payments;

-- Nombre de bulletins générés
SELECT COUNT(*) FROM bulletin_generations;

-- Vérifier l'année scolaire courante
SELECT * FROM school_years WHERE is_current = 1;
```

### 3. Tester l'application localement

```bash
cd back
php artisan serve

# Dans un autre terminal
cd front
npm start
```

Accédez à: http://localhost:3006

---

## 🐛 Analyse du Problème de Calcul

Une fois la base importée, voici comment analyser les erreurs de calcul:

### A. Problème de Bulletin

```sql
-- Vérifier les notes d'un étudiant spécifique
SELECT
    s.name AS subject,
    g.score,
    g.max_score,
    g.coefficient,
    seq.name AS sequence,
    t.name AS trimestre
FROM grades g
JOIN class_series_subjects css ON g.class_series_subject_id = css.id
JOIN subjects s ON css.subject_id = s.id
JOIN sequences seq ON g.sequence_id = seq.id
JOIN trimesters t ON g.trimester_id = t.id
WHERE g.student_id = XXX  -- Remplacer par l'ID de l'étudiant
ORDER BY t.id, seq.id, s.name;
```

### B. Problème de Paiement

```sql
-- Vérifier les paiements d'un étudiant
SELECT
    p.payment_date,
    p.total_amount,
    pd.fee_type,
    pd.amount AS amount_paid,
    pd.required_amount,
    s.percentage AS scholarship_percentage
FROM payments p
JOIN payment_details pd ON p.id = pd.payment_id
LEFT JOIN class_scholarships s ON p.class_scholarship_id = s.id
WHERE p.student_id = XXX  -- Remplacer par l'ID de l'étudiant
ORDER BY p.payment_date;

-- Vérifier les réductions manuelles
SELECT * FROM student_manual_discounts
WHERE student_id = XXX;
```

### C. Problème de Moyenne/DS

```bash
cd back
php artisan tinker
```

```php
use App\Services\BulletinService;
$service = new BulletinService();

// Calculer la moyenne DS d'un étudiant
$studentId = 123; // Remplacer
$subjectId = 5;   // Remplacer
$trimesterId = 1; // Trimestre 1

$ds = $service->calculateDSAverage($trimesterId, $studentId, $subjectId);
echo "DS Average: $ds\n";

// Voir les notes de séquences individuelles (Deuxième Cycle)
$seqGrades = $service->getIndividualSequenceGrades($trimesterId, $studentId, $subjectId);
print_r($seqGrades);

// Voir la note de composition
$comp = $service->getCompositionGrade($trimesterId, $studentId, $subjectId);
echo "Composition: $comp\n";
```

---

## 📝 Checklist Complète

### Avant l'export (sur serveur production)

- [ ] Vérifier l'espace disque disponible: `df -h`
- [ ] S'assurer que mysqldump est installé: `which mysqldump`
- [ ] Vérifier les credentials dans `.env`
- [ ] Choisir la méthode d'export (Script Bash / Artisan / mysqldump)

### Pendant l'export

- [ ] Noter l'heure de début
- [ ] Surveiller la création du fichier: `ls -lh backups/`
- [ ] Vérifier qu'il n'y a pas d'erreurs dans la console

### Après l'export

- [ ] Vérifier la taille du fichier (ne doit pas être 0 Ko)
- [ ] Tester l'intégrité: `gzip -t backup_*.sql.gz`
- [ ] Télécharger le fichier sur votre machine locale
- [ ] **IMPORTANT**: Supprimer le backup du serveur après téléchargement (sécurité)

### Import en local

- [ ] Sauvegarder votre base locale actuelle (si existante)
- [ ] Créer/vider la base `c0admin`: `mysql -u root -p -e "DROP DATABASE IF EXISTS c0admin; CREATE DATABASE c0admin;"`
- [ ] Importer le backup
- [ ] Vérifier le nombre de tables
- [ ] Vérifier les données critiques (queries ci-dessus)

### Analyse du problème

- [ ] Identifier l'étudiant/classe concerné(e)
- [ ] Reproduire le problème en local
- [ ] Exécuter les requêtes SQL de diagnostic
- [ ] Utiliser `php artisan tinker` pour les calculs de bulletin
- [ ] Documenter le problème trouvé

---

## 🆘 En Cas de Problème

### Erreur: "MySQL has gone away" pendant l'export

```bash
# Augmenter max_allowed_packet
mysql -u root -p -e "SET GLOBAL max_allowed_packet=1073741824;"

# Puis relancer l'export
```

### Erreur: "Out of memory" pendant l'import

```bash
# Augmenter memory_limit dans php.ini
sudo nano /etc/php/8.2/cli/php.ini
# memory_limit = 512M

# Ou utiliser --quick option
mysql -u root -p --quick c0admin < backup.sql
```

### Timeout persistant

Utilisez l'export par groupes de tables (voir `GUIDE_EXPORT_BASE_PRODUCTION.md`)

---

## 🔐 Sécurité

⚠️ **TRÈS IMPORTANT**:

- ❌ Ne partagez JAMAIS le backup sur GitHub, Google Drive public, etc.
- ✅ Supprimez le backup du serveur après téléchargement
- ✅ Chiffrez le fichier si vous devez l'envoyer par email
- ✅ Utilisez une connexion sécurisée (SSH, SFTP) pour le transfert
- ✅ Changez les mots de passe après résolution si backup exposé

---

## 📞 Prochaines Étapes

1. **Exportez la base** avec la méthode de votre choix
2. **Partagez-moi** les informations suivantes:
   - Quelle erreur de calcul exactement ? (bulletin, paiement, moyenne DS ?)
   - Pour quel étudiant/classe ?
   - Quel est le résultat attendu vs. le résultat obtenu ?
   - Captures d'écran si possible

3. **J'analyserai** le problème avec la base de données réelle
4. **Nous corrigerons** ensemble le problème
5. **Nous testerons** la correction en local
6. **Nous déploierons** la correction en production

---

**Besoin d'aide ?**

Consultez le guide complet: `GUIDE_EXPORT_BASE_PRODUCTION.md`

**Contact:** [Votre canal de communication préféré]
