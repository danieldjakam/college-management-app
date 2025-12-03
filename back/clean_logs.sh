#!/bin/bash

# ============================================
# SCRIPT DE NETTOYAGE DES LOGS LARAVEL
# ============================================
# Ce script archive les vieux logs et crée un nouveau fichier propre
#
# Usage: bash clean_logs.sh
# ============================================

LOG_DIR="storage/logs"
ARCHIVE_DIR="storage/logs/archives"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🧹 Nettoyage des logs Laravel..."
echo ""

# Créer le dossier d'archives
mkdir -p "$ARCHIVE_DIR"

# Vérifier la taille du fichier actuel
LOG_SIZE=$(du -h "$LOG_DIR/laravel.log" | cut -f1)
echo "📊 Taille actuelle: $LOG_SIZE"

# Archiver le fichier existant
if [ -f "$LOG_DIR/laravel.log" ]; then
    echo "📦 Archivage de laravel.log..."

    # Compresser et archiver
    gzip -c "$LOG_DIR/laravel.log" > "$ARCHIVE_DIR/laravel_${TIMESTAMP}.log.gz"

    if [ $? -eq 0 ]; then
        echo "✅ Archive créée: $ARCHIVE_DIR/laravel_${TIMESTAMP}.log.gz"

        # Créer un nouveau fichier vide
        echo "" > "$LOG_DIR/laravel.log"
        echo "✅ Nouveau fichier laravel.log créé"

        # Afficher la nouvelle taille
        NEW_SIZE=$(du -h "$LOG_DIR/laravel.log" | cut -f1)
        echo "📊 Nouvelle taille: $NEW_SIZE"
    else
        echo "❌ Erreur lors de l'archivage"
        exit 1
    fi
else
    echo "⚠️  laravel.log n'existe pas"
fi

echo ""
echo "🗑️  Suppression des archives de plus de 30 jours..."
find "$ARCHIVE_DIR" -name "laravel_*.log.gz" -mtime +30 -delete
echo "✅ Archives anciennes supprimées"

echo ""
echo "📋 Archives disponibles:"
ls -lh "$ARCHIVE_DIR" | grep laravel

echo ""
echo "✅ Nettoyage terminé!"
echo ""
echo "💡 Pour voir les logs en temps réel:"
echo "   tail -f storage/logs/laravel.log"
