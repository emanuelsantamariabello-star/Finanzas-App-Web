#!/usr/bin/env bash
# Backup simple para MySQL/MariaDB
# Edita las variables abajo o exporta en el entorno antes de ejecutar.

set -euo pipefail

# Configuración (editar según tu hosting)
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="finanzas_app"
DB_USER="backup_user"
DB_PASS="cambia_esto"
BACKUP_DIR="/var/backups/finanzas_app"
RETENTION_DAYS=14

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
mkdir -p "$BACKUP_DIR"

FILENAME="${DB_NAME}_${TIMESTAMP}.sql"
mysqldump --single-transaction --quick --lock-tables=false -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
    -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/$FILENAME"

gzip -f "$BACKUP_DIR/$FILENAME"

echo "Backup creado: $BACKUP_DIR/$FILENAME.gz"

# Rotación simple: borrar backups más viejos que RETENTION_DAYS
find "$BACKUP_DIR" -type f -name "${DB_NAME}_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backups más antiguos a $RETENTION_DAYS días eliminados."

exit 0
