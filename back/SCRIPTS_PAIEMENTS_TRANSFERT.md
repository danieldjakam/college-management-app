# Scripts de Gestion des Paiements après Transfert

## Vue d'ensemble

Deux scripts sont disponibles pour gérer les paiements des élèves transférés :

1. **`payments:fix-transfers`** - Script original pour corriger les paiements pending
2. **`payments:analyze-transfers`** - Nouveau script d'analyse complète (RECOMMANDÉ)

---

## Script Recommandé : `payments:analyze-transfers`

### Description
Ce script analyse TOUS les élèves qui ont été transférés et identifie ceux qui ont encore des paiements en attente.

### Commandes disponibles

#### 1. Analyser tous les élèves transférés (mode lecture seule)
```bash
php artisan payments:analyze-transfers
```
Affiche tous les élèves transférés avec des problèmes de paiement, sans rien modifier.

#### 2. Corriger tous les élèves transférés
```bash
php artisan payments:analyze-transfers --fix
```
Analyse ET corrige automatiquement tous les paiements problématiques des élèves transférés.

#### 3. Analyser un élève spécifique par ID
```bash
php artisan payments:analyze-transfers --student-id=1336
```
Analyse uniquement l'élève avec l'ID spécifié.

#### 4. Analyser un élève spécifique par matricule
```bash
php artisan payments:analyze-transfers --student-number="25A01304"
```
Analyse uniquement l'élève avec le matricule spécifié.

#### 5. Corriger un élève spécifique
```bash
php artisan payments:analyze-transfers --fix --student-number="25A01304"
```
Corrige uniquement les paiements de l'élève spécifié.

---

## Script Original : `payments:fix-transfers`

### Description
Script pour corriger les paiements pending (ancienne version, plus restrictive).

### Commandes disponibles

#### 1. Analyser en mode dry-run
```bash
php artisan payments:fix-transfers --dry-run
```

#### 2. Corriger tous les paiements
```bash
php artisan payments:fix-transfers
```

#### 3. Corriger un élève spécifique
```bash
php artisan payments:fix-transfers --student-id=1336
```

---

## Comment ça fonctionne ?

### Logique de correction

Quand un élève est transféré d'une classe à une autre :

1. **Problème** : Le paiement a été fait pour l'ancienne classe (avec les frais d'inscription de l'ancienne classe)
2. **Solution** : Le script recalcule avec les frais de la nouvelle classe

### Exemple concret

**Situation initiale :**
- Élève payé : 150 000 FCFA dans la classe A (inscription = 80 000 FCFA)
- Élève transféré vers classe B (inscription = 60 000 FCFA)

**Correction appliquée :**
```
Inscription classe B : 60 000 FCFA
Reste               : 90 000 FCFA (affecté à la 1ère tranche)
Total               : 150 000 FCFA ✓
```

Le paiement est ensuite validé automatiquement avec une note explicative.

---

## Résultats Actuels

D'après l'analyse du **2 octobre 2025** :

```bash
php artisan payments:analyze-transfers
```

**Résultat :** ✅ Tous les paiements des élèves transférés sont corrects !

- 1 élève transféré trouvé dans le système
- MBOKOU LEORIS DIANE (25A01304) : Paiements corrects
- Aucune correction nécessaire

---

## Logs et Traçabilité

Tous les paiements corrigés sont enregistrés dans les logs Laravel :

```
storage/logs/laravel.log
```

Les informations suivantes sont conservées :
- ID du paiement corrigé
- ID et nom de l'élève
- Montant d'inscription calculé
- Montant restant affecté
- Date et heure de la correction

---

## Support et Dépannage

### Erreur : "Tranche inscription introuvable"
**Cause :** La tranche d'inscription (order = 1) n'est pas active.
**Solution :** Vérifier la table `payment_tranches` et activer la tranche d'inscription.

### Erreur : "Élève sans classe assignée"
**Cause :** L'élève n'a pas de `class_series_id` défini.
**Solution :** Assigner l'élève à une classe avant de corriger les paiements.

### Aucun élève transféré trouvé
**Cause :** Aucun paiement avec mention "Transfert" dans les notes.
**Solution :** Vérifier manuellement les paiements ou utiliser l'ancien script.

---

## Exemples d'utilisation

### Cas 1 : Vérification hebdomadaire
```bash
# Tous les lundis, vérifier s'il y a des problèmes
php artisan payments:analyze-transfers

# Si des problèmes sont trouvés, les corriger
php artisan payments:analyze-transfers --fix
```

### Cas 2 : Après un transfert manuel
```bash
# Un élève vient d'être transféré (ID 1234)
php artisan payments:analyze-transfers --student-id=1234

# Si un problème est détecté, corriger
php artisan payments:analyze-transfers --fix --student-id=1234
```

### Cas 3 : Élève signale un problème
```bash
# L'élève 25A01304 signale que son paiement n'apparaît pas
php artisan payments:analyze-transfers --student-number="25A01304"

# Corriger si nécessaire
php artisan payments:analyze-transfers --fix --student-number="25A01304"
```

---

## Notes importantes

⚠️ **Attention :**
- Les corrections sont **irréversibles**
- Toujours utiliser le mode analyse d'abord (sans --fix)
- Vérifier les logs après chaque correction
- En cas de doute, corriger élève par élève avec --student-id

✅ **Bonnes pratiques :**
- Faire une sauvegarde de la base avant correction massive
- Vérifier les montants après correction dans l'interface
- Tenir un registre des corrections effectuées

---

## Fichiers concernés

- **Script d'analyse** : `app/Console/Commands/AnalyzeTransferPayments.php`
- **Script de correction** : `app/Console/Commands/FixTransferPayments.php`
- **Logique de transfert** : `app/Http/Controllers/StudentController.php` (méthode `handlePaymentAdjustmentOnTransfer`)
