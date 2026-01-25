#!/bin/bash

###############################################################################
# Script d'import du backup de production dans MySQL local (XAMPP)
# Collège Polyvalent Bilingue de Douala
###############################################################################

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Import Base de Données - Production → Local          ║${NC}"
echo -e "${BLUE}║  Collège Polyvalent Bilingue de Douala                ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Configuration
MYSQL_BIN="/Applications/XAMPP/xamppfiles/bin/mysql"
MYSQLADMIN_BIN="/Applications/XAMPP/xamppfiles/bin/mysqladmin"
BACKUP_FILE="backup_production_20260125_121618.sql.gz"
DB_NAME="c0admin"
DB_USER="root"
DB_PASS=""

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Vérifier que le fichier backup existe
if [ ! -f "$BACKUP_FILE" ]; then
    log_error "Fichier backup introuvable: $BACKUP_FILE"
    log_info "Assurez-vous d'avoir téléchargé le fichier avec:"
    echo "  scp adminChrisDev@31.207.34.69:/var/www/.../backup_production_20260125_121618.sql.gz ./"
    exit 1
fi

# Afficher la taille du fichier
FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
log_info "Fichier backup trouvé: $BACKUP_FILE ($FILE_SIZE)"
echo ""

# Vérifier que MySQL est en cours d'exécution
log_info "Vérification de MySQL..."
if ! pgrep -x "mysqld" > /dev/null; then
    log_error "MySQL n'est pas en cours d'exécution!"
    log_info "Démarrez XAMPP et relancez ce script."
    exit 1
fi
log_success "MySQL est en cours d'exécution"
echo ""

# Demander confirmation
log_warning "⚠️  ATTENTION: Cette opération va:"
echo "  1. Supprimer la base de données locale '$DB_NAME' si elle existe"
echo "  2. Créer une nouvelle base de données '$DB_NAME'"
echo "  3. Importer toutes les données de production (1.4 GB)"
echo "  4. Cela peut prendre 10-30 minutes"
echo ""
read -p "Voulez-vous continuer ? (o/N): " confirm

if [[ ! "$confirm" =~ ^[Oo]$ ]]; then
    log_info "Import annulé."
    exit 0
fi

echo ""
log_info "Démarrage de l'import..."
echo ""

# Étape 1 : Supprimer l'ancienne base si elle existe
log_info "Étape 1/4 : Suppression de l'ancienne base (si existe)..."
if [ -z "$DB_PASS" ]; then
    $MYSQL_BIN -u $DB_USER -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null
else
    $MYSQL_BIN -u $DB_USER -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null
fi
log_success "Ancienne base supprimée (si existait)"

# Étape 2 : Créer la nouvelle base
log_info "Étape 2/4 : Création de la nouvelle base de données..."
if [ -z "$DB_PASS" ]; then
    $MYSQL_BIN -u $DB_USER -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
else
    $MYSQL_BIN -u $DB_USER -p"$DB_PASS" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

if [ $? -eq 0 ]; then
    log_success "Base de données '$DB_NAME' créée"
else
    log_error "Erreur lors de la création de la base"
    exit 1
fi

# Étape 3 : Décompresser et importer
log_info "Étape 3/4 : Import du backup (cela va prendre du temps)..."
log_warning "Veuillez patienter... (10-30 minutes estimées)"
echo ""

START_TIME=$(date +%s)

if [ -z "$DB_PASS" ]; then
    gunzip -c "$BACKUP_FILE" | $MYSQL_BIN -u $DB_USER $DB_NAME
else
    gunzip -c "$BACKUP_FILE" | $MYSQL_BIN -u $DB_USER -p"$DB_PASS" $DB_NAME
fi

if [ $? -eq 0 ]; then
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    MINUTES=$((DURATION / 60))
    SECONDS=$((DURATION % 60))

    echo ""
    log_success "Import terminé avec succès!"
    log_info "Durée: ${MINUTES}m ${SECONDS}s"
else
    log_error "Erreur lors de l'import"
    exit 1
fi

# Étape 4 : Vérification
log_info "Étape 4/4 : Vérification des données importées..."
echo ""

if [ -z "$DB_PASS" ]; then
    MYSQL_CMD="$MYSQL_BIN -u $DB_USER $DB_NAME"
else
    MYSQL_CMD="$MYSQL_BIN -u $DB_USER -p$DB_PASS $DB_NAME"
fi

# Compter les tables
TABLE_COUNT=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" 2>/dev/null)
log_success "Nombre de tables: $TABLE_COUNT"

# Compter les étudiants
STUDENT_COUNT=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM students;" 2>/dev/null)
log_success "Nombre d'étudiants: $STUDENT_COUNT"

# Compter les notes
GRADE_COUNT=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM grades;" 2>/dev/null)
log_success "Nombre de notes: $GRADE_COUNT"

# Compter les paiements
PAYMENT_COUNT=$($MYSQL_CMD -N -e "SELECT COUNT(*) FROM payments;" 2>/dev/null)
log_success "Nombre de paiements: $PAYMENT_COUNT"

# Vérifier l'année scolaire courante
SCHOOL_YEAR=$($MYSQL_CMD -N -e "SELECT name FROM school_years WHERE is_current = 1 LIMIT 1;" 2>/dev/null)
log_success "Année scolaire courante: $SCHOOL_YEAR"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  Import terminé avec succès!                           ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

log_info "Vous pouvez maintenant:"
echo "  1. Lancer le backend Laravel: cd back && php artisan serve"
echo "  2. Lancer le frontend React: cd front && npm start"
echo "  3. Accéder à l'application: http://localhost:3006"
echo ""

log_warning "⚠️  Pensez à mettre à jour back/.env avec:"
echo "  DB_PORT=3306"
echo "  DB_USERNAME=root"
echo "  DB_PASSWORD="
echo ""
