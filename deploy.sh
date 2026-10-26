#!/usr/bin/env bash
set -e

APP_DIR="/opt/1panel/www/sites/skiv2/ci4"
cd "$APP_DIR" || exit 1

echo "📥 Pulling latest code..."
git pull origin main

echo "🎨 Rebuilding frontend assets with Tailwind & DaisyUI..."
if command -v npm >/dev/null 2>&1; then
    npm run build
fi

echo "🗄️ Running migrations..."
if docker ps --format '{{.Names}}' | grep -q '^PHP$'; then
    docker exec PHP php /www/sites/skiv2/ci4/spark migrate
else
    php spark migrate
fi

echo "🧹 Clearing cache..."
if docker ps --format '{{.Names}}' | grep -q '^PHP$'; then
    docker exec PHP php /www/sites/skiv2/ci4/spark cache:clear
else
    php spark cache:clear
fi

echo "🔒 Recreating writable folders & permissions..."
mkdir -p writable/cache writable/session writable/logs writable/uploads writable/debugbar
chmod -R 777 writable/

echo "✅ Deployment complete!"
