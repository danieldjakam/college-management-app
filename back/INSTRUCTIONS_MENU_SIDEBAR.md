# Instructions pour ajouter "Gestion des Groupes de Matières" au menu

## 🎯 Objectif
Ajouter un lien dans le sidebar pour accéder à la page de gestion des groupes de matières.

## 📍 Où ajouter le menu

Le menu doit être ajouté dans la section **Configuration** ou **Paramètres** du sidebar.

## 🔧 Configuration à ajouter

### Option 1 : Si vous utilisez un fichier de configuration JSON

Ajoutez cet objet dans votre configuration de menu :

```json
{
  "id": "subject-groups",
  "label": "Groupes de Matières",
  "icon": "fas fa-layer-group",
  "route": "/admin/subject-groups",
  "roles": ["admin", "principal", "directeur_etudes"],
  "section": "Configuration"
}
```

### Option 2 : Si vous utilisez React/Vue Component

```javascript
{
  path: '/admin/subject-groups',
  name: 'Groupes de Matières',
  icon: 'layer-group',
  component: () => window.location.href = 'http://admin1.cpb-douala.com/admin/subject-groups',
  roles: ['admin', 'principal', 'directeur_etudes']
}
```

### Option 3 : HTML Direct

Si le menu est généré en HTML, ajoutez :

```html
<li class="nav-item">
    <a class="nav-link" href="/admin/subject-groups">
        <i class="fas fa-layer-group me-2"></i>
        <span>Groupes de Matières</span>
    </a>
</li>
```

## 🎨 Icône recommandée

**Font Awesome:** `fa-layer-group` ou `fa-books` ou `fa-th-large`

## 📊 Position suggérée

Le menu devrait être placé dans la section **Configuration/Paramètres**, près de :
- Gestion des matières
- Configuration des classes
- Paramètres académiques

## 🔐 Permissions

Seuls ces rôles doivent voir ce menu :
- ✅ Admin
- ✅ Principal
- ✅ Directeur des Études

## 📝 Exemple de structure complète

```json
{
  "sections": [
    {
      "title": "Configuration",
      "items": [
        {
          "label": "Matières",
          "route": "/admin/subjects",
          "icon": "book"
        },
        {
          "label": "Groupes de Matières",
          "route": "/admin/subject-groups",
          "icon": "layer-group",
          "badge": "NEW"
        },
        {
          "label": "Classes",
          "route": "/admin/classes",
          "icon": "users"
        }
      ]
    }
  ]
}
```

## 🌐 URL de la page

**Production:** `http://admin1.cpb-douala.com/admin/subject-groups`
**Local:** `http://127.0.0.1:8001/admin/subject-groups`

## ✅ Vérification

Après avoir ajouté le menu, vérifiez que :
1. Le lien apparaît dans le sidebar
2. Il est visible uniquement pour les rôles autorisés
3. Le clic redirige vers la bonne page
4. La page se charge correctement

## 💡 Besoin d'aide ?

Si vous ne trouvez pas où est géré le menu, cherchez ces fichiers :
- `sidebar.js` ou `sidebar.jsx` ou `sidebar.vue`
- `menu.js` ou `navigation.js`
- `routes.js` ou `router.js`
- Fichiers dans `resources/js/components/`
- Fichiers dans `public/js/`

Ou dites-moi où se trouve votre code frontend et je vous aiderai à l'ajouter !
