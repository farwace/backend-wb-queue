#!/usr/bin/env bash
#
# Production deploy for wb-alex (dockerized). Run on the SERVER.
#   ./docker/prod/deploy/deploy-prod.sh
#
# Pulls latest code, rebuilds PHP dependencies & assets inside the app image,
# runs migrations, then restarts the PHP containers (to clear OPcache, since
# opcache.validate_timestamps=0).
#
set -euo pipefail

cd "$(dirname "$0")/../../.."   # repo root (docker/prod/deploy -> up 3 levels)

COMPOSE="docker compose -f docker-compose.prod.yml"

echo "🔄 git pull"
git pull --ff-only

echo "📦 composer install (no-dev) inside app container"
$COMPOSE run --rm --no-deps app composer install --no-dev --optimize-autoloader --no-interaction

echo "🏗️  building frontend assets (npm ci + npm run build)"
$COMPOSE run --rm --no-deps app sh -lc 'npm ci && npm run build'

echo "🚀 starting / updating stack"
$COMPOSE up -d --build

echo "🗄️  migrations + Laravel caches"
$COMPOSE exec -T app php artisan down --retry=10 || true
$COMPOSE exec -T app php artisan migrate --force
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan event:cache
$COMPOSE exec -T app php artisan view:cache
$COMPOSE exec -T app php artisan up

echo "♻️  restarting php containers (clear OPcache) + resuming workers"
# nginx included so it re-resolves app/reverb IPs (guards against stale-upstream 502)
$COMPOSE restart app queue reverb scheduler nginx
$COMPOSE exec -T app php artisan queue:restart || true

echo "✅ deploy done"
$COMPOSE ps
