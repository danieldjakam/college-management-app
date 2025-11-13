# 📚 GUIDE UTILISATEUR : Modifier les Groupes de Matières

## 🎯 Objectif

Ce guide vous explique comment **personnaliser les noms des groupes de matières** (A, B, C, D) qui apparaissent sur les bulletins de notes.

---

## 🔗 ACCÉDER À LA FONCTIONNALITÉ

### URL directe
```
http://votre-domaine.com/#/admin/subject-groups-settings
```

### Ou via le menu (à ajouter)
```
Administration > Paramètres > Groupes de Matières
```

---

## 📋 PRÉSENTATION DE L'INTERFACE

### 1. **Vue d'ensemble**

L'interface affiche **4 cartes**, une pour chaque groupe (A, B, C, D) :

```
┌─────────────────────────────────────────────────┐
│ GROUPE A                          [Modifier]    │
├─────────────────────────────────────────────────┤
│ Nom Français:   MATIÈRES LITTÉRAIRES           │
│ Nom Anglais:    LITERARY SUBJECTS              │
│ Description:    Groupe A: Français, Anglais... │
└─────────────────────────────────────────────────┘
```

Chaque groupe a une **couleur distinctive** :
- 🔵 **Groupe A** : Bleu (Littéraire)
- 🟢 **Groupe B** : Vert (Scientifique)
- 🟡 **Groupe C** : Orange (Pratique)
- 🟣 **Groupe D** : Violet (Autres)

---

## ✏️ COMMENT MODIFIER UN GROUPE ?

### **Étape 1 : Cliquer sur "Modifier"**

Cliquez sur le bouton **"Modifier"** du groupe que vous souhaitez personnaliser.

### **Étape 2 : Remplir le formulaire**

Trois champs s'affichent :

1. **Nom du groupe (Français)** ⭐ OBLIGATOIRE
   - Exemple : `MATIÈRES LINGUISTIQUES ET LITTÉRAIRES`
   - Maximum 255 caractères
   - Utilisez des MAJUSCULES (convention)

2. **Nom du groupe (Anglais)** ⭐ RECOMMANDÉ
   - Exemple : `LINGUISTIC AND LITERARY SUBJECTS`
   - Important pour les établissements bilingues

3. **Description** (Optionnel)
   - Exemple : `Groupe A : Français, Anglais, Histoire, Géographie, Philosophie`
   - Usage interne uniquement

### **Étape 3 : Enregistrer**

Cliquez sur le bouton **"Enregistrer"** (vert).

✅ Un message de confirmation apparaît : **"Le groupe a été mis à jour avec succès"**

---

## 💡 EXEMPLES D'UTILISATION

### **Exemple 1 : École Technique**

Vous voulez adapter les groupes pour une école technique :

```
Groupe C actuel:
  - MATIÈRES PRATIQUES

Groupe C modifié:
  - Nom FR: MATIÈRES TECHNIQUES ET PROFESSIONNELLES
  - Nom EN: TECHNICAL AND VOCATIONAL SUBJECTS
  - Description: Mécanique, Électricité, Soudure, Menuiserie
```

### **Exemple 2 : École Anglophone**

Vous voulez passer entièrement en anglais :

```
Groupe A modifié:
  - Nom FR: ARTS ET HUMANITÉS (gardé pour administration)
  - Nom EN: ARTS AND HUMANITIES
  - Description: English, Literature, History, Geography
```

### **Exemple 3 : École Bilingue**

Vous voulez des noms équilibrés :

```
Groupe B modifié:
  - Nom FR: SCIENCES ET MATHÉMATIQUES
  - Nom EN: SCIENCES AND MATHEMATICS
  - Description: Groupe B : Maths, Physics, Chemistry, Biology
```

---

## 🖼️ APERÇU VISUEL

### **Avant modification** (Défaut)
```
┌───────────────────────────────────────────┐
│ BULLETIN DE NOTES                         │
├───────────────────────────────────────────┤
│ GROUPE A : MATIÈRES LITTÉRAIRES          │
│ ┌─────────────┬──────┬──────┬──────┐     │
│ │ FRANÇAIS    │ 15/20│  3   │ 45   │     │
│ │ ANGLAIS     │ 12/20│  2   │ 24   │     │
│ └─────────────┴──────┴──────┴──────┘     │
└───────────────────────────────────────────┘
```

### **Après modification**
```
┌───────────────────────────────────────────┐
│ BULLETIN DE NOTES                         │
├───────────────────────────────────────────┤
│ GROUPE A : LANGUES ET LITTÉRATURE        │
│ ┌─────────────┬──────┬──────┬──────┐     │
│ │ FRANÇAIS    │ 15/20│  3   │ 45   │     │
│ │ ANGLAIS     │ 12/20│  2   │ 24   │     │
│ └─────────────┴──────┴──────┴──────┘     │
└───────────────────────────────────────────┘
```

---

## ⚠️ POINTS IMPORTANTS

### ✅ Ce qui EST possible
- ✅ Modifier le **nom français** du groupe
- ✅ Modifier le **nom anglais** du groupe
- ✅ Ajouter/modifier la **description**
- ✅ Modifications **immédiates** sur tous les nouveaux bulletins

### ❌ Ce qui N'EST PAS possible
- ❌ Changer le **code** du groupe (A reste A, B reste B, etc.)
- ❌ **Supprimer** un groupe
- ❌ **Ajouter** un 5ème groupe
- ❌ Changer l'**ordre** des groupes
- ❌ Modifier rétroactivement les bulletins **déjà générés**

---

## 🔄 ANNULER UNE MODIFICATION

Pour revenir aux noms par défaut :

1. Cliquez sur **"Modifier"**
2. Remettez les valeurs d'origine :
   ```
   Groupe A:
   - Nom FR: MATIÈRES LITTÉRAIRES
   - Nom EN: LITERARY SUBJECTS

   Groupe B:
   - Nom FR: MATIÈRES SCIENTIFIQUES
   - Nom EN: SCIENTIFIC SUBJECTS

   Groupe C:
   - Nom FR: MATIÈRES PRATIQUES
   - Nom EN: PRACTICAL SUBJECTS

   Groupe D:
   - Nom FR: AUTRES MATIÈRES
   - Nom EN: OTHER SUBJECTS
   ```
3. Cliquez sur **"Enregistrer"**

---

## 📱 IMPACT DES MODIFICATIONS

### Où les nouveaux noms apparaissent-ils ?

✅ **Bulletins de séquences** → Immédiat
✅ **Bulletins de trimestres** → Immédiat
✅ **Bulletins annuels** → Immédiat
✅ **Relevés de notes** → Immédiat
✅ **Procès-verbaux (PV)** → Immédiat

### Quels fichiers sont affectés ?

- ✅ Tous les **PDF générés après** la modification
- ✅ Aperçus dans l'**interface admin**
- ❌ **PAS** les PDF déjà générés (stockés)

---

## 🎨 BONNES PRATIQUES

### ✍️ Style d'écriture
- Utilisez des **MAJUSCULES** (convention officielle)
- Restez **concis** (max 50 caractères recommandé)
- Soyez **clair** et **descriptif**

### 🌍 Bilinguisme
- **Toujours remplir les 2 champs** (FR et EN)
- Utilisez des traductions **officielles**
- Gardez la **même structure** dans les 2 langues

### 📝 Description
- Usage **interne** uniquement (n'apparaît pas sur les bulletins)
- Listez les **matières principales** du groupe
- Utile pour les **nouveaux utilisateurs**

---

## 🛠️ DÉPANNAGE

### Problème : "Erreur lors de l'enregistrement"

**Solutions :**
1. Vérifiez votre **connexion internet**
2. Vérifiez que vous êtes toujours **connecté** (token valide)
3. Assurez-vous que le **nom français n'est pas vide**
4. Réessayez après quelques secondes

### Problème : Les changements n'apparaissent pas

**Solutions :**
1. **Rafraîchissez la page** (F5 ou Ctrl+R)
2. **Videz le cache** du navigateur
3. **Régénérez un nouveau bulletin** (les anciens ne changent pas)

### Problème : Je n'ai pas accès à la page

**Cause :**
Vous devez avoir le rôle **Admin** ou **Principal**.

**Solution :**
Contactez un administrateur pour qu'il vous donne les permissions.

---

## 📞 BESOIN D'AIDE ?

### Support technique
- 📧 Email : support@cpb-douala.com
- 📱 Téléphone : +237 XXX XX XX XX

### Documentation
- 📄 Guide backend : `/back/GUIDE_GROUPES_MATIERES.md`
- 🔗 API Documentation : `/api/subject-groups/groups`

---

## 📊 RÉCAPITULATIF RAPIDE

| Action | Bouton | Résultat |
|--------|--------|----------|
| Voir les groupes | - | Page d'accueil |
| Modifier un groupe | 🖊️ Modifier | Formulaire d'édition |
| Enregistrer | 💾 Enregistrer | Mise à jour immédiate |
| Annuler | ❌ Annuler | Retour à l'affichage |

---

**Dernière mise à jour :** 13/11/2025
**Version :** 1.0
**Auteur :** Équipe technique CPBD

---

## ✅ CHECKLIST RAPIDE

Avant de modifier un groupe :

- [ ] J'ai vérifié que je suis **Admin** ou **Principal**
- [ ] J'ai préparé les **noms français et anglais**
- [ ] J'ai utilisé des **MAJUSCULES**
- [ ] Les noms sont **clairs** et **concis**
- [ ] J'ai ajouté une **description** si nécessaire
- [ ] Je comprends que ça affecte les **nouveaux bulletins uniquement**

**Prêt à modifier ? Rendez-vous sur `/admin/subject-groups-settings` !** 🚀
