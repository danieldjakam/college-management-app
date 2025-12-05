# 🧪 GUIDE : Tester la correction en local avec la BD de production

## 🎯 Objectif

Importer la base de données de production en local, tester la correction, puis l'appliquer en production en toute sécurité.

---

## ✅ Étape 1 : Préparer l'import

### 1.1 Vérifiez que MySQL est démarré

Sur macOS avec XAMPP :
- Ouvrez XAMPP
- Vérifiez que MySQL est démarré (bouton vert)

### 1.2 Vérifiez votre fichier d'export

Vous devez avoir un fichier `.sql` exporté depuis la production. Par exemple :
- `~/Downloads/production_export.sql`
- `~/Desktop/c0admin_20251205.sql`

**Taille typique :** 50-500 MB selon le nombre de données

---

## ✅ Étape 2 : Import de la base de données

### Option A : Script automatique (RECOMMANDÉ)

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

# Remplacez par le chemin de votre fichier SQL
./scripts/import_production_db.sh ~/Downloads/votre_fichier.sql
```

**Le script va :**
- ✅ Vérifier que le fichier existe
- ✅ Vous demander confirmation
- ✅ Importer avec des paramètres optimisés
- ✅ Afficher la progression

**Temps estimé :** 5-15 minutes

### Option B : Import manuel (si le script échoue)

```bash
# Import direct via MySQL
/Applications/XAMPP/bin/mysql -u root -p c0admin < ~/Downloads/votre_fichier.sql

# Entrez votre mot de passe MySQL quand demandé
```

**⏱️ Attendez que l'import se termine avant de continuer !**

---

## ✅ Étape 3 : Test complet automatique

Une fois l'import terminé, exécutez le script de test :

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

./scripts/test_local_flow.sh
```

**Ce script va :**
1. 🔍 Vérifier la connexion à la BD
2. 📋 Exécuter le diagnostic
3. 🔧 Appliquer la correction
4. ✅ Vérifier que tout est OK

Le script est interactif et vous demandera de valider chaque étape.

---

## ✅ Étape 4 : Test dans l'interface web

### 4.1 Démarrez le serveur Laravel

Dans un terminal :
```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back
php artisan serve
```

### 4.2 Démarrez le frontend React

Dans un autre terminal :
```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/front
npm start
```

### 4.3 Testez la génération de PV

1. Ouvrez votre navigateur : `http://localhost:3006`
2. Connectez-vous avec un compte admin
3. Allez sur **PV → Génération de PV**
4. Sélectionnez une classe (par exemple "1er ACC A")
5. **Vérifiez la liste des évaluations :**

**❌ AVANT (incorrect) :**
```
1. Séquence 1 - Trimestre 1
2. Séquence 2 - Trimestre 1
3. Séquence 3 - Trimestre 1  ← MAUVAIS !
4. Composition 1 - Trimestre 1
```

**✅ APRÈS (correct) :**
```
1. Séquence 1 - Trimestre 1
2. Séquence 2 - Trimestre 1
3. Composition 1 - Trimestre 1  ← BON !
```

### 4.4 Générez un PV de test

1. Sélectionnez "Composition 1"
2. Cliquez sur "Générer le PV"
3. **Vérifiez le PDF généré :**
   - Le titre doit être : "Composition 1 - Trimestre 1"
   - Pas de "Séquence 3" ou "Séquence 0"

---

## ✅ Étape 5 : Vérification des bulletins

1. Allez sur **Bulletins → Génération**
2. Sélectionnez un étudiant
3. Générez un bulletin de Trimestre 1
4. **Vérifiez que les moyennes sont correctes**

---

## ✅ Étape 6 : Application en production

Si tout fonctionne parfaitement en local, appliquez la même correction en production.

### 6.1 Uploadez les scripts sur le serveur

Via FTP/SFTP, uploadez ces 3 fichiers dans `/path/to/project/back/scripts/` :
- `diagnose_sequences_production.php`
- `fix_sequences_production.php`
- `README_SEQUENCES_PRODUCTION.md`

**OU** via Git :
```bash
# Sur votre machine locale
git add scripts/
git commit -m "Add sequence correction scripts"
git push origin main

# Sur le serveur
git pull origin main
```

### 6.2 Connectez-vous au serveur

```bash
ssh votre_user@votre_serveur.com
cd /path/to/project/back
```

### 6.3 Sauvegarde OBLIGATOIRE

```bash
mysqldump -u votre_user -p votre_base > backup_avant_correction_$(date +%Y%m%d_%H%M%S).sql
```

**⚠️ Ne sautez PAS cette étape !**

### 6.4 Exécutez les scripts

```bash
# Diagnostic
php artisan tinker < scripts/diagnose_sequences_production.php

# Vérifiez le résultat, puis correction
php artisan tinker < scripts/fix_sequences_production.php
```

### 6.5 Testez en production

1. Allez sur votre site en production
2. Générez un PV
3. Vérifiez que "Composition 1" s'affiche correctement

---

## 🆘 En cas de problème

### L'import prend trop de temps (>30 min)

Le fichier est peut-être trop gros. Augmentez les limites MySQL :

Éditez `/Applications/XAMPP/etc/my.cnf` :
```ini
[mysqld]
max_allowed_packet=1G
net_buffer_length=32M
wait_timeout=3600
```

Redémarrez MySQL dans XAMPP.

### Erreur "Table doesn't exist"

La base de données n'est pas complètement importée. Réessayez l'import.

### Les séquences ne sont toujours pas corrigées

Vérifiez que le script `fix_sequences_production.php` s'est exécuté sans erreur.

Réexécutez le diagnostic :
```bash
php artisan tinker < scripts/diagnose_sequences_production.php
```

### Le PV affiche toujours "Séquence 3" en local

1. Videz le cache Laravel :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

2. Redémarrez les serveurs (Laravel + React)

3. Rafraîchissez le navigateur (Cmd+Shift+R)

---

## ✅ Checklist finale

Avant d'appliquer en production, vérifiez :

- [ ] L'import local s'est terminé sans erreur
- [ ] Le diagnostic ne montre plus d'anomalies
- [ ] Le script de correction s'est exécuté sans erreur
- [ ] Les PV affichent "Composition 1" en local
- [ ] Les bulletins se génèrent correctement en local
- [ ] Aucune note n'a disparu
- [ ] Vous avez uploadé les scripts sur le serveur
- [ ] Vous avez fait une sauvegarde de la BD de production
- [ ] Vous êtes prêt à appliquer en production

---

## 📞 Support

Si vous rencontrez un problème :

1. Lisez attentivement les messages d'erreur
2. Vérifiez les logs Laravel : `storage/logs/laravel.log`
3. Consultez le README : `scripts/README_SEQUENCES_PRODUCTION.md`
4. N'hésitez pas à demander de l'aide !

---

**🎉 Bonne chance avec la correction !**
