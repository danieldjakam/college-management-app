# 🐘 Configuration PostgreSQL pour Laravel

## 📋 Prérequis

### 1. Installation PostgreSQL sur macOS
```bash
# Avec Homebrew
brew install postgresql
brew services start postgresql

# Ou télécharger depuis https://postgresapp.com/
```

### 2. Créer la base de données
```bash
# Se connecter à PostgreSQL
psql -U postgres

# Créer la base de données
CREATE DATABASE college_management_app;

# Créer un utilisateur dédié (optionnel)
CREATE USER college_user WITH ENCRYPTED PASSWORD 'college_password_123';
GRANT ALL PRIVILEGES ON DATABASE college_management_app TO college_user;

# Sortir de psql
\q
```

## ⚙️ Configuration Laravel

### 1. Modifier le fichier `.env`
```env
# Configuration PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=college_management_app
DB_USERNAME=college_user
DB_PASSWORD=college_password_123

# Ou si vous utilisez l'utilisateur postgres par défaut
# DB_USERNAME=postgres
# DB_PASSWORD=
```

### 2. Installer le driver PostgreSQL pour PHP
```bash
# Sur macOS avec Homebrew
brew install php-pdo-pgsql

# Ou vérifier que l'extension est activée
php -m | grep pgsql
```

### 3. Configuration dans `config/database.php` (vérification)
```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
```

## 🔄 Migration et données

### 1. Effacer les anciens caches
```bash
cd "/Users/macbookpro/Desktop/Developments/Personnals/School College App/college-management-app/back"

php artisan config:cache
php artisan cache:clear
php artisan route:clear
```

### 2. Tester la connexion
```bash
php artisan tinker
DB::connection()->getPDO();
echo "Connexion PostgreSQL réussie !";
exit
```

### 3. Migrer les données (ATTENTION: Va supprimer toutes les données)
```bash
# Réinitialiser complètement la base
php artisan migrate:fresh

# Ou juste migrer
php artisan migrate

# Avec des seeders si vous en avez
php artisan migrate:fresh --seed
```

### 4. Recréer les utilisateurs de test
```bash
php artisan tinker
```

Dans tinker :
```php
// Recréer l'admin de test
$admin = new App\Models\User();
$admin->name = 'Admin Test';
$admin->username = 'admin_test';
$admin->email = 'admin@test.com';
$admin->password = Hash::make('password123');
$admin->role = 'admin';
$admin->is_active = true;
$admin->save();

// Vérifier que PIEFLEYOU JACQUELINE existe toujours
$jacqueline = App\Models\User::where('name', 'LIKE', '%PIEFLEYOU%')->first();
if ($jacqueline) {
    $jacqueline->qr_code = 'STAFF_56';
    $jacqueline->save();
    echo "QR Code STAFF_56 attribué à " . $jacqueline->name;
} else {
    echo "Utilisateur PIEFLEYOU JACQUELINE non trouvé";
}
```

## 🔍 Vérifications

### 1. Tester la connexion PostgreSQL
```bash
# Test direct PostgreSQL
psql -h 127.0.0.1 -U college_user -d college_management_app -c "SELECT version();"

# Test Laravel
php artisan migrate:status
```

### 2. Vérifier les tables
```bash
php artisan tinker
```

Dans tinker :
```php
// Lister les tables
DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");

// Compter les utilisateurs
App\Models\User::count();

// Compter les scans (doit être 0)
App\Models\StaffAttendance::count();
```

## 🚀 Avantages PostgreSQL vs MySQL

✅ **Performances** : Meilleures performances pour les requêtes complexes
✅ **Types de données** : Plus de types natifs (JSON, arrays, etc.)
✅ **Concurrence** : Gestion MVCC plus avancée
✅ **Transactions** : Support complet des transactions ACID
✅ **Extensions** : Extensible avec de nombreuses extensions
✅ **Conformité SQL** : Plus strict et conforme aux standards SQL

## ⚠️ Points d'attention

- **Sensibilité à la casse** : PostgreSQL est plus strict sur les noms
- **Syntaxe légèrement différente** : Quelques différences SQL avec MySQL
- **Mémoire** : Utilise généralement plus de RAM que MySQL

## 🧪 Script de test complet

```bash
#!/bin/bash
echo "🧪 Test configuration PostgreSQL..."

# 1. Test connexion
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPDO();
    echo '✅ Connexion PostgreSQL réussie' . PHP_EOL;
    echo 'Version: ' . \$pdo->query('SELECT version()')->fetchColumn() . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Erreur connexion: ' . \$e->getMessage() . PHP_EOL;
}
"

# 2. Test tables
php artisan migrate:status

# 3. Test données
php artisan tinker --execute="
echo 'Utilisateurs: ' . App\Models\User::count() . PHP_EOL;
echo 'Scans: ' . App\Models\StaffAttendance::count() . PHP_EOL;
"

echo "🎉 Configuration terminée !"
```

Suivez ces étapes dans l'ordre et votre base sera prête avec PostgreSQL ! 🚀