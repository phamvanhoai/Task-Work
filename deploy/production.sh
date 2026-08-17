#!/usr/bin/env bash
set -Eeuo pipefail

exec 9>/tmp/taskwork-deploy.lock
flock -n 9 || { echo "DEPLOY_SKIPPED: another deployment is running"; exit 0; }

SITE=/www/wwwroot/work.it.id.vn
PHP=/www/server/php/84/bin/php
COMPOSER=/usr/local/bin/composer
NODE_BIN=/usr/local/node-v22/bin

cd "$SITE"
export PATH="$NODE_BIN:$PATH"
export COMPOSER_ALLOW_SUPERUSER=1

echo "[1/7] Synchronizing origin/main"
git fetch --prune origin main
git reset --hard origin/main
git clean -fd

echo "[2/7] Installing PHP production dependencies"
"$PHP" "$COMPOSER" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

echo "[3/7] Building frontend assets"
npm ci --no-audit --no-fund
npm run build

echo "[4/7] Running database migrations"
"$PHP" artisan migrate --force

echo "[5/7] Refreshing Laravel caches"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

echo "[6/7] Fixing writable directory permissions"
chown -R www:www "$SITE/storage" "$SITE/bootstrap/cache" "$SITE/public/build"
chmod -R 775 "$SITE/storage" "$SITE/bootstrap/cache"

echo "[7/7] Reloading PHP workers"
/etc/init.d/php-fpm-84 reload >/dev/null 2>&1 || true

echo "DEPLOY_SUCCESS: $(git rev-parse --short HEAD)"
