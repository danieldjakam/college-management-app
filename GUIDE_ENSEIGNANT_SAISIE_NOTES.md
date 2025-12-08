# 📚 Guide Enseignant - Saisie des Notes de Composition

## Table des matières

1. [Connexion à la plateforme](#1-connexion-à-la-plateforme)
2. [Accès au tableau de bord](#2-accès-au-tableau-de-bord)
3. [Sélection du trimestre et de la séquence](#3-sélection-du-trimestre-et-de-la-séquence)
4. [Sélection de la matière](#4-sélection-de-la-matière)
5. [Saisie des notes](#5-saisie-des-notes)
6. [Validation et enregistrement](#6-validation-et-enregistrement)
7. [Questions fréquentes](#7-questions-fréquentes)

---

DB_USERNAME=c0admin_cpb
DB_PASSWORD=Estuaire@2025

## 1. Connexion à la plateforme

mysqldump -u c0admin -p > backup.sql

### Étape 1.1 : Ouvrir le site

- Ouvrez votre navigateur web (Chrome, Firefox, Safari, etc.)
- Tapez l'adresse : **http://admin.cpb-douala.com** ou **http://admin1.cpb-douala.com**
- Appuyez sur **Entrée**

### Étape 1.2 : Entrer vos identifiants

- **Nom d'utilisateur** : Votre identifiant fourni par l'administration
- **Mot de passe** : Votre mot de passe personnel
- Cliquez sur le bouton **"Se connecter"**

> ⚠️ **Important** : Si vous avez oublié votre mot de passe, contactez le secrétariat ou l'administration.

---

## 2. Accès au tableau de bord

Après connexion, vous arrivez sur votre **tableau de bord enseignant** qui affiche :

- Vos statistiques (nombre de classes, matières, élèves)
- La liste de vos matières assignées
- Vos classes où vous êtes professeur principal (si applicable)

---

## 3. Sélection du trimestre et de la séquence

### Étape 3.1 : Accéder aux trimestres

Dans le menu de gauche, cliquez sur :

- **"📅 Trimestres"** ou **"Mes Trimestres"**

Vous verrez la liste des trimestres de l'année scolaire en cours :

- **Trimestre 1**
- **Trimestre 2**
- **Trimestre 3**

### Étape 3.2 : Sélectionner un trimestre

Cliquez sur le trimestre souhaité (exemple : **Trimestre 1**)

### Étape 3.3 : Choisir une séquence

Pour chaque trimestre, vous verrez les séquences disponibles :

- **Séquence 1**
- **Séquence 2**
- **Composition** (Évaluation finale du trimestre)

Cliquez sur **"Composition"** pour saisir les notes de composition.

---

## 4. Sélection de la matière

### Étape 4.1 : Liste des matières

Après avoir sélectionné "Composition", vous verrez la liste de **vos matières assignées** pour ce trimestre.

Exemple d'affichage :

```
📖 Mathématiques
   Classe: 6ème A - Scientifique
   Coefficient: 4

📖 Physique
   Classe: 5ème B - Scientifique
   Coefficient: 3
```

### Étape 4.2 : Cliquer sur une matière

Cliquez sur la matière pour laquelle vous voulez saisir les notes.

Vous serez redirigé vers la **liste des élèves** de cette classe pour cette matière.

---

## 5. Création de l'évaluation

> ⚠️ **IMPORTANT** : Avant de saisir les notes, vous devez d'abord **créer une évaluation**.

### Étape 5.1 : Accéder à la création d'évaluation

Deux options possibles :

**Option A - Depuis le menu principal :**

1. Cliquez sur **"📝 Mes Évaluations"** dans le menu de gauche
2. Cliquez sur le bouton **"+ Créer une évaluation"** en haut à droite

**Option B - Depuis la liste des matières :**

1. Après avoir sélectionné Trimestre → Composition → Matière
2. Cliquez sur **"+ Créer une évaluation"**

### Étape 5.2 : Remplir le formulaire d'évaluation

Vous verrez un formulaire avec les champs suivants :

**Champs obligatoires :**

1. **Nom de l'évaluation** 📝

   - Exemple : "Composition Trimestre 1 - Mathématiques 6ème A"
   - Soyez précis pour identifier facilement l'évaluation

2. **Séquence** 📅

   - Sélectionnez la séquence concernée
   - Pour une composition : Choisissez "Composition Trimestre X"

3. **Matière** 📖

   - Sélectionnez la matière dans laquelle vous évaluez
   - Seules vos matières assignées apparaissent

4. **Classe/Série** 🏫

   - Sélectionnez la classe concernée
   - Exemple : "6ème A - Scientifique"

5. **Type d'évaluation** 🎯

   - **Composition** : Pour les évaluations de fin de trimestre
   - **Travaux Pratiques** : Pour les TP ou autres évaluations

6. **Date de l'évaluation** 📆

   - Cliquez sur le calendrier et sélectionnez la date

7. **Note maximale** 🔢
   - Par défaut : **20**
   - Vous pouvez changer (ex: 10, 25, etc.)

**Champs optionnels :**

8. **Description** 📋

   - Ajoutez des détails sur l'évaluation (optionnel)
   - Exemple : "Composition portant sur les chapitres 1 à 5"

9. **Coefficient** ⚖️
   - Le coefficient de la matière s'affiche automatiquement
   - Vous ne pouvez généralement pas le modifier (défini par l'administration)

### Étape 5.3 : Valider la création

1. Vérifiez que tous les champs sont correctement remplis
2. Cliquez sur le bouton **"Créer l'évaluation"**
3. Un message de confirmation apparaît : ✅ **"Évaluation créée avec succès"**

---

## 6. Saisie des notes

### Étape 6.1 : Accéder à la saisie des notes

Après avoir créé l'évaluation, vous pouvez maintenant saisir les notes.

**Deux façons d'accéder :**

**Option A - Directement après création :**

- Après avoir créé l'évaluation, cliquez sur **"Saisir les notes"**

**Option B - Depuis la liste des évaluations :**

1. Allez dans **"📝 Mes Évaluations"**
2. Trouvez votre évaluation dans la liste
3. Cliquez sur l'icône **👁️ (Œil)** ou **"Saisir les notes"**

### Étape 6.2 : Tableau de saisie des notes

Vous verrez un tableau avec tous les élèves de la classe :

| N°  | Matricule | Nom & Prénom | Note /20 | Actions |
| --- | --------- | ------------ | -------- | ------- |
| 1   | 25A00001  | NKOTTO Jean  | [ ]      | 💾      |
| 2   | 25A00002  | MBARGA Marie | [ ]      | 💾      |
| 3   | 25A00003  | OBAMA Paul   | [ ]      | 💾      |

### Étape 6.3 : Entrer les notes

Pour chaque élève :

1. **Cliquez dans la case "Note"** à côté du nom de l'élève
2. **Tapez la note** (exemple : 15.5)
3. La note doit être entre **0 et 20** (ou selon la note max définie)
4. Appuyez sur **Entrée** ou cliquez sur l'icône **💾 (Enregistrer)**

> ✅ **Astuce** : Vous pouvez utiliser la touche **Tab** pour passer rapidement d'un élève à l'autre.

### Étape 6.4 : Notes spéciales

- **Absent** : Si l'élève était absent, cochez la case **"Absent"** ou entrez **"ABS"**
- **Dispensé** : Si l'élève est dispensé, cochez **"Dispensé"**

---

## 7. Validation et enregistrement

### Étape 7.1 : Enregistrement automatique

- Chaque note est **enregistrée automatiquement** dès que vous cliquez sur 💾 ou appuyez sur Entrée
- Un message de confirmation apparaît : ✅ **"Note enregistrée avec succès"**

### Étape 7.2 : Vérifier vos saisies

Avant de quitter la page :

1. **Vérifiez** que toutes les notes sont bien enregistrées
2. Vous pouvez **modifier** une note en cliquant dessus et en entrant une nouvelle valeur
3. Les notes sont immédiatement mises à jour

### Étape 7.3 : Statistiques

En bas de page, vous verrez les statistiques de la classe :

- **Moyenne de la classe**
- **Note la plus haute**
- **Note la plus basse**
- **Nombre d'élèves notés**
- **Nombre d'absents**

---

## 8. Questions fréquentes

### ❓ Dois-je créer une évaluation à chaque fois ?

**Oui**, vous devez créer une nouvelle évaluation pour chaque composition/test. Une fois créée, vous pouvez saisir et modifier les notes de cette évaluation.

### ❓ Puis-je créer plusieurs évaluations pour la même matière ?

**Oui**, vous pouvez créer plusieurs évaluations (Séquence 1, Séquence 2, Composition, TP, etc.) pour une même matière et classe.

### ❓ Puis-je modifier une note déjà enregistrée ?

**Oui**, tant que les notes ne sont pas encore validées/clôturées par l'administration. Cliquez simplement sur la note et modifiez-la.

### ❓ Que faire si un élève était absent ?

Cochez la case **"Absent"** à côté de son nom. Vous pourrez entrer sa note plus tard lors de la session de rattrapage.

### ❓ Comment supprimer une note par erreur ?

Cliquez sur l'icône **🗑️ (Supprimer)** à côté de la note, puis confirmez la suppression.

### ❓ Puis-je entrer des notes décimales ?

**Oui**, vous pouvez entrer des notes avec décimales (exemple : 15.5, 12.75). Utilisez le point (.) comme séparateur.

### ❓ Que faire si je ne vois pas ma matière dans la liste ?

Contactez le **secrétariat** ou l'**administration**. Vous devez d'abord être assigné à cette matière pour cette classe.

### ❓ Les notes sont-elles visibles par les élèves immédiatement ?

**Non**, les notes ne sont visibles par les élèves et parents qu'après validation par l'administration.

### ❓ Puis-je saisir les notes depuis mon téléphone ?

**Oui**, la plateforme est accessible depuis un téléphone ou une tablette. Assurez-vous d'avoir une connexion internet stable.

### ❓ J'ai une erreur "Connexion au serveur impossible"

Vérifiez :

- Votre connexion internet
- Que vous utilisez la bonne adresse (http://admin.cpb-douala.com)
- Si le problème persiste, contactez le support technique

---

## 📞 Support et assistance

En cas de problème technique :

- **Contact** : Secrétariat du collège
- **Email** : support@cpb-douala.com (si disponible)
- **Téléphone** : [Numéro du secrétariat]

---

## 📝 Résumé rapide - Processus complet

### Étape 1 : Connexion

→ http://admin.cpb-douala.com

### Étape 2 : Créer l'évaluation (OBLIGATOIRE)

→ Menu **"📝 Mes Évaluations"** → **"+ Créer une évaluation"**
→ Remplir le formulaire (nom, séquence, matière, classe, type, date, note max)
→ Cliquer sur **"Créer l'évaluation"**

### Étape 3 : Saisir les notes

→ Depuis **"Mes Évaluations"** → Cliquer sur l'évaluation créée
→ Ou cliquer sur **"Saisir les notes"** directement après création
→ Entrer les notes pour chaque élève
→ Les notes s'enregistrent automatiquement ✅

### 🎯 Points clés à retenir

- ✅ **Toujours créer l'évaluation AVANT de saisir les notes**
- ✅ Une évaluation = Une composition/test spécifique
- ✅ Les notes sont enregistrées automatiquement
- ✅ Vous pouvez modifier les notes tant qu'elles ne sont pas clôturées

---

**Bonne saisie de notes !** 🎓

_Document créé le : 10 octobre 2025_
_Version : 1.0_
