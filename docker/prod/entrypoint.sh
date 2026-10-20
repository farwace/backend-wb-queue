#!/bin/sh
set -e

cd /var/www/html

# Ensure Laravel's writable directories are owned by the PHP-FPM pool user.
# (Code is bind-mounted from the host, so ownership can drift after git pull.)
if [ -d storage ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

# Optional one-shot bootstrap on first boot. Enable by setting RUN_MIGRATIONS=1
# for a single service in docker-compose.prod.yml (disabled by default so the
# queue/reverb/scheduler containers don't all race to migrate).
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    echo "[entrypoint] running migrations + optimize ..."
    php artisan migrate --force
    php artisan optimize
fi

exec "$@"
