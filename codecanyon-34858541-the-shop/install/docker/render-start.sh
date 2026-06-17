#!/usr/bin/env bash
# Render web-service entrypoint: wait for DB, seed on first boot, then serve.
set -e
cd /app

PORT="${PORT:-8000}"
DB_PORT="${DB_PORT:-3306}"

echo "==> Waiting for database ${DB_HOST}:${DB_PORT} ..."
for i in $(seq 1 60); do
  if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
    echo "==> Database is up."
    break
  fi
  sleep 2
done

# composer install ran with --no-scripts; finish Laravel package discovery now
# that env vars are available.
php artisan package:discover --ansi || true

# Seed schema + demo data on first boot (when the `users` table is absent).
HAS_USERS=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='users';" 2>/dev/null || echo 0)

if [ "$HAS_USERS" = "0" ]; then
  echo "==> First boot: importing shop.sql ..."
  mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /app/shop.sql
  echo "==> Import complete."
else
  echo "==> Database already seeded; applying any new migrations."
  php artisan migrate --force || true
fi

php artisan storage:link || true
php artisan config:clear || true
php artisan cache:clear || true

echo "==> Starting server on :${PORT}"
exec php -S 0.0.0.0:"$PORT" -t /app /app/router.php
