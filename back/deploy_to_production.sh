#!/bin/bash

# ============================================
# SCRIPT DE DÉPLOIEMENT RAPIDE EN PRODUCTION
# ============================================
# Ce script guide le déploiement étape par étape
# avec confirmation à chaque étape critique
#
# Usage: bash deploy_to_production.sh
# ============================================

set -e  # Arrêter si une erreur survient

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================"
echo "🚀 DÉPLOIEMENT EN PRODUCTION"
echo "========================================${NC}"
echo ""

# ============================================
# ÉTAPE 1: VÉRIFICATIONS LOCALES
# ============================================
echo -e "${GREEN}📋 ÉTAPE 1/9: Vérifications locales${NC}"
echo "--------------------------------------------"

# Vérifier qu'on est dans le bon dossier
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis le dossier back/${NC}"
    exit 1
fi

# Vérifier Git
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}❌ Erreur: Pas de dépôt Git trouvé${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Dossier correct${NC}"

# Vérifier l'état Git
if [[ -n $(git status -s) ]]; then
    echo -e "${YELLOW}⚠️  Vous avez des modifications non commitées:${NC}"
    git status -s
    echo ""
    read -p "Voulez-vous continuer quand même? (y/n) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo -e "${GREEN}✅ Git OK${NC}"
echo ""

# ============================================
# ÉTAPE 2: COMMIT ET PUSH
# ============================================
echo -e "${GREEN}📤 ÉTAPE 2/9: Commit et push${NC}"
echo "--------------------------------------------"

read -p "Avez-vous commité et pushé vos modifications? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo -e "${YELLOW}💡 Commandes pour commit et push:${NC}"
    echo "  git add ."
    echo "  git commit -m 'Optimisations database'"
    echo "  git push origin personnals"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ Code pushé${NC}"
echo ""

# ============================================
# ÉTAPE 3: INFORMATIONS SERVEUR
# ============================================
echo -e "${BLUE}🔧 ÉTAPE 3/9: Configuration serveur${NC}"
echo "--------------------------------------------"

read -p "Adresse du serveur (ex: user@server.com): " SERVER_SSH
read -p "Chemin du projet sur le serveur (ex: /var/www/app/back): " SERVER_PATH
read -p "Branche à déployer (ex: personnals ou main): " BRANCH

echo ""
echo -e "${YELLOW}📋 Résumé:${NC}"
echo "  Serveur: $SERVER_SSH"
echo "  Chemin: $SERVER_PATH"
echo "  Branche: $BRANCH"
echo ""

read -p "Ces informations sont-elles correctes? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# ============================================
# ÉTAPE 4: GÉNÉRATION DU SCRIPT DE DÉPLOIEMENT
# ============================================
echo ""
echo -e "${GREEN}📝 ÉTAPE 4/9: Génération du script...${NC}"
echo "--------------------------------------------"

DEPLOY_SCRIPT="/tmp/deploy_commands_$(date +%s).sh"

cat > "$DEPLOY_SCRIPT" << EOF
#!/bin/bash
set -e

echo "🚀 Déploiement en cours..."
cd $SERVER_PATH

# Backup base de données
echo "💾 Backup base de données..."
mysqldump -u root -p c0admin > /tmp/backup_\$(date +%Y%m%d_%H%M%S).sql
if [ \$? -eq 0 ]; then
    echo "✅ Backup créé"
else
    echo "❌ Erreur backup"
    exit 1
fi

# Mise à jour du code
echo "📥 Mise à jour du code..."
git stash
git fetch origin
git pull origin $BRANCH
echo "✅ Code mis à jour"

# Application des migrations
echo "🗄️  Application des migrations..."
php artisan migrate --force
echo "✅ Migrations appliquées"

# Vérification des index
echo "🔍 Vérification des index..."
php artisan tinker --execute='
\$indexes = DB::select("SHOW INDEX FROM grades WHERE Key_name LIKE \"idx_%\"");
echo "Index créés: " . count(\$indexes) . "\n";
foreach (\$indexes as \$idx) {
    echo "  - " . \$idx->Key_name . "\n";
}
'

# Nettoyage des caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches nettoyés"

# Optimisation des caches
echo "⚡ Optimisation des caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Caches optimisés"

# Redémarrage des services
echo "🔄 Redémarrage des services..."
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
echo "✅ Services redémarrés"

# Test de performance
echo "🧪 Test de performance..."
php artisan tinker --execute='
\$start = microtime(true);
\$student = App\Models\Student::where("is_active", true)->first();
if (\$student) {
    \$data = (new App\Services\BulletinService())->generateSequenceBulletinData(1, \$student->id);
    \$time = round((microtime(true) - \$start) * 1000, 2);
    echo "⏱️  Temps: {\$time} ms\n";
    echo (\$time < 5000) ? "✅ RAPIDE!\n" : "⚠️  Encore lent\n";
}
'

echo ""
echo "🎉 Déploiement terminé avec succès!"
echo ""
echo "📋 Prochaines étapes:"
echo "  1. Tester via l'interface web"
echo "  2. Surveiller les logs: tail -f storage/logs/laravel.log"
echo "  3. Demander à des utilisateurs de tester"
EOF

chmod +x "$DEPLOY_SCRIPT"

echo -e "${GREEN}✅ Script généré: $DEPLOY_SCRIPT${NC}"
echo ""

# ============================================
# ÉTAPE 5: CONNEXION ET EXÉCUTION
# ============================================
echo -e "${YELLOW}⚠️  ATTENTION${NC}"
echo "Le déploiement va maintenant commencer sur le serveur."
echo "Un backup automatique sera créé avant toute modification."
echo ""

read -p "Êtes-vous prêt à déployer en PRODUCTION? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Déploiement annulé"
    exit 1
fi

echo ""
echo -e "${BLUE}========================================"
echo "🚀 DÉPLOIEMENT EN COURS"
echo "========================================${NC}"
echo ""

# Copier le script sur le serveur
echo "📤 Copie du script sur le serveur..."
scp "$DEPLOY_SCRIPT" "$SERVER_SSH:/tmp/deploy.sh"

# Exécuter le script
echo ""
echo -e "${GREEN}🔄 Exécution du déploiement...${NC}"
echo ""

ssh -t "$SERVER_SSH" "bash /tmp/deploy.sh"

# Nettoyage
rm "$DEPLOY_SCRIPT"

echo ""
echo -e "${GREEN}========================================"
echo "✅ DÉPLOIEMENT TERMINÉ"
echo "========================================${NC}"
echo ""
echo -e "${BLUE}📋 Prochaines étapes:${NC}"
echo "  1. Tester l'application web"
echo "  2. Générer un bulletin de test"
echo "  3. Surveiller les logs pendant 15 minutes"
echo "  4. Demander à 2-3 utilisateurs de tester"
echo ""
echo -e "${YELLOW}💡 Commandes utiles:${NC}"
echo "  # Voir les logs en temps réel"
echo "  ssh $SERVER_SSH 'tail -f $SERVER_PATH/storage/logs/laravel.log'"
echo ""
echo "  # Rollback en cas de problème"
echo "  ssh $SERVER_SSH 'cd $SERVER_PATH && php artisan migrate:rollback --step=1'"
echo ""
echo -e "${GREEN}🎉 Félicitations!${NC}"
