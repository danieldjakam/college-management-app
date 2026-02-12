# Configuration du Serveur Web LWS pour CPB Douala

## 🚨 Problèmes Identifiés

1. **http://admin1.cpb-douala.com/** → Page par défaut ISPConfig
2. **https://admin1.cpb-douala.com/** → Site Insam Technologie (mauvaise config)
3. **Backend Laravel** → Non accessible
4. **Frontend React** → Connexion OK mais pas de communication avec backend

## 📋 Architecture Cible

```
admin1.cpb-douala.com (Backend Laravel API)
└── Document Root: /web/laravel/public/

cpb-douala.com (Frontend React - optionnel)
└── Document Root: /web/
```

## Cause Principale

Sur LWS avec **ISPConfig**, le Document Root pointe vers `/web/` qui contient `index.html` par défaut. Il faut configurer ISPConfig pour pointer vers le dossier `public/` de Laravel.

---

## 🔧 SOLUTION RAPIDE - Configuration via SSH (LWS)

### Étape 1 : Se connecter en SSH

```bash
# Connectez-vous à votre serveur LWS
ssh votre_utilisateur@admin1.cpb-douala.com
# Entrez votre mot de passe SSH
```

### Étape 2 : Localiser votre répertoire web

Sur LWS, votre site est généralement dans :
```bash
# Aller à la racine de votre compte
cd ~
ls -la

# Vous devriez voir quelque chose comme :
# web/         (Document root public - ce que voit le visiteur)
# private/     (Fichiers privés)
# log/         (Logs)
```

**Vérifier le chemin exact :**
```bash
pwd
# Devrait afficher : /homepages/XX/dXXXXXXXXX/htdocs
# ou : /kunden/homepages/XX/dXXXXXXXXX/htdocs
```

### Étape 3 : Uploader le code Laravel

**Option A : Via FTP (recommandé pour débutants)**
1. Utilisez FileZilla ou un client FTP
2. Connectez-vous à : `admin1.cpb-douala.com`
3. Uploadez TOUT le contenu du dossier `back/` vers `/htdocs/laravel/`
4. Assurez-vous que la structure est :
   ```
   /htdocs/laravel/
   ├── app/
   ├── config/
   ├── public/
   │   └── index.php
   ├── storage/
   ├── .env
   └── artisan
   ```

**Option B : Via Git (si disponible)**
```bash
cd ~/htdocs
mkdir laravel
cd laravel
# Si Git est installé sur le serveur
git clone https://votre-repo.git .
# Sinon utiliser FTP (Option A)
```

### Étape 4 : Configurer le fichier .env

```bash
cd ~/htdocs/laravel
nano .env  # ou vim .env
```

**Contenu minimal du .env :**
```env
APP_NAME="CPB Douala"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=false
APP_URL=https://admin1.cpb-douala.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=dXXXXXXX  # Nom de votre base LWS (voir panel LWS)
DB_USERNAME=dXXXXXXX  # Utilisateur MySQL LWS
DB_PASSWORD=VOTRE_MDP_MYSQL

JWT_SECRET=VOTRE_JWT_SECRET_ICI
JWT_TTL=60

QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_DRIVER=file
```

**Générer la clé APP_KEY :**
```bash
# Si Composer est installé sur le serveur
composer install --no-dev --optimize-autoloader
php artisan key:generate

# Sinon, générez-la en local et copiez la valeur dans .env
```

### Étape 5 : Configuration ISPConfig (CRITIQUE)

🔴 **C'EST L'ÉTAPE LA PLUS IMPORTANTE**

**Via le panel web LWS/ISPConfig :**
1. Connectez-vous à https://admin1.cpb-douala.com:8080 (ou panel.lws.fr)
2. Allez dans **Sites** → **admin1.cpb-douala.com**
3. Onglet **Options**
4. **Changez le Document Root** :
   ```
   Ancien : /web
   Nouveau : /web/laravel/public
   ```
   OU si votre structure est différente :
   ```
   /htdocs/laravel/public
   ```
5. **Cochez** : `Suivi de liens symboliques`
6. **Sauvegardez**

### Étape 6 : Créer un .htaccess dans public/

```bash
cd ~/htdocs/laravel/public
nano .htaccess
```

Contenu :
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Redirection HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Désactiver l'affichage des erreurs
php_flag display_errors off
```

### Étape 7 : Permissions (IMPORTANT)

```bash
cd ~/htdocs/laravel
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Si vous avez accès sudo (rare sur LWS)
# chown -R www-data:www-data storage bootstrap/cache
```

### Étape 8 : Optimiser Laravel pour Production

```bash
cd ~/htdocs/laravel

# Cacher la configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Si erreur "composer not found", utilisez le chemin complet
# /usr/local/bin/php artisan config:cache
```

### Étape 9 : Tester l'installation

**Test 1 : Vérifier que le backend répond**
```bash
# Dans votre navigateur, allez sur :
https://admin1.cpb-douala.com

# Vous devriez voir une page Laravel (même si erreur, c'est déjà bon signe)
# PAS la page "Welcome to your website!"
```

**Test 2 : Tester l'API**
```bash
# En ligne de commande SSH :
curl https://admin1.cpb-douala.com/api/health

# Devrait retourner quelque chose (JSON ou erreur Laravel)
# Si vous voyez "404 Not Found" → OK, Laravel fonctionne
```

**Test 3 : Vérifier les logs**
```bash
# Logs Laravel
tail -f ~/htdocs/laravel/storage/logs/laravel.log

# Logs Apache (si accès)
tail -f ~/log/error.log
```

---

## 🔴 SI LE PROBLÈME PERSISTE

### Diagnostic Rapide SSH

Connectez-vous en SSH et exécutez ces commandes :

```bash
# 1. Vérifier la structure
cd ~
find . -name "index.php" -type f 2>/dev/null
# Devrait trouver : ./htdocs/laravel/public/index.php

# 2. Vérifier que .env existe
ls -la ~/htdocs/laravel/.env
# Devrait afficher le fichier .env

# 3. Vérifier les permissions
ls -la ~/htdocs/laravel/storage
# storage/ doit être en 755 ou 775

# 4. Tester PHP
php -v
# Devrait afficher PHP 8.x

# 5. Vérifier la connexion DB
cd ~/htdocs/laravel
php artisan tinker
>>> DB::connection()->getPdo();
# Devrait afficher PDO object (si DB OK)
```

### Solution Alternative : Lien Symbolique

Si vous ne pouvez PAS modifier le Document Root dans ISPConfig :

```bash
cd ~/htdocs
# Sauvegarder les anciens fichiers
mkdir backup_old_web
mv web/* backup_old_web/ 2>/dev/null

# Créer des liens symboliques
ln -s laravel/public/* web/
# OU copier les fichiers
cp -r laravel/public/* web/
```

### Configuration .htaccess à la racine (Plan B)

Si vraiment rien ne fonctionne, créez ceci :

**Fichier : `~/htdocs/web/.htaccess`**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Rediriger tout vers laravel/public
    RewriteRule ^(.*)$ laravel/public/$1 [L,QSA]
</IfModule>
```

**⚠️ ATTENTION** : Cette méthode expose vos fichiers sources. À utiliser uniquement temporairement.

---

## 📋 Checklist Complète

Une fois configuré, vérifiez :

- [ ] `https://admin1.cpb-douala.com` → Affiche Laravel (pas "Welcome to your website!")
- [ ] `https://admin1.cpb-douala.com/api/health` → Retourne JSON ou erreur Laravel
- [ ] Fichier `.env` configuré avec bonnes valeurs DB
- [ ] `APP_ENV=production` et `APP_DEBUG=false` dans .env
- [ ] Permissions 755 sur `storage/` et `bootstrap/cache/`
- [ ] Cache Laravel généré (`config:cache`, `route:cache`)
- [ ] Migrations DB exécutées : `php artisan migrate --force`
- [ ] Logs sans erreurs critiques : `tail -f storage/logs/laravel.log`

---

## 🌐 Déploiement du Frontend React (Optionnel)

Si vous voulez aussi déployer le frontend sur `cpb-douala.com` :

### 1. Builder le frontend en local

```bash
# Sur votre machine locale
cd /Users/redwolf-dark/Documents/Projet/college-management-app/front

# Configurer l'URL de l'API
nano .env
# Ajouter : REACT_APP_API_URL=https://admin1.cpb-douala.com/api

# Builder
npm run build
```

### 2. Uploader via FTP

1. Connectez-vous à `cpb-douala.com` via FTP
2. Uploadez le contenu du dossier `build/` vers `/htdocs/web/`
3. Structure finale :
   ```
   /htdocs/web/
   ├── index.html
   ├── static/
   │   ├── css/
   │   ├── js/
   │   └── media/
   └── .htaccess
   ```

### 3. Créer .htaccess pour React Router

**Fichier : `/htdocs/web/.htaccess`**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirection HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

    # React Router - Toutes les routes vers index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
```

### 4. Tester

- Allez sur `https://cpb-douala.com`
- La page de connexion devrait s'afficher
- Testez une connexion avec un compte admin

---

## 🆘 Problèmes Courants & Solutions

### Erreur 500 Internal Server Error

**Causes possibles :**
```bash
# 1. Vérifier les logs Laravel
tail -f ~/htdocs/laravel/storage/logs/laravel.log

# 2. Vérifier permissions
chmod -R 755 ~/htdocs/laravel/storage
chmod -R 755 ~/htdocs/laravel/bootstrap/cache

# 3. Vérifier .env
cat ~/htdocs/laravel/.env | grep APP_KEY
# Si vide → php artisan key:generate

# 4. Vérifier connexion DB
cd ~/htdocs/laravel
php artisan tinker
>>> DB::connection()->getPdo();
```

### Page toujours "Welcome to your website!"

**Solution :**
```bash
# Vérifier le Document Root dans ISPConfig
# Doit pointer vers : /htdocs/laravel/public

# OU créer lien symbolique
cd ~/htdocs
ln -s laravel/public web

# OU copier les fichiers
cp -r laravel/public/* web/
```

### Erreur CORS (Frontend ne peut pas communiquer avec Backend)

**Fichier : `~/htdocs/laravel/config/cors.php`**
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://cpb-douala.com',
        'https://www.cpb-douala.com',
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

Puis :
```bash
cd ~/htdocs/laravel
php artisan config:cache
```

### Site affiche Insam Technologie au lieu de CPB

Cela signifie que **le mauvais site est configuré sur le domaine**. Solutions :

1. Via ISPConfig : Vérifier que `admin1.cpb-douala.com` pointe vers le bon répertoire
2. Supprimer les anciens fichiers Insam :
   ```bash
   cd ~/htdocs/web
   ls -la
   # Si vous voyez des fichiers Insam, les supprimer :
   rm -rf *
   ```

---

## 📞 Support & Contact

**Si le problème persiste :**

1. **Logs à vérifier :**
   ```bash
   tail -f ~/htdocs/laravel/storage/logs/laravel.log
   tail -f ~/log/error.log
   ```

2. **Diagnostic complet :**
   ```bash
   # Exécuter ces commandes et envoyer la sortie
   pwd
   ls -la
   ls -la htdocs/laravel/
   cat htdocs/laravel/.env | head -15
   php -v
   which composer
   ```

3. **Support LWS :**
   - Contacter le support technique LWS
   - Demander vérification du Document Root
   - Vérifier que PHP 8.2 est actif
   - Vérifier que mod_rewrite est activé

4. **Support CPB Douala :**
   - Email : support@cpb-douala.com
   - Envoyer logs + captures d'écran

---

## ✅ Résumé Rapide

**Pour admin1.cpb-douala.com (Backend Laravel) :**
1. Uploader le code dans `/htdocs/laravel/`
2. Configurer `.env` avec DB LWS
3. Modifier Document Root dans ISPConfig → `/htdocs/laravel/public`
4. Permissions : `chmod -R 755 storage bootstrap/cache`
5. Cache : `php artisan config:cache && php artisan route:cache`

**Pour cpb-douala.com (Frontend React) :**
1. Builder en local : `npm run build`
2. Uploader `build/*` vers `/htdocs/web/`
3. Créer `.htaccess` pour React Router
4. Configurer CORS sur le backend

**Test final :**
- `https://admin1.cpb-douala.com/api/health` → Retourne JSON
- `https://cpb-douala.com` → Affiche interface de connexion
- Connexion réussie → Backend + Frontend OK ✅
