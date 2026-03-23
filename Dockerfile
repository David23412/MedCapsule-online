# ─────────────────────────────────────────────────────────────────────────────
# MedCapsule — Production Dockerfile
# PHP 8.2 + Apache + Node.js 20 (for Vite build)
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.2-apache

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring exif pcntl bcmath xml zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── PHP Redis extension ───────────────────────────────────────────────────────
RUN pecl install igbinary redis \
    && docker-php-ext-enable igbinary redis

# ── Node.js 20 (for Vite frontend build) ────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────────────────────
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# ── PHP dependencies (cached layer) ──────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist

# ── Node.js dependencies (cached layer) ──────────────────────────────────────
COPY package.json package-lock.json ./
RUN npm ci

# ── Application source ────────────────────────────────────────────────────────
COPY . .

# ── Run Composer post-install scripts (package:discover, etc.) ───────────────
RUN composer run-script post-autoload-dump

# ── Build frontend assets, then drop node_modules to shrink image ─────────────
RUN npm run build && rm -rf node_modules

# ── Storage & cache permissions ───────────────────────────────────────────────
RUN mkdir -p storage/logs \
              storage/framework/cache/data \
              storage/framework/sessions \
              storage/framework/views \
              bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# ── Apache configuration ──────────────────────────────────────────────────────
RUN a2enmod rewrite

RUN printf '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes\n\
    </Directory>\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
