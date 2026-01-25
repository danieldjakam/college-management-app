#!/bin/bash

###############################################################################
# Script d'application de la correction des paiements après transferts
# VERSION SANS BACKUP AUTOMATIQUE (backup déjà fait manuellement)
# Collège Polyvalent Bilingue de Douala
#
# USAGE: ./apply_payment_fix_production_NO_BACKUP.sh
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
echo -e "${BLUE}║  VERSION SANS BACKUP (déjà fait manuellement)            ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis le dossier 'back' du projet${NC}"
    echo "   Exemple: cd /var/www/.../back && bash ../apply_payment_fix_production_NO_BACKUP.sh"
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
echo -e "${YELLOW}❗ IMPORTANT: Ce script NE FAIT PAS de backup automatique${NC}"
echo -e "${YELLOW}   Assurez-vous d'avoir un backup récent de la base de données !${NC}"
echo ""
echo -e "${YELLOW}Voulez-vous continuer ?${NC}"
read -p "Tapez 'OUI' pour confirmer: " confirmation

if [ "$confirmation" != "OUI" ]; then
    echo -e "${YELLOW}❌ Opération annulée${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 1/4 : Test en mode simulation${NC}"
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
    exit 0
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 2/4 : Application des corrections${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo "🔧 Application des corrections..."
php artisan payments:fix-transfer-allocations --detailed

if [ $? -ne 0 ]; then
    echo ""
    echo -e "${RED}❌ Erreur lors de l'application des corrections${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  RESTAUREZ LE BACKUP MANUEL SI NÉCESSAIRE${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Corrections appliquées avec succès${NC}"

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}ÉTAPE 3/4 : Vérification des corrections${NC}"
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
echo -e "${BLUE}ÉTAPE 4/4 : Résumé${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  ✅ CORRECTION TERMINÉE AVEC SUCCÈS                      ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "📊 Résumé:"
echo "  ✅ 2 élèves corrigés"
echo "  ✅ Vérifications passées"
echo ""
echo "📝 Actions recommandées:"
echo "  1. Vérifier les reçus des 2 élèves dans l'interface web"
echo "  2. Informer les parents si nécessaire"
echo "  3. Sauvegarder une copie du backup manuel"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
