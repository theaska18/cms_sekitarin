#!/bin/sh
set -eu

: "${CMS_DB_HOST:=cms-db}"
: "${CMS_DB_NAME:=cmsdb}"
: "${CMS_DB_USER:=cmsuser}"
: "${CMS_DB_PASSWORD:?CMS_DB_PASSWORD is required}"
: "${BACKUP_ROOT:=/backup}"
: "${LATEST_DIR:=/var/www/html/data}"
: "${BACKUP_RETENTION:=30}"

export MYSQL_PWD="$CMS_DB_PASSWORD"
umask 077
mkdir -p "$BACKUP_ROOT" "$LATEST_DIR"

stamp="$(date -u +%Y%m%d-%H%M%S)"
target="$BACKUP_ROOT/cmsdb-$stamp.sql.gz"
sql_tmp="$BACKUP_ROOT/.cmsdb-$stamp.sql.tmp"
archive_tmp="$BACKUP_ROOT/.cmsdb-$stamp.sql.gz.tmp"
latest_tmp="$LATEST_DIR/.cmsdb-latest.sql.gz.tmp"

cleanup() {
    rm -f "$sql_tmp" "$archive_tmp" "$latest_tmp"
}
trap cleanup EXIT INT TERM

echo "Starting backup: $target"
mysqldump \
    --host="$CMS_DB_HOST" \
    --user="$CMS_DB_USER" \
    --single-transaction \
    --quick \
    --skip-ssl \
    --no-tablespaces \
    --routines \
    --triggers \
    --events \
    --default-character-set=utf8mb4 \
    "$CMS_DB_NAME" > "$sql_tmp"

gzip -9 -c "$sql_tmp" > "$archive_tmp"
gzip -t "$archive_tmp"
mv "$archive_tmp" "$target"
rm -f "$sql_tmp"
trap - EXIT INT TERM

cp "$target" "$latest_tmp"
mv "$latest_tmp" "$LATEST_DIR/cmsdb-latest.sql.gz"
find "$LATEST_DIR" -maxdepth 1 -type f -name '*.sql.gz' ! -name 'cmsdb-latest.sql.gz' -delete

# Keep only the newest $BACKUP_RETENTION archives.
index=0
for old in $(ls -1t "$BACKUP_ROOT"/cmsdb-*.sql.gz 2>/dev/null || true); do
    index=$((index + 1))
    if [ "$index" -gt "$BACKUP_RETENTION" ]; then
        rm -f -- "$old"
    fi
done

echo "Backup complete: $target"
