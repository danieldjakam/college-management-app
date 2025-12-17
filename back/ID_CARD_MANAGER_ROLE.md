# Rôle : Gestionnaire de Cartes d'Identité (`id_card_manager`)

## 📋 Vue d'ensemble

Le rôle **`id_card_manager`** (Gestionnaire de Cartes d'Identité) est un rôle spécialisé créé pour les personnes responsables de la gestion des photos des élèves et de l'impression des cartes d'identité scolaires au COLLEGE POLYVALENT BILINGUE DE DOUALA.

**Date de création**: 16 Décembre 2024
**Fichier de migration**: `2025_12_16_232008_add_id_card_manager_role_documentation.php`

---

## 🎯 Objectif

Ce rôle limite l'accès d'un utilisateur aux fonctionnalités strictement nécessaires pour:
1. Gérer les photos des élèves
2. Prévisualiser les cartes d'identité
3. Imprimer les cartes d'identité (individuelles ou par classe)

**Aucun accès** aux notes, paiements, bulletins, ou autres fonctionnalités administratives.

---

## 🔐 Permissions

### ✅ Accès autorisé

| Fonctionnalité | Description |
|----------------|-------------|
| **Voir toutes les classes** | Consultation des classes actives (lecture seule) |
| **Voir tous les élèves** | Liste des élèves par classe avec informations de base |
| **Modifier les photos** | Upload/remplacement de photos d'élèves (UNIQUEMENT) |
| **Prévisualiser les cartes** | Visualisation HTML de la carte avant impression |
| **Générer les cartes** | Génération PDF des cartes (individuelles) |
| **Imprimer par classe** | Génération PDF groupée (10 cartes par page) |
| **Statistiques photos** | Tableau de bord avec progression photos/classe |

### ❌ Accès interdit

- Modifier les informations personnelles des élèves (sauf photo)
- Voir ou modifier les notes/bulletins
- Accéder aux paiements et frais
- Gérer les enseignants ou le personnel
- Modifier les classes, séries, matières
- Accéder aux rapports financiers
- Gérer les utilisateurs

---

## 🗂️ Structure Backend

### Contrôleur dédié
**Fichier**: `app/Http/Controllers/IdCardManagerController.php`

**Méthodes**:
```php
// Dashboard avec statistiques
GET /api/id-card-manager/dashboard

// Liste des classes
GET /api/id-card-manager/classes

// Élèves d'une classe
GET /api/id-card-manager/classes/{classId}/students

// Détails d'un élève
GET /api/id-card-manager/students/{studentId}

// Mise à jour photo uniquement
POST /api/id-card-manager/students/{studentId}/update-photo
```

### Routes API modifiées

Les routes suivantes ont été mises à jour pour inclure `id_card_manager`:

```php
// Génération de cartes
POST /api/student-cards/class/{classId}/generate
POST /api/student-cards/student/{studentId}/generate
POST /api/student-cards/student/{studentId}/preview

// Paramètres de mise en page (lecture seule)
GET /api/card-layout-settings
```

### Middleware
**Fichier**: `app/Http/Middleware/CheckRole.php`

Le middleware existant gère automatiquement le nouveau rôle via la liste de rôles autorisés.

---

## 💻 Structure Frontend

### Composants React créés

1. **Dashboard**
   **Fichier**: `front/src/pages/IdCardManager/IdCardManagerDashboard.jsx`
   - Statistiques globales (photos/élèves)
   - Progression par classe
   - Tableaux de bord visuels

2. **Gestion des élèves**
   **Fichier**: `front/src/pages/IdCardManager/IdCardManagerStudents.jsx`
   - Liste des élèves par classe
   - Upload de photos (modal)
   - Actions: Prévisualiser, Imprimer

### Routes React

```javascript
// Dashboard principal
/id-card-manager/dashboard

// Élèves d'une classe
/id-card-manager/classes/:classId/students
```

### Protection des routes
**Fichier**: `front/src/components/ProtectedRoute.jsx`

```javascript
export const IdCardManagerRoute = ({ children, fallbackPath = "/" }) => {
  return (
    <ProtectedRoute requiredRoles={["id_card_manager"]} fallbackPath={fallbackPath}>
      {children}
    </ProtectedRoute>
  );
};
```

### Redirection automatique
Après connexion, l'utilisateur `id_card_manager` est redirigé vers `/id-card-manager/dashboard`.

---

## 🚀 Création d'un compte

### Via Artisan (Recommandé)

```bash
cd back
php artisan user:create-id-card-manager
```

**Mode interactif**:
Le script demande:
- Nom d'utilisateur (défaut: `card_manager`)
- Email (défaut: `cards@cpbdouala.cm`)
- Nom complet (défaut: `Gestionnaire de Cartes`)
- Mot de passe (min. 6 caractères)

**Mode paramétré**:
```bash
php artisan user:create-id-card-manager card_manager \
  --email=cards@cpbdouala.cm \
  --name="Gestionnaire de Cartes" \
  --password=motdepasse123
```

### Via Tinker

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Gestionnaire de Cartes',
    'username' => 'card_manager',
    'email' => 'cards@cpbdouala.cm',
    'role' => 'id_card_manager',
    'password' => Hash::make('votre_mot_de_passe')
]);
```

### Via SQL (Déconseillé)

```sql
INSERT INTO users (name, username, email, role, password, created_at, updated_at)
VALUES (
    'Gestionnaire de Cartes',
    'card_manager',
    'cards@cpbdouala.cm',
    'id_card_manager',
    '$2y$12$...',  -- Hash bcrypt du mot de passe
    NOW(),
    NOW()
);
```

---

## 📊 Workflow utilisateur typique

### 1. Connexion
```
URL: http://localhost:3000/login
Username: card_manager
Password: [mot de passe défini]
```

### 2. Dashboard
L'utilisateur voit:
- **Total élèves**: Nombre total d'élèves actifs
- **Avec photo**: Nombre d'élèves ayant une photo
- **Sans photo**: Nombre d'élèves sans photo
- **Progression globale**: Barre de progression en %
- **Tableau par classe**: Statistiques détaillées

### 3. Gestion d'une classe
**Action**: Cliquer sur "Gérer" pour une classe

**Page affichée**: Liste des élèves
- Matricule
- Nom complet
- Série
- Statut photo (✅/❌)
- Actions:
  - **Upload Photo**: Modal avec preview de l'ancienne photo
  - **Prévisualiser**: Ouvre la carte en HTML dans un nouvel onglet
  - **Imprimer**: Télécharge le PDF de la carte individuelle

### 4. Upload d'une photo
1. Cliquer sur "Photo" pour un élève
2. Modal s'ouvre avec preview de la photo actuelle (si existe)
3. Sélectionner une nouvelle photo (JPG, PNG max 2MB)
4. Cliquer sur "Enregistrer"
5. La liste se recharge automatiquement

### 5. Impression groupée
**Bouton**: "Imprimer Toutes les Cartes" (en haut de page)

**Résultat**: PDF généré avec 10 cartes par page (2 colonnes × 5 lignes)

---

## 🎨 Interface utilisateur

### Dashboard

```
┌──────────────────────────────────────────────────────────────┐
│  🎫 Gestion des Cartes d'Identité                           │
│  Année Scolaire: 2024-2025                                   │
├──────────────────────────────────────────────────────────────┤
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐                   │
│  │  500  │ │  450  │ │  50   │ │  25   │                   │
│  │Élèves │ │ Avec  │ │ Sans  │ │Classes│                   │
│  └───────┘ └───────┘ └───────┘ └───────┘                   │
├──────────────────────────────────────────────────────────────┤
│  Progression Globale                                         │
│  [██████████████████░░░░░] 90%                              │
│  450 élèves sur 500 ont leur photo                          │
├──────────────────────────────────────────────────────────────┤
│  Classe    │ Total │ Avec │ Sans │ Progression │ Actions   │
│  6ème A    │  45   │  42  │  3   │ [███████░]  │ [Gérer]   │
│  5ème B    │  38   │  35  │  3   │ [███████░]  │ [Gérer]   │
│  ...       │  ...  │  ... │  ... │     ...     │   ...     │
└──────────────────────────────────────────────────────────────┘
```

### Page élèves d'une classe

```
┌──────────────────────────────────────────────────────────────┐
│  ← 6ème A                       [Imprimer Toutes les Cartes] │
│  45 élèves • 42 avec photo • 3 sans photo                    │
├──────────────────────────────────────────────────────────────┤
│ Matricule │ Nom Complet      │ Série │ Photo │ Actions       │
│ 2024001   │ NKOA Jean        │ 6ème A│  ✅   │ 📷 👁 🖨      │
│ 2024002   │ TCHOUMI Marie    │ 6ème A│  ❌   │ 📷 ⚠ ⚠       │
│ 2024003   │ KAMGA Paul       │ 6ème A│  ✅   │ 📷 👁 🖨      │
│ ...       │ ...              │ ...   │ ...   │ ...           │
└──────────────────────────────────────────────────────────────┘

Légende:
📷 = Upload photo
👁 = Prévisualiser carte
🖨 = Imprimer carte
⚠ = Action désactivée (pas de photo)
```

---

## 🔧 Sécurité

### Validation backend

1. **Upload photo**:
   - Taille max: 2MB
   - Formats: JPG, JPEG, PNG
   - Vérification que le fichier est bien une image

2. **Middleware**:
   - Authentification JWT obligatoire
   - Vérification du rôle sur chaque requête
   - Vérification que l'enseignant ne modifie que ce qu'il enseigne

3. **Restriction stricte**:
   - L'endpoint `/update-photo` ne modifie QUE le champ `photo`
   - Aucun autre champ de `students` n'est modifiable

---

## 📁 Fichiers créés/modifiés

### Backend (Laravel)

**Nouveaux fichiers**:
- `app/Http/Controllers/IdCardManagerController.php`
- `app/Console/Commands/CreateIdCardManager.php`
- `database/migrations/2025_12_16_232008_add_id_card_manager_role_documentation.php`
- `back/ID_CARD_MANAGER_ROLE.md` (ce fichier)

**Fichiers modifiés**:
- `routes/api.php` (ajout routes + middleware)
- `app/Http/Middleware/CheckRole.php` (aucune modification nécessaire)

### Frontend (React)

**Nouveaux fichiers**:
- `front/src/pages/IdCardManager/IdCardManagerDashboard.jsx`
- `front/src/pages/IdCardManager/IdCardManagerStudents.jsx`

**Fichiers modifiés**:
- `front/src/components/ProtectedRoute.jsx` (ajout `IdCardManagerRoute`)
- `front/src/App.js` (ajout imports et routes)

---

## 🧪 Tests

### Test backend (via Postman/Insomnia)

1. **Créer un compte**:
```bash
php artisan user:create-id-card-manager test_cards --email=test@test.com --password=test123
```

2. **Login**:
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "username": "test_cards",
  "password": "test123"
}
```

3. **Test dashboard**:
```http
GET http://localhost:8000/api/id-card-manager/dashboard
Authorization: Bearer {token}
```

4. **Test liste classes**:
```http
GET http://localhost:8000/api/id-card-manager/classes
Authorization: Bearer {token}
```

5. **Test upload photo**:
```http
POST http://localhost:8000/api/id-card-manager/students/1/update-photo
Authorization: Bearer {token}
Content-Type: multipart/form-data

photo: [fichier image]
```

### Test frontend

1. Ouvrir http://localhost:3000/login
2. Se connecter avec `test_cards` / `test123`
3. Vérifier la redirection vers `/id-card-manager/dashboard`
4. Naviguer vers une classe
5. Tester l'upload d'une photo
6. Tester la prévisualisation
7. Tester l'impression

---

## 📝 Notes importantes

1. **Stockage des photos**: Les photos sont stockées dans `storage/app/public/students/photos/`
2. **Format des cartes**: Les cartes utilisent le même template que l'admin (`StudentCardController`)
3. **Année scolaire**: Le système utilise automatiquement l'année scolaire active
4. **Permissions réelles**: Le gestionnaire de cartes ne peut PAS:
   - Créer/modifier/supprimer des élèves
   - Modifier les informations personnelles (nom, date naissance, etc.)
   - Accéder aux modules notes/paiements/bulletins

---

## 🔄 Évolutions possibles

- [ ] Permettre l'upload massif de photos (ZIP)
- [ ] Ajouter un système de validation de photos par l'admin
- [ ] Historique des modifications de photos
- [ ] Statistiques d'impression de cartes
- [ ] Notification automatique quand toutes les photos d'une classe sont complètes
- [ ] Intégration avec un scanner de photos

---

## 👥 Contact

Pour toute question sur ce rôle:
- Documentation technique: `back/ID_CARD_MANAGER_ROLE.md`
- Migration: `database/migrations/2025_12_16_232008_add_id_card_manager_role_documentation.php`
- Contrôleur: `app/Http/Controllers/IdCardManagerController.php`

---

**Créé le**: 16 Décembre 2024
**Version**: 1.0
**Auteur**: System Administrator - CPB Douala
