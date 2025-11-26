# 🚀 API Groupes de Matières Dynamiques

## 📝 Vue d'ensemble

Le système de groupes de matières est maintenant **complètement dynamique** ! Vous pouvez :
- ✅ **Créer** de nouveaux groupes (pas limité à A, B, C, D)
- ✅ **Modifier** le code, nom et couleurs des groupes
- ✅ **Supprimer** des groupes (si aucune matière n'y est assignée)
- ✅ Personnaliser les couleurs de chaque groupe

---

## 🔧 Changements par rapport à l'ancien système

### Avant (système fixe):
- ❌ 4 groupes fixes (A, B, C, D)
- ❌ Codes non modifiables
- ❌ Impossible de créer ou supprimer des groupes
- ❌ Couleurs hardcodées dans le frontend

### Maintenant (système dynamique):
- ✅ Nombre illimité de groupes
- ✅ Codes personnalisables (ex: A, B, C, MAT-LIT, SCI, etc.)
- ✅ Création et suppression dynamique
- ✅ Couleurs stockées en base de données

---

## 📋 Nouveaux Endpoints API

### 1. **Créer un nouveau groupe**

```http
POST /api/subject-groups/groups
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "E",
  "header": "GROUPE E",
  "header_en": "GROUP E",
  "name": "MATIÈRES COMPLÉMENTAIRES",
  "name_en": "COMPLEMENTARY SUBJECTS",
  "description": "Matières complémentaires et optionnelles",
  "color": "#ec4899",
  "bg_color": "#fdf2f8"
}
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "Groupe créé avec succès",
  "data": {
    "id": 5,
    "code": "E",
    "header": "GROUPE E",
    "header_en": "GROUP E",
    "name": "MATIÈRES COMPLÉMENTAIRES",
    "name_en": "COMPLEMENTARY SUBJECTS",
    "description": "Matières complémentaires et optionnelles",
    "color": "#ec4899",
    "bg_color": "#fdf2f8",
    "order": 5,
    "is_active": true
  }
}
```

**Validation:**
- `code` : **Obligatoire**, unique, max 50 caractères
- `header` : **Obligatoire**, max 255 caractères
- `name` : **Obligatoire**, max 255 caractères
- Autres champs : optionnels

---

### 2. **Modifier un groupe (incluant le code)**

```http
PUT /api/subject-groups/groups/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "LITT",
  "header": "SECTION LITTÉRAIRE",
  "header_en": "LITERARY SECTION",
  "name": "MATIÈRES DE LANGUE ET LITTÉRATURE",
  "name_en": "LANGUAGE AND LITERATURE SUBJECTS",
  "description": "Toutes les matières liées aux langues",
  "color": "#2563eb",
  "bg_color": "#eff6ff"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Groupe mis à jour avec succès",
  "data": {
    "id": 1,
    "code": "LITT",
    "header": "SECTION LITTÉRAIRE",
    ...
  }
}
```

**⚠️ Important :** Si vous modifiez le `code`, toutes les matières assignées à l'ancien code seront automatiquement migrées vers le nouveau code.

---

### 3. **Supprimer un groupe**

```http
DELETE /api/subject-groups/groups/{id}
Authorization: Bearer {token}
```

**Réponse (succès):**
```json
{
  "success": true,
  "message": "Le groupe 'MATIÈRES COMPLÉMENTAIRES' a été supprimé avec succès"
}
```

**Réponse (erreur - groupe utilisé):**
```json
{
  "success": false,
  "message": "Impossible de supprimer ce groupe car 15 matière(s) y sont assignées. Veuillez d'abord réassigner ces matières à un autre groupe.",
  "subjects_count": 15
}
```

**Protection:**
- ✅ Vous **ne pouvez pas supprimer** un groupe si des matières y sont assignées
- ✅ Vous devez d'abord déplacer toutes les matières vers d'autres groupes

---

### 4. **Lister tous les groupes**

```http
GET /api/subject-groups/groups
Authorization: Bearer {token}
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "A",
      "header": "GROUPE A",
      "header_en": "GROUP A",
      "name": "MATIÈRES LITTÉRAIRES",
      "name_en": "LITERARY SUBJECTS",
      "description": "Groupe A: Français, Anglais, Histoire, Géographie, etc.",
      "color": "#3b82f6",
      "bg_color": "#eff6ff",
      "order": 1,
      "is_active": true
    },
    ...
  ]
}
```

---

### 5. **Assigner une matière à un groupe**

```http
PUT /api/subject-groups/{subjectId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "group": "E"
}
```

**Note:** Le code du groupe doit exister dans la table `subject_groups`.

---

## 🗄️ Structure de la base de données

### Table: `subject_groups`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | Clé primaire |
| `code` | VARCHAR(10) | **Code unique du groupe** (modifiable) |
| `header` | VARCHAR(255) | En-tête FR (ex: "GROUPE A") |
| `header_en` | VARCHAR(255) | En-tête EN (ex: "GROUP A") |
| `name` | VARCHAR(255) | Nom complet FR |
| `name_en` | VARCHAR(255) | Nom complet EN |
| `description` | TEXT | Description du groupe |
| `color` | VARCHAR(20) | **Couleur principale** (hex) |
| `bg_color` | VARCHAR(20) | **Couleur de fond** (hex) |
| `order` | INT | Ordre d'affichage |
| `is_active` | BOOLEAN | Groupe actif ou non |

### Table: `subjects`

Le champ `group` a été modifié :
- **Avant:** `ENUM('A','B','C','D')`
- **Maintenant:** `VARCHAR(50)` avec clé étrangère vers `subject_groups.code`

**Relation:** `subjects.group` → `subject_groups.code` (ON DELETE SET NULL)

---

## 💻 Exemples d'utilisation

### Créer un nouveau groupe pour les matières techniques

```bash
curl -X POST "http://localhost:8000/api/subject-groups/groups" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "TECH",
    "header": "SECTION TECHNIQUE",
    "header_en": "TECHNICAL SECTION",
    "name": "MATIÈRES TECHNIQUES ET PROFESSIONNELLES",
    "name_en": "TECHNICAL AND VOCATIONAL SUBJECTS",
    "description": "Matières techniques, professionnelles et pratiques",
    "color": "#0891b2",
    "bg_color": "#ecfeff"
  }'
```

### Modifier le code d'un groupe existant

```bash
curl -X PUT "http://localhost:8000/api/subject-groups/groups/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "LIT",
    "name": "MATIÈRES LITTÉRAIRES ET HUMANITÉS"
  }'
```

### Supprimer un groupe vide

```bash
# D'abord, déplacer toutes les matières du groupe
curl -X POST "http://localhost:8000/api/subject-groups/bulk-update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "updates": [
      {"id": 15, "group": "A"},
      {"id": 23, "group": "A"}
    ]
  }'

# Ensuite, supprimer le groupe
curl -X DELETE "http://localhost:8000/api/subject-groups/groups/5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎨 Palette de couleurs recommandées

| Groupe | Couleur | Hex (color) | Hex (bg_color) |
|--------|---------|-------------|----------------|
| Littéraire | Bleu | `#3b82f6` | `#eff6ff` |
| Scientifique | Vert | `#10b981` | `#f0fdf4` |
| Pratique | Orange | `#f59e0b` | `#fffbeb` |
| Autres | Violet | `#8b5cf6` | `#f5f3ff` |
| Technique | Cyan | `#0891b2` | `#ecfeff` |
| Artistique | Rose | `#ec4899` | `#fdf2f8` |
| Complémentaire | Indigo | `#6366f1` | `#eef2ff` |

---

## 🔐 Permissions

**Rôles autorisés :**
- `admin`
- `principal`
- `directeur_etudes`

**Middleware:** `auth:api, role:admin,principal,directeur_etudes`

---

## 🧪 Tests avec Tinker

### Créer un groupe
```bash
php artisan tinker

$group = App\Models\SubjectGroup::create([
    'code' => 'ART',
    'header' => 'SECTION ARTISTIQUE',
    'header_en' => 'ARTISTIC SECTION',
    'name' => 'MATIÈRES ARTISTIQUES',
    'name_en' => 'ARTISTIC SUBJECTS',
    'description' => 'Arts plastiques, musique, etc.',
    'color' => '#ec4899',
    'bg_color' => '#fdf2f8',
    'order' => 6,
    'is_active' => true
]);
```

### Modifier le code d'un groupe
```bash
php artisan tinker

$group = App\Models\SubjectGroup::find(1);
$oldCode = $group->code;
$group->code = 'LITT';
$group->save();

// Mettre à jour les matières
App\Models\Subject::where('group', $oldCode)->update(['group' => 'LITT']);
```

### Compter les matières par groupe
```bash
php artisan tinker

$groups = App\Models\SubjectGroup::withCount('subjects')->get();
foreach ($groups as $g) {
    echo "{$g->code}: {$g->subjects_count} matières\n";
}
```

### Supprimer un groupe
```bash
php artisan tinker

$group = App\Models\SubjectGroup::where('code', 'E')->first();

// Vérifier s'il y a des matières
$count = App\Models\Subject::where('group', $group->code)->count();
if ($count > 0) {
    echo "Impossible de supprimer : {$count} matières assignées\n";
} else {
    $group->delete();
    echo "Groupe supprimé\n";
}
```

---

## 📊 Impact sur les bulletins

### Génération dynamique des groupes

Le service `BulletinService.php` a été mis à jour pour :

1. **Récupérer les groupes depuis la base de données**
```php
$subjectGroups = \App\Models\SubjectGroup::where('is_active', true)
                                         ->orderBy('order')
                                         ->get();
```

2. **Créer des clés dynamiques**
```php
foreach ($subjectGroups as $subjectGroup) {
    $groupKey = "GROUPE {$subjectGroup->code} : " . strtoupper($subjectGroup->name);
    $groups[$groupKey] = [];
}
```

3. **Traduction automatique pour sections anglophones**
```php
// Extrait le code depuis "GROUPE E : MATIÈRES COMPLÉMENTAIRES"
// Récupère la traduction depuis la base de données
$translatedGroupName = strtoupper($subjectGroup->header_en) . ': ' . strtoupper($subjectGroup->name_en);
```

**Résultat:** Les bulletins s'adaptent automatiquement aux nouveaux groupes créés !

---

## ⚠️ Points importants

### ✅ Bonnes pratiques

1. **Codes courts et explicites** : Utilisez des codes courts (1-10 caractères) mais explicites
   - ✅ Bon: `A`, `B`, `SCI`, `LIT`, `TECH`
   - ❌ Mauvais: `GROUPE_SCIENTIFIQUE_2024`

2. **Couleurs cohérentes** : Utilisez des couleurs distinctes pour chaque groupe

3. **Noms bilingues** : Remplissez toujours `name` ET `name_en` pour support anglophone

4. **Ordre logique** : Utilisez le champ `order` pour contrôler l'affichage

### ⚠️ Limitations

- **Codes uniques** : Chaque code de groupe doit être unique
- **Suppression protégée** : Impossible de supprimer un groupe avec des matières
- **Migration automatique** : Modifier le code migre automatiquement les matières

### 🔄 Migration des données

Lors de la mise à jour :
```bash
# La migration modifie automatiquement
php artisan migrate

# Les couleurs sont ajoutées aux groupes existants
# Le champ group passe de ENUM à VARCHAR
# Toutes les matières existantes conservent leur groupe
```

---

## 📞 Support et dépannage

### Erreur : "Le code de groupe n'existe pas"
**Solution :** Vérifiez que le code existe dans `subject_groups.code`

### Erreur : "Impossible de supprimer ce groupe"
**Solution :** Déplacez d'abord toutes les matières vers un autre groupe

### Les bulletins n'affichent pas les nouveaux groupes
**Solution :**
1. Vérifiez que `is_active = true` pour le groupe
2. Assignez au moins une matière au groupe
3. Videz le cache : `php artisan cache:clear`

---

**Date de création :** 26 novembre 2025
**Version :** 2.0 (Système dynamique)
**Auteur :** Claude Code
