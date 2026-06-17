#!/bin/sh
set -e

echo "⏳ Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
  sleep 2
done
echo "✅ MySQL ready."

echo "🔄 Running migrations..."
php artisan migrate --force 2>&1 || echo "⚠️  Migration warning (tables may already exist — safe to ignore)"

echo "🚀 Starting: $@"
exec "$@"
