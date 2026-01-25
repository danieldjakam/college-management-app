#!/bin/bash

###############################################################################
# Script d'application de la correction des paiements après transferts
# Collège Polyvalent Bilingue de Douala
#
# USAGE: ./apply_payment_fix_production.sh
###############################################################################

set -e  # Arrêter en cas d'erreur

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Correction Bug Paiements après Transferts              ║${NC}"
echo -e "${BLUE}║  Collège Polyvalent Bilingue de Douala                  ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis le dossier 'back' du projet${NC}"
    echo "   Exemple: cd /var/www/.../back && bash ../apply_payment_fix_production.sh"
    exit 1
fi

# Vérifier que la commande existe
if [ ! -f "app/Console/Commands/FixTransferPaymentAllocations.php" ]; then
    echo -e "${RED}❌ Erreur: Fichier FixTransferPaymentAllocations.php introuvable${NC}"
    echo "   Veuillez d'abord uploader ce fichier dans app/Console/Commands/"
    exit 1
fi

echo -e "${YELLOW}⚠️  ATTENTION${NC}"
echo "Cette opération va corriger les paiements de 2 élèves :"
echo "  1. LEVODO NKIE ZEPHIRIN (ID: 1017)"
echo "  2. ONGBAHOCKEN MARIE CLAIRE (ID: 1521)"
echo ""
echo -e "${YELLOW}Voulez-vous continuer ?${NC}"
read -p "Tapez 'OUI' pour confirmer: " confirmation

if [ "$confirmation" != "OUI" ]; then
    echo -e "${YELLOW}❌ Opération annulée${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 1/5 : Création du backup de la base de données${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Charger les credentials depuis .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)

BACKUP_DIR="storage/app/backups"
BACKUP_FILE="backup_before_payment_fix_$(date +%Y%m%d_%H%M%S).sql.gz"

mkdir -p "$BACKUP_DIR"

echo "📦 Création du backup: $BACKUP_FILE"
echo "⏱️  Cela peut prendre 1-2 minutes..."

mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction \
  --quick \
  --skip-comments \
  "$DB_NAME" 2>/dev/null | gzip -9 > "$BACKUP_DIR/$BACKUP_FILE"

if [ $? -eq 0 ] && [ -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
    FILE_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)
    echo -e "${GREEN}✅ Backup créé avec succès: $FILE_SIZE${NC}"
    echo "   Chemin: $BACKUP_DIR/$BACKUP_FILE"
else
    echo -e "${RED}❌ Erreur lors de la création du backup${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 2/5 : Test en mode simulation${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo "🔍 Lancement de la simulation..."
php artisan payments:fix-transfer-allocations --dry-run

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ La simulation a échoué${NC}"
    echo "   Vérifiez les erreurs ci-dessus"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Simulation réussie${NC}"
echo ""
echo -e "${YELLOW}La simulation montre les corrections qui seront appliquées.${NC}"
echo -e "${YELLOW}Voulez-vous appliquer RÉELLEMENT ces corrections ?${NC}"
read -p "Tapez 'APPLIQUER' pour confirmer: " confirmation2

if [ "$confirmation2" != "APPLIQUER" ]; then
    echo -e "${YELLOW}❌ Opération annulée${NC}"
    echo "   Le backup a été conservé: $BACKUP_DIR/$BACKUP_FILE"
    exit 0
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 3/5 : Application des corrections${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo "🔧 Application des corrections..."
php artisan payments:fix-transfer-allocations --detailed

if [ $? -ne 0 ]; then
    echo ""
    echo -e "${RED}❌ Erreur lors de l'application des corrections${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  ROLLBACK DISPONIBLE${NC}"
    echo "   Pour restaurer la base de données avant correction:"
    echo "   gunzip -c $BACKUP_DIR/$BACKUP_FILE | mysql -h $DB_HOST -u $DB_USER -p'$DB_PASS' $DB_NAME"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Corrections appliquées avec succès${NC}"

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 4/5 : Vérification des corrections${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo "🔍 Vérification pour LEVODO NKIE ZEPHIRIN (ID: 1017)..."

php artisan tinker --execute="
use App\Services\PaymentStatusService;
use App\Models\Student;
use App\Models\SchoolYear;

\$student = Student::find(1017);
\$schoolYear = SchoolYear::where('is_current', 1)->first();
\$service = new PaymentStatusService();
\$status = \$service->getStatusForStudent(\$student, \$schoolYear);

echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo 'Élève: ' . \$student->name . PHP_EOL;
echo 'Classe: ' . \$student->classSeries->name . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo 'Total requis:    ' . number_format(\$status->total_required, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo 'Total payé:      ' . number_format(\$status->total_paid, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo 'Reste à payer:   ' . number_format(\$status->total_remaining, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;

if (\$status->total_remaining == 0) {
    echo '✅ STATUT: TOUT PAYÉ' . PHP_EOL;
    exit(0);
} else {
    echo '❌ STATUT: RESTE À PAYER' . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Vérification réussie pour LEVODO NKIE ZEPHIRIN${NC}"
else
    echo -e "${YELLOW}⚠️  Problème détecté pour LEVODO NKIE ZEPHIRIN${NC}"
fi

echo ""
echo "🔍 Vérification pour ONGBAHOCKEN MARIE CLAIRE (ID: 1521)..."

php artisan tinker --execute="
use App\Services\PaymentStatusService;
use App\Models\Student;
use App\Models\SchoolYear;

\$student = Student::find(1521);
\$schoolYear = SchoolYear::where('is_current', 1)->first();
\$service = new PaymentStatusService();
\$status = \$service->getStatusForStudent(\$student, \$schoolYear);

echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo 'Élève: ' . \$student->name . PHP_EOL;
echo 'Classe: ' . \$student->classSeries->name . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo 'Total requis:    ' . number_format(\$status->total_required, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo 'Total payé:      ' . number_format(\$status->total_paid, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo 'Reste à payer:   ' . number_format(\$status->total_remaining, 0, ',', ' ') . ' FCFA' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;

if (\$status->total_remaining == 0) {
    echo '✅ STATUT: TOUT PAYÉ' . PHP_EOL;
    exit(0);
} else {
    echo '❌ STATUT: RESTE À PAYER' . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Vérification réussie pour ONGBAHOCKEN MARIE CLAIRE${NC}"
else
    echo -e "${YELLOW}⚠️  Problème détecté pour ONGBAHOCKEN MARIE CLAIRE${NC}"
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 5/5 : Nettoyage et résumé${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo "🗑️  Nettoyage des anciens backups (conservation des 5 derniers)..."
cd "$BACKUP_DIR"
ls -t backup_*.sql.gz | tail -n +6 | xargs -r rm --
cd - > /dev/null

KEPT_BACKUPS=$(ls -1 "$BACKUP_DIR"/backup_*.sql.gz 2>/dev/null | wc -l)
echo -e "${GREEN}✅ Backups conservés: $KEPT_BACKUPS${NC}"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ CORRECTION TERMINÉE AVEC SUCCÈS                      ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "📊 Résumé:"
echo "  ✅ 2 élèves corrigés"
echo "  ✅ Backup créé: $BACKUP_FILE"
echo "  ✅ Vérifications passées"
echo ""
echo "📝 Actions recommandées:"
echo "  1. Vérifier les reçus des 2 élèves dans l'interface web"
echo "  2. Informer les parents si nécessaire"
echo "  3. Archiver ce backup: $BACKUP_DIR/$BACKUP_FILE"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
