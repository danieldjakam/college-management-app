# 🔧 Scripts de Diagnostic et Correction des Séquences en Production

## 📋 Problème

En production, vous voyez "Séquence 3" au lieu de "Composition 1" dans le Trimestre 1 lors de la génération des PV.

**Structure attendue:**
- **Trimestre 1**: Séquence 1, Séquence 2, Composition 1
- **Trimestre 2**: Séquence 3, Séquence 4, Composition 2
- **Trimestre 3**: Composition 3 (uniquement)

## 🚀 Instructions pour la Production

### Étape 1: Diagnostic

Connectez-vous en SSH à votre serveur de production et exécutez:

```bash
cd /chemin/vers/votre/projet/back
php artisan tinker
```

Dans tinker, copiez-collez le contenu du fichier `scripts/diagnose_sequences_production.php`

**Ou** si vous avez accès aux fichiers:

```bash
php artisan tinker < scripts/diagnose_sequences_production.php
```

**Ce que fait ce script:**
- Liste toutes les séquences avec leurs propriétés
- Détecte les anomalies (compositions mal configurées, doublons, etc.)
- Affiche les statistiques des évaluations

**⚠️ Ne modifie rien dans la base de données** (lecture seule)

### Étape 2: Correction (si des anomalies sont détectées)

⚠️ **ATTENTION**: Ce script va modifier la base de données!

**Recommandations avant d'exécuter:**
1. ✅ Faites une sauvegarde complète de la base de données
2. ✅ Exécutez le script en dehors des heures de pointe
3. ✅ Prévenez les utilisateurs d'une maintenance

```bash
# Sauvegarde de la base de données (exemple avec mysqldump)
mysqldump -u root -p nom_base_de_donnees > backup_sequences_$(date +%Y%m%d_%H%M%S).sql
```

Puis exécutez le script de correction:

```bash
php artisan tinker
```

Copiez-collez le contenu de `scripts/fix_sequences_production.php`

**Ou:**

```bash
php artisan tinker < scripts/fix_sequences_production.php
```

**Ce que fait ce script:**
- ✅ Corrige le flag `is_composition` des compositions
- ✅ Met le `number` des compositions à 0
- ✅ Supprime les doublons de séquences
- ✅ Migre les évaluations vers les bonnes séquences
- ✅ Affiche un résumé des modifications

### Étape 3: Vérification

Après la correction, vérifiez que tout fonctionne:

1. **Générer un PV:**
   - Allez sur `/admin/pv`
   - Sélectionnez une classe
   - Vérifiez que vous voyez: "Séquence 1", "Séquence 2", "Composition 1" (et non "Séquence 3")
   - Générez un PV et vérifiez qu'il affiche "Composition 1 - Trimestre 1"

2. **Générer un bulletin:**
   - Testez la génération d'un bulletin pour un étudiant
   - Vérifiez que les moyennes sont correctes

3. **Vérifier les grades:**
   - Assurez-vous que toutes les notes sont toujours présentes
   - Aucune note ne doit avoir disparu

## 📝 Structure Finale Attendue

Après correction, voici ce que vous devriez avoir dans la table `sequences`:

```
=== TRIMESTRE 1 ===
ID 1 | Séquence 1    | Number: 1 | is_composition: 0
ID 2 | Séquence 2    | Number: 2 | is_composition: 0
ID 3 | Composition 1 | Number: 0 | is_composition: 1

=== TRIMESTRE 2 ===
ID 4 | Séquence 3    | Number: 3 | is_composition: 0
ID 5 | Séquence 4    | Number: 4 | is_composition: 0
ID 6 | Composition 2 | Number: 0 | is_composition: 1

=== TRIMESTRE 3 ===
ID 7 | Composition 3 | Number: 0 | is_composition: 1
```

## 🆘 En cas de Problème

Si quelque chose ne va pas après la correction:

### Restaurer la sauvegarde

```bash
mysql -u root -p nom_base_de_donnees < backup_sequences_YYYYMMDD_HHMMSS.sql
```

### Contacter le support

Si le problème persiste, fournissez:
1. Le résultat du script de diagnostic
2. Les messages d'erreur du script de correction
3. Les logs Laravel (`storage/logs/laravel.log`)

## 📚 Fichiers Impliqués

**Backend:**
- `app/Http/Controllers/PVController.php` - Génération des PV
- `app/Services/PVService.php` - Logique métier des PV
- `app/Models/Sequence.php` - Modèle Eloquent

**Frontend:**
- `front/src/pages/PV/PVGeneration.jsx` - Interface de génération

**Base de données:**
- Table `sequences` - Liste des séquences et compositions
- Table `evaluations` - Évaluations liées aux séquences
- Table `grades` - Notes des étudiants

## ✅ Checklist Finale

Après avoir exécuté les scripts:

- [ ] Les séquences ont les bons flags `is_composition`
- [ ] Les compositions ont `number = 0`
- [ ] Pas de doublons dans la table `sequences`
- [ ] Les PV affichent "Composition 1" et non "Séquence 3"
- [ ] Les bulletins se génèrent correctement
- [ ] Toutes les notes sont présentes
- [ ] Les moyennes sont correctes

## 🔄 Si vous créez de nouvelles séquences à l'avenir

Respectez toujours cette convention:

**Pour les séquences normales:**
```php
[
    'name' => 'Séquence X',
    'number' => X,  // 1, 2, 3, 4, etc.
    'is_composition' => false,
    'trimester_id' => Y
]
```

**Pour les compositions:**
```php
[
    'name' => 'Composition X',
    'number' => 0,  // TOUJOURS 0 pour les compositions!
    'is_composition' => true,
    'trimester_id' => X
]
```
