#!/bin/bash

# Script d'import de la base de données de production en local
# Usage: ./scripts/import_production_db.sh chemin/vers/fichier.sql

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}===========================================${NC}"
echo -e "${YELLOW}IMPORT BASE DE DONNÉES PRODUCTION${NC}"
echo -e "${YELLOW}===========================================${NC}\n"

# Vérifier le fichier SQL
if [ -z "$1" ]; then
    echo -e "${RED}❌ Usage: $0 <fichier.sql>${NC}"
    echo "Exemple: $0 ~/Downloads/production_export.sql"
    exit 1
fi

SQL_FILE="$1"

if [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}❌ Erreur: Fichier $SQL_FILE introuvable${NC}"
    exit 1
fi

FILE_SIZE=$(du -h "$SQL_FILE" | cut -f1)
echo -e "${GREEN}📄 Fichier trouvé: $SQL_FILE ($FILE_SIZE)${NC}\n"

# Configuration
DB_NAME="c0admin"
DB_USER="root"
DB_HOST="127.0.0.1"

echo -e "${YELLOW}📊 Configuration:${NC}"
echo "  Base de données: $DB_NAME"
echo "  Utilisateur: $DB_USER"
echo "  Hôte: $DB_HOST"
echo ""

# Demander confirmation
read -p "⚠️  Cette opération va ÉCRASER la base de données locale $DB_NAME. Continuer? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}❌ Import annulé${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🔄 Import en cours...${NC}"
echo -e "${YELLOW}⏱️  Cela peut prendre plusieurs minutes (5-15 min pour une grosse BD)${NC}"
echo ""

# Détecter si on utilise XAMPP sur macOS
MYSQL_CMD="mysql"
if [ -f "/Applications/XAMPP/bin/mysql" ]; then
    MYSQL_CMD="/Applications/XAMPP/bin/mysql"
    echo -e "${GREEN}✅ XAMPP détecté, utilisation de $MYSQL_CMD${NC}"
fi

# Import avec options optimisées pour les grosses bases
$MYSQL_CMD -u $DB_USER -p \
    --host=$DB_HOST \
    --max_allowed_packet=512M \
    --net_buffer_length=16M \
    --default-character-set=utf8mb4 \
    $DB_NAME < "$SQL_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}===========================================${NC}"
    echo -e "${GREEN}✅ IMPORT RÉUSSI!${NC}"
    echo -e "${GREEN}===========================================${NC}"
    echo ""
    echo "💡 Prochaines étapes:"
    echo "  1. Vérifiez la connexion: php artisan tinker"
    echo "  2. Exécutez le diagnostic: php artisan tinker < scripts/diagnose_sequences_production.php"
    echo "  3. Exécutez la correction: php artisan tinker < scripts/fix_sequences_production.php"
    echo ""
else
    echo ""
    echo -e "${RED}===========================================${NC}"
    echo -e "${RED}❌ ERREUR LORS DE L'IMPORT${NC}"
    echo -e "${RED}===========================================${NC}"
    echo ""
    echo "🔍 Solutions possibles:"
    echo "  1. Vérifiez que MySQL est démarré (XAMPP)"
    echo "  2. Vérifiez le mot de passe MySQL"
    echo "  3. Augmentez max_allowed_packet dans my.cnf"
    echo "  4. Essayez d'importer par morceaux"
    echo ""
    exit 1
fi
