# 📚 Gestion des Groupes de Matières

## Vue d'ensemble

Ce système permet à l'administrateur de **configurer dynamiquement** quelles matières appartiennent à quel groupe dans les bulletins scolaires. Les groupes sont utilisés pour organiser les matières par catégorie.

## Les 4 Groupes

| Groupe | Nom | Description | Exemple de matières |
|--------|-----|-------------|-------------------|
| **A** | Matières Littéraires | Langues, histoire, géographie, etc. | Français, Anglais, Histoire, Géographie, Expression Écrite |
| **B** | Matières Scientifiques | Sciences exactes | Mathématiques, Physique, SVT, Sciences |
| **C** | Matières Pratiques | Activités pratiques et artistiques | EPS, Informatique, Arts Plastiques, Travail Manuel |
| **D** | Autres Matières | Matières transversales | ECM, Éducation Civique |

## 🌐 Interface Web

### Accès
URL: **http://admin1.cpb-douala.com/admin/subject-groups**

### Fonctionnalités
- ✅ **Glisser-Déposer (Drag & Drop)** : Déplacez les matières entre les groupes
- ✅ **Organisation visuelle** : 4 colonnes colorées pour chaque groupe
- ✅ **Compteur en temps réel** : Nombre de matières par groupe
- ✅ **Section "Sans groupe"** : Matières non encore assignées
- ✅ **Bouton d'enregistrement** : Sauvegarde toutes les modifications en un clic

### Comment utiliser
1. **Connectez-vous** en tant qu'admin, principal ou directeur des études
2. **Accédez à la page** : Menu > Gestion des Groupes de Matières
3. **Glissez-déposez** les matières dans les bons groupes
4. **Cliquez sur "Enregistrer les modifications"**
5. **C'est fait !** Les bulletins utiliseront cette nouvelle configuration

## 🔌 API Endpoints

### 1. Lister tous les groupes
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
      "A": [...],
      "B": [...],
      "C": [...],
      "D": [...],
      "uncategorized": [...]
    },
    "groups": [
      {"code": "A", "name": "MATIÈRES LITTÉRAIRES"},
      {"code": "B", "name": "MATIÈRES SCIENTIFIQUES"},
      {"code": "C", "name": "MATIÈRES PRATIQUES"},
      {"code": "D", "name": "AUTRES MATIÈRES"}
    ]
  }
}
```

### 2. Modifier le groupe d'une matière
```http
PUT /api/subject-groups/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "group": "B"
}
```

**Exemple :**
```bash
# Déplacer la matière ID=5 vers le groupe B (Scientifiques)
curl -X PUT "http://admin1.cpb-douala.com/api/subject-groups/5" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"group": "B"}'
```

### 3. Modification en masse (Bulk Update)
```http
POST /api/subject-groups/bulk-update
Content-Type: application/json
Authorization: Bearer {token}

{
  "updates": [
    {"id": 5, "group": "A"},
    {"id": 12, "group": "B"},
    {"id": 23, "group": "C"}
  ]
}
```

**Exemple :**
```bash
# Déplacer plusieurs matières en une seule requête
curl -X POST "http://admin1.cpb-douala.com/api/subject-groups/bulk-update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "updates": [
      {"id": 5, "group": "A"},
      {"id": 12, "group": "B"}
    ]
  }'
```

## 📊 Base de données

### Table: `subjects`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Identifiant unique |
| `name` | VARCHAR | Nom de la matière |
| `code` | VARCHAR | Code de la matière |
| `description` | TEXT | Description |
| `group` | ENUM('A','B','C','D') | **NOUVEAU** : Groupe de la matière |
| `is_active` | BOOLEAN | Matière active ou non |

### Migration exécutée
```bash
php artisan migrate
# Migration: 2025_11_08_130422_add_group_to_subjects_table
```

### Seeder exécuté
```bash
php artisan db:seed --class=SubjectGroupsSeeder
```

**Résultats :**
- ✅ Groupe A (Littéraires): 10 matières
- ✅ Groupe B (Scientifiques): 8 matières
- ✅ Groupe C (Pratiques): 4 matières
- ✅ Groupe D (Autres): 1 matière

## 🎯 Impact sur les bulletins

### Avant
Les groupes étaient **hardcodés** dans `BulletinService.php` et basés sur le nom des matières.

### Maintenant
Les bulletins utilisent **automatiquement** le champ `group` de la base de données.

**Avantages :**
- ✅ **Flexibilité totale** : L'admin peut changer les groupes à tout moment
- ✅ **Pas besoin de modifier le code** : Tout se fait via l'interface
- ✅ **Fallback intelligent** : Si une matière n'a pas de groupe, le système utilise l'ancien système basé sur le nom

### Code modifié
**Fichier:** `app/Services/BulletinService.php`
**Fonction:** `groupSubjectsByType()`
**Ligne:** ~1215

```php
// Avant
if (in_array($subjectName, ['anglais', 'français', ...])) {
    $groups['GROUPE A : MATIÈRES LITTÉRAIRES'][] = $subject;
}

// Maintenant
$subjectModel = \App\Models\Subject::find($subject['subject_id']);
if ($subjectModel && $subjectModel->group) {
    $groupKey = match($subjectModel->group) {
        'A' => 'GROUPE A : MATIÈRES LITTÉRAIRES',
        'B' => 'GROUPE B : MATIÈRES SCIENTIFIQUES',
        'C' => 'GROUPE C : MATIÈRES PRATIQUES',
        'D' => 'GROUPE D : AUTRES MATIÈRES',
    };
    $groups[$groupKey][] = $subject;
}
```

## 🔐 Permissions

### Rôles autorisés
- ✅ **admin**
- ✅ **principal**
- ✅ **directeur_etudes**

### Middleware appliqué
```php
Route::middleware(['auth:api', 'role:admin,principal,directeur_etudes'])
```

## 🚀 Déploiement en production

### 1. Exécuter les migrations
```bash
ssh admin1.cpb-douala.com
cd /path/to/project
php artisan migrate
```

### 2. Exécuter le seeder (optionnel)
```bash
php artisan db:seed --class=SubjectGroupsSeeder
```

### 3. Vérifier les permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### 4. Clear cache (si nécessaire)
```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

## 📝 Notes importantes

1. **Matières sans groupe** : Si une matière n'a pas de groupe assigné, elle apparaîtra dans la section "Matières sans groupe" de l'interface et sera automatiquement assignée via le système de fallback dans les bulletins.

2. **Modification en direct** : Les changements de groupes sont **immédiats** dès que vous cliquez sur "Enregistrer". Les nouveaux bulletins générés utiliseront la nouvelle configuration.

3. **Sauvegarde recommandée** : Avant de faire des modifications importantes, faites une sauvegarde de la table `subjects`.

4. **Compatibilité** : Le système est **rétrocompatible**. Si le champ `group` est NULL pour une matière, le système utilisera l'ancien algorithme basé sur le nom.

## 🐛 Dépannage

### Problème : "Erreur lors du chargement des matières"
**Solution :** Vérifiez que le token d'authentification est valide dans localStorage.

### Problème : "Aucune modification à enregistrer"
**Solution :** Vous devez d'abord glisser-déposer au moins une matière avant de cliquer sur "Enregistrer".

### Problème : Les bulletins n'utilisent pas les nouveaux groupes
**Solution :**
1. Videz le cache Laravel : `php artisan cache:clear`
2. Vérifiez que les matières ont bien leur champ `group` rempli dans la base de données

## 📞 Support

Pour toute question ou problème, contactez l'équipe de développement.

---

**Dernière mise à jour :** 8 novembre 2025
**Version :** 1.0.0
