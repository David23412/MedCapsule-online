#!/bin/sh
# Run on the droplet as an operator; restore only into a disposable database.
set -eu
umask 077
exec 8>/var/lock/medcapsule-backup-check.lock
flock -n 8
backup=$(find /root/medcapsule-deployment-backups -maxdepth 1 -type f -name '*.sql' | sort | tail -n 1)
[ -n "$backup" ] && [ -s "$backup" ]
# The rehearsal must never switch back to the application database.
if grep -Eiq '^[[:space:]]*(USE[[:space:]]|CREATE[[:space:]]+DATABASE|DROP[[:space:]]+DATABASE)' "$backup"; then
    echo 'Backup requires manual database-name isolation before rehearsal.' >&2
    exit 1
fi
rehearsal_db=medcapsule_restore_check_$(date -u +%Y%m%d%H%M%S)
case "$rehearsal_db" in medcapsule_restore_check_[0-9]*) ;; *) exit 1 ;; esac
cleanup() {
    if ! docker exec -e REHEARSAL_DB="$rehearsal_db" medcapsule-db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "DROP DATABASE IF EXISTS \`$REHEARSAL_DB\`"' >/dev/null 2>&1; then
        echo "Could not remove temporary recovery database: $rehearsal_db" >&2
        return 1
    fi
    rm -f /tmp/medcapsule-restore-check-errors.log /tmp/medcapsule-restore-check-results.log
}
trap cleanup EXIT HUP INT TERM
docker exec -e REHEARSAL_DB="$rehearsal_db" medcapsule-db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "CREATE DATABASE \`$REHEARSAL_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
if ! docker exec -i -e REHEARSAL_DB="$rehearsal_db" medcapsule-db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$REHEARSAL_DB"' < "$backup" 2>/tmp/medcapsule-restore-check-errors.log; then
    echo 'Isolated SQL restore failed.' >&2
    exit 1
fi
expected=$(grep -c '^CREATE TABLE ' "$backup")
actual=$(docker exec -e REHEARSAL_DB="$rehearsal_db" medcapsule-db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -N -uroot -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '\''$REHEARSAL_DB'\'' AND table_type = '\''BASE TABLE'\''"')
[ "$expected" = "$actual" ]
docker exec -e REHEARSAL_DB="$rehearsal_db" medcapsule-db sh -c 'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; for table in $(mysql -N -uroot "$REHEARSAL_DB" -e "SHOW TABLES"); do mysql -N -uroot -e "CHECK TABLE \`$REHEARSAL_DB\`.\`$table\`"; done' > /tmp/medcapsule-restore-check-results.log 2>/tmp/medcapsule-restore-check-errors.log
checked=$(awk '$3 == "status" && $4 == "OK" {count++} END {print count+0}' /tmp/medcapsule-restore-check-results.log)
[ "$checked" = "$actual" ]
rm -f /tmp/medcapsule-restore-check-results.log
cleanup
trap - EXIT HUP INT TERM
echo "Backup recovery passed: $actual tables restored and checked in a separate temporary database."
