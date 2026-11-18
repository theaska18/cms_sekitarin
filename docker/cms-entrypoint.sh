#!/bin/sh
set -e

# One-click updates replace files inside the bind-mounted web tree. Grant the
# PHP user write access to directories so it can create and rename files.
# web/updates is published by the host-side release generator and web/data is
# owned by the backup scheduler, so leave both trees alone.
find /var/www/html -type d \
    ! -path '/var/www/html/updates*' \
    ! -path '/var/www/html/data*' \
    -exec chown www-data:www-data {} + 2>/dev/null || true

if [ "${BACKUP_ENABLED:-1}" != "0" ]; then
    /usr/local/bin/cms-backup/backup-loop.sh &
fi

exec docker-php-entrypoint "$@"
