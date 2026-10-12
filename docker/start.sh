#!/bin/sh
# Starts the demo with fresh sample data every time the container boots.
set -e

cd /app

# Render gives the public URL in RENDER_EXTERNAL_URL.
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"

# A new key per boot is fine for a demo: sessions and data reset anyway.
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY
fi

rm -f database/database.sqlite
touch database/database.sqlite

php artisan migrate --force --seed
php artisan optimize
php artisan filament:optimize

echo "HireDesk is starting on port ${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}" --no-reload
