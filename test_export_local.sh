#!/bin/bash

echo "🚀 Test d'export de la base de données c0admin en local..."
echo ""

# Configuration
MYSQLDUMP="/usr/local/mysql-8.0.30-macos12-x86_64/bin/mysqldump"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USER="root"
DB_NAME="c0admin"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="backup_local_${TIMESTAMP}.sql.gz"

# Vérifier que mysqldump existe
if [ ! -f "$MYSQLDUMP" ]; then
    echo "❌ mysqldump non trouvé à: $MYSQLDUMP"
    echo "Tentative avec XAMPP..."
    MYSQLDUMP="/Applications/XAMPP/xamppfiles/bin/mysqldump"
fi

echo "📝 Utilisation de: $MYSQLDUMP"
echo "🗄️  Base de données: $DB_NAME"
echo "📦 Fichier: $FILENAME"
echo ""

# Export avec compression
echo "⏳ Export en cours..."
$MYSQLDUMP -h $DB_HOST -P $DB_PORT -u $DB_USER \
    --single-transaction \
    --quick \
    --skip-comments \
    --column-statistics=0 \
    $DB_NAME 2>&1 | gzip -9 > "$FILENAME"

# Vérifier le résultat
if [ -f "$FILENAME" ]; then
    FILE_SIZE=$(ls -lh "$FILENAME" | awk '{print $5}')
    echo ""
    echo "✅ Export réussi!"
    echo "📦 Fichier: $FILENAME"
    echo "📊 Taille: $FILE_SIZE"
    echo ""

    # Tester l'intégrité du fichier
    echo "🔍 Test d'intégrité du fichier compressé..."
    if gzip -t "$FILENAME" 2>&1; then
        echo "✅ Fichier compressé valide!"
    else
        echo "❌ Erreur: Fichier compressé corrompu"
    fi

    echo ""
    echo "📋 Pour restaurer ce backup:"
    echo "   gunzip -c $FILENAME | mysql -h 127.0.0.1 -u root -p c0admin"
    echo ""
    echo "🔍 Pour voir le contenu sans restaurer:"
    echo "   gunzip -c $FILENAME | head -50"
else
    echo "❌ Erreur: Fichier non créé"
    exit 1
fi
