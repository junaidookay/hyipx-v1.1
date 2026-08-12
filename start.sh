#!/bin/bash

# Generate .env from Railway environment variables
cat > core/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=production
APP_KEY=${APP_KEY:-base64:AfL8uWPSVNUe0+326/o6tVSOKDeO0bHF9QI/FlKHVzY=}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${RAILWAY_PUBLIC_DOMAIN:+https://$RAILWAY_PUBLIC_DOMAIN}
APP_URL=${APP_URL:-http://localhost:8000}

LOG_CHANNEL=stack
ADMIN_ROUTE=admin

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-${MYSQLHOST:-127.0.0.1}}
DB_PORT=${DB_PORT:-${MYSQLPORT:-3306}}
DB_DATABASE=${DB_DATABASE:-${MYSQLDATABASE:-laravel}}
DB_USERNAME=${DB_USERNAME:-${MYSQLUSER:-root}}
DB_PASSWORD=${DB_PASSWORD:-${MYSQLPASSWORD:-}}

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

cd core
php artisan config:cache 2>/dev/null || true

php -r "
require 'vendor/autoload.php';
\$app = require bootstrap/app.php;
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    \$exists = \Illuminate\Support\Facades\Schema::hasTable('general_settings');
    if (!\$exists) {
        echo 'Table missing - importing database...\n';
        \$sql = file_get_contents(__DIR__ . '/../install/database.sql');
        \$statements = array_filter(array_map('trim', explode(';', \$sql)));
        foreach (\$statements as \$stmt) {
            if (!empty(\$stmt)) {
                \Illuminate\Support\Facades\DB::unprepared(\$stmt);
            }
        }
        echo 'Database imported.\n';
    } else {
        echo 'Database already set up.\n';
    }
} catch (Exception \$e) {
    echo 'DB connection error: ' . \$e->getMessage() . '\n';
}
" 2>&1

touch /app/installed
cd /app

exec php -S 0.0.0.0:${PORT:-8000} index.php
