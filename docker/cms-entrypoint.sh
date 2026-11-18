#!/bin/sh
set -e

if [ "${BACKUP_ENABLED:-1}" != "0" ]; then
    /usr/local/bin/cms-backup/backup-loop.sh &
fi

exec docker-php-entrypoint "$@"
