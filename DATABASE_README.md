# 📚 Base de Données - Système de Gestion Scolaire GSBPL

Groupe Scolaire Bilingue Privé La Semence

## 🗄️ Structure de la Base de Données

Cette base de données supporte un système complet de gestion scolaire avec :

### 📋 Fonctionnalités Principales
- ✅ Gestion multi-établissements (multi-tenancy)
- ✅ Gestion des utilisateurs (Admin, Comptable, Enseignants)
- ✅ Structure académique (Sections, Classes, Matières)
- ✅ Gestion des étudiants et enseignants
- ✅ Système d'évaluation complet (Notes, Compétences, Domaines)
- ✅ Gestion financière (Paiements, Frais scolaires)
- ✅ Rapports et statistiques
- ✅ Système de séquences et trimestres

## 🚀 Installation

### Prérequis
- MySQL 8.0+ ou MariaDB 10.5+
- Droits administrateur sur le serveur de base de données

### Étape 1 : Créer la Base de Données

```sql
-- Se connecter à MySQL en tant qu'administrateur
mysql -u root -p

-- Exécuter le script de création
source /path/to/database_schema.sql
```

### Étape 2 : Insérer les Données de Test

```sql
-- Exécuter le script de données de test
source /path/to/seed_data.sql
```

### Étape 3 : Créer un Utilisateur Dédié (Recommandé)

```sql
-- Créer un utilisateur pour l'application
CREATE USER 'gsbpl_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';

-- Accorder les permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON gsbpl_school_management.* TO 'gsbpl_user'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;
```

## 📊 Structure des Tables

### 🏫 Tables Principales

| Table | Description | Relations |
|-------|-------------|-----------|
| `users` | Administrateurs et comptables | → `payments_details` |
| `teachers` | Enseignants | → `class` |
| `students` | Étudiants | → `notes`, `payments_details` |
| `sections` | Sections académiques | → `class`, `subjects`, `domains` |
| `class` | Classes | → `students`, `teachers` |

### 📚 Tables Académiques

| Table | Description | Relations |
|-------|-------------|-----------|
| `subjects` | Matières par section | → `notesBySubject` |
| `domains` | Domaines de compétences | → `activities`, `notesByDomain` |
| `activities` | Activités par domaine | → `notesByDomain` |
| `com` | Compétences | → `sub_com` |
| `sub_com` | Sous-compétences | → `notes` |

### 📝 Tables d'Évaluation

| Table | Description | Type de Notes |
|-------|-------------|---------------|
| `notes` | Notes par compétences | Système par compétences |
| `notesBySubject` | Notes par matière | Système traditionnel |
| `notesByDomain` | Notes par domaine | Système par activités |
| `stats` | Statistiques générales | Totaux et moyennes |

### 💰 Tables Financières

| Table | Description | Utilisation |
|-------|-------------|-------------|
| `payments_details` | Détails des paiements | Reçus et transactions |

### ⚙️ Tables Système

| Table | Description | Configuration |
|-------|-------------|---------------|
| `settings` | Paramètres système | Configuration par école |
| `seq` | Séquences | Périodes d'évaluation |
| `trims` | Trimestres | Regroupement de séquences |
| `annual_exams` | Examens annuels | Évaluations de fin d'année |

## 🔐 Comptes de Test

### 👨‍💼 Administrateur
- **Username:** `admin`
- **Password:** `password123`
- **Rôle:** Administrateur (ad)
- **Accès:** Gestion complète du système

### 💼 Comptable
- **Username:** `comptable`
- **Password:** `password123`
- **Rôle:** Comptable (comp)
- **Accès:** Gestion financière et rapports

### 👩‍🏫 Enseignants (Exemples)
| Matricule | Password | Classe | Section |
|-----------|----------|--------|---------|
| `SEM-PETITE-SECTION` | `1234` | Petite Section | Maternelle |
| `SEM-CP-A` | `5678` | CP A | Primaire FR |
| `SEM-CE1-A` | `9012` | CE1 A | Primaire FR |
| `SEM-CM2-A` | `7890` | CM2 A | Primaire FR |
| `SEM-SIL-A` | `4567` | SIL A | Primaire EN |

## 📈 Données de Test Incluses

### 🏛️ Structure Académique
- **5 sections** : Maternelle, Primaire FR/EN, Secondaire FR/EN
- **24 classes** : De la Petite Section à SIL C
- **10 enseignants** assignés aux classes principales
- **15 étudiants** répartis dans différentes classes

### 📚 Contenu Pédagogique
- **Matières complètes** pour chaque section
- **Domaines et activités** pour la maternelle
- **Compétences et sous-compétences** détaillées
- **6 séquences** et **3 trimestres** configurés

### 💵 Données Financières
- **Barèmes de frais** par niveau et type d'étudiant
- **Exemples de paiements** avec reçus
- **Structure tarifaire** complète

## 🔧 Configuration Backend

### Variables d'Environnement
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=gsbpl_school_management
DB_USER=gsbpl_user
DB_PASSWORD=votre_mot_de_passe_securise
```

### Connexion TypeScript/Node.js
```typescript
import mysql from 'mysql2/promise';

const connection = await mysql.createConnection({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  charset: 'utf8mb4'
});
```

## 🛡️ Sécurité

### ⚠️ Actions Requises en Production
1. **Changer tous les mots de passe par défaut**
2. **Utiliser des mots de passe forts** pour les comptes admin
3. **Configurer SSL/TLS** pour les connexions
4. **Limiter les accès réseau** à la base de données
5. **Mettre en place des sauvegardes** automatiques
6. **Activer les logs d'audit**

### 🔒 Bonnes Pratiques
- Utiliser des **paramètres préparés** pour toutes les requêtes
- **Valider toutes les entrées** côté backend
- **Limiter les privilèges** des utilisateurs de base de données
- **Chiffrer les données sensibles** si nécessaire

## 📊 Maintenance

### Sauvegardes
```bash
# Sauvegarde complète
mysqldump -u root -p gsbpl_school_management > backup_$(date +%Y%m%d).sql

# Sauvegarde structure uniquement
mysqldump -u root -p --no-data gsbpl_school_management > structure_$(date +%Y%m%d).sql
```

### Optimisation
```sql
-- Analyser les performances
ANALYZE TABLE students, class, notes, payments_details;

-- Optimiser les tables
OPTIMIZE TABLE students, class, notes, payments_details;
```

### Monitoring
- Surveiller l'espace disque utilisé
- Vérifier les logs d'erreurs régulièrement
- Contrôler les performances des requêtes

## 🆘 Support

### Logs Utiles
- **MySQL Error Log** : `/var/log/mysql/error.log`
- **Slow Query Log** : Pour identifier les requêtes lentes
- **General Log** : Pour déboguer les requêtes

### Commandes de Diagnostic
```sql
-- Vérifier l'état des tables
CHECK TABLE students, class, notes;

-- Réparer si nécessaire
REPAIR TABLE nom_de_table;

-- Statistiques d'utilisation
SHOW TABLE STATUS LIKE 'students';
```

## 📞 Contact

Pour toute question ou support technique :
- **Développeur** : [Votre nom]
- **Email** : [Votre email]
- **Documentation** : Voir le fichier CLAUDE.md du projet

---

*© 2024 Groupe Scolaire Bilingue Privé La Semence - Système de Gestion Scolaire*