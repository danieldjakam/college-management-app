#!/bin/bash

# Script d'exportation de la base de données c0admin par parties
# Pour éviter les timeouts et optimiser la vitesse

DB_NAME="c0admin"
DB_USER="root"
DB_PASS=""
BACKUP_DIR="./database_backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Créer le répertoire de backup s'il n'existe pas
mkdir -p "$BACKUP_DIR"

echo "🚀 Début de l'exportation de la base de données $DB_NAME..."

# 1. Export de la structure uniquement (très rapide)
echo "📋 Exportation de la structure..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} --no-data "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_structure_${TIMESTAMP}.sql.gz"

# 2. Export des tables importantes avec données
echo "📊 Exportation des tables critiques..."

# Tables de configuration et paramètres (petit volume)
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  school_settings \
  school_years \
  academic_periods \
  sequences \
  trimesters \
  sections \
  levels \
  class_series \
  subjects \
  subject_groups \
  geolocation_zones \
  card_layout_settings \
  bus_settings \
| gzip > "$BACKUP_DIR/${DB_NAME}_config_${TIMESTAMP}.sql.gz"

# Tables académiques (volume moyen)
echo "🎓 Exportation des tables académiques..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  students \
  teachers \
  school_classes \
  class_series_subjects \
  teacher_assignments \
  main_teachers \
| gzip > "$BACKUP_DIR/${DB_NAME}_academics_${TIMESTAMP}.sql.gz"

# Tables de notes et bulletins (gros volume possible)
echo "📝 Exportation des notes et bulletins..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  evaluations \
  grades \
  bulletin_generations \
| gzip > "$BACKUP_DIR/${DB_NAME}_grades_${TIMESTAMP}.sql.gz"

# Tables de paiements (gros volume possible)
echo "💰 Exportation des paiements..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  payments \
  payment_details \
  payment_tranches \
  class_payment_amounts \
  class_scholarships \
| gzip > "$BACKUP_DIR/${DB_NAME}_payments_${TIMESTAMP}.sql.gz"

# Tables d'assiduité (gros volume possible)
echo "📅 Exportation des présences..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  attendances \
  student_attendances \
  teacher_attendances \
  staff_attendances \
  daily_attendance_states \
| gzip > "$BACKUP_DIR/${DB_NAME}_attendance_${TIMESTAMP}.sql.gz"

# Tables utilisateurs et authentification
echo "👥 Exportation des utilisateurs..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  users \
  parent_guardians \
  parent_student_relationships \
  departments \
| gzip > "$BACKUP_DIR/${DB_NAME}_users_${TIMESTAMP}.sql.gz"

# Tables restantes (moins critiques)
echo "📦 Exportation des autres tables..."
mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" \
  --ignore-table=$DB_NAME.telescope_entries \
  --ignore-table=$DB_NAME.telescope_entries_tags \
  --ignore-table=$DB_NAME.telescope_monitoring \
  --ignore-table=$DB_NAME.jobs \
  --ignore-table=$DB_NAME.job_batches \
  --ignore-table=$DB_NAME.failed_jobs \
  --ignore-table=$DB_NAME.cache \
  --ignore-table=$DB_NAME.cache_locks \
  --ignore-table=$DB_NAME.sessions \
| gzip > "$BACKUP_DIR/${DB_NAME}_others_${TIMESTAMP}.sql.gz"

# Créer un script de restauration
cat > "$BACKUP_DIR/restore_${TIMESTAMP}.sh" << 'EOF'
#!/bin/bash
# Script de restauration automatique
# Usage: ./restore_YYYYMMDD_HHMMSS.sh

DB_NAME="c0admin"
DB_USER="root"
read -sp "Mot de passe MySQL: " DB_PASS
echo

echo "🔄 Restauration de la base de données..."

# Créer la base si elle n'existe pas
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restaurer dans l'ordre
for file in *_structure_*.sql.gz *_config_*.sql.gz *_academics_*.sql.gz *_users_*.sql.gz *_grades_*.sql.gz *_payments_*.sql.gz *_attendance_*.sql.gz *_others_*.sql.gz; do
  if [ -f "$file" ]; then
    echo "📥 Restauration de $file..."
    gunzip -c "$file" | mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME"
  fi
done

echo "✅ Restauration terminée!"
EOF

chmod +x "$BACKUP_DIR/restore_${TIMESTAMP}.sh"

# Statistiques
echo ""
echo "✅ Exportation terminée!"
echo "📁 Fichiers sauvegardés dans: $BACKUP_DIR/"
echo ""
echo "📊 Taille des fichiers:"
ls -lh "$BACKUP_DIR"/*_${TIMESTAMP}*
echo ""
echo "💾 Taille totale:"
du -sh "$BACKUP_DIR"

echo ""
echo "🔄 Pour restaurer cette sauvegarde:"
echo "   cd $BACKUP_DIR && ./restore_${TIMESTAMP}.sh"
