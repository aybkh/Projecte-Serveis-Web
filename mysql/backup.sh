#!/bin/bash

# Configuració
CONTAINER_NAME="mysql_backend"
DB_USER="admin"
DB_PASS="Passw0rd"
DB_NAME="serveis_db"
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"

# Crear directori de backup si no existeix
mkdir -p "$BACKUP_DIR"

# Executar mysqldump dins del contenidor
echo "Iniciant backup de la base de dades '$DB_NAME'..."

docker exec "$CONTAINER_NAME" mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$FILENAME"

# Comprovar el resultat
if [ $? -eq 0 ]; then
    echo "Backup completat correctament!"
    echo "Fitxer: $FILENAME"
    echo "Mida: $(du -h "$FILENAME" | cut -f1)"
else
    echo "Error: No s'ha pogut realitzar el backup."
    rm -f "$FILENAME"
    exit 1
fi
