#!/bin/sh
# Installed as the forced command for the CI-only SSH key.
set -eu
umask 077

case "${SSH_ORIGINAL_COMMAND:-}" in
    'deploy '*) revision=${SSH_ORIGINAL_COMMAND#deploy } ;;
    *) echo 'Expected: deploy <commit SHA>' >&2; exit 2 ;;
esac
case "$revision" in ''|*[!0-9a-f]*) echo 'Invalid commit SHA' >&2; exit 2 ;; esac
[ "${#revision}" -eq 40 ] || exit 2

# Serialize deployments, including direct administrative requests.
exec 9>/var/lock/medcapsule-deploy.lock
flock -w 600 9
cd /var/www/medcapsule
image="ghcr.io/david23412/medcapsule:sha-$(printf '%.7s' "$revision")"
docker pull "$image"
actual_revision=$(docker inspect --format '{{ index .Config.Labels "org.opencontainers.image.revision" }}' "$image")
[ "$actual_revision" = "$revision" ] || { echo 'Image revision mismatch' >&2; exit 1; }

backup_dir=/root/medcapsule-deployment-backups
mkdir -p "$backup_dir"
chmod 700 "$backup_dir"
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
    > "$backup_dir/$(date -u +%Y%m%dT%H%M%SZ)-$revision.sql"

docker tag "$image" ghcr.io/david23412/medcapsule:latest
docker compose up -d --no-build --no-deps app
docker compose exec -T app php artisan migrate --force

for attempt in $(seq 1 20); do
    if docker compose exec -T app docker-healthcheck >/dev/null 2>&1; then
        curl --fail --silent --show-error --retry 3 --retry-delay 2 https://medcapsule.online/up >/dev/null
        echo "Deployed and verified $revision"
        exit 0
    fi
    sleep 3
done
echo 'Application did not become healthy after deployment' >&2
exit 1
