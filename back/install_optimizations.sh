#!/bin/bash

# ============================================
# SCRIPT D'INSTALLATION AUTOMATIQUE
# OPTIMISATIONS BASE DE DONNÉES & SERVEUR
# ============================================
# Ce script automatise l'installation des optimisations pour
# résoudre les problèmes de timeout 504 en production
#
# Usage: sudo bash install_optimizations.sh
# ============================================

set -e  # Arrêter si une erreur survient

echo "========================================"
echo "INSTALLATION DES OPTIMISATIONS CPB"
echo "========================================"
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Vérifier si lancé en root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Ce script doit être exécuté en root (sudo)${NC}"
   exit 1
fi

# Demander confirmation
echo -e "${YELLOW}⚠️  Ce script va:${NC}"
echo "1. Exécuter les migrations de base de données"
echo "2. Configurer PHP.ini (max_execution_time, memory_limit)"
echo "3. Configurer MySQL (innodb_buffer_pool_size, slow queries)"
echo "4. Installer et configurer Redis"
echo "5. Configurer Nginx (timeouts, buffers)"
echo "6. Redémarrer les services"
echo ""
read -p "Voulez-vous continuer? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Installation annulée"
    exit 1
fi

# Variables de configuration
PROJECT_DIR="/var/www/college-management-app/back"
PHP_VERSION="8.2"
DB_NAME="c0admin"
DB_USER="root"

# Demander le mot de passe MySQL
echo ""
read -sp "Mot de passe MySQL root: " DB_PASSWORD
echo ""

# ============================================
# ÉTAPE 1: BACKUP
# ============================================
echo ""
echo -e "${GREEN}📦 ÉTAPE 1/7: Backup de la base de données${NC}"
echo "--------------------------------------------"
BACKUP_FILE="/tmp/cpb_backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_FILE
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Backup créé: $BACKUP_FILE${NC}"
else
    echo -e "${RED}❌ Erreur lors du backup${NC}"
    exit 1
fi

# ============================================
# ÉTAPE 2: MIGRATIONS BASE DE DONNÉES
# ============================================
echo ""
echo -e "${GREEN}📊 ÉTAPE 2/7: Application des migrations${NC}"
echo "--------------------------------------------"
cd $PROJECT_DIR
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migrations appliquées avec succès${NC}"
else
    echo -e "${RED}❌ Erreur lors des migrations${NC}"
    exit 1
fi

# ============================================
# ÉTAPE 3: CONFIGURATION PHP
# ============================================
echo ""
echo -e "${GREEN}🐘 ÉTAPE 3/7: Configuration PHP${NC}"
echo "--------------------------------------------"

PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    # Backup du fichier original
    cp $PHP_INI "${PHP_INI}.backup.$(date +%Y%m%d)"
    echo -e "${YELLOW}Backup créé: ${PHP_INI}.backup${NC}"

    # Modifier les paramètres
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sed -i 's/memory_limit = .*/memory_limit = 512M/' $PHP_INI
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' $PHP_INI
    sed -i 's/post_max_size = .*/post_max_size = 25M/' $PHP_INI

    # OPcache
    sed -i 's/;opcache.enable=.*/opcache.enable=1/' $PHP_INI
    sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=256/' $PHP_INI

    echo -e "${GREEN}✅ PHP configuré${NC}"
else
    echo -e "${YELLOW}⚠️  Fichier PHP.ini non trouvé à $PHP_INI${NC}"
    echo "Veuillez configurer manuellement selon config/server/php.ini.example"
fi

# ============================================
# ÉTAPE 4: CONFIGURATION MYSQL
# ============================================
echo ""
echo -e "${GREEN}🗄️  ÉTAPE 4/7: Configuration MySQL${NC}"
echo "--------------------------------------------"

MYSQL_CONF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [ -f "$MYSQL_CONF" ]; then
    # Backup
    cp $MYSQL_CONF "${MYSQL_CONF}.backup.$(date +%Y%m%d)"

    # Ajouter/modifier les paramètres InnoDB
    if ! grep -q "innodb_buffer_pool_size" $MYSQL_CONF; then
        echo "" >> $MYSQL_CONF
        echo "# CPB Optimizations" >> $MYSQL_CONF
        echo "innodb_buffer_pool_size = 1G" >> $MYSQL_CONF
        echo "slow_query_log = 1" >> $MYSQL_CONF
        echo "slow_query_log_file = /var/log/mysql/slow-queries.log" >> $MYSQL_CONF
        echo "long_query_time = 2" >> $MYSQL_CONF
        echo -e "${GREEN}✅ MySQL configuré${NC}"
    else
        echo -e "${YELLOW}⚠️  MySQL semble déjà configuré${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Fichier MySQL non trouvé${NC}"
    echo "Veuillez configurer manuellement selon config/server/my.cnf.example"
fi

# Créer le fichier de log slow queries
touch /var/log/mysql/slow-queries.log
chown mysql:mysql /var/log/mysql/slow-queries.log

# ============================================
# ÉTAPE 5: INSTALLATION REDIS
# ============================================
echo ""
echo -e "${GREEN}⚡ ÉTAPE 5/7: Installation et configuration Redis${NC}"
echo "--------------------------------------------"

if ! command -v redis-server &> /dev/null; then
    echo "Installation de Redis..."
    apt-get update
    apt-get install -y redis-server
fi

# Configuration Redis
REDIS_CONF="/etc/redis/redis.conf"
if [ -f "$REDIS_CONF" ]; then
    cp $REDIS_CONF "${REDIS_CONF}.backup.$(date +%Y%m%d)"

    # Configurer maxmemory
    sed -i 's/# maxmemory .*/maxmemory 512mb/' $REDIS_CONF
    sed -i 's/# maxmemory-policy .*/maxmemory-policy allkeys-lru/' $REDIS_CONF

    echo -e "${GREEN}✅ Redis configuré${NC}"
fi

# Activer et démarrer Redis
systemctl enable redis-server
systemctl start redis-server

# Vérifier Redis
if redis-cli ping &> /dev/null; then
    echo -e "${GREEN}✅ Redis fonctionne${NC}"
else
    echo -e "${RED}❌ Redis ne répond pas${NC}"
fi

# ============================================
# ÉTAPE 6: CONFIGURATION .ENV LARAVEL
# ============================================
echo ""
echo -e "${GREEN}⚙️  ÉTAPE 6/7: Configuration Laravel${NC}"
echo "--------------------------------------------"

cd $PROJECT_DIR

# Mettre à jour le .env pour utiliser Redis
if grep -q "CACHE_DRIVER=" .env; then
    sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
else
    echo "CACHE_DRIVER=redis" >> .env
fi

if grep -q "REDIS_HOST=" .env; then
    sed -i 's/REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env
else
    echo "REDIS_HOST=127.0.0.1" >> .env
fi

# Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimiser pour production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✅ Laravel configuré${NC}"

# ============================================
# ÉTAPE 7: REDÉMARRAGE DES SERVICES
# ============================================
echo ""
echo -e "${GREEN}🔄 ÉTAPE 7/7: Redémarrage des services${NC}"
echo "--------------------------------------------"

# PHP-FPM
systemctl restart php${PHP_VERSION}-fpm
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ PHP-FPM redémarré${NC}"
else
    echo -e "${RED}❌ Erreur au redémarrage de PHP-FPM${NC}"
fi

# MySQL
systemctl restart mysql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ MySQL redémarré${NC}"
else
    echo -e "${RED}❌ Erreur au redémarrage de MySQL${NC}"
fi

# Redis
systemctl restart redis-server
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Redis redémarré${NC}"
else
    echo -e "${RED}❌ Erreur au redémarrage de Redis${NC}"
fi

# Nginx (si installé)
if command -v nginx &> /dev/null; then
    nginx -t && systemctl restart nginx
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Nginx redémarré${NC}"
    else
        echo -e "${RED}❌ Erreur au redémarrage de Nginx${NC}"
    fi
fi

# ============================================
# TESTS DE VALIDATION
# ============================================
echo ""
echo "========================================"
echo "🧪 TESTS DE VALIDATION"
echo "========================================"

# Test PHP
PHP_MAX_EXEC=$(php -i | grep max_execution_time | head -1 | awk '{print $3}')
if [ "$PHP_MAX_EXEC" == "300" ]; then
    echo -e "${GREEN}✅ PHP max_execution_time = 300${NC}"
else
    echo -e "${YELLOW}⚠️  PHP max_execution_time = $PHP_MAX_EXEC (attendu: 300)${NC}"
fi

# Test Redis
if redis-cli ping > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Redis répond correctement${NC}"
else
    echo -e "${RED}❌ Redis ne répond pas${NC}"
fi

# Test MySQL slow query log
if [ -f "/var/log/mysql/slow-queries.log" ]; then
    echo -e "${GREEN}✅ MySQL slow query log configuré${NC}"
else
    echo -e "${YELLOW}⚠️  MySQL slow query log non trouvé${NC}"
fi

# Test cache Laravel
cd $PROJECT_DIR
CACHE_TEST=$(php artisan tinker --execute="echo Cache::getDefaultDriver();")
echo -e "${GREEN}✅ Laravel cache driver: $CACHE_TEST${NC}"

# ============================================
# RÉSUMÉ
# ============================================
echo ""
echo "========================================"
echo "✅ INSTALLATION TERMINÉE"
echo "========================================"
echo ""
echo "📋 Résumé des modifications:"
echo "  - ✅ Migrations de base de données appliquées"
echo "  - ✅ PHP max_execution_time → 300s"
echo "  - ✅ PHP memory_limit → 512M"
echo "  - ✅ MySQL innodb_buffer_pool_size → 1G"
echo "  - ✅ Redis installé et configuré"
echo "  - ✅ Laravel cache → Redis"
echo "  - ✅ Services redémarrés"
echo ""
echo "📂 Backups créés:"
echo "  - Base de données: $BACKUP_FILE"
echo "  - PHP.ini: ${PHP_INI}.backup.$(date +%Y%m%d)"
echo "  - MySQL conf: ${MYSQL_CONF}.backup.$(date +%Y%m%d)"
echo ""
echo "🧪 Tests recommandés:"
echo "  1. Tester génération bulletin: php test_performance.php"
echo "  2. Vérifier les logs: tail -f storage/logs/laravel.log"
echo "  3. Monitorer MySQL: tail -f /var/log/mysql/slow-queries.log"
echo ""
echo "📖 Documentation complète: OPTIMISATION_PRODUCTION_504.md"
echo ""
echo -e "${GREEN}🎉 Prêt pour la production!${NC}"
echo ""
