# 🔐 Guide d'installation HTTPS pour CPB Douala

## Prérequis
- Accès SSH à votre VPS LWS
- Nginx installé
- Deux sous-domaines configurés dans les DNS de LWS :
  - `admin1.cpb-douala.com` (backend API Laravel)
  - `admin.cpb-douala.com` (frontend React)

---

## 🚀 Installation automatique (Méthode recommandée)

### Étape 1 : Connexion SSH au serveur

```bash
ssh votre_utilisateur@votre_ip_serveur
```

### Étape 2 : Exécuter le script d'installation

Copiez et collez ce script complet dans votre terminal SSH :

```bash
#!/bin/bash

echo "============================================"
echo "🔐 Installation HTTPS pour CPB Douala"
echo "============================================"
echo ""

# Variables - MODIFIEZ SELON VOS BESOINS
BACKEND_DOMAIN="admin1.cpb-douala.com"
FRONTEND_DOMAIN="admin.cpb-douala.com"
EMAIL="admin@cpb-douala.com"  # Email pour Let's Encrypt

echo "Configuration :"
echo "- Backend : $BACKEND_DOMAIN"
echo "- Frontend : $FRONTEND_DOMAIN"
echo "- Email : $EMAIL"
echo ""

read -p "Est-ce correct ? (oui/non) : " confirm
if [ "$confirm" != "oui" ]; then
    echo "❌ Installation annulée. Modifiez les variables dans le script."
    exit 1
fi

echo ""
echo "🔍 Vérification de l'environnement..."

# Vérifier si Nginx est installé
if ! command -v nginx &> /dev/null; then
    echo "❌ Nginx n'est pas installé !"
    echo "Installez-le avec : sudo apt install nginx"
    exit 1
else
    echo "✅ Nginx est installé (version $(nginx -v 2>&1 | cut -d'/' -f2))"
fi

# Vérifier si Nginx est en cours d'exécution
if ! systemctl is-active --quiet nginx; then
    echo "⚠️  Nginx n'est pas démarré. Démarrage..."
    sudo systemctl start nginx
else
    echo "✅ Nginx est en cours d'exécution"
fi

# Vérifier si Certbot est déjà installé
if command -v certbot &> /dev/null; then
    echo "✅ Certbot est déjà installé (version $(certbot --version 2>&1 | cut -d' ' -f2))"
    CERTBOT_INSTALLED=true
else
    echo "⚠️  Certbot n'est pas installé"
    CERTBOT_INSTALLED=false
fi

# Vérifier si les certificats existent déjà
BACKEND_CERT_EXISTS=false
FRONTEND_CERT_EXISTS=false

if [ -d "/etc/letsencrypt/live/$BACKEND_DOMAIN" ]; then
    echo "✅ Certificat SSL déjà présent pour $BACKEND_DOMAIN"
    BACKEND_CERT_EXISTS=true
fi

if [ -d "/etc/letsencrypt/live/$FRONTEND_DOMAIN" ]; then
    echo "✅ Certificat SSL déjà présent pour $FRONTEND_DOMAIN"
    FRONTEND_CERT_EXISTS=true
fi

echo ""
echo "📦 Étape 1/5 : Mise à jour du système..."
sudo apt update

# Installer Certbot seulement s'il n'est pas déjà installé
if [ "$CERTBOT_INSTALLED" = false ]; then
    echo ""
    echo "📦 Étape 2/5 : Installation de Certbot..."
    sudo apt install certbot python3-certbot-nginx -y

    if [ $? -eq 0 ]; then
        echo "✅ Certbot installé avec succès"
    else
        echo "❌ Erreur lors de l'installation de Certbot"
        exit 1
    fi
else
    echo ""
    echo "⏭️  Étape 2/5 : Certbot déjà installé, passage à l'étape suivante..."
fi

# Obtenir ou renouveler le certificat backend
echo ""
if [ "$BACKEND_CERT_EXISTS" = true ]; then
    echo "🔄 Étape 3/5 : Renouvellement du certificat SSL pour le BACKEND..."
    sudo certbot renew --cert-name $BACKEND_DOMAIN --nginx --non-interactive
else
    echo "🔒 Étape 3/5 : Obtention du certificat SSL pour le BACKEND..."
    sudo certbot --nginx -d $BACKEND_DOMAIN --email $EMAIL --agree-tos --redirect --non-interactive
fi

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de la configuration SSL du backend"
    echo "Vérifiez que :"
    echo "  - Le domaine $BACKEND_DOMAIN pointe bien vers ce serveur"
    echo "  - Le port 80 et 443 sont ouverts dans le firewall"
    exit 1
fi

# Obtenir ou renouveler le certificat frontend
echo ""
if [ "$FRONTEND_CERT_EXISTS" = true ]; then
    echo "🔄 Étape 4/5 : Renouvellement du certificat SSL pour le FRONTEND..."
    sudo certbot renew --cert-name $FRONTEND_DOMAIN --nginx --non-interactive
else
    echo "🔒 Étape 4/5 : Obtention du certificat SSL pour le FRONTEND..."
    sudo certbot --nginx -d $FRONTEND_DOMAIN --email $EMAIL --agree-tos --redirect --non-interactive
fi

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de la configuration SSL du frontend"
    echo "Vérifiez que :"
    echo "  - Le domaine $FRONTEND_DOMAIN pointe bien vers ce serveur"
    echo "  - Le port 80 et 443 sont ouverts dans le firewall"
    exit 1
fi

echo ""
echo "✅ Étape 5/5 : Vérification et redémarrage de Nginx..."
sudo nginx -t

if [ $? -eq 0 ]; then
    sudo systemctl reload nginx
    echo ""
    echo "============================================"
    echo "✅ INSTALLATION TERMINÉE AVEC SUCCÈS !"
    echo "============================================"
    echo ""
    echo "Vos sites sont maintenant en HTTPS :"
    echo "- Backend : https://$BACKEND_DOMAIN"
    echo "- Frontend : https://$FRONTEND_DOMAIN"
    echo ""
    echo "📋 Certificats installés :"
    sudo certbot certificates
    echo ""
    echo "🔄 Renouvellement automatique configuré !"
    echo "Testez avec : sudo certbot renew --dry-run"
    echo ""
    echo "🔍 Prochaines étapes :"
    echo "1. Rebuilder le frontend : cd /var/www/cpb-douala/front && npm run build"
    echo "2. Tester : https://$FRONTEND_DOMAIN"
    echo "3. Vérifier les logs : sudo tail -f /var/log/nginx/access.log"
    echo ""
else
    echo ""
    echo "❌ ERREUR dans la configuration Nginx !"
    echo "Vérifiez les logs : sudo tail -f /var/log/nginx/error.log"
    exit 1
fi
```

### Étape 3 : Rendre le script exécutable et le lancer

```bash
# Créer le fichier
nano install_https.sh

# Coller le script ci-dessus, puis sauvegarder (Ctrl+X, Y, Entrée)

# Rendre exécutable
chmod +x install_https.sh

# Exécuter
./install_https.sh
```

---

## 🛠️ Installation manuelle (Méthode alternative)

### 1. Installer Certbot

```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx -y
```

### 2. Obtenir le certificat SSL pour le backend

```bash
sudo certbot --nginx -d admin1.cpb-douala.com
```

Choisissez :
- **Option 2** : Redirect (forcer HTTPS)

### 3. Obtenir le certificat SSL pour le frontend

```bash
sudo certbot --nginx -d admin.cpb-douala.com
```

Choisissez :
- **Option 2** : Redirect (forcer HTTPS)

### 4. Vérifier la configuration

```bash
# Tester la configuration Nginx
sudo nginx -t

# Recharger Nginx
sudo systemctl reload nginx
```

### 5. Vérifier le renouvellement automatique

```bash
# Test à blanc
sudo certbot renew --dry-run
```

---

## 📝 Configurations Nginx générées (pour référence)

### Backend (admin1.cpb-douala.com)

Certbot devrait générer quelque chose comme :

```nginx
server {
    listen 80;
    server_name admin1.cpb-douala.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin1.cpb-douala.com;

    ssl_certificate /etc/letsencrypt/live/admin1.cpb-douala.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin1.cpb-douala.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/cpb-douala/back/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Frontend (admin.cpb-douala.com)

```nginx
server {
    listen 80;
    server_name admin.cpb-douala.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin.cpb-douala.com;

    ssl_certificate /etc/letsencrypt/live/admin.cpb-douala.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.cpb-douala.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/cpb-douala/front/build;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 🔍 Vérification après installation

### 1. Tester les URLs HTTPS

```bash
# Test backend
curl -I https://admin1.cpb-douala.com/api

# Test frontend
curl -I https://admin.cpb-douala.com
```

### 2. Vérifier les certificats

```bash
# Backend
sudo certbot certificates | grep admin1.cpb-douala.com

# Frontend
sudo certbot certificates | grep admin.cpb-douala.com
```

### 3. Tester dans le navigateur

- ✅ Ouvrir https://admin.cpb-douala.com
- ✅ Vérifier le cadenas vert dans la barre d'adresse
- ✅ Tester la connexion et les fonctionnalités

### 4. Vérifier les logs Nginx

```bash
# Logs d'erreur
sudo tail -f /var/log/nginx/error.log

# Logs d'accès
sudo tail -f /var/log/nginx/access.log
```

---

## 🚨 Résolution de problèmes

### Erreur : "Connexion au serveur impossible"

**Cause** : Le frontend essaie d'appeler le backend en HTTP au lieu de HTTPS

**Solution** : Vérifiez que le fichier `front/src/utils/fetch.js` utilise bien HTTPS :
```javascript
export const host = "https://admin1.cpb-douala.com";
```

### Erreur : "Mixed Content"

**Cause** : Mélange de ressources HTTP et HTTPS

**Solution** :
1. Vérifiez que TOUTES les URLs dans le code utilisent HTTPS
2. Vérifiez la configuration CORS dans `back/config/cors.php`

### Erreur : "Certificate not found"

**Cause** : Les DNS ne pointent pas vers le serveur

**Solution** :
1. Vérifiez les enregistrements DNS dans votre panel LWS
2. Attendez la propagation DNS (jusqu'à 24h)
3. Testez avec : `nslookup admin1.cpb-douala.com`

### Erreur : Nginx ne démarre pas

```bash
# Vérifier les erreurs
sudo nginx -t

# Voir les logs
sudo journalctl -u nginx -n 50
```

---

## 🔄 Renouvellement automatique

Certbot configure automatiquement un cron job pour renouveler les certificats.

### Vérifier le renouvellement automatique

```bash
# Test à blanc (simulation)
sudo certbot renew --dry-run

# Voir le statut du timer
sudo systemctl status certbot.timer
```

### Renouveler manuellement (si nécessaire)

```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## 📋 Checklist finale

Après installation, vérifiez :

- [ ] ✅ https://admin1.cpb-douala.com/api fonctionne
- [ ] ✅ https://admin.cpb-douala.com s'affiche correctement
- [ ] ✅ Connexion enseignant fonctionne
- [ ] ✅ Les requêtes API passent bien en HTTPS
- [ ] ✅ Pas d'erreur "Mixed Content" dans la console
- [ ] ✅ Cadenas vert dans le navigateur
- [ ] ✅ Test de renouvellement : `sudo certbot renew --dry-run`

---

## 📞 Support

En cas de problème :
1. Vérifiez les logs Nginx : `sudo tail -f /var/log/nginx/error.log`
2. Vérifiez les certificats : `sudo certbot certificates`
3. Testez la configuration : `sudo nginx -t`
4. Contactez le support LWS si problème DNS

---

## 📚 Ressources utiles

- Documentation Let's Encrypt : https://letsencrypt.org/
- Documentation Certbot : https://certbot.eff.org/
- Test SSL : https://www.ssllabs.com/ssltest/

---

**Date de création** : 11 octobre 2025
**Version** : 1.0
**Projet** : CPB Douala - Plateforme de gestion scolaire
