#!/bin/bash

# ============================================
# VIEWER DE LOGS LARAVEL - SIMPLE ET RAPIDE
# ============================================
# Ce script facilite la lecture des logs Laravel
#
# Usage: bash view_logs.sh [option]
#
# Options:
#   last      - Voir les 100 dernières lignes
#   errors    - Voir seulement les erreurs
#   today     - Voir les logs d'aujourd'hui
#   search    - Rechercher un mot-clé
#   live      - Suivre les logs en temps réel
#   size      - Afficher la taille du fichier
# ============================================

LOG_FILE="storage/logs/laravel.log"
OPTION="${1:-last}"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Vérifier que le fichier existe
if [ ! -f "$LOG_FILE" ]; then
    echo -e "${RED}❌ Fichier de log non trouvé: $LOG_FILE${NC}"
    exit 1
fi

case "$OPTION" in
    "last")
        echo -e "${BLUE}📋 100 dernières lignes de log:${NC}"
        echo "-----------------------------------"
        tail -100 "$LOG_FILE"
        ;;

    "errors")
        echo -e "${RED}🔴 Erreurs uniquement:${NC}"
        echo "-----------------------------------"
        tail -500 "$LOG_FILE" | grep -i "error\|exception\|fatal" --color=always
        ;;

    "today")
        TODAY=$(date +%Y-%m-%d)
        echo -e "${GREEN}📅 Logs du $TODAY:${NC}"
        echo "-----------------------------------"
        grep "$TODAY" "$LOG_FILE" | tail -100
        ;;

    "search")
        if [ -z "$2" ]; then
            echo -e "${YELLOW}⚠️  Usage: bash view_logs.sh search <mot-clé>${NC}"
            exit 1
        fi
        echo -e "${BLUE}🔍 Recherche de: $2${NC}"
        echo "-----------------------------------"
        grep -i "$2" "$LOG_FILE" | tail -50 --color=always
        ;;

    "live")
        echo -e "${GREEN}📡 Logs en temps réel (Ctrl+C pour arrêter):${NC}"
        echo "-----------------------------------"
        tail -f "$LOG_FILE"
        ;;

    "size")
        SIZE=$(du -h "$LOG_FILE" | cut -f1)
        LINES=$(wc -l < "$LOG_FILE")
        echo -e "${BLUE}📊 Informations du fichier de log:${NC}"
        echo "-----------------------------------"
        echo "  Taille: $SIZE"
        echo "  Lignes: $(printf '%s\n' "$LINES" | sed ':a;s/\B[0-9]\{3\}\>/,&/;ta')"
        echo ""

        # Vérifier si le fichier est trop gros
        SIZE_MB=$(du -m "$LOG_FILE" | cut -f1)
        if [ "$SIZE_MB" -gt 100 ]; then
            echo -e "${RED}⚠️  ATTENTION: Le fichier est très volumineux (${SIZE_MB}M)${NC}"
            echo -e "${YELLOW}💡 Recommandation: Exécuter bash clean_logs.sh${NC}"
        else
            echo -e "${GREEN}✅ Taille correcte${NC}"
        fi
        ;;

    "clean")
        echo -e "${YELLOW}🧹 Nettoyage des logs...${NC}"
        bash clean_logs.sh
        ;;

    "help"|*)
        echo -e "${BLUE}📖 VIEWER DE LOGS LARAVEL${NC}"
        echo ""
        echo "Usage: bash view_logs.sh [option]"
        echo ""
        echo "Options disponibles:"
        echo "  ${GREEN}last${NC}      - Voir les 100 dernières lignes"
        echo "  ${RED}errors${NC}    - Voir seulement les erreurs"
        echo "  ${YELLOW}today${NC}     - Voir les logs d'aujourd'hui"
        echo "  ${BLUE}search${NC}    - Rechercher un mot-clé"
        echo "  ${GREEN}live${NC}      - Suivre les logs en temps réel"
        echo "  ${BLUE}size${NC}      - Afficher la taille du fichier"
        echo "  ${YELLOW}clean${NC}     - Nettoyer et archiver les logs"
        echo ""
        echo "Exemples:"
        echo "  bash view_logs.sh last"
        echo "  bash view_logs.sh errors"
        echo "  bash view_logs.sh search bulletin"
        echo "  bash view_logs.sh live"
        ;;
esac
