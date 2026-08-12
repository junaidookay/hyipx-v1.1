FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev libxml2-dev \
    libonig-dev libcurl4-openssl-dev libssl-dev libgmp-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl soap gmp \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

RUN mkdir -p core/bootstrap/cache && \
    mkdir -p core/storage/framework/cache && \
    mkdir -p core/storage/framework/sessions && \
    mkdir -p core/storage/framework/views && \
    mkdir -p core/storage/logs && \
    cd core && composer config --no-plugins policy.advisories.block false && \
    composer install --no-dev --optimize-autoloader --no-scripts

EXPOSE 8000

CMD php -S 0.0.0.0:${PORT:-8000} index.php
