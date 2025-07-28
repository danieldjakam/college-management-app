# CPBD - Système de Gestion de Collège (Backend Laravel)

## Description
Backend Laravel pour le système de gestion du Collège Polyvalent Bilingue de Douala. Cette API fournit les mêmes fonctionnalités que le backend Node.js existant.

## Installation

### Prérequis
- PHP 8.1 ou supérieur
- Composer
- MySQL 5.7 ou supérieur
- Laravel 10.x

### Configuration

1. **Installation des dépendances**
```bash
composer install
```

2. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configuration de la base de données**
Modifiez le fichier `.env` avec vos paramètres de base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=college_management
DB_USERNAME=root
DB_PASSWORD=
```

4. **Exécution des migrations et seeders**
```bash
php artisan migrate
php artisan db:seed
```

5. **Génération de la clé JWT**
```bash
php artisan jwt:secret
```

6. **Démarrage du serveur**
```bash
php artisan serve
```

L'API sera accessible sur `http://localhost:8000`

## Structure de l'API

### Authentification
- `POST /api/auth/login` - Connexion utilisateur
- `POST /api/auth/register` - Inscription utilisateur
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/user` - Profil utilisateur connecté

### Gestion des Sections
- `GET /api/sections` - Liste des sections
- `POST /api/sections` - Créer une section
- `GET /api/sections/{id}` - Détails d'une section
- `PUT /api/sections/{id}` - Modifier une section
- `DELETE /api/sections/{id}` - Supprimer une section

### Gestion des Classes
- `GET /api/classes` - Liste des classes
- `POST /api/classes` - Créer une classe
- `GET /api/classes/{id}` - Détails d'une classe
- `PUT /api/classes/{id}` - Modifier une classe
- `DELETE /api/classes/{id}` - Supprimer une classe

### Gestion des Étudiants
- `GET /api/students` - Liste des étudiants
- `POST /api/students/{classId}` - Ajouter un étudiant
- `GET /api/students/{id}` - Détails d'un étudiant
- `PUT /api/students/{id}` - Modifier un étudiant
- `DELETE /api/students/{id}` - Supprimer un étudiant
- `GET /api/students/class/{classId}` - Étudiants d'une classe
- `GET /api/students/{id}/payments` - Paiements d'un étudiant

### Gestion des Enseignants
- `GET /api/teachers` - Liste des enseignants
- `POST /api/teachers` - Ajouter un enseignant
- `GET /api/teachers/{id}` - Détails d'un enseignant
- `PUT /api/teachers/{id}` - Modifier un enseignant
- `DELETE /api/teachers/{id}` - Supprimer un enseignant

### Gestion des Notes
- `GET /api/notes/student/{studentId}` - Notes d'un étudiant
- `POST /api/notes` - Ajouter/modifier une note
- `DELETE /api/notes/{id}` - Supprimer une note
- `GET /api/notes/class/{classId}/sequence/{sequenceId}` - Bulletin de classe
- `GET /api/notes/student/{studentId}/sequence/{sequenceId}` - Bulletin étudiant

### Statistiques
- `GET /api/stats/general` - Statistiques générales
- `GET /api/students/total` - Total des étudiants

## Comptes par Défaut

### Administrateur
- **Username:** admin
- **Password:** admin123
- **Rôle:** Administrateur (ad)

### Comptable
- **Username:** comptable
- **Password:** comptable123
- **Rôle:** Comptable (comp)

### Enseignant
- **Username:** jdupont
- **Password:** teacher123
- **Rôle:** Enseignant (classe 6ème)

## Middlewares

- `auth.global` - Authentification générale
- `auth.user` - Authentification utilisateur/enseignant
- `auth.admin` - Authentification administrateur

## Modèles Principaux

- **User** - Utilisateurs administrateurs/comptables
- **Teacher** - Enseignants
- **Student** - Étudiants
- **Section** - Sections (Maternelle, Primaire, Secondaire)
- **SchoolClass** - Classes
- **Subject** - Matières
- **Domain** - Domaines de matières
- **Note** - Notes des étudiants
- **Sequence** - Séquences d'évaluation
- **Trimester** - Trimestres
- **PaymentDetail** - Détails des paiements
- **Setting** - Paramètres de l'application

## Structure des Données

### Étudiants
Chaque étudiant a des informations personnelles et financières :
- Informations personnelles (nom, prénom, sexe, contact)
- Informations scolaires (classe, statut)
- Frais de scolarité (inscription, tranches, graduation, assurance)
- Paiements effectués

### Notes
Système de notation avec :
- Notes par matière et séquence
- Coefficients par matière
- Calcul automatique des moyennes
- Bulletins par séquence et trimestre

### Authentification JWT
- Tokens JWT pour l'authentification
- Rôles différenciés (admin, comptable, enseignant)
- Middleware de protection des routes

## Tests

Pour exécuter les tests :
```bash
php artisan test
```

## Commandes Artisan Utiles

```bash
# Vider le cache
php artisan cache:clear

# Régénérer l'autoloader
composer dump-autoload

# Voir les routes
php artisan route:list

# Voir les migrations
php artisan migrate:status
```

## Support

Pour toute question ou problème, contactez l'équipe de développement.