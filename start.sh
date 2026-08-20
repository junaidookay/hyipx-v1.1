#!/bin/bash

PORT=${PORT:-8080}

# Generate .env from environment variables
cat > /app/core/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=production
APP_KEY=${APP_KEY:-base64:AfL8uWPSVNUe0+326/o6tVSOKDeO0bHF9QI/FlKHVzY=}
APP_DEBUG=true
APP_URL=${APP_URL:-http://localhost:${PORT}}

LOG_CHANNEL=stack
ADMIN_ROUTE=admin

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-hyipx}
DB_PASSWORD=${DB_PASSWORD:-hyipx_pass}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME='${APP_NAME:-Laravel}'
EOF

# Clear all caches
cd /app/core
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
php artisan view:clear 2>&1 || true
php artisan cache:clear 2>&1 || true
cd /app

# Wait for MySQL to be ready
echo "Waiting for database..."
for i in $(seq 1 30); do
    php /app/core/artisan migrate:status >/dev/null 2>&1 && break
    sleep 2
done

# Import database and activate license
php import_db.php 2>&1 || true

# Mark as installed
touch /app/installed

# Set permissions
chmod -R 775 /app/core/storage 2>/dev/null || true
chmod -R 775 /app/core/bootstrap/cache 2>/dev/null || true

echo "Starting server on port ${PORT}..."
exec php -S 0.0.0.0:${PORT} index.php
