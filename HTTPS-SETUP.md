# 🔒 Configuration HTTPS pour l'Accès Caméra

Les navigateurs modernes exigent HTTPS pour accéder à la caméra (getUserMedia). Voici comment configurer HTTPS pour le développement et la production.

## 🚀 Développement Local

### Option 1 : mkcert (Recommandée)

```bash
# 1. Exécuter le script de configuration
cd front/
./setup-https-mkcert.sh

# 2. Démarrer l'application
npm start

# 3. Accéder à https://localhost:3006
```

### Option 2 : Certificats auto-signés

```bash
# 1. Générer les certificats
cd front/
./generate-certs.sh

# 2. Démarrer avec HTTPS
npm start

# 3. Accepter l'avertissement de sécurité dans le navigateur
```

## 🌐 Production

### Option 1 : Let's Encrypt (Gratuit)

```bash
# Installation Certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx

# Génération certificat
sudo certbot --nginx -d votre-domaine.com

# Auto-renouvellement
sudo crontab -e
# Ajouter : 0 12 * * * /usr/bin/certbot renew --quiet
```

### Option 2 : Reverse Proxy Nginx

```nginx
# /etc/nginx/sites-available/college-app
server {
    listen 443 ssl;
    server_name votre-domaine.com;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Frontend React
    location / {
        proxy_pass http://localhost:3006;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Backend Laravel API
    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Redirection HTTP vers HTTPS
server {
    listen 80;
    server_name votre-domaine.com;
    return 301 https://$server_name$request_uri;
}
```

### Option 3 : Laravel avec HTTPS

```php
// config/app.php
'url' => env('APP_URL', 'https://votre-domaine.com'),

// Dans AppServiceProvider
public function boot()
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

## 🔧 Configuration React pour HTTPS

### Variables d'environnement (.env)

```env
# HTTPS obligatoire pour l'accès caméra
HTTPS=true
SSL_CRT_FILE=./certs/localhost.crt
SSL_KEY_FILE=./certs/localhost.key
PORT=3006

# URL API backend
REACT_APP_API_URL=https://localhost:8000
```

### Package.json scripts

```json
{
  "scripts": {
    "start": "HTTPS=true react-scripts start",
    "start:https": "HTTPS=true SSL_CRT_FILE=./certs/localhost.crt SSL_KEY_FILE=./certs/localhost.key react-scripts start",
    "setup-https": "./setup-https-mkcert.sh"
  }
}
```

## 🚨 Résolution de Problèmes

### Erreur "Camera access not allowed"
- ✅ Vérifiez que vous êtes en HTTPS
- ✅ Autorisez l'accès caméra dans le navigateur
- ✅ Vérifiez les permissions système

### Certificat non reconnu
```bash
# Réinstaller l'autorité de certification
mkcert -install

# Regénérer les certificats
mkcert -key-file certs/localhost.key -cert-file certs/localhost.crt localhost
```

### Problème CORS avec HTTPS
```php
// Laravel - config/cors.php
'supports_credentials' => true,
'allowed_origins' => ['https://localhost:3006'],
```

## 📱 Test Mobile

Pour tester sur mobile en développement :

1. **Trouver votre IP locale :**
```bash
ifconfig | grep inet
```

2. **Générer certificat pour IP :**
```bash
mkcert -key-file certs/mobile.key -cert-file certs/mobile.crt 192.168.1.100 localhost
```

3. **Accéder via :** `https://192.168.1.100:3006`

## 🔐 Sécurité

- ❌ **Jamais** exposer les clés privées
- ✅ Utiliser des certificats valides en production
- ✅ Configurer HSTS headers
- ✅ Forcer HTTPS pour toute l'application

```nginx
# Headers de sécurité
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
```