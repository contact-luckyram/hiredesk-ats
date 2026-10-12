# HireDesk demo image (used by Render's free plan, works on any Docker host).
FROM php:8.4-cli

# PHP extensions Filament needs (pdo_sqlite is built in).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/app.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=production \
    APP_TIMEZONE=America/New_York \
    APP_DEBUG=false \
    DEMO_MODE=true \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr \
    PHP_CLI_SERVER_WORKERS=4 \
    PORT=10000

# Install PHP packages first so this layer is cached between deploys.
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan filament:assets \
    && chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache database

USER www-data
EXPOSE 10000

CMD ["sh", "/app/docker/start.sh"]
