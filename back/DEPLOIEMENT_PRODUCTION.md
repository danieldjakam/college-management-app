# 📋 GUIDE DE DÉPLOIEMENT EN PRODUCTION

## 🎯 Modifications incluses

### 1. Bulletin de séquence optimisé (1 page A4)
- **Fichier**: `resources/views/bulletins/cpbd_bulletin_pdf.html`
- **Changement**: Template optimisé pour tenir sur une seule page A4
- **Impact**: Tous les nouveaux bulletins de séquence générés utiliseront le nouveau format

### 2. Correction PVService (Type casting)
- **Fichier**: `app/Services/PVService.php`
- **Changement**: Ajout de cast `(float)` avant `number_format()` pour éviter les erreurs de typage
- **Impact**: Génération de PV plus stable

### 3. Script de correction des usernames
- **Fichier**: `fix_usernames_production.php` (nouveau)
- **Changement**: Script pour corriger automatiquement les usernames avec espaces
- **Impact**: Correction du problème de connexion pour les utilisateurs affectés

---

## 🚀 ÉTAPES DE DÉPLOIEMENT

### Étape 1 : Backup de la base de données (IMPORTANT!)

```bash
# Sur le serveur de production
cd /path/to/your/project
php artisan backup:run

# OU via mysqldump
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Étape 2 : Pull des modifications Git

```bash
# Sur le serveur de production
cd /path/to/your/project/back

# Vérifier la branche actuelle
git branch

# Si vous êtes sur 'main' ou 'master'
git pull origin main

# Si vous êtes sur 'personnals'
git pull origin personnals

# OU si vous voulez merger 'personnals' dans 'main'
git checkout main
git merge personnals
```

### Étape 3 : Vérifier les fichiers modifiés

```bash
# Vérifier que les fichiers sont bien à jour
ls -lh resources/views/bulletins/cpbd_bulletin_pdf.html
ls -lh app/Services/PVService.php
ls -lh fix_usernames_production.php
```

### Étape 4 : Corriger les usernames avec espaces

```bash
# Exécuter le script de correction
cd /path/to/your/project/back
php fix_usernames_production.php
```

**Résultat attendu:**
```
==============================================
  CORRECTION DES USERNAMES AVEC ESPACES
==============================================

Trouvé X utilisateur(s) avec des espaces:

✅ 'josephine b_98' -> 'josephineb_98' (ID: 98, JOSEPHINE B JOHNIE)
✅ 'angela dioh_111' -> 'angeladioh_111' (ID: 111, ANGELA DIOH NJINYERU)
✅ 'mirabel wei_114' -> 'mirabelwei_114' (ID: 114, MIRABEL WEI MBAIN)
...

==============================================
  RÉSUMÉ
==============================================
✅ Corrigés:   X
❌ Erreurs:    0
📊 Total:      X
==============================================

🎉 Tous les usernames ont été corrigés avec succès!
```

### Étape 5 : Vider le cache Laravel (Important!)

```bash
# Vider tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Étape 6 : Tester les bulletins de séquence

```bash
# Option 1: Via l'interface web
# - Se connecter avec un compte admin
# - Aller dans Bulletins > Générer bulletin de séquence
# - Sélectionner un élève et une séquence
# - Vérifier que le PDF tient sur 1 page

# Option 2: Via API (avec curl)
curl -X POST https://votre-domaine.com/api/bulletins/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 123,
    "bulletin_type": "sequence",
    "period_identifier": "seq1",
    "force": true
  }'
```

### Étape 7 : Tester la connexion corrigée

```bash
# Tester la connexion avec un username corrigé
curl -X POST https://votre-domaine.com/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "josephineb_98",
    "password": "password123"
  }'
```

**Résultat attendu:**
```json
{
  "success": true,
  "access_token": "eyJ0eXAi...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

## ⚠️ ROLLBACK (Si problème)

### Rollback Git
```bash
# Trouver le commit précédent
git log --oneline -5

# Revenir au commit précédent
git reset --hard COMMIT_HASH

# OU annuler juste les fichiers modifiés
git checkout HEAD~1 -- resources/views/bulletins/cpbd_bulletin_pdf.html
git checkout HEAD~1 -- app/Services/PVService.php
```

### Rollback Base de données (usernames)
```bash
# Restaurer le backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

---

## 🔍 VÉRIFICATIONS POST-DÉPLOIEMENT

### ✅ Checklist

- [ ] Le script `fix_usernames_production.php` s'est exécuté sans erreur
- [ ] Les bulletins de séquence tiennent sur 1 page A4
- [ ] Les utilisateurs avec usernames corrigés peuvent se connecter
- [ ] Les PV se génèrent sans erreur de typage
- [ ] Le cache Laravel a été vidé et optimisé
- [ ] Les logs ne montrent pas d'erreurs (`storage/logs/laravel.log`)

### 📊 Monitoring

```bash
# Surveiller les logs en temps réel
tail -f storage/logs/laravel.log

# Vérifier les erreurs récentes
grep -i "error" storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## 📞 SUPPORT

En cas de problème, vérifier:

1. **Les permissions des fichiers**
   ```bash
   chmod -R 755 resources/views/bulletins/
   chmod -R 755 storage/
   ```

2. **Les logs d'erreur**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. **La configuration de la base de données**
   ```bash
   php artisan tinker
   >>> User::count()
   ```

4. **Les variables d'environnement**
   ```bash
   cat .env | grep DB_
   ```

---

## 📝 NOTES

- Le script `fix_usernames_production.php` peut être supprimé après exécution
- Les bulletins déjà générés ne seront pas régénérés automatiquement
- Pour régénérer les anciens bulletins: utiliser `force=true` dans l'API
- Les usernames corrigés ne peuvent pas être annulés facilement (backup requis)

---

**Date de création**: 2025-01-12
**Dernière mise à jour**: 2025-01-12
**Auteur**: Claude Code
**Version**: 1.0
