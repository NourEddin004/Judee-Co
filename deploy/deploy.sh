#!/usr/bin/env bash
# Deploys one branch on the server. Run as the deploy user from /var/www/jude.
# REPO is the git address of this project. BRANCH defaults to main, which is created at launch (M4).
# MIGRATE_DB_USERNAME / MIGRATE_DB_PASSWORD hold the migration account (deploy/mysql/grants.sql).
set -euo pipefail

APP=/var/www/jude
RELEASE="$APP/releases/$(date +%Y%m%d%H%M%S)"

# Ubuntu 24.04's own php and node are too old; see deploy/HOSTING-CHECKLIST.md, section 2.
php -r 'exit(PHP_VERSION_ID >= 80500 ? 0 : 1);' || { echo "The php command must be PHP 8.5 or newer (found $(php -r 'echo PHP_VERSION;'))." >&2; exit 1; }
node -e 'const [a, b] = process.versions.node.split(".").map(Number); process.exit((a === 20 && b >= 19) || (a === 22 && b >= 12) || a > 22 ? 0 : 1)' \
  || { echo "Node must be 20.19+, 22.12+ or newer (found $(node -v))." >&2; exit 1; }

git clone --depth 1 --branch "${BRANCH:-main}" "${REPO:?set REPO}" "$RELEASE"
cd "$RELEASE"

ln -s "$APP/shared/.env" .env
rm -rf storage && ln -s "$APP/shared/storage" storage

composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build && rm -rf node_modules

# Structure changes run only here, with the separate account (REQ-DB-34).
DB_USERNAME="${MIGRATE_DB_USERNAME:?}" DB_PASSWORD="${MIGRATE_DB_PASSWORD:?}" php artisan migrate --force

php artisan storage:link
# Caches the settings, routes, events, views and the admin panel's components.
php artisan optimize

ln -sfn "$RELEASE" "$APP/current"
sudo systemctl reload php8.5-fpm
sudo supervisorctl restart jude-queue

# Keep the last 5 releases.
ls -1dt "$APP"/releases/* | tail -n +6 | xargs -r rm -rf
