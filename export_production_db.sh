#!/bin/bash

###############################################################################
# Script d'export de la base de données de production
# Collège Polyvalent Bilingue de Douala
###############################################################################

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Export Base de Données - Production                  ║${NC}"
echo -e "${BLUE}║  Collège Polyvalent Bilingue de Douala                ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="backup_production_${TIMESTAMP}.sql.gz"
BACKUP_DIR="./backups"
LOG_FILE="${BACKUP_DIR}/export_${TIMESTAMP}.log"

# Créer le répertoire de backup s'il n'existe pas
mkdir -p "${BACKUP_DIR}"

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "${LOG_FILE}"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1" | tee -a "${LOG_FILE}"
}

log_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1" | tee -a "${LOG_FILE}"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1" | tee -a "${LOG_FILE}"
}

# Vérifier si on est dans le bon répertoire
if [ ! -d "back" ]; then
    log_error "Erreur: Le répertoire 'back' n'existe pas. Exécutez ce script depuis la racine du projet."
    exit 1
fi

# Charger les variables d'environnement depuis .env
if [ -f "back/.env" ]; then
    log_info "Chargement de la configuration depuis back/.env..."
    export $(grep -v '^#' back/.env | grep -E '^DB_' | xargs)
else
    log_error "Fichier back/.env introuvable!"
    exit 1
fi

# Afficher la configuration (masquer le mot de passe)
echo ""
log_info "Configuration détectée:"
echo "  - Hôte: ${DB_HOST:-localhost}"
echo "  - Port: ${DB_PORT:-3306}"
echo "  - Base: ${DB_DATABASE:-c0admin}"
echo "  - User: ${DB_USERNAME:-root}"
echo "  - Pass: ${DB_PASSWORD:+***masqué***}"
echo ""

# Tables à ignorer (optionnel - pour réduire la taille)
IGNORE_TABLES=(
    "telescope_entries"
    "telescope_entries_tags"
    "telescope_monitoring"
    "cache"
    "cache_locks"
    "sessions"
    "jobs"
    "job_batches"
)

# Construire les options --ignore-table
IGNORE_OPTS=""
for table in "${IGNORE_TABLES[@]}"; do
    IGNORE_OPTS="${IGNORE_OPTS} --ignore-table=${DB_DATABASE}.${table}"
done

# Demander confirmation
echo -e "${YELLOW}Voulez-vous exclure les tables temporaires (cache, sessions, jobs) ?${NC}"
echo "Cela réduira la taille du backup mais ne contiendra pas les données de debug."
read -p "Exclure les tables temporaires ? (o/N): " exclude_temp
echo ""

if [[ "$exclude_temp" =~ ^[Oo]$ ]]; then
    USE_IGNORE_OPTS=$IGNORE_OPTS
    log_info "Export SANS les tables temporaires (optimisé)"
else
    USE_IGNORE_OPTS=""
    log_info "Export COMPLET (toutes les tables)"
fi

# Commande mysqldump
log_info "Démarrage de l'export..."
echo ""

MYSQLDUMP_CMD="mysqldump \
    --host=${DB_HOST:-localhost} \
    --port=${DB_PORT:-3306} \
    --user=${DB_USERNAME:-root} \
    --password=${DB_PASSWORD} \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --skip-comments \
    --routines \
    --triggers \
    --events \
    ${USE_IGNORE_OPTS} \
    ${DB_DATABASE}"

# Exécuter l'export avec compression
if $MYSQLDUMP_CMD 2>> "${LOG_FILE}" | gzip -9 > "${BACKUP_DIR}/${BACKUP_NAME}"; then

    # Vérifier que le fichier existe et n'est pas vide
    if [ -f "${BACKUP_DIR}/${BACKUP_NAME}" ] && [ -s "${BACKUP_DIR}/${BACKUP_NAME}" ]; then
        FILE_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}" | cut -f1)
        FILE_SIZE_BYTES=$(stat -f%z "${BACKUP_DIR}/${BACKUP_NAME}" 2>/dev/null || stat -c%s "${BACKUP_DIR}/${BACKUP_NAME}" 2>/dev/null)

        echo ""
        log_success "Export réussi!"
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║  Détails du backup                                     ║${NC}"
        echo -e "${GREEN}╠════════════════════════════════════════════════════════╣${NC}"
        echo -e "${GREEN}║${NC}  Fichier: ${BACKUP_NAME}"
        echo -e "${GREEN}║${NC}  Taille:  ${FILE_SIZE}"
        echo -e "${GREEN}║${NC}  Chemin:  ${BACKUP_DIR}/${BACKUP_NAME}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
        echo ""

        # Vérifier l'intégrité du fichier .gz
        if gzip -t "${BACKUP_DIR}/${BACKUP_NAME}" 2>/dev/null; then
            log_success "Fichier compressé valide (intégrité OK)"
        else
            log_warning "Le fichier compressé pourrait être corrompu"
        fi

        # Afficher les premières lignes pour vérification
        log_info "Aperçu du contenu (premières lignes):"
        echo ""
        gunzip -c "${BACKUP_DIR}/${BACKUP_NAME}" | head -20
        echo ""

        # Instructions pour la restauration
        echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"
        echo -e "${BLUE}Pour restaurer ce backup en local:${NC}"
        echo ""
        echo -e "  ${YELLOW}1. Décompresser et importer:${NC}"
        echo "     gunzip -c ${BACKUP_DIR}/${BACKUP_NAME} | mysql -u root -p c0admin"
        echo ""
        echo -e "  ${YELLOW}2. Ou en deux étapes:${NC}"
        echo "     gunzip ${BACKUP_DIR}/${BACKUP_NAME}"
        echo "     mysql -u root -p c0admin < ${BACKUP_DIR}/${BACKUP_NAME%.gz}"
        echo ""
        echo -e "${BLUE}════════════════════════════════════════════════════════${NC}"

        # Proposer de copier le fichier ailleurs
        echo ""
        read -p "Voulez-vous copier le backup dans un autre emplacement ? (o/N): " copy_backup
        if [[ "$copy_backup" =~ ^[Oo]$ ]]; then
            read -p "Entrez le chemin de destination: " dest_path
            if [ -d "$dest_path" ]; then
                cp "${BACKUP_DIR}/${BACKUP_NAME}" "$dest_path/"
                log_success "Backup copié vers: $dest_path/${BACKUP_NAME}"
            else
                log_error "Le chemin de destination n'existe pas: $dest_path"
            fi
        fi

        exit 0
    else
        log_error "Le fichier de backup est vide ou n'a pas été créé"
        exit 1
    fi
else
    log_error "Erreur lors de l'export"
    log_error "Consultez le log: ${LOG_FILE}"
    exit 1
fi
