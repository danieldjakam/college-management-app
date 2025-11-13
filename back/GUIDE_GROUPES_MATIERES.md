# 📚 GUIDE DE GESTION DES GROUPES DE MATIÈRES

## 🎯 Fonctionnalité

Vous pouvez maintenant **modifier les noms des groupes de matières** (A, B, C, D) directement via l'API.

---

## 📋 GROUPES PAR DÉFAUT

| ID | Code | Nom Français | Nom Anglais |
|----|------|--------------|-------------|
| 1  | A    | MATIÈRES LITTÉRAIRES | LITERARY SUBJECTS |
| 2  | B    | MATIÈRES SCIENTIFIQUES | SCIENTIFIC SUBJECTS |
| 3  | C    | MATIÈRES PRATIQUES | PRACTICAL SUBJECTS |
| 4  | D    | AUTRES MATIÈRES | OTHER SUBJECTS |

---

## 🔌 ENDPOINTS API

### 1. **Lister tous les groupes**

```http
GET /api/subject-groups/groups
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "A",
      "name": "MATIÈRES LITTÉRAIRES",
      "name_en": "LITERARY SUBJECTS",
      "description": "Groupe A: Français, Anglais, Histoire, Géographie, etc.",
      "order": 1,
      "is_active": true
    },
    ...
  ]
}
```

---

### 2. **Modifier le nom d'un groupe**

```http
PUT /api/subject-groups/groups/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "MATIÈRES LINGUISTIQUES ET LITTÉRAIRES",
  "name_en": "LINGUISTIC AND LITERARY SUBJECTS",
  "description": "Nouveau groupe A: Toutes les matières littéraires"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Groupe mis à jour avec succès",
  "data": {
    "id": 1,
    "code": "A",
    "name": "MATIÈRES LINGUISTIQUES ET LITTÉRAIRES",
    "name_en": "LINGUISTIC AND LITERARY SUBJECTS",
    "description": "Nouveau groupe A: Toutes les matières littéraires",
    "order": 1,
    "is_active": true
  }
}
```

---

### 3. **Lister toutes les matières avec leurs groupes**

```http
GET /api/subject-groups
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "subjects": [...],
    "grouped": {
      "A": [...matières du groupe A],
      "B": [...matières du groupe B],
      "C": [...matières du groupe C],
      "D": [...matières du groupe D]
    },
    "groups": [...groupes avec leurs noms]
  }
}
```

---

## 🖥️ EXEMPLES D'UTILISATION

### **Exemple 1 : Récupérer tous les groupes**

```bash
curl -X GET "http://localhost:8000/api/subject-groups/groups" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### **Exemple 2 : Renommer le Groupe A**

```bash
curl -X PUT "http://localhost:8000/api/subject-groups/groups/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "MATIÈRES DE LANGUE ET LITTÉRATURE",
    "name_en": "LANGUAGE AND LITERATURE SUBJECTS",
    "description": "Matières axées sur les langues et la littérature"
  }'
```

---

### **Exemple 3 : Modifier le Groupe B**

```bash
curl -X PUT "http://localhost:8000/api/subject-groups/groups/2" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "SCIENCES EXACTES",
    "name_en": "EXACT SCIENCES"
  }'
```

---

## 🔒 PERMISSIONS REQUISES

**Rôles autorisés :**
- `admin`
- `principal`
- `directeur_etudes`

Les autres utilisateurs ne peuvent pas modifier les groupes.

---

## 📊 IMPACT DES MODIFICATIONS

### ✅ Ce qui est mis à jour automatiquement :
- **Bulletins de notes** : Les nouveaux noms apparaîtront automatiquement
- **Affichages dans l'interface** : Mise à jour immédiate
- **Exports PDF** : Utilisent les nouveaux noms

### ⚠️ Ce qui n'est PAS affecté :
- **Code du groupe** (A, B, C, D) : Ne peut pas être modifié
- **Ordre des groupes** : Reste fixe (A→B→C→D)
- **Matières déjà assignées** : Restent dans leur groupe

---

## 🎨 CAS D'USAGE COURANTS

### **1. Adapter au système anglophone**

```json
PUT /api/subject-groups/groups/1
{
  "name": "ARTS AND HUMANITIES",
  "name_en": "ARTS AND HUMANITIES"
}
```

### **2. Personnaliser pour le système technique**

```json
PUT /api/subject-groups/groups/3
{
  "name": "MATIÈRES TECHNIQUES ET PROFESSIONNELLES",
  "name_en": "TECHNICAL AND VOCATIONAL SUBJECTS"
}
```

### **3. Adapter au contexte bilingue**

```json
PUT /api/subject-groups/groups/4
{
  "name": "ÉDUCATION CIVIQUE ET MORALE",
  "name_en": "CIVIC AND MORAL EDUCATION"
}
```

---

## 🧪 TESTER EN LOCAL

### **1. Via Tinker**

```bash
php artisan tinker

# Lire tous les groupes
SubjectGroup::all();

# Modifier un groupe
$group = SubjectGroup::find(1);
$group->name = "NOUVEAU NOM";
$group->save();

# Vérifier
SubjectGroup::find(1)->name;
```

---

### **2. Via API (Postman / Insomnia)**

**Collection à importer :**

```json
{
  "name": "Groupes de Matières",
  "requests": [
    {
      "name": "Liste des groupes",
      "method": "GET",
      "url": "{{base_url}}/api/subject-groups/groups",
      "headers": {
        "Authorization": "Bearer {{token}}"
      }
    },
    {
      "name": "Modifier Groupe A",
      "method": "PUT",
      "url": "{{base_url}}/api/subject-groups/groups/1",
      "headers": {
        "Authorization": "Bearer {{token}}",
        "Content-Type": "application/json"
      },
      "body": {
        "name": "NOUVEAU NOM GROUPE A"
      }
    }
  ]
}
```

---

## 📝 NOTES IMPORTANTES

### ✅ Bonnes pratiques
- **Toujours mettre les 2 langues** (français et anglais)
- **Utiliser MAJUSCULES** pour les noms de groupes (convention)
- **Description claire** de ce que contient le groupe

### ⚠️ Limitations
- Ne peut pas **supprimer** un groupe (A, B, C, D sont fixes)
- Ne peut pas **ajouter** de nouveau groupe (limité à 4)
- Ne peut pas **changer les codes** (A reste A, B reste B, etc.)

### 🔄 Pour annuler une modification
Utilisez le même endpoint PUT avec les valeurs d'origine :

```bash
curl -X PUT "http://localhost:8000/api/subject-groups/groups/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "MATIÈRES LITTÉRAIRES",
    "name_en": "LITERARY SUBJECTS"
  }'
```

---

## 🚀 PROCHAINES ÉTAPES

Pour intégrer dans le frontend :

1. **Créer une page d'administration** des groupes
2. **Formulaire de modification** pour chaque groupe
3. **Prévisualisation** des bulletins avec les nouveaux noms
4. **Historique** des modifications (à implémenter)

---

## 📞 SUPPORT

En cas de problème :
- Vérifier les logs : `storage/logs/laravel.log`
- Vérifier la base : `SELECT * FROM subject_groups`
- Tester avec Tinker : `php artisan tinker`

---

**Créé le** : 13/11/2025
**Version** : 1.0
**Auteur** : Claude Code
