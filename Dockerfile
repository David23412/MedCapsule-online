FROM php:8.3-apache AS app-base

RUN apt-get update && apt-get install -y \
    cron \
    curl \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_mysql mbstring exif pcntl bcmath xml zip gd opcache \
    && pecl install igbinary redis \
    && docker-php-ext-enable igbinary redis \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p \
        /var/www/html/storage/logs \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

FROM node:20-bookworm-slim AS frontend-builder

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=app-base /var/www/html/vendor /var/www/html/vendor

RUN npm ci \
    && npm run build

FROM app-base AS production

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/cron/laravel-scheduler /etc/cron.d/laravel-scheduler
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=frontend-builder /var/www/html/public/build /var/www/html/public/build

RUN chmod 0644 /etc/cron.d/laravel-scheduler \
    && chmod +x /usr/local/bin/docker-entrypoint \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["docker-entrypoint"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
  CMD curl -f http://localhost/up || exit 1

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
