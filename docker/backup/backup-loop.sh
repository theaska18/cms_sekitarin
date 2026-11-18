#!/bin/sh
set -eu

: "${BACKUP_TIME:=02:00}"
: "${BACKUP_ROOT:=/backup}"
export TZ="${TZ:-UTC}"

case "$BACKUP_TIME" in
    [0-2][0-9]:[0-5][0-9]) ;;
    *) echo "Invalid BACKUP_TIME=$BACKUP_TIME; using 02:00" >&2; BACKUP_TIME=02:00 ;;
esac

# Ensure there is a backup for today shortly after startup.
today="$(date -u +%Y%m%d)"
if ! ls "$BACKUP_ROOT"/cmsdb-"$today"-*.sql.gz >/dev/null 2>&1; then
    /usr/local/bin/cms-backup/backup-once.sh || echo "Initial backup failed; will retry on schedule" >&2
fi

while true; do
    hour="${BACKUP_TIME%%:*}"
    minute="${BACKUP_TIME#*:}"
    hour="${hour#0}"
    minute="${minute#0}"
    [ -n "$hour" ] || hour=0
    [ -n "$minute" ] || minute=0
    target_seconds=$((hour * 3600 + minute * 60))
    now="$(date -u +%s)"
    seconds_today=$((now % 86400))
    if [ "$seconds_today" -ge "$target_seconds" ]; then
        wait_seconds=$((86400 - seconds_today + target_seconds))
    else
        wait_seconds=$((target_seconds - seconds_today))
    fi
    echo "Next backup in ${wait_seconds}s (UTC)"
    sleep "$wait_seconds"
    /usr/local/bin/cms-backup/backup-once.sh || echo "Scheduled backup failed" >&2
done
