# 📦 GUIDE : Export de Base de Données en Production

Guide complet pour exporter la base de données `c0admin` depuis le serveur de production sans timeout.

---

## 🎯 Problème

L'export via phpMyAdmin prend trop de temps ou échoue à cause de :
- ✗ Taille importante de la base de données
- ✗ Timeout du serveur web
- ✗ Limite de mémoire PHP
- ✗ Tables volumineuses (grades, payments, attendances)

---

## ✅ Solutions Recommandées

### 🔥 Solution 1 : Commande Laravel Artisan (LA MEILLEURE)

Si vous avez accès SSH au serveur de production :

```bash
# Se connecter au serveur
ssh user@votre-serveur.com

# Aller dans le dossier du projet
cd /path/to/college-management-app/back

# Lancer l'export
php artisan db:backup --compress

# Export créé dans: storage/app/backups/backup_c0admin_YYYYMMDD_HHMMSS.sql.gz
```

**Avantages :**
- ✅ Pas de timeout
- ✅ Compression automatique (réduit 80-90% de la taille)
- ✅ Garde les 5 derniers backups automatiquement
- ✅ Rapide (2-5 minutes pour grosse base)

**Télécharger le backup :**
```bash
# Depuis votre machine locale
scp user@serveur:/path/to/back/storage/app/backups/backup_*.sql.gz ./
```

---

### 🔥 Solution 2 : Export mysqldump direct (TRÈS RAPIDE)

Si vous avez accès SSH :

```bash
# Se connecter au serveur
ssh user@votre-serveur.com

# Export avec compression
mysqldump -u DB_USER -p DB_NAME | gzip -9 > c0admin_$(date +%Y%m%d).sql.gz

# Télécharger sur votre machine locale
# (depuis votre machine, PAS depuis le serveur)
scp user@serveur:~/c0admin_20260119.sql.gz ./
```

**Avec exclusion des tables temporaires (encore plus rapide) :**
```bash
mysqldump -u DB_USER -p \
  --single-transaction \
  --quick \
  --skip-comments \
  --ignore-table=c0admin.telescope_entries \
  --ignore-table=c0admin.telescope_entries_tags \
  --ignore-table=c0admin.telescope_monitoring \
  --ignore-table=c0admin.cache \
  --ignore-table=c0admin.cache_locks \
  --ignore-table=c0admin.sessions \
  --ignore-table=c0admin.jobs \
  --ignore-table=c0admin.job_batches \
  c0admin | gzip -9 > backup_optimized.sql.gz
```

---

### 🔥 Solution 3 : Script PHP sur le serveur

Si vous n'avez PAS accès SSH, mais pouvez uploader des fichiers :

**Étapes :**

1. **Uploadez le fichier** `export_db_production.php` sur votre serveur (racine du site)

2. **Modifiez les credentials** dans le fichier :
```php
$dbHost = '127.0.0.1';
$dbName = 'c0admin';
$dbUser = 'VOTRE_USER';  // ⚠️ À CHANGER
$dbPass = 'VOTRE_PASS';  // ⚠️ À CHANGER
```

3. **Définissez une clé secrète** :
```php
define('SECRET_KEY', 'ma_cle_secrete_123456');
```

4. **Exécutez via navigateur** :
```
https://votre-site.com/export_db_production.php?key=ma_cle_secrete_123456
```

5. **Téléchargez le backup** :
```
https://votre-site.com/export_db_production.php?key=ma_cle_secrete_123456&download=1
```

6. **⚠️ IMPORTANT** : Supprimez le fichier après utilisation !

---

### 🔥 Solution 4 : phpMyAdmin OPTIMISÉ (dernier recours)

Si AUCUNE autre solution ne fonctionne, utilisez phpMyAdmin avec ces paramètres optimaux :

#### Configuration Étape par Étape :

1. **Méthode d'exportation** : `Personnalisée` ✅

2. **Format** : `SQL` ✅

3. **Tables** : `Tout sélectionner` ✅

4. **SORTIE** (le plus important) :
   - ✅ Cocher `Enregistrer la sortie vers un fichier`
   - **Compression** : Choisir `gzippé` (au lieu de "Aucun(e)")
   - ✅ **Cocher `Exporter les tables en fichiers séparés`** 🔥 (CRITIQUE!)
   - **Ignorer les tables de plus de** : Mettre `50` Mio

5. **Options spécifiques au format** :
   - ❌ **Décocher** `Afficher les commentaires`
   - ❌ **Décocher** `Inclure un horodatage`
   - ❌ **Décocher** `Afficher les relations de clés étrangères`
   - ❌ **Décocher** `Afficher les types MIME`
   - ❌ **Décocher** `Utiliser le mode transactionnel`
   - ✅ **Cocher** `Désactiver la vérification des clés étrangères`

6. **Options de création d'objets** :
   - ✅ Cocher `Ajouter une instruction DROP TABLE`
   - ❌ Décocher `IF NOT EXISTS`

7. **Options de création de données** :
   - Syntaxe : `insérer des lignes multiples avec chaque instruction INSERT`
   - **Taille maximale de la requête** : Réduire à `10000` (au lieu de 50000)

8. **Cliquer sur "Exporter"**

**Résultat :**
- Vous obtiendrez un fichier ZIP contenant plusieurs fichiers SQL (un par table)
- Beaucoup plus rapide et fiable
- Chaque table s'exporte indépendamment (pas de timeout global)

---

### 🔥 Solution 5 : Export par Groupes (si tout échoue)

Exportez en plusieurs fois via phpMyAdmin :

#### **Groupe 1 : Configuration** (très petit)
```
academic_periods, academic_system_config, school_settings, school_years,
sequences, trimesters, sections, levels, subjects, subject_groups,
geolocation_zones, card_layout_settings, bus_settings, grading_scales
```

#### **Groupe 2 : Utilisateurs et structure** (petit)
```
users, teachers, departments, parent_guardians, school_classes,
class_series, class_series_subjects, teacher_assignments, main_teachers
```

#### **Groupe 3 : Élèves** (moyen)
```
students, parent_student_relationships, student_rame_status,
student_discipline, student_cards, supervisor_class_assignments
```

#### **Groupe 4 : Notes et évaluations** (gros)
```
evaluations, grades, bulletin_generations, student_evaluations
```

#### **Groupe 5 : Paiements** (gros)
```
payments, payment_details, payment_tranches, class_payment_amounts,
class_scholarships, documentary_fees
```

#### **Groupe 6 : Présences** (très gros)
```
attendances, student_attendances, teacher_attendances, staff_attendances,
daily_attendance_states
```

#### **Groupe 7 : Autres tables**
Toutes les tables restantes SAUF :
```
telescope_entries, telescope_entries_tags, telescope_monitoring,
cache, cache_locks, sessions, jobs, job_batches, failed_jobs
```

**Comment faire :**
1. Sélectionnez uniquement les tables du groupe
2. Exportez en `gzippé`
3. Nommez le fichier : `c0admin_groupe1.sql.gz`
4. Répétez pour chaque groupe

---

## 📊 Comparaison des Solutions

| Solution | Vitesse | Facilité | Accès requis | Compression |
|----------|---------|----------|--------------|-------------|
| Artisan Command | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | SSH | ✅ Oui |
| mysqldump SSH | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | SSH | ✅ Oui |
| Script PHP | ⭐⭐⭐⭐ | ⭐⭐⭐ | FTP/SFTP | ✅ Oui |
| phpMyAdmin Optimisé | ⭐⭐⭐ | ⭐⭐⭐ | phpMyAdmin | ✅ Oui |
| Export par Groupes | ⭐⭐ | ⭐⭐ | phpMyAdmin | ✅ Oui |

---

## 🔄 Restauration du Backup

### Depuis un fichier .sql.gz :

```bash
# Décompresser et importer
gunzip -c backup.sql.gz | mysql -u root -p c0admin

# Ou en une seule commande
zcat backup.sql.gz | mysql -u root -p c0admin
```

### Depuis plusieurs fichiers (export par tables) :

```bash
# Décompresser tous les fichiers
gunzip *.sql.gz

# Importer chaque fichier
for file in *.sql; do
    echo "Import de $file..."
    mysql -u root -p c0admin < "$file"
done
```

### Via Laravel Artisan (si commande créée) :

```bash
php artisan db:restore storage/app/backups/backup_20260119.sql.gz
```

---

## ⚡ Astuces pour Optimiser l'Export

### 1. Ignorer les tables temporaires

Ces tables peuvent être ignorées car elles se régénèrent :
```
cache, cache_locks, sessions, jobs, job_batches, failed_jobs,
telescope_entries, telescope_entries_tags, telescope_monitoring
```

### 2. Augmenter les timeouts (si accès serveur)

Dans `php.ini` :
```ini
max_execution_time = 600
memory_limit = 512M
```

Dans `.htaccess` :
```apache
php_value max_execution_time 600
php_value memory_limit 512M
```

### 3. Planifier des exports automatiques

Ajoutez dans `back/app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    // Backup quotidien à 2h du matin
    $schedule->command('db:backup --compress')
             ->daily()
             ->at('02:00');
}
```

---

## 🆘 En cas de problème

### Erreur : "MySQL has gone away"
```bash
# Augmenter max_allowed_packet
mysql -u root -p -e "SET GLOBAL max_allowed_packet=1073741824;"
```

### Erreur : "Out of memory"
```bash
# Utiliser --quick option
mysqldump --quick --single-transaction -u root -p c0admin | gzip > backup.sql.gz
```

### Timeout persistant
- Utilisez l'export par groupes de tables
- Contactez votre hébergeur pour augmenter les limites

---

## 📝 Checklist Finale

Avant de commencer l'export :
- [ ] Vérifier l'espace disque disponible (au moins 2x la taille de la base)
- [ ] Choisir la solution adaptée à votre accès (SSH, FTP, phpMyAdmin)
- [ ] Tester avec une petite table d'abord
- [ ] Prévoir un créneau de faible activité si possible
- [ ] Vérifier que le backup est complet après export

Après l'export :
- [ ] Vérifier la taille du fichier (ne doit pas être 0 Ko)
- [ ] Tester la restauration sur base locale
- [ ] Sauvegarder le fichier en lieu sûr (3 copies : local, cloud, externe)
- [ ] Supprimer les fichiers temporaires du serveur
- [ ] Documenter la date et version du backup

---

## 🔐 Sécurité

⚠️ **IMPORTANT** : Les backups contiennent des données sensibles !

- Ne partagez JAMAIS les backups publiquement
- Chiffrez les backups si envoi par email/cloud
- Supprimez les fichiers PHP d'export après utilisation
- Utilisez des clés secrètes complexes
- Changez les mots de passe après export si exposition

---

**Besoin d'aide ?**
Consultez les logs Laravel : `back/storage/logs/laravel.log`
