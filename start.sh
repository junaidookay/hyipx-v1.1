#!/bin/bash

PORT=${PORT:-8000}

# Generate .env from Railway environment variables
cat > /app/core/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=production
APP_KEY=${APP_KEY:-base64:AfL8uWPSVNUe0+326/o6tVSOKDeO0bHF9QI/FlKHVzY=}
APP_DEBUG=true
APP_URL=https://${RAILWAY_PUBLIC_DOMAIN:-localhost}

LOG_CHANNEL=stack
ADMIN_ROUTE=admin

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-${MYSQLHOST}}
DB_PORT=${DB_PORT:-${MYSQLPORT}}
DB_DATABASE=${DB_DATABASE:-${MYSQLDATABASE}}
DB_USERNAME=${DB_USERNAME:-${MYSQLUSER}}
DB_PASSWORD=${DB_PASSWORD:-${MYSQLPASSWORD}}

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
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
MIX_PUSHER_APP_KEY=''
MIX_PUSHER_APP_CLUSTER=''
EOF

# Cache config
cd /app/core && php artisan config:cache 2>&1 || true
cd /app

# Import database
php import_db.php 2>&1 || true

# Mark as installed
touch /app/installed

echo "Starting server on port ${PORT}..."
exec php -S 0.0.0.0:${PORT} index.php
